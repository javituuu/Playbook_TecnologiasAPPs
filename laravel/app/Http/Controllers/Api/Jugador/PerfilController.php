<?php

namespace App\Http\Controllers\Api\Jugador;

use App\Http\Controllers\Controller;
use App\Http\Resources\Jugador\PerfilJugadorResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    /**
     * GET /api/perfil
     * Retorna el perfil deportivo y los bloqueos horarios del jugador autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->load([
            'membresiaPrincipal.equipo.club',
            'membresiaPrincipal.equipo.disciplina',
            'bloqueosHorarios',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => (new PerfilJugadorResource($user))->resolve(),
        ]);
    }
}
