<?php

namespace App\Models;

use App\Domain\Enums\EstadoInvitacionEnum;
use App\Domain\Enums\RolMembresiaEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo Pivot personalizado para la membresía de un jugador en un equipo (RF.06 / RF.07).
 */
#[Fillable([
    'equipo_id',
    'user_id',
    'posicion_principal',
    'posicion_secundaria',
    'rango_declarativo',
    'rol_membresia',
    'estado_invitacion',
    'es_principal',
])]
class EquipoJugador extends Pivot
{
    protected $table = 'equipo_jugador';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rol_membresia' => RolMembresiaEnum::class,
            'estado_invitacion' => EstadoInvitacionEnum::class,
            'es_principal' => 'boolean',
        ];
    }

    /**
     * Equipo al que pertenece esta membresía.
     *
     * @return BelongsTo<Equipo, $this>
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    /**
     * Jugador al que pertenece esta membresía.
     *
     * @return BelongsTo<User, $this>
     */
    public function jugador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Filtra únicamente membresías con invitación aceptada.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAceptados(Builder $query): Builder
    {
        return $query->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA);
    }

    /**
     * Filtra únicamente jugadores titulares.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeTitulares(Builder $query): Builder
    {
        return $query->where('rol_membresia', RolMembresiaEnum::TITULAR);
    }

    /**
     * Indica si el jugador puede cubrir una posición dada (como principal o secundaria).
     */
    public function puedeCubrirPosicion(string $posicion): bool
    {
        $pos = strtoupper($posicion);

        return strtoupper((string) $this->posicion_principal) === $pos
            || strtoupper((string) $this->posicion_secundaria) === $pos;
    }
}
