<?php

namespace App\Models;

use App\Domain\Enums\EstadoPartidoEnum;
use App\Domain\Enums\FormatoSerieEnum;
use App\Domain\Enums\TipoPartidoEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Partido oficial de liga o práctica interna de equipo (RF.04 / RF.08 / RF.09).
 */
#[Fillable([
    'liga_id',
    'equipo_local_id',
    'equipo_visitante_id',
    'tipo',
    'formato_serie',
    'minutos_por_partida',
    'jornada_numero',
    'bloque_etiqueta',
    'lugar',
    'fecha_hora_inicio',
    'fecha_hora_fin',
    'estado',
])]
class Partido extends Model
{
    protected $table = 'partidos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPartidoEnum::class,
            'formato_serie' => FormatoSerieEnum::class,
            'minutos_por_partida' => 'integer',
            'jornada_numero' => 'integer',
            'fecha_hora_inicio' => 'datetime',
            'fecha_hora_fin' => 'datetime',
            'estado' => EstadoPartidoEnum::class,
        ];
    }

    /**
     * Liga a la que pertenece el partido (nullable si es práctica interna).
     *
     * @return BelongsTo<Liga, $this>
     */
    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'liga_id');
    }

    /**
     * Equipo que actúa como local (o propietario de la práctica).
     *
     * @return BelongsTo<Equipo, $this>
     */
    public function equipoLocal(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_local_id');
    }

    /**
     * Equipo que actúa como visitante (nullable en prácticas internas).
     *
     * @return BelongsTo<Equipo, $this>
     */
    public function equipoVisitante(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_visitante_id');
    }

    /**
     * Citaciones emitidas a jugadores para este partido/práctica.
     *
     * @return HasMany<Citacion, $this>
     */
    public function citaciones(): HasMany
    {
        return $this->hasMany(Citacion::class, 'partido_id');
    }

    /**
     * Filtra partidos cuyo intervalo [fecha_hora_inicio, fecha_hora_fin) se solapa
     * con el rango dado: (InicioA < FinB) AND (FinA > InicioB).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSolapaCon(Builder $query, CarbonInterface $inicio, CarbonInterface $fin): Builder
    {
        return $query
            ->where('fecha_hora_inicio', '<', $fin)
            ->where('fecha_hora_fin', '>', $inicio);
    }

    /**
     * Filtra partidos que ocupan efectivamente la agenda (excluye borradores y cancelados).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOcupanAgenda(Builder $query): Builder
    {
        return $query->whereNotIn('estado', [
            EstadoPartidoEnum::BORRADOR->value,
            EstadoPartidoEnum::CANCELADO->value,
        ]);
    }

    /**
     * Filtra todos los partidos donde un equipo participa como local o visitante.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDelEquipo(Builder $query, int $equipoId): Builder
    {
        return $query->where(function (Builder $sub) use ($equipoId) {
            $sub->where('equipo_local_id', $equipoId)
                ->orWhere('equipo_visitante_id', $equipoId);
        });
    }

    /**
     * Retorna el nombre de la liga o "Práctica" si no pertenece a una liga.
     */
    public function nombreLigaOPractica(): string
    {
        return $this->liga?->nombre ?? 'Práctica';
    }

    /**
     * Dado el equipo que convocó al jugador, obtiene el nombre del equipo rival
     * (o "Práctica interna" si no hay visitante).
     */
    public function nombreRivalPara(?int $equipoConvocanteId): string
    {
        if ((int) $equipoConvocanteId === (int) $this->equipo_local_id) {
            return $this->equipoVisitante?->nombre ?? 'Práctica interna';
        }

        return $this->equipoLocal?->nombre ?? 'Práctica interna';
    }
}
