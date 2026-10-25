<?php

use Illuminate\Support\Facades\Route;

// Endpoints de Autenticación y Verificación del API Gateway
require __DIR__.'/api/auth.php';

// Endpoints de recursos (resuelven al usuario del token Bearer o fallback local en modo demo)
Route::middleware('api.user')->group(function () {
    require __DIR__.'/api/perfil.php';
    require __DIR__.'/api/agenda.php';
    require __DIR__.'/api/citaciones.php';
    require __DIR__.'/api/bloqueos.php';
});
