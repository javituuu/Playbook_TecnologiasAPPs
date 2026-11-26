<?php

namespace App\Http\Requests\Auth;

use App\Application\DTOs\Auth\LoginCredentialsDTO;
use App\Domain\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'rol_esperado' => ['nullable', 'string', Rule::in(UserRoleEnum::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'rol_esperado.in' => 'El rol esperado no es válido. Valores permitidos: '.implode(', ', UserRoleEnum::values()).'.',
        ];
    }

    public function toDTO(): LoginCredentialsDTO
    {
        return LoginCredentialsDTO::fromRequest($this);
    }
}
