<?php

namespace App\Http\Requests\Clubes;

use App\Application\DTOs\Clubes\ActualizarMiembroRosterDTO;
use App\Domain\Enums\RolMembresiaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para asignar posición, rol y rango a un miembro del roster de un equipo (RF.06 / RF.07).
 */
class UpdateRosterMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'posicion_principal' => ['nullable', 'string', 'max:30'],
            'posicion_secundaria' => ['nullable', 'string', 'max:30', 'different:posicion_principal'],
            'rol_membresia' => ['required', 'string', Rule::in(RolMembresiaEnum::values())],
            'rango_declarativo' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function toDTO(int $equipoId, int $userId): ActualizarMiembroRosterDTO
    {
        return new ActualizarMiembroRosterDTO(
            equipoId: $equipoId,
            userId: $userId,
            posicionPrincipal: $this->input('posicion_principal'),
            posicionSecundaria: $this->input('posicion_secundaria'),
            rolMembresia: RolMembresiaEnum::from((string) $this->input('rol_membresia')),
            rangoDeclarativo: $this->input('rango_declarativo'),
        );
    }
}
