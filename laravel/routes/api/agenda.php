<?php

<<<<<<< HEAD
=======
use App\Http\Resources\Jugador\AgendaPartidoResource;
>>>>>>> rama-laravel
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Support\Facades\Route;

<<<<<<< HEAD
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
=======
/**
 * Microservicio: Agenda del Jugador
 * Retorna la lista de partidos en los cuales el jugador tiene una citación "confirmada".
 *
 * Fase 2: Migrado de DB::table() a Eloquent + AgendaPartidoResource manteniendo el contrato JSON de Ionic.
 */
Route::get('/agenda', function () {
    $user = User::where('email', 'javier@email.com')->firstOrFail();

    $citacionesConfirmadas = Citacion::query()
        ->where('user_id', $user->id)
        ->confirmadas()
        ->with(['partido.liga', 'partido.equipoLocal', 'partido.equipoVisitante'])
        ->get()
        ->sortBy(fn (Citacion $c) => $c->partido?->fecha_hora_inicio)
        ->values();
>>>>>>> rama-laravel

    return response()->json([
        'status' => 'success',
        'data' => AgendaPartidoResource::collection($citacionesConfirmadas)->resolve(),
    ]);
});
