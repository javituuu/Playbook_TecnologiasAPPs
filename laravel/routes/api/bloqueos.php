<?php

use App\Enums\DiaSemanaEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

/**
 * Microservicio: Bloqueos de Horario (Mantenedor CRUD)
 *
 * NOTA (Fase 1): adaptado al esquema relacional manteniendo el contrato JSON de Ionic.
 * En la Fase 5 se reemplaza por Api\Jugador\BloqueoHorarioController + StoreBloqueoHorarioRequest.
 */

// CREAR un bloqueo
Route::post('/bloqueos', function (Request $request) {
    // Validar requerimientos básicos
    $request->validate([
        'dia_semana' => ['required', 'string', Rule::in(DiaSemanaEnum::values())],
        'hora_inicio' => ['required', 'date_format:H:i,H:i:s'],
        'hora_fin' => [
            'required',
            'date_format:H:i,H:i:s',
            function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                // Normaliza a H:i:s para comparar ambos formatos aceptados.
                $inicio = substr((string) $request->input('hora_inicio').':00', 0, 8);
                $fin = substr((string) $value.':00', 0, 8);

                if ($fin <= $inicio) {
                    $fail('La hora de término debe ser posterior a la hora de inicio.');
                }
            },
        ],
    ]);

    $user = DB::table('users')->where('email', 'javier@email.com')->first();
    $dia = DiaSemanaEnum::from($request->input('dia_semana'));

    $id = DB::table('bloqueos_horarios')->insertGetId([
        'user_id' => $user->id,
        'dia_semana' => $dia->value,
        'dia_semana_iso' => $dia->iso(),
        'hora_inicio' => $request->input('hora_inicio'),
        'hora_fin' => $request->input('hora_fin'),
        'es_recurrente' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Horario bloqueado exitosamente',
        'id' => $id
    ]);
});

// ELIMINAR un bloqueo
Route::delete('/bloqueos/{id}', function ($id) {
    $deleted = DB::table('bloqueos_horarios')->where('id', $id)->delete();

    if ($deleted) {
        return response()->json(['status' => 'success', 'message' => 'Horario desbloqueado exitosamente']);
    }

    return response()->json(['status' => 'error', 'message' => 'No se encontró el bloqueo'], 404);
});
