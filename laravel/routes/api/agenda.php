<?php

use App\Models\Citacion;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Agenda = partidos donde ya confirmó su citación.
Route::get('/agenda', function () {
    $user = User::where('email', 'javier@email.com')->first();

    $agenda = Citacion::with('partido')
        ->where('user_id', $user->id)
        ->where('estado_respuesta', 'confirmado')
        ->get()
        ->map(fn ($c) => [
            'id_partido' => $c->partido->id,
            'fecha_hora' => $c->partido->fecha_hora_inicio,
            'liga' => $c->partido->liga,
            'equipo_local' => $c->partido->equipo_local,
            'equipo_visitante' => $c->partido->equipo_visitante,
            'lugar' => $c->partido->lugar,
            'estado' => $c->partido->estado,
        ]);

    return response()->json([
        "status" => "success",
        "data" => $agenda
    ]);
});
