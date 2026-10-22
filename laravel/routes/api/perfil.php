<?php

use App\Enums\EstadoInvitacionEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Perfil del Jugador
 * Retorna los datos básicos del usuario y sus bloqueos de horario asociados.
 *
 * NOTA (Fase 1): adaptado al esquema relacional manteniendo el contrato JSON de Ionic.
 * En la Fase 5 se reemplaza por Api\Jugador\PerfilController + PerfilJugadorResource.
 */
Route::get('/perfil', function () {
    $user = DB::table('users')->where('email', 'javier@email.com')->first();

    $membresia = DB::table('equipo_jugador')
        ->join('equipos', 'equipo_jugador.equipo_id', '=', 'equipos.id')
        ->join('clubes', 'equipos.club_id', '=', 'clubes.id')
        ->join('disciplinas', 'equipos.disciplina_id', '=', 'disciplinas.id')
        ->where('equipo_jugador.user_id', $user->id)
        ->orderByDesc('equipo_jugador.es_principal')
        ->select(
            'clubes.nombre as club',
            'clubes.siglas as club_siglas',
            'equipos.nombre as equipo',
            'disciplinas.nombre as disciplina',
            'equipo_jugador.posicion_principal',
            'equipo_jugador.rango_declarativo',
            'equipo_jugador.estado_invitacion'
        )
        ->first();

    $bloqueos = DB::table('bloqueos_horarios')
        ->where('user_id', $user->id)
        ->select('id', 'dia_semana', 'hora_inicio', 'hora_fin')
        ->orderBy('dia_semana_iso')
        ->orderBy('hora_inicio')
        ->get();

    return response()->json([
        "status" => "success",
        "data" => [
            "id" => $user->id,
            "nombre_completo" => $user->name,
            "email" => $user->email,
            "club_actual" => $membresia ? "{$membresia->club} ({$membresia->club_siglas})" : "Sin club",
            "disciplina" => $membresia->disciplina ?? "Sin disciplina",
            "rol" => $membresia->posicion_principal ?? "Sin posición",
            "rango" => $membresia->rango_declarativo ?? "Sin rango",
            "estado" => $membresia && $membresia->estado_invitacion === EstadoInvitacionEnum::ACEPTADA->value
                ? "Activo en el plantel"
                : "Sin plantel activo",
            "horarios_bloqueados" => $bloqueos
        ]
    ]);
});
