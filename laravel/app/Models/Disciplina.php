<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de disciplinas deportivas/esports (League of Legends, Valorant, etc.)
 * junto con sus reglas de quórum y posiciones oficiales.
 */
#[Fillable([
    'nombre',
    'slug',
    'titulares_requeridos',
    'quorum_minimo_practica',
    'duracion_partido_min',
    'posiciones_validas',
])]
class Disciplina extends Model
{
    protected $table = 'disciplinas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'titulares_requeridos' => 'integer',
            'quorum_minimo_practica' => 'integer',
            'duracion_partido_min' => 'integer',
            'posiciones_validas' => 'array',
        ];
    }

    /**
     * Equipos que compiten en esta disciplina.
     *
     * @return HasMany<Equipo, $this>
     */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'disciplina_id');
    }

    /**
     * Ligas/torneos organizados para esta disciplina.
     *
     * @return HasMany<Liga, $this>
     */
    public function ligas(): HasMany
    {
        return $this->hasMany(Liga::class, 'disciplina_id');
    }

    /**
     * Verifica si una posición (ej. "TOP", "SUPPORT") es válida en esta disciplina.
     */
    public function esPosicionValida(string $posicion): bool
    {
        $posiciones = $this->posiciones_validas ?? [];

        return in_array(strtoupper($posicion), array_map('strtoupper', $posiciones), true);
    }
}
