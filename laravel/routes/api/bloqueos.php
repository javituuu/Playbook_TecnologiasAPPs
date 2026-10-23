<?php

use App\Http\Requests\Disponibilidad\StoreBloqueoHorarioRequest;
use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Bloqueos de Horario (Mantenedor CRUD)
 *
 * Fase 2: Migrado de DB::table() a Eloquent + StoreBloqueoHorarioRequest + CrearBloqueoHorarioDTO.
 */

// CREAR un bloqueo
Route::post('/bloqueos', function (StoreBloqueoHorarioRequest $request) {
    $user = User::where('email', 'javier@email.com')->firstOrFail();
    $dto = $request->toDTO($user->id);

    $bloqueo = BloqueoHorario::create($dto->toDatabaseArray());

    return response()->json([
        'status' => 'success',
        'message' => 'Horario bloqueado exitosamente',
        'id' => $bloqueo->id,
    ]);
});

// ELIMINAR un bloqueo
Route::delete('/bloqueos/{id}', function ($id) {
    $bloqueo = BloqueoHorario::find($id);

    if ($bloqueo) {
        $bloqueo->delete();

        return response()->json(['status' => 'success', 'message' => 'Horario desbloqueado exitosamente']);
    }

    return response()->json(['status' => 'error', 'message' => 'No se encontró el bloqueo'], 404);
});
