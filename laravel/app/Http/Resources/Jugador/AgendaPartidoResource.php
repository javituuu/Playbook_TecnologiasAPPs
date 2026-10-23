<?php

namespace App\Http\Resources\Jugador;

use App\Models\Citacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización de un ítem de la agenda del jugador compatible con AgendaItem de Ionic.
 *
 * @mixin Citacion
 */
class AgendaPartidoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $partido = $this->partido;
        $estadoPartido = $partido?->estado instanceof \BackedEnum
            ? $partido->estado->value
            : $partido?->estado;

        return [
            'id_partido' => $partido?->id,
            'fecha_hora' => $partido?->fecha_hora_inicio?->format('Y-m-d H:i:s'),
            'liga' => $partido ? $partido->nombreLigaOPractica() : 'Práctica',
            'equipo_local' => $partido?->equipoLocal?->nombre ?? '-',
            'equipo_visitante' => $partido?->equipoVisitante?->nombre ?? '-',
            'lugar' => $partido?->lugar,
            'estado' => $estadoPartido,
        ];
    }
}
