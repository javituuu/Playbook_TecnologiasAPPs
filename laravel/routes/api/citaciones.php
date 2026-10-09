<?php

use App\Models\Citacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Citaciones que el jugador todavía no responde.
Route::get('/citaciones', function () {
    $user = User::where('email', 'javier@email.com')->first();

    $citaciones = Citacion::with('partido')
        ->where('user_id', $user->id)
        ->where('estado_respuesta', 'pendiente')
        ->get()
        ->map(fn ($c) => [
            'id_citacion' => $c->id,
            'id_partido' => $c->partido->id,
            'fecha_hora' => $c->partido->fecha_hora_inicio,
            'equipo_rival' => $c->partido->equipo_visitante,
            'liga' => $c->partido->liga,
            'mensaje_coach' => $c->mensaje_coach,
            'estado_respuesta' => $c->estado_respuesta,
        ]);

    return response()->json([
        "status" => "success",
        "data" => $citaciones
    ]);
});

// Responde una citación con 'confirmado' o 'rechazado'.
// Ojo: no revisa que siga pendiente ni que sea del jugador, actualiza cualquier id que le llegue.
Route::patch('/citaciones/{id}/responder', function ($id, Request $request) {
    $estado = $request->input('estado');

    if (!in_array($estado, ['confirmado', 'rechazado'])) {
        return response()->json(['status' => 'error', 'message' => 'Estado inválido'], 400);
    }

    Citacion::where('id', $id)->update(['estado_respuesta' => $estado]);

    return response()->json(['status' => 'success', 'message' => 'Citación actualizada exitosamente']);
});
