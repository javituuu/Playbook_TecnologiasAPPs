<?php

namespace App\Http\Resources\Jugador;

use App\Models\BloqueoHorario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización de un bloqueo horario compatible con la app Ionic.
 *
 * @mixin BloqueoHorario
 */
class BloqueoHorarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dia_semana' => $this->dia_semana instanceof \BackedEnum
                ? $this->dia_semana->value
                : $this->dia_semana,
            'hora_inicio' => $this->hora_inicio,
            'hora_fin' => $this->hora_fin,
        ];
    }
}
