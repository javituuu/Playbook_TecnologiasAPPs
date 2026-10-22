<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Agenda del Jugador
 * Retorna la lista de partidos en los cuales el jugador tiene una citación "confirmada".
 *
 * NOTA (Fase 1): adaptado al esquema relacional manteniendo el contrato JSON de Ionic.
 * En la Fase 5 se reemplaza por Api\Jugador\AgendaController + AgendaPartidoResource.
 */
Route::get('/agenda', function () {
    $user = DB::table('users')->where('email', 'javier@email.com')->first();

    $agenda = DB::table('citaciones')
        ->join('partidos', 'citaciones.partido_id', '=', 'partidos.id')
        ->join('equipos as local', 'partidos.equipo_local_id', '=', 'local.id')
        ->leftJoin('equipos as visitante', 'partidos.equipo_visitante_id', '=', 'visitante.id')
        ->leftJoin('ligas', 'partidos.liga_id', '=', 'ligas.id')
        ->where('citaciones.user_id', $user->id)
        ->where('citaciones.estado_respuesta', 'confirmado')
        ->orderBy('partidos.fecha_hora_inicio')
        ->select(
            'partidos.id as id_partido',
            'partidos.fecha_hora_inicio as fecha_hora',
            DB::raw("COALESCE(ligas.nombre, 'Práctica') as liga"),
            'local.nombre as equipo_local',
            DB::raw("COALESCE(visitante.nombre, '-') as equipo_visitante"),
            'partidos.lugar',
            'partidos.estado'
        )
        ->get();

    return response()->json([
        "status" => "success",
        "data" => $agenda
    ]);
});
