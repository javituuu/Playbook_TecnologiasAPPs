<?php

namespace App\Application\DTOs\Clubes;

use App\Domain\Enums\RolMembresiaEnum;

/**
 * Datos para actualizar la posición, rol de membresía o rango de un jugador en un equipo (RF.06 / RF.07).
 */
readonly class ActualizarMiembroRosterDTO
{
    public function __construct(
        public int $equipoId,
        public int $userId,
        public ?string $posicionPrincipal,
        public ?string $posicionSecundaria,
        public RolMembresiaEnum $rolMembresia,
        public ?string $rangoDeclarativo = null,
    ) {}
}
