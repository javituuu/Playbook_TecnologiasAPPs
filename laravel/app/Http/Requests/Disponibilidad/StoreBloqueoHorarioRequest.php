<?php

namespace App\Http\Requests\Disponibilidad;

use App\DTOs\Disponibilidad\CrearBloqueoHorarioDTO;
use App\Enums\DiaSemanaEnum;
use App\Models\BloqueoHorario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación desacoplada para registrar un bloqueo de horario (RF.02).
 */
class StoreBloqueoHorarioRequest extends FormRequest
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
            'dia_semana' => ['required', 'string', Rule::in(DiaSemanaEnum::values())],
            'hora_inicio' => ['required', 'date_format:H:i,H:i:s'],
            'hora_fin' => [
                'required',
                'date_format:H:i,H:i:s',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $inicio = BloqueoHorario::normalizarHora((string) $this->input('hora_inicio'));
                    $fin = BloqueoHorario::normalizarHora((string) $value);

                    if ($fin <= $inicio) {
                        $fail('La hora de término debe ser posterior a la hora de inicio.');
                    }
                },
            ],
            'motivo' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'fecha_especifica' => ['nullable', 'date'],
            'es_recurrente' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Convierte la petición validada en un DTO inmutable para la capa de servicios.
     */
    public function toDTO(int $userId): CrearBloqueoHorarioDTO
    {
        $fechaEspecifica = $this->filled('fecha_especifica')
            ? (string) $this->input('fecha_especifica')
            : null;

        $esRecurrente = $this->has('es_recurrente')
            ? $this->boolean('es_recurrente')
            : ($fechaEspecifica === null);

        return new CrearBloqueoHorarioDTO(
            userId: $userId,
            diaSemana: DiaSemanaEnum::from((string) $this->input('dia_semana')),
            horaInicio: (string) $this->input('hora_inicio'),
            horaFin: (string) $this->input('hora_fin'),
            motivo: $this->input('motivo'),
            ubicacion: $this->input('ubicacion'),
            fechaEspecifica: $fechaEspecifica,
            esRecurrente: $esRecurrente,
        );
    }
}
