<?php

use App\Enums\EstadoCitacionEnum;
use App\Http\Resources\Jugador\CitacionResource;
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Citaciones del Jugador
 * Retorna las invitaciones a partidos pendientes de respuesta ('pendiente') del jugador autenticado.
 */
Route::get('/citaciones', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

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

/**
 * Responde una citación con 'confirmado' o 'rechazado', verificando que pertenezca al jugador autenticado.
 */
Route::patch('/citaciones/{id}/responder', function ($id, Request $request) {
    /** @var User $user */
    $user = $request->user();
    $estado = $request->input('estado');

    if (! in_array($estado, [EstadoCitacionEnum::CONFIRMADO->value, EstadoCitacionEnum::RECHAZADO->value], true)) {
        return response()->json(['status' => 'error', 'message' => 'Estado inválido'], 400);
    }

    $citacion = Citacion::find($id);

    if (! $citacion) {
        return response()->json(['status' => 'error', 'message' => 'No se encontró la citación'], 404);
    }

    if ((int) $citacion->user_id !== (int) $user->id) {
        return response()->json([
            'status' => 'error',
            'message' => 'No tienes permiso para responder una citación de otro jugador.',
        ], 403);
    }

    $citacion->update([
        'estado_respuesta' => EstadoCitacionEnum::from($estado),
        'respondido_en' => now(),
    ]);

    return response()->json(['status' => 'success', 'message' => 'Citación actualizada exitosamente']);
});
