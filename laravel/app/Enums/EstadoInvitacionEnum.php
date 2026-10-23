<?php

namespace App\Enums;

/**
 * Estado de una invitación (jugador → equipo, o equipo → liga).
 */
enum EstadoInvitacionEnum: string
{
    case PENDIENTE = 'pendiente';
    case ACEPTADA = 'aceptada';
    case RECHAZADA = 'rechazada';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
