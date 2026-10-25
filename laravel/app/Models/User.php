<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'avatar_url', 'telefono', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRoleEnum::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * Indica si el usuario posee alguno de los roles indicados.
     */
    public function hasRole(UserRoleEnum|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $value = $role instanceof UserRoleEnum ? $role->value : $role;

            if ($this->role?->value === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bloqueos horarios académicos/personales registrados por el jugador (RF.02).
     *
     * @return HasMany<BloqueoHorario, $this>
     */
    public function bloqueosHorarios(): HasMany
    {
        return $this->hasMany(BloqueoHorario::class, 'user_id')
            ->orderBy('dia_semana_iso')
            ->orderBy('hora_inicio');
    }

    /**
     * Citaciones a partidos y prácticas recibidas por el jugador (RF.04).
     *
     * @return HasMany<Citacion, $this>
     */
    public function citaciones(): HasMany
    {
        return $this->hasMany(Citacion::class, 'user_id');
    }

    /**
     * Membresías en planteles de equipos (modelo pivot EquipoJugador).
     *
     * @return HasMany<EquipoJugador, $this>
     */
    public function membresias(): HasMany
    {
        return $this->hasMany(EquipoJugador::class, 'user_id');
    }

    /**
     * Membresía principal del jugador (para mostrar en /api/perfil).
     *
     * @return HasOne<EquipoJugador, $this>
     */
    public function membresiaPrincipal(): HasOne
    {
        return $this->hasOne(EquipoJugador::class, 'user_id')
            ->orderByDesc('es_principal')
            ->orderBy('id');
    }

    /**
     * Equipos en los que participa el jugador.
     *
     * @return BelongsToMany<Equipo, $this, EquipoJugador>
     */
    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'equipo_jugador', 'user_id', 'equipo_id')
            ->using(EquipoJugador::class)
            ->withPivot([
                'id',
                'posicion_principal',
                'posicion_secundaria',
                'rango_declarativo',
                'rol_membresia',
                'estado_invitacion',
                'es_principal',
            ])
            ->withTimestamps();
    }

    /**
     * Clubes administrados por este usuario (rol admin_club).
     *
     * @return HasMany<Club, $this>
     */
    public function clubesAdministrados(): HasMany
    {
        return $this->hasMany(Club::class, 'admin_user_id');
    }

    /**
     * Equipos dirigidos por este usuario (rol coach).
     *
     * @return HasMany<Equipo, $this>
     */
    public function equiposDirigidos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'coach_user_id');
    }

    /**
     * Ligas organizadas por este usuario (rol admin_liga).
     *
     * @return HasMany<Liga, $this>
     */
    public function ligasOrganizadas(): HasMany
    {
        return $this->hasMany(Liga::class, 'organizador_user_id');
    }

    /**
     * Permisos (abilities de Sanctum) asignados al emitir un token según el rol del usuario.
     *
     * @return list<string>
     */
    public function defaultTokenAbilities(): array
    {
        return match ($this->role) {
            UserRoleEnum::JUGADOR => [
                'role:jugador',
                'perfil:read',
                'agenda:read',
                'citaciones:read',
                'citaciones:respond',
                'bloqueos:manage',
            ],
            UserRoleEnum::COACH => [
                'role:coach',
                'perfil:read',
                'plantel:read',
                'disponibilidad:read',
                'nomina:manage',
                'practicas:manage',
            ],
            UserRoleEnum::ADMIN_CLUB => [
                'role:admin_club',
                'club:manage',
                'roster:manage',
                'inscripciones:manage',
            ],
            UserRoleEnum::ADMIN_LIGA => [
                'role:admin_liga',
                'liga:manage',
                'fixture:manage',
            ],
            default => ['role:jugador'],
        };
    }
}
