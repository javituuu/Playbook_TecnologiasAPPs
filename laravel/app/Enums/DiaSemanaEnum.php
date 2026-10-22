<?php

namespace App\Enums;

/**
 * Días de la semana. El valor string se mantiene en español porque es el
 * contrato que ya consume la app Ionic (ej. "Miércoles"), y se expone el
 * número ISO-8601 (1 = Lunes ... 7 = Domingo) para cálculos con Carbon.
 */
enum DiaSemanaEnum: string
{
    case LUNES = 'Lunes';
    case MARTES = 'Martes';
    case MIERCOLES = 'Miércoles';
    case JUEVES = 'Jueves';
    case VIERNES = 'Viernes';
    case SABADO = 'Sábado';
    case DOMINGO = 'Domingo';

    public function iso(): int
    {
        return match ($this) {
            self::LUNES => 1,
            self::MARTES => 2,
            self::MIERCOLES => 3,
            self::JUEVES => 4,
            self::VIERNES => 5,
            self::SABADO => 6,
            self::DOMINGO => 7,
        };
    }

    public static function fromIso(int $iso): self
    {
        foreach (self::cases() as $case) {
            if ($case->iso() === $iso) {
                return $case;
            }
        }

        throw new \ValueError("Día ISO inválido: {$iso}");
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
