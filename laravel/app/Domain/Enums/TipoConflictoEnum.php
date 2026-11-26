<?php

namespace App\Domain\Enums;

/**
 * Severidad de una alerta del validador de fixture (ver mockups de Figma).
 */
enum TipoConflictoEnum: string
{
    /** Bloquea la publicación del fixture (ej. jugador con partido en otra liga). */
    case VIOLACION_DURA = 'violacion_dura';

    /** Solo informativa (ej. descanso < 24h, desequilibrio de localía). */
    case ADVERTENCIA_BLANDA = 'advertencia_blanda';

    public function bloqueaPublicacion(): bool
    {
        return $this === self::VIOLACION_DURA;
    }
}
