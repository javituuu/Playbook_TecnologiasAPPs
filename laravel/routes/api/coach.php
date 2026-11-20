<?php

use App\Http\Controllers\Api\Coach\CoachController;
use Illuminate\Support\Facades\Route;

/**
 * Endpoints REST para la aplicación Flutter del Coach (Playbook Coach).
 */
Route::prefix('coach')->group(function () {
    // 1. Heatmap de Disponibilidad Semanal
    Route::get('/equipos/{equipoId}/heatmap', [CoachController::class, 'heatmap'])
        ->whereNumber('equipoId')
        ->name('api.coach.heatmap');

    // 2. Prácticas Programadas
    Route::get('/equipos/{equipoId}/practicas', [CoachController::class, 'listarPracticas'])
        ->whereNumber('equipoId')
        ->name('api.coach.practicas.index');

    Route::post('/practicas', [CoachController::class, 'agendarPractica'])
        ->name('api.coach.practicas.store');

    Route::patch('/practicas/{practicaId}/cancelar', [CoachController::class, 'cancelarPractica'])
        ->whereNumber('practicaId')
        ->name('api.coach.practicas.cancelar');

    // 3. Nómina y Citaciones
    Route::get('/equipos/{equipoId}/nomina', [CoachController::class, 'listarNomina'])
        ->whereNumber('equipoId')
        ->name('api.coach.nomina.index');

    Route::post('/nomina/{eventoId}/intercambiar', [CoachController::class, 'intercambiarNomina'])
        ->whereNumber('eventoId')
        ->name('api.coach.nomina.intercambiar');

    Route::patch('/nomina/{eventoId}/citaciones/{jugadorId}', [CoachController::class, 'actualizarEstadoCitacion'])
        ->whereNumber(['eventoId', 'jugadorId'])
        ->name('api.coach.nomina.citaciones.update');

    // 4. Plantel del Equipo
    Route::get('/equipos/{equipoId}/plantel', [CoachController::class, 'plantel'])
        ->whereNumber('equipoId')
        ->name('api.coach.plantel');
});
