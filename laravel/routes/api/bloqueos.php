<?php

use App\Http\Requests\Disponibilidad\StoreBloqueoHorarioRequest;
use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Bloqueos de Horario (Mantenedor CRUD del jugador autenticado)
 */

// CREAR un bloqueo para el jugador autenticado
Route::post('/bloqueos', function (StoreBloqueoHorarioRequest $request) {
    /** @var User $user */
    $user = $request->user();
    $dto = $request->toDTO($user->id);

    $bloqueo = BloqueoHorario::create($dto->toDatabaseArray());

    return response()->json([
        'status' => 'success',
        'message' => 'Horario bloqueado exitosamente',
        'id' => $bloqueo->id,
    ]);
});

// ELIMINAR un bloqueo verificando que pertenezca al jugador autenticado
Route::delete('/bloqueos/{id}', function ($id, Request $request) {
    /** @var User $user */
    $user = $request->user();
    $bloqueo = BloqueoHorario::find($id);

    if (! $bloqueo) {
        return response()->json(['status' => 'error', 'message' => 'No se encontró el bloqueo'], 404);
    }

    if ((int) $bloqueo->user_id !== (int) $user->id) {
        return response()->json([
            'status' => 'error',
            'message' => 'No tienes permiso para eliminar un bloqueo horario de otro jugador.',
        ], 403);
    }

    $bloqueo->delete();

    return response()->json(['status' => 'success', 'message' => 'Horario desbloqueado exitosamente']);
});
