<?php

<<<<<<< HEAD
=======
use App\Enums\EstadoCitacionEnum;
use App\Http\Resources\Jugador\CitacionResource;
>>>>>>> rama-laravel
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

<<<<<<< HEAD
// Citaciones que el jugador todavía no responde.
Route::get('/citaciones', function () {
    $user = User::where('email', 'javier@email.com')->first();

    $citaciones = Citacion::with('partido')
        ->where('user_id', $user->id)
        ->where('estado_respuesta', 'pendiente')
        ->get()
        ->map(fn ($c) => [
            'id_citacion' => $c->id,
            'id_partido' => $c->partido->id,
            'fecha_hora' => $c->partido->fecha_hora_inicio,
            'equipo_rival' => $c->partido->equipo_visitante,
            'liga' => $c->partido->liga,
            'mensaje_coach' => $c->mensaje_coach,
            'estado_respuesta' => $c->estado_respuesta,
        ]);
=======
/**
 * Microservicio: Citaciones del Jugador
 * Retorna las invitaciones a partidos que están pendientes de respuesta ('pendiente').
 *
 * Fase 2: Migrado de DB::table() a Eloquent + CitacionResource manteniendo el contrato JSON de Ionic.
 */
Route::get('/citaciones', function () {
    $user = User::where('email', 'javier@email.com')->firstOrFail();

    $citacionesPendientes = Citacion::query()
        ->where('user_id', $user->id)
        ->pendientes()
        ->with(['partido.liga', 'partido.equipoLocal', 'partido.equipoVisitante'])
        ->get()
        ->sortBy(fn (Citacion $c) => $c->partido?->fecha_hora_inicio)
        ->values();
>>>>>>> rama-laravel

    return response()->json([
        'status' => 'success',
        'data' => CitacionResource::collection($citacionesPendientes)->resolve(),
    ]);
});

// Responde una citación con 'confirmado' o 'rechazado'.
// Ojo: no revisa que siga pendiente ni que sea del jugador, actualiza cualquier id que le llegue.
Route::patch('/citaciones/{id}/responder', function ($id, Request $request) {
    $estado = $request->input('estado');

<<<<<<< HEAD
    if (!in_array($estado, ['confirmado', 'rechazado'])) {
        return response()->json(['status' => 'error', 'message' => 'Estado inválido'], 400);
    }

    Citacion::where('id', $id)->update(['estado_respuesta' => $estado]);
=======
    if (! in_array($estado, [EstadoCitacionEnum::CONFIRMADO->value, EstadoCitacionEnum::RECHAZADO->value], true)) {
        return response()->json(['status' => 'error', 'message' => 'Estado inválido'], 400);
    }

    $citacion = Citacion::find($id);

    if (! $citacion) {
        return response()->json(['status' => 'error', 'message' => 'No se encontró la citación'], 404);
    }

    $citacion->update([
        'estado_respuesta' => EstadoCitacionEnum::from($estado),
        'respondido_en' => now(),
    ]);
>>>>>>> rama-laravel

    return response()->json(['status' => 'success', 'message' => 'Citación actualizada exitosamente']);
});
