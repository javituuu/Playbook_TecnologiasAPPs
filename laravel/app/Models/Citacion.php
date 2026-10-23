<?php

namespace App\Models;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'partido_id', 'mensaje_coach', 'estado_respuesta'])]
=======
use App\Enums\EstadoCitacionEnum;
use App\Enums\RolMembresiaEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Citación de un jugador a un partido oficial o práctica de equipo (RF.04).
 */
#[Fillable([
    'partido_id',
    'user_id',
    'equipo_id',
    'tipo_convocatoria',
    'mensaje_coach',
    'estado_respuesta',
    'respondido_en',
])]
>>>>>>> rama-laravel
class Citacion extends Model
{
    protected $table = 'citaciones';

<<<<<<< HEAD
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
=======
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_convocatoria' => RolMembresiaEnum::class,
            'estado_respuesta' => EstadoCitacionEnum::class,
            'respondido_en' => 'datetime',
        ];
    }

    /**
     * Partido o práctica a la que se convoca.
     *
     * @return BelongsTo<Partido, $this>
     */
    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'partido_id');
    }

    /**
     * Jugador convocado.
     *
     * @return BelongsTo<User, $this>
     */
    public function jugador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Equipo por el cual fue convocado el jugador.
     *
     * @return BelongsTo<Equipo, $this>
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    /**
     * Filtra citaciones pendientes de respuesta.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_respuesta', EstadoCitacionEnum::PENDIENTE);
    }

    /**
     * Filtra citaciones confirmadas (agenda activa del jugador).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeConfirmadas(Builder $query): Builder
    {
        return $query->where('estado_respuesta', EstadoCitacionEnum::CONFIRMADO);
>>>>>>> rama-laravel
    }
}
