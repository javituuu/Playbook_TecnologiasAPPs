<?php

namespace App\Http\Controllers\Api\Jugador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Disponibilidad\StoreBloqueoHorarioRequest;
use App\Models\BloqueoHorario;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloqueoHorarioController extends Controller
{
    /**
     * POST /api/bloqueos
     * Registra un nuevo bloqueo horario para el jugador autenticado.
     */
    public function store(StoreBloqueoHorarioRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $dto = $request->toDTO($user->id);

        $bloqueo = BloqueoHorario::create($dto->toDatabaseArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Horario bloqueado exitosamente',
            'id' => $bloqueo->id,
        ]);
    }

    /**
     * DELETE /api/bloqueos/{id}
     * Elimina un bloqueo horario verificando pertenencia al jugador autenticado.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $bloqueo = BloqueoHorario::find($id);

        if (! $bloqueo) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró el bloqueo',
            ], 404);
        }

        if ((int) $bloqueo->user_id !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permiso para eliminar un bloqueo horario de otro jugador.',
            ], 403);
        }

        $bloqueo->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Horario desbloqueado exitosamente',
        ]);
    }
}
