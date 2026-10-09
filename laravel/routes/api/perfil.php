<?php

use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Perfil del Jugador
 * Retorna los datos básicos del usuario y sus bloqueos de horario asociados.
 */
Route::get('/perfil', function () {
    $user = User::where('email', 'javier@email.com')->first();

    $bloqueos = BloqueoHorario::where('user_id', $user->id)
        ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fin']);

    return response()->json([
        "status" => "success",
        "data" => [
            "id" => $user->id,
            "nombre_completo" => $user->name,
            "email" => $user->email,
            "club_actual" => "Universidad De Dioses (UDD)",
            "disciplina" => "League of Legends",
            "rol" => "Top Laner",
            "rango" => "Hierro 4",
            "estado" => "Activo en el plantel",
            "horarios_bloqueados" => $bloqueos
        ]
    ]);
});
