<?php

use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Bloqueos de Horario (Mantenedor CRUD)
 */

// CREAR un bloqueo
Route::post('/bloqueos', function (Request $request) {
    // Validar requerimientos básicos
    $request->validate([
        'dia_semana' => 'required|string',
        'hora_inicio' => 'required',
        'hora_fin' => 'required'
    ]);

    $user = User::where('email', 'javier@email.com')->first();

    $bloqueo = BloqueoHorario::create([
        'user_id' => $user->id,
        'dia_semana' => $request->input('dia_semana'),
        'hora_inicio' => $request->input('hora_inicio'),
        'hora_fin' => $request->input('hora_fin')
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Horario bloqueado exitosamente',
        'id' => $bloqueo->id
    ]);
});

// ELIMINAR un bloqueo
Route::delete('/bloqueos/{id}', function ($id) {
    if (BloqueoHorario::destroy($id)) {
        return response()->json(['status' => 'success', 'message' => 'Horario desbloqueado exitosamente']);
    }

    return response()->json(['status' => 'error', 'message' => 'No se encontró el bloqueo'], 404);
});
