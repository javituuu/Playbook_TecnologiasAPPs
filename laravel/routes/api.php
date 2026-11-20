<?php

use Illuminate\Support\Facades\Route;

// 1. Endpoints de Autenticación y Verificación del API Gateway
require __DIR__.'/api/auth.php';

// 2. Microservicios del Jugador (Protegidos por Token Bearer + RBAC 'role:jugador')
Route::middleware(['api.user', 'role:jugador'])->group(function () {
    require __DIR__.'/api/perfil.php';
    require __DIR__.'/api/agenda.php';
    require __DIR__.'/api/citaciones.php';
    require __DIR__.'/api/bloqueos.php';
});

// 3. Microservicios del Coach en Flutter (Protegidos por Token Bearer + RBAC 'role:coach')
Route::middleware(['api.user', 'role:coach,admin_club,admin_liga'])->group(function () {
    require __DIR__.'/api/coach.php';
});
