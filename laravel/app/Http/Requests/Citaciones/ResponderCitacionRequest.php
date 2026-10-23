<?php

namespace App\Http\Requests\Citaciones;

use App\Enums\EstadoCitacionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para responder una citación a partido o práctica ('confirmado' o 'rechazado') (RF.04).
 */
class ResponderCitacionRequest extends FormRequest
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
            'estado' => [
                'required',
                'string',
                Rule::in([
                    EstadoCitacionEnum::CONFIRMADO->value,
                    EstadoCitacionEnum::RECHAZADO->value,
                ]),
            ],
        ];
    }

    public function estadoEnum(): EstadoCitacionEnum
    {
        return EstadoCitacionEnum::from((string) $this->input('estado'));
    }
}
