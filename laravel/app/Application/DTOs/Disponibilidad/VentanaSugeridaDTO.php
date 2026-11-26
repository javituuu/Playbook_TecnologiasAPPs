<?php

namespace App\Application\DTOs\Disponibilidad;

use Illuminate\Support\Collection;

/**
 * Representa una ventana horaria evaluada al cruzar la disponibilidad de dos equipos.
 */
readonly class VentanaSugeridaDTO
{
    /**
     * @param  Collection<int, ConflictoDetectadoDTO>  $conflictos
     */
    public function __construct(
        public string $fecha,
        public string $horaInicio,
        public string $horaFin,
        public bool $disponible,
        public string $etiquetaEstado,
        public Collection $conflictos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin,
            'disponible' => $this->disponible,
            'etiqueta_estado' => $this->etiquetaEstado,
            'conflictos' => $this->conflictos->map(fn (ConflictoDetectadoDTO $c) => $c->toArray())->values()->all(),
        ];
    }
}
