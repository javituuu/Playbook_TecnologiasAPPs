<?php

use App\Enums\EstadoCitacionEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Citaciones del Jugador
 * Retorna las invitaciones a partidos que están pendientes de respuesta ('pendiente').
 *
 * NOTA (Fase 1): adaptado al esquema relacional manteniendo el contrato JSON de Ionic.
 * En la Fase 5 se reemplaza por Api\Jugador\CitacionController + CitacionResource.
 */
Route::get('/citaciones', function () {
    $user = DB::table('users')->where('email', 'javier@email.com')->first();

    $citaciones = DB::table('citaciones')
        ->join('partidos', 'citaciones.partido_id', '=', 'partidos.id')
        ->join('equipos as local', 'partidos.equipo_local_id', '=', 'local.id')
        ->leftJoin('equipos as visitante', 'partidos.equipo_visitante_id', '=', 'visitante.id')
        ->leftJoin('ligas', 'partidos.liga_id', '=', 'ligas.id')
        ->where('citaciones.user_id', $user->id)
        ->where('citaciones.estado_respuesta', 'pendiente')
        ->orderBy('partidos.fecha_hora_inicio')
        ->select(
            'citaciones.id as id_citacion',
            'partidos.id as id_partido',
            'partidos.fecha_hora_inicio as fecha_hora',
            'citaciones.equipo_id',
            'partidos.equipo_local_id',
            'local.nombre as equipo_local',
            'visitante.nombre as equipo_visitante',
            DB::raw("COALESCE(ligas.nombre, 'Práctica') as liga"),
            'citaciones.mensaje_coach',
            'citaciones.estado_respuesta'
        )
        ->get()
        ->map(fn ($c) => [
            'id_citacion' => $c->id_citacion,
            'id_partido' => $c->id_partido,
            'fecha_hora' => $c->fecha_hora,
            // El rival es el equipo contrario al que convocó al jugador.
            'equipo_rival' => (int) $c->equipo_id === (int) $c->equipo_local_id
                ? ($c->equipo_visitante ?? 'Práctica interna')
                : $c->equipo_local,
            'liga' => $c->liga,
            'mensaje_coach' => $c->mensaje_coach,
            'estado_respuesta' => $c->estado_respuesta,
        ]);

    return response()->json([
        "status" => "success",
        "data" => $citaciones
    ]);
});

/**
 * Microservicio: Responder Citación
 * Permite cambiar el estado de una citación específica ('confirmado' o 'rechazado').
 */
Route::patch('/citaciones/{id}/responder', function ($id, Request $request) {
    $estado = $request->input('estado');

    // Validación de estado
    if (!in_array($estado, [EstadoCitacionEnum::CONFIRMADO->value, EstadoCitacionEnum::RECHAZADO->value], true)) {
        return response()->json(['status' => 'error', 'message' => 'Estado inválido'], 400);
    }

    // Actualización de registro
    $actualizadas = DB::table('citaciones')
        ->where('id', $id)
        ->update([
            'estado_respuesta' => $estado,
            'respondido_en' => now(),
            'updated_at' => now()
        ]);

    if ($actualizadas === 0) {
        return response()->json(['status' => 'error', 'message' => 'No se encontró la citación'], 404);
    }

    return response()->json(['status' => 'success', 'message' => 'Citación actualizada exitosamente']);
});
