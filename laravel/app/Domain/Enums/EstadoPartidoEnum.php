<?php

namespace App\Domain\Enums;

/**
 * Ciclo de vida de un partido/práctica dentro del fixture (RF.08 / RF.09).
 */
enum EstadoPartidoEnum: string
{
    case BORRADOR = 'borrador';
    case TENTATIVO = 'tentativo';
    case CONFLICTO = 'conflicto';
    case PROGRAMADO = 'programado';
    case PUBLICADO = 'publicado';
    case FINALIZADO = 'finalizado';
    case CANCELADO = 'cancelado';

    /** Estados que ocupan la agenda del jugador y deben considerarse al cruzar horarios. */
    public function ocupaAgenda(): bool
    {
        return ! in_array($this, [self::BORRADOR, self::CANCELADO], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
