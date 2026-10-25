<?php

use App\Http\Resources\Jugador\PerfilJugadorResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Perfil del Jugador
 * Retorna los datos básicos del usuario autenticado (vía Sanctum Bearer token) y sus bloqueos de horario.
 */
Route::get('/perfil', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    $user->load([
        'membresiaPrincipal.equipo.club',
        'membresiaPrincipal.equipo.disciplina',
        'bloqueosHorarios',
    ]);

    return response()->json([
        'status' => 'success',
        'data' => (new PerfilJugadorResource($user))->resolve(),
    ]);
});
