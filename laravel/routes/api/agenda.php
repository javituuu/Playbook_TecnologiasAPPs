<?php

use App\Http\Resources\Jugador\AgendaPartidoResource;
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Agenda del Jugador
 * Retorna la lista de partidos en los cuales el jugador tiene una citación "confirmada".
 *
 * Fase 2: Migrado de DB::table() a Eloquent + AgendaPartidoResource manteniendo el contrato JSON de Ionic.
 */
Route::get('/agenda', function () {
    $user = User::where('email', 'javier@email.com')->firstOrFail();

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
