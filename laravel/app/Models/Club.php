<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Institución o Club Deportivo Universitario (ej. UDD, PUC, USM).
 */
#[Fillable([
    'nombre',
    'siglas',
    'logo_url',
    'admin_user_id',
])]
class Club extends Model
{
    protected $table = 'clubes';

    /**
     * Usuario con rol admin_club responsable del club.
     *
     * @return BelongsTo<User, $this>
     */
    public function administrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Equipos pertenecientes a este club.
     *
     * @return HasMany<Equipo, $this>
     */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'club_id');
    }

    /**
     * Nombre formateado con siglas para visualización en perfil (ej. "Universidad del Desarrollo (UDD)").
     */
    public function nombreConSiglas(): string
    {
        return "{$this->nombre} ({$this->siglas})";
    }
}
