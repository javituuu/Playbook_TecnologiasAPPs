<?php

use App\Http\Resources\Jugador\AgendaPartidoResource;
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Agenda del Jugador
 * Retorna la lista de partidos en los cuales el jugador autenticado tiene una citación "confirmada".
 */
Route::get('/agenda', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    $citacionesConfirmadas = Citacion::query()
        ->where('user_id', $user->id)
        ->confirmadas()
        ->with(['partido.liga', 'partido.equipoLocal', 'partido.equipoVisitante'])
        ->get()
        ->sortBy(fn (Citacion $c) => $c->partido?->fecha_hora_inicio)
        ->values();

    return response()->json([
        'status' => 'success',
        'data' => AgendaPartidoResource::collection($citacionesConfirmadas)->resolve(),
    ]);
});
