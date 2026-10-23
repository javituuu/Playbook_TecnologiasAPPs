<?php

use App\Enums\EstadoCitacionEnum;
use App\Http\Resources\Jugador\CitacionResource;
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

    return response()->json([
        'status' => 'success',
        'data' => CitacionResource::collection($citacionesPendientes)->resolve(),
    ]);
});

// Responde una citación con 'confirmado' o 'rechazado'.
// Ojo: no revisa que siga pendiente ni que sea del jugador, actualiza cualquier id que le llegue.
Route::patch('/citaciones/{id}/responder', function ($id, Request $request) {
    $estado = $request->input('estado');

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

    return response()->json(['status' => 'success', 'message' => 'Citación actualizada exitosamente']);
});
