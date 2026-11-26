<?php

namespace App\Http\Resources\Jugador;

use App\Domain\Enums\EstadoInvitacionEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización del perfil del jugador compatible con PerfilResponse de Ionic.
 *
 * @mixin User
 */
class PerfilJugadorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $membresia = $this->membresiaPrincipal;
        $equipo = $membresia?->equipo;
        $club = $equipo?->club;
        $disciplina = $equipo?->disciplina;

        $estadoInvitacion = $membresia?->estado_invitacion instanceof EstadoInvitacionEnum
            ? $membresia->estado_invitacion
            : ($membresia?->estado_invitacion ? EstadoInvitacionEnum::tryFrom((string) $membresia->estado_invitacion) : null);

        return [
            'id' => $this->id,
            'nombre_completo' => $this->name,
            'email' => $this->email,
            'club_actual' => $club ? $club->nombreConSiglas() : 'Sin club',
            'disciplina' => $disciplina?->nombre ?? 'Sin disciplina',
            'rol' => $membresia?->posicion_principal ?? 'Sin posición',
            'rango' => $membresia?->rango_declarativo ?? 'Sin rango',
            'estado' => $estadoInvitacion === EstadoInvitacionEnum::ACEPTADA
                ? 'Activo en el plantel'
                : 'Sin plantel activo',
            'horarios_bloqueados' => BloqueoHorarioResource::collection(
                $this->whenLoaded('bloqueosHorarios', fn () => $this->bloqueosHorarios, $this->bloqueosHorarios)
            ),
        ];
    }
}
