<?php

use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Perfil del jugador + sus bloqueos de horario.
// Por ahora no hay login, así que todas las rutas trabajan con Javier fijo.
Route::get('/perfil', function () {
    $user = User::where('email', 'javier@email.com')->first();

    $bloqueos = BloqueoHorario::where('user_id', $user->id)
        ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fin']);

    return response()->json([
        "status" => "success",
        "data" => [
            // Club, disciplina, rol y rango van a mano por ahora, todavía no están en la BD.
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
