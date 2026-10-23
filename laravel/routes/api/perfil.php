<?php

use App\Http\Resources\Jugador\PerfilJugadorResource;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Perfil del Jugador
 * Retorna los datos básicos del usuario y sus bloqueos de horario asociados.
 *
 * Fase 2: Migrado de DB::table() a Eloquent + PerfilJugadorResource manteniendo el contrato JSON de Ionic.
 */
Route::get('/perfil', function () {
    $user = User::query()
        ->where('email', 'javier@email.com')
        ->with([
            'membresiaPrincipal.equipo.club',
            'membresiaPrincipal.equipo.disciplina',
            'bloqueosHorarios',
        ])
        ->firstOrFail();

    return response()->json([
        'status' => 'success',
        'data' => (new PerfilJugadorResource($user))->resolve(),
    ]);
});
