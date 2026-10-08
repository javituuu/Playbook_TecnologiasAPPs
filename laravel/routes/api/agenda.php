<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/**
 * Microservicio: Agenda del Jugador
 * Retorna la lista de partidos en los cuales el jugador tiene una citación "confirmada".
 */
Route::get('/agenda', function () {
    $user = DB::table('users')->where('email', 'javier@email.com')->first();
    
    $agenda = DB::table('citaciones')
        ->join('partidos', 'citaciones.partido_id', '=', 'partidos.id')
        ->where('citaciones.user_id', $user->id)
        ->where('citaciones.estado_respuesta', 'confirmado')
        ->select(
            'partidos.id as id_partido', 
            'partidos.fecha_hora_inicio as fecha_hora', 
            'partidos.liga', 
            'partidos.equipo_local', 
            'partidos.equipo_visitante', 
            'partidos.lugar', 
            'partidos.estado'
        )
        ->get();

    return response()->json([
        "status" => "success",
        "data" => $agenda
    ]);
});
