<?php

namespace App\Models;

use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoLigaEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Liga o torneo universitario gestionado por un Administrador de Liga (RF.08 / RF.09).
 */
#[Fillable([
    'disciplina_id',
    'organizador_user_id',
    'nombre',
    'temporada',
    'estado',
    'descanso_minimo_horas',
    'tolerancia_penalizacion_max',
])]
class Liga extends Model
{
    protected $table = 'ligas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoLigaEnum::class,
            'descanso_minimo_horas' => 'integer',
            'tolerancia_penalizacion_max' => 'integer',
        ];
    }

    /**
     * Disciplina en la que se disputa la liga.
     *
     * @return BelongsTo<Disciplina, $this>
     */
    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    /**
     * Usuario organizador (rol admin_liga) propietario del torneo.
     *
     * @return BelongsTo<User, $this>
     */
    public function organizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizador_user_id');
    }

    /**
     * Registros de inscripción/invitación de equipos a esta liga.
     *
     * @return HasMany<InscripcionLiga, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionLiga::class, 'liga_id');
    }

    /**
     * Equipos inscritos en la liga.
     *
     * @return BelongsToMany<Equipo, $this>
     */
    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'inscripciones_liga', 'liga_id', 'equipo_id')
            ->withPivot([
                'id',
                'codigo_invitacion',
                'estado_invitacion',
                'roster_validado',
                'respondido_en',
            ])
            ->withTimestamps();
    }

    /**
     * Equipos con inscripción aceptada en la liga.
     *
     * @return BelongsToMany<Equipo, $this>
     */
    public function equiposAceptados(): BelongsToMany
    {
        return $this->equipos()
            ->wherePivot('estado_invitacion', EstadoInvitacionEnum::ACEPTADA->value);
    }

    /**
     * Partidos programados dentro del fixture de esta liga.
     *
     * @return HasMany<Partido, $this>
     */
    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class, 'liga_id')
            ->orderBy('jornada_numero')
            ->orderBy('fecha_hora_inicio');
    }
}
