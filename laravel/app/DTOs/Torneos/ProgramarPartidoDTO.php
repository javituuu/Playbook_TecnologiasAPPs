<?php

namespace App\DTOs\Torneos;

use App\Enums\EstadoPartidoEnum;
use App\Enums\FormatoSerieEnum;
use App\Enums\TipoPartidoEnum;
use Carbon\CarbonImmutable;

/**
 * Datos inmutables para programar un partido oficial o práctica de equipo (RF.08).
 */
readonly class ProgramarPartidoDTO
{
    public function __construct(
        public ?int $ligaId,
        public int $equipoLocalId,
        public ?int $equipoVisitanteId,
        public CarbonImmutable $fechaHoraInicio,
        public CarbonImmutable $fechaHoraFin,
        public TipoPartidoEnum $tipo = TipoPartidoEnum::OFICIAL,
        public EstadoPartidoEnum $estado = EstadoPartidoEnum::TENTATIVO,
        public ?int $jornadaNumero = null,
        public ?string $bloqueEtiqueta = null,
        public ?string $lugar = null,
        public FormatoSerieEnum $formatoSerie = FormatoSerieEnum::BO1,
        public int $minutosPorPartida = FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA,
    ) {}

    /**
     * Calcula la duración total en minutos de este partido/serie.
     */
    public function duracionTotalMinutos(): int
    {
        return (int) $this->fechaHoraInicio->diffInMinutes($this->fechaHoraFin);
    }
}
