<?php

<<<<<<< HEAD
use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Mantenedor (crear / borrar) de los bloqueos de horario.

Route::post('/bloqueos', function (Request $request) {
    $request->validate([
        'dia_semana' => 'required|string',
        'hora_inicio' => 'required',
        'hora_fin' => 'required'
    ]);

    $user = User::where('email', 'javier@email.com')->first();

    $bloqueo = BloqueoHorario::create([
        'user_id' => $user->id,
        'dia_semana' => $request->input('dia_semana'),
        'hora_inicio' => $request->input('hora_inicio'),
        'hora_fin' => $request->input('hora_fin')
    ]);
=======
use App\Http\Requests\Disponibilidad\StoreBloqueoHorarioRequest;
use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Microservicio: Bloqueos de Horario (Mantenedor CRUD)
 *
 * Fase 2: Migrado de DB::table() a Eloquent + StoreBloqueoHorarioRequest + CrearBloqueoHorarioDTO.
 */

// CREAR un bloqueo
Route::post('/bloqueos', function (StoreBloqueoHorarioRequest $request) {
    $user = User::where('email', 'javier@email.com')->firstOrFail();
    $dto = $request->toDTO($user->id);

    $bloqueo = BloqueoHorario::create($dto->toDatabaseArray());
>>>>>>> rama-laravel

    return response()->json([
        'status' => 'success',
        'message' => 'Horario bloqueado exitosamente',
<<<<<<< HEAD
        'id' => $bloqueo->id
=======
        'id' => $bloqueo->id,
>>>>>>> rama-laravel
    ]);
});

Route::delete('/bloqueos/{id}', function ($id) {
<<<<<<< HEAD
    // destroy() devuelve cuántos borró, así que 0 = el id no existe.
    // Ojo que tampoco revisa que el bloqueo sea del jugador.
    if (BloqueoHorario::destroy($id)) {
=======
    $bloqueo = BloqueoHorario::find($id);

    if ($bloqueo) {
        $bloqueo->delete();

>>>>>>> rama-laravel
        return response()->json(['status' => 'success', 'message' => 'Horario desbloqueado exitosamente']);
    }

    return response()->json(['status' => 'error', 'message' => 'No se encontró el bloqueo'], 404);
});
