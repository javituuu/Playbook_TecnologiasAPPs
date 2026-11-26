<?php

namespace App\Http\Resources\Auth;

use App\Domain\Enums\UserRoleEnum;
use App\Models\Club;
use App\Models\Equipo;
use App\Models\Liga;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON estandarizado para respuestas de identidad en el API Gateway.
 * Entrega a Ionic y Flutter los datos del usuario, su rol y el contexto de sus equipos/clubes.
 *
 * @mixin User
 */
class AuthenticatedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role instanceof UserRoleEnum
            ? $this->role
            : UserRoleEnum::tryFrom((string) $this->role) ?? UserRoleEnum::JUGADOR;

        $contexto = $this->resolverContextoPorRol($role);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nombre_completo' => $this->name,
            'email' => $this->email,
            'role' => $role->value,
            'role_label' => $role->label(),
            'avatar_url' => $this->avatar_url,
            'telefono' => $this->telefono,
            'activo' => (bool) $this->activo,
            'contexto' => $contexto,
            ...($role === UserRoleEnum::COACH ? [
                'equipos_dirigidos' => $contexto['equipos_dirigidos'] ?? [],
            ] : []),
        ];
    }

    /**
     * Datos adicionales útiles al iniciar sesión según el rol del usuario.
     *
     * @return array<string, mixed>
     */
    private function resolverContextoPorRol(UserRoleEnum $role): array
    {
        return match ($role) {
            UserRoleEnum::JUGADOR => $this->contextoJugador(),
            UserRoleEnum::COACH => [
                'equipos_dirigidos' => $this->equiposDirigidos->map(fn (Equipo $e) => [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'club' => $e->club?->nombreConSiglas(),
                    'disciplina' => $e->disciplina?->nombre,
                    'quorum_minimo' => $e->disciplina?->quorum_minimo_practica ?? 4,
                ])->values()->all(),
            ],
            UserRoleEnum::ADMIN_CLUB => [
                'clubes_administrados' => $this->clubesAdministrados->map(fn (Club $c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'siglas' => $c->siglas,
                ])->values()->all(),
            ],
            UserRoleEnum::ADMIN_LIGA => [
                'ligas_organizadas' => $this->ligasOrganizadas->map(fn (Liga $l) => [
                    'id' => $l->id,
                    'nombre' => $l->nombre,
                    'temporada' => $l->temporada,
                    'estado' => $l->estado instanceof \BackedEnum ? $l->estado->value : $l->estado,
                ])->values()->all(),
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function contextoJugador(): array
    {
        $membresia = $this->membresiaPrincipal;
        $equipo = $membresia?->equipo;

        return [
            'equipo_principal_id' => $equipo?->id,
            'equipo_principal' => $equipo?->nombre,
            'club_actual' => $equipo?->club ? $equipo->club->nombreConSiglas() : 'Sin club',
            'disciplina' => $equipo?->disciplina?->nombre ?? 'Sin disciplina',
            'posicion' => $membresia?->posicion_principal ?? 'Sin posición',
            'rango' => $membresia?->rango_declarativo ?? 'Sin rango',
        ];
    }
}
