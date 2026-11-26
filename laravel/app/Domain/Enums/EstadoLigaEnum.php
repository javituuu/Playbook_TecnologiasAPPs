<?php

namespace App\Domain\Enums;

enum EstadoLigaEnum: string
{
    case BORRADOR = 'borrador';
    case INSCRIPCIONES = 'inscripciones';
    case EN_CURSO = 'en_curso';
    case FINALIZADA = 'finalizada';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
