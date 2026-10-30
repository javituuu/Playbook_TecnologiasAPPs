<?php

use App\Http\Controllers\Api\Jugador\BloqueoHorarioController;
use Illuminate\Support\Facades\Route;

Route::post('/bloqueos', [BloqueoHorarioController::class, 'store'])->name('api.jugador.bloqueos.store');
Route::delete('/bloqueos/{id}', [BloqueoHorarioController::class, 'destroy'])
    ->whereNumber('id')
    ->name('api.jugador.bloqueos.destroy');
