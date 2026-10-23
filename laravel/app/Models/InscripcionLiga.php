<?php

namespace App\Models;

use App\Enums\EstadoInvitacionEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitación e inscripción de un equipo universitario en una liga/torneo.
 */
#[Fillable([
    'liga_id',
    'equipo_id',
    'codigo_invitacion',
    'estado_invitacion',
    'roster_validado',
    'respondido_en',
])]
class InscripcionLiga extends Model
{
    protected $table = 'inscripciones_liga';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_invitacion' => EstadoInvitacionEnum::class,
            'roster_validado' => 'boolean',
            'respondido_en' => 'datetime',
        ];
    }

    /**
     * Liga a la que corresponde la inscripción.
     *
     * @return BelongsTo<Liga, $this>
     */
    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'liga_id');
    }

    /**
     * Equipo invitado/inscrito.
     *
     * @return BelongsTo<Equipo, $this>
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    /**
     * Filtra inscripciones aceptadas.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAceptadas(Builder $query): Builder
    {
        return $query->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA);
    }
}
