<?php

namespace App\Http\Controllers\Api\Jugador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citaciones\ResponderCitacionRequest;
use App\Http\Resources\Jugador\CitacionResource;
use App\Models\Citacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitacionController extends Controller
{
    /**
     * GET /api/citaciones
     * Lista convocatorias a partidos pendientes de respuesta del jugador.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $citacionesPendientes = Citacion::query()
            ->where('user_id', $user->id)
            ->pendientes()
            ->with(['partido.liga', 'partido.equipoLocal', 'partido.equipoVisitante'])
            ->get()
            ->sortBy(fn (Citacion $c) => $c->partido?->fecha_hora_inicio)
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => CitacionResource::collection($citacionesPendientes)->resolve(),
        ]);
    }

    /**
     * PATCH /api/citaciones/{id}/responder
     * Confirma o rechaza una citación verificando pertenencia al jugador autenticado.
     */
    public function responder(int $id, ResponderCitacionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $citacion = Citacion::find($id);

        if (! $citacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró la citación',
            ], 404);
        }

        if ((int) $citacion->user_id !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permiso para responder una citación de otro jugador.',
            ], 403);
        }

        $citacion->update([
            'estado_respuesta' => $request->estadoEnum(),
            'respondido_en' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Citación actualizada exitosamente',
        ]);
    }
}
