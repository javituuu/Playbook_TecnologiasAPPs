<?php

use App\Http\Controllers\Api\Jugador\AgendaController;
use Illuminate\Support\Facades\Route;

Route::get('/agenda', [AgendaController::class, 'index'])->name('api.jugador.agenda');
