<?php

namespace App\Models;

use App\Enums\EstadoInvitacionEnum;
use App\Enums\RolMembresiaEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Equipo competitivo de un club en una disciplina específica (ej. UDD Titans en LoL).
 */
#[Fillable([
    'club_id',
    'disciplina_id',
    'coach_user_id',
    'nombre',
    'activo',
])]
class Equipo extends Model
{
    protected $table = 'equipos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Club universitario al que pertenece el equipo.
     *
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'club_id');
    }

    /**
     * Disciplina en la que compite el equipo.
     *
     * @return BelongsTo<Disciplina, $this>
     */
    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    /**
     * Entrenador (rol coach) asignado al equipo.
     *
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    /**
     * Registros de membresía en el roster del equipo.
     *
     * @return HasMany<EquipoJugador, $this>
     */
    public function membresias(): HasMany
    {
        return $this->hasMany(EquipoJugador::class, 'equipo_id');
    }

    /**
     * Jugadores asociados al roster del equipo.
     *
     * @return BelongsToMany<User, $this, EquipoJugador>
     */
    public function jugadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'equipo_jugador', 'equipo_id', 'user_id')
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
     * Jugadores con invitación aceptada y rol titular.
     *
     * @return BelongsToMany<User, $this, EquipoJugador>
     */
    public function titularesAceptados(): BelongsToMany
    {
        return $this->jugadores()
            ->wherePivot('estado_invitacion', EstadoInvitacionEnum::ACEPTADA->value)
            ->wherePivot('rol_membresia', RolMembresiaEnum::TITULAR->value);
    }

    /**
     * Inscripciones del equipo en distintas ligas/torneos.
     *
     * @return HasMany<InscripcionLiga, $this>
     */
    public function inscripcionesLiga(): HasMany
    {
        return $this->hasMany(InscripcionLiga::class, 'equipo_id');
    }

    /**
     * Partidos en los que el equipo actúa como local.
     *
     * @return HasMany<Partido, $this>
     */
    public function partidosLocal(): HasMany
    {
        return $this->hasMany(Partido::class, 'equipo_local_id');
    }

    /**
     * Partidos en los que el equipo actúa como visitante.
     *
     * @return HasMany<Partido, $this>
     */
    public function partidosVisitante(): HasMany
    {
        return $this->hasMany(Partido::class, 'equipo_visitante_id');
    }

    /**
     * Filtra equipos activos.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Evalúa qué posiciones requeridas por la disciplina aún no tienen un titular aceptado.
     *
     * @return list<string>
     */
    public function posicionesSinTitular(): array
    {
        $posicionesRequeridas = $this->disciplina?->posiciones_validas ?? [];
        if ($posicionesRequeridas === []) {
            return [];
        }

        $ocupadas = $this->membresias()
            ->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA)
            ->where('rol_membresia', RolMembresiaEnum::TITULAR)
            ->pluck('posicion_principal')
            ->filter()
            ->map(fn (string $pos) => strtoupper($pos))
            ->all();

        $faltantes = [];
        foreach ($posicionesRequeridas as $posicion) {
            if (! in_array(strtoupper($posicion), $ocupadas, true)) {
                $faltantes[] = $posicion;
            }
        }

        return $faltantes;
    }

    /**
     * Determina si el equipo cumple con la totalidad de titulares requeridos por su disciplina.
     */
    public function tieneRosterValido(): bool
    {
        $requeridos = $this->disciplina?->titulares_requeridos ?? 5;
        $titularesActuales = $this->membresias()
            ->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA)
            ->where('rol_membresia', RolMembresiaEnum::TITULAR)
            ->count();

        return $titularesActuales >= $requeridos && $this->posicionesSinTitular() === [];
    }
}
