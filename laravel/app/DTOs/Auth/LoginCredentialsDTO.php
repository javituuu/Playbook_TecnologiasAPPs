<?php

namespace App\DTOs\Auth;

use App\Enums\UserRoleEnum;
use App\Http\Requests\Auth\LoginRequest;

/**
 * Datos inmutables de credenciales para autenticación vía API Gateway.
 */
readonly class LoginCredentialsDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName = 'mobile-client',
        public ?UserRoleEnum $rolEsperado = null,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        $rolInput = $request->validated('rol_esperado');

        return new self(
            email: strtolower(trim((string) $request->validated('email'))),
            password: (string) $request->validated('password'),
            deviceName: (string) ($request->validated('device_name') ?: $request->userAgent() ?: 'mobile-client'),
            rolEsperado: $rolInput ? UserRoleEnum::from($rolInput) : null,
        );
    }
}
