<?php

namespace App\Domain\Enums;

/**
 * Respuesta de un jugador a una citación (RF.04). Valores compatibles con Ionic.
 */
enum EstadoCitacionEnum: string
{
    case PENDIENTE = 'pendiente';
    case CONFIRMADO = 'confirmado';
    case RECHAZADO = 'rechazado';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
