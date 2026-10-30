<?php

use App\Http\Controllers\Api\Jugador\PerfilController;
use Illuminate\Support\Facades\Route;

Route::get('/perfil', [PerfilController::class, 'show'])->name('api.jugador.perfil');
