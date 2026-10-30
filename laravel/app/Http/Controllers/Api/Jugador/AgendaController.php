<?php

namespace App\Http\Controllers\Api\Jugador;

use App\Http\Controllers\Controller;
use App\Http\Resources\Jugador\AgendaPartidoResource;
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * GET /api/agenda
     * Retorna la agenda de partidos con citación confirmada del jugador.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $citacionesConfirmadas = Citacion::query()
            ->where('user_id', $user->id)
            ->confirmadas()
            ->with(['partido.liga', 'partido.equipoLocal', 'partido.equipoVisitante'])
            ->get()
            ->sortBy(fn (Citacion $c) => $c->partido?->fecha_hora_inicio)
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => AgendaPartidoResource::collection($citacionesConfirmadas)->resolve(),
        ]);
    }
}
