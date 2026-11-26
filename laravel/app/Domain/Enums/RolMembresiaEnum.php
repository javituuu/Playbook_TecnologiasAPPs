<?php

namespace App\Domain\Enums;

/**
 * Situación de un jugador dentro del roster de un equipo (RF.06 / RF.07).
 */
enum RolMembresiaEnum: string
{
    case TITULAR = 'titular';
    case SUPLENTE = 'suplente';
    case EN_PRUEBA = 'en_prueba';

    public function label(): string
    {
        return match ($this) {
            self::TITULAR => 'Titular',
            self::SUPLENTE => 'Suplente',
            self::EN_PRUEBA => 'En prueba',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
