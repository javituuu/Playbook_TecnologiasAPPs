<?php

namespace App\DTOs\Disponibilidad;

use App\Enums\TipoConflictoEnum;

/**
 * Representa un conflicto de horario o restricción detectada por el motor de disponibilidad (RF.09).
 */
readonly class ConflictoDetectadoDTO
{
    public function __construct(
        public TipoConflictoEnum $tipo,
        public string $codigoRegla,
        public string $mensaje,
        public ?int $userId = null,
        public ?string $nombreJugador = null,
        public ?int $equipoId = null,
        public ?string $nombreEquipo = null,
        public ?int $partidoConflictoId = null,
        public ?string $rangoHorario = null,
        public int $puntosPenalizacion = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo->value,
            'bloquea_publicacion' => $this->tipo->bloqueaPublicacion(),
            'codigo_regla' => $this->codigoRegla,
            'mensaje' => $this->mensaje,
            'user_id' => $this->userId,
            'nombre_jugador' => $this->nombreJugador,
            'equipo_id' => $this->equipoId,
            'nombre_equipo' => $this->nombreEquipo,
            'partido_conflicto_id' => $this->partidoConflictoId,
            'rango_horario' => $this->rangoHorario,
            'puntos_penalizacion' => $this->puntosPenalizacion,
        ];
    }
}
