<?php

namespace App\Application\DTOs\Disponibilidad;

use App\Domain\Enums\DiaSemanaEnum;

/**
 * Datos tipados e inmutables para crear un bloqueo horario de jugador (RF.02).
 */
readonly class CrearBloqueoHorarioDTO
{
    public function __construct(
        public int $userId,
        public DiaSemanaEnum $diaSemana,
        public string $horaInicio,
        public string $horaFin,
        public ?string $motivo = null,
        public ?string $ubicacion = null,
        public ?string $fechaEspecifica = null,
        public bool $esRecurrente = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseArray(): array
    {
        return [
            'user_id' => $this->userId,
            'dia_semana' => $this->diaSemana->value,
            'dia_semana_iso' => $this->diaSemana->iso(),
            'fecha_especifica' => $this->fechaEspecifica,
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin,
            'motivo' => $this->motivo,
            'ubicacion' => $this->ubicacion,
            'es_recurrente' => $this->esRecurrente,
        ];
    }
}
