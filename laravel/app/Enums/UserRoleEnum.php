<?php

namespace App\Enums;

/**
 * Roles globales del ecosistema Playbook (RF.01).
 */
enum UserRoleEnum: string
{
    case JUGADOR = 'jugador';
    case COACH = 'coach';
    case ADMIN_CLUB = 'admin_club';
    case ADMIN_LIGA = 'admin_liga';

    public function label(): string
    {
        return match ($this) {
            self::JUGADOR => 'Jugador',
            self::COACH => 'Coach',
            self::ADMIN_CLUB => 'Administrador de Club',
            self::ADMIN_LIGA => 'Administrador de Liga',
        };
    }

    /** Roles que operan desde el panel web de Laravel. */
    public function esRolWeb(): bool
    {
        return in_array($this, [self::ADMIN_CLUB, self::ADMIN_LIGA], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
