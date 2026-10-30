<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * Endpoints de Autenticación y Verificación del API Gateway (Ionic & Flutter).
 */
Route::prefix('auth')->group(function () {
    // Público con protección contra ataques de fuerza bruta (5 intentos/min)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('api.auth.login');

    // Protegidos por Laravel Sanctum: requieren Header "Authorization: Bearer <token>"
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::get('/verify', [AuthController::class, 'me'])->name('api.auth.verify');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.auth.logout-all');
    });
});
