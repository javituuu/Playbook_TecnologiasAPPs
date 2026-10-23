<?php

namespace App\Http\Resources\Jugador;

use App\Models\Citacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización de una citación pendiente compatible con CitacionItem de Ionic.
 *
 * @mixin Citacion
 */
class CitacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $partido = $this->partido;
        $estadoRespuesta = $this->estado_respuesta instanceof \BackedEnum
            ? $this->estado_respuesta->value
            : $this->estado_respuesta;

        return [
            'id_citacion' => $this->id,
            'id_partido' => $partido?->id,
            'fecha_hora' => $partido?->fecha_hora_inicio?->format('Y-m-d H:i:s'),
            'equipo_rival' => $partido ? $partido->nombreRivalPara($this->equipo_id) : 'Práctica interna',
            'liga' => $partido ? $partido->nombreLigaOPractica() : 'Práctica',
            'mensaje_coach' => $this->mensaje_coach,
            'estado_respuesta' => $estadoRespuesta,
        ];
    }
}
