<?php

namespace App\Enums;

enum TipoPartidoEnum: string
{
    case OFICIAL = 'oficial';
    case PRACTICA = 'practica';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
