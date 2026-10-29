<?php

use App\Http\Controllers\Api\Jugador\CitacionController;
use Illuminate\Support\Facades\Route;

Route::get('/citaciones', [CitacionController::class, 'index'])->name('api.jugador.citaciones.index');
Route::patch('/citaciones/{id}/responder', [CitacionController::class, 'responder'])
    ->whereNumber('id')
    ->name('api.jugador.citaciones.responder');
