<?php

use App\Http\Controllers\Panel\PanelAuthController;
use App\Http\Controllers\Panel\PanelWebController;
use Illuminate\Support\Facades\Route;

/**
 * Panel Web Interactivo de Playbook (Admin de Liga, Admin de Club y Coach).
 * Conecta las vistas Blade con autenticación de sesión y el Motor de Conflictos y Disponibilidad.
 */

// 0. Autenticación Web (Login / Logout)
Route::get('/login', [PanelAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [PanelAuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [PanelAuthController::class, 'logout'])->name('logout');

// 1. Fixture, Validación Automática de Liga y Programador de Partidos
Route::get('/', [PanelWebController::class, 'fixture'])->name('fixture');
Route::post('/ligas/{liga}/revalidar', [PanelWebController::class, 'revalidarLiga'])->name('ligas.revalidar');
Route::post('/ligas/{liga}/publicar', [PanelWebController::class, 'publicarFixture'])->name('ligas.publicar');
Route::post('/partidos', [PanelWebController::class, 'storePartido'])->name('partidos.store');
Route::patch('/partidos/{partido}/reprogramar', [PanelWebController::class, 'reprogramarPartido'])->name('partidos.reprogramar');

// 2. Equipos, Gestión de Roster y Matriz Semanal de Disponibilidad
Route::get('/equipos', [PanelWebController::class, 'equipos'])->name('equipos');
Route::get('/equipos/{equipo}', [PanelWebController::class, 'showEquipo'])->name('equipos.show');
Route::patch('/equipos/{equipo}/roster/{user}', [PanelWebController::class, 'updateRosterMember'])
    ->whereNumber('user')
    ->name('equipos.roster.update');
