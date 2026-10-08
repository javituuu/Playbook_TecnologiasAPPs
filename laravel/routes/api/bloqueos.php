<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

    $user = DB::table('users')->where('email', 'javier@email.com')->first();
    
    $id = DB::table('bloqueos_horarios')->insertGetId([
        'user_id' => $user->id,
        'dia_semana' => $request->input('dia_semana'),
        'hora_inicio' => $request->input('hora_inicio'),
        'hora_fin' => $request->input('hora_fin')
    ]);

    return response()->json([
        'status' => 'success', 
        'message' => 'Horario bloqueado exitosamente', 
        'id' => $id
    ]);
});

// ELIMINAR un bloqueo
Route::delete('/bloqueos/{id}', function ($id) {
    $deleted = DB::table('bloqueos_horarios')->where('id', $id)->delete();
    
    if ($deleted) {
        return response()->json(['status' => 'success', 'message' => 'Horario desbloqueado exitosamente']);
    }
    
    return response()->json(['status' => 'error', 'message' => 'No se encontró el bloqueo'], 404);
});
