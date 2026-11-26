<?php

namespace App\Domain\Enums;

/**
 * Formato de serie competitiva de un partido (al mejor de 1, 3 o 5 partidas).
 * Permite calcular la duración máxima reservada en agenda como:
 *   duracion_total = maxPartidas() * minutosPorPartida
 */
enum FormatoSerieEnum: string
{
    case BO1 = 'bo1';
    case BO3 = 'bo3';
    case BO5 = 'bo5';

    /**
     * Duración estándar (en minutos) de una partida individual por defecto.
     */
    public const MINUTOS_ESTANDAR_POR_PARTIDA = 50;

    /**
     * Cantidad máxima de mapas/partidas (n) que puede durar la serie.
     */
    public function maxPartidas(): int
    {
        return match ($this) {
            self::BO1 => 1,
            self::BO3 => 3,
            self::BO5 => 5,
        };
    }

    /**
     * Etiqueta legible para mostrar en CLI o UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::BO1 => 'Mejor de 1 (Bo1)',
            self::BO3 => 'Mejor de 3 (Bo3)',
            self::BO5 => 'Mejor de 5 (Bo5)',
        };
    }

    /**
     * Calcula la duración total en minutos de la serie: n * minutosPorPartida.
     */
    public function calcularDuracionMinutos(int $minutosPorPartida = self::MINUTOS_ESTANDAR_POR_PARTIDA): int
    {
        return $this->maxPartidas() * max(1, $minutosPorPartida);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
