<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Microservicio: Citaciones del Jugador
 * Retorna las invitaciones a partidos que están pendientes de respuesta ('pendiente').
 */
Route::get('/citaciones', function () {
    $user = DB::table('users')->where('email', 'javier@email.com')->first();

    $citaciones = DB::table('citaciones')
        ->join('partidos', 'citaciones.partido_id', '=', 'partidos.id')
        ->where('citaciones.user_id', $user->id)
        ->where('citaciones.estado_respuesta', 'pendiente')
        ->select(
            'citaciones.id as id_citacion', 
            'partidos.id as id_partido', 
            'partidos.fecha_hora_inicio as fecha_hora', 
            'partidos.equipo_visitante as equipo_rival', 
            'partidos.liga', 
            'citaciones.mensaje_coach', 
            'citaciones.estado_respuesta'
        )
        ->get();

    return response()->json([
        "status" => "success",
        "data" => $citaciones
    ]);
});

/**
 * Microservicio: Responder Citación
 * Permite cambiar el estado de una citación específica ('confirmado' o 'rechazado').
 */
Route::patch('/citaciones/{id}/responder', function ($id, Request $request) {
    $estado = $request->input('estado');
    
    // Validación de estado
    if (!in_array($estado, ['confirmado', 'rechazado'])) {
        return response()->json(['status' => 'error', 'message' => 'Estado inválido'], 400);
    }

    // Actualización de registro
    DB::table('citaciones')
        ->where('id', $id)
        ->update([
            'estado_respuesta' => $estado, 
            'updated_at' => now()
        ]);

    return response()->json(['status' => 'success', 'message' => 'Citación actualizada exitosamente']);
});
