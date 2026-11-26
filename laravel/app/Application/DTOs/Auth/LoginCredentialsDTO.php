<?php

namespace App\Application\DTOs\Auth;

use App\Domain\Enums\UserRoleEnum;
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
        $deviceName = (string) ($request->validated('device_name') ?: $request->userAgent() ?: 'mobile-client');

        if (! $rolInput && str_contains(strtolower($deviceName), 'coach')) {
            $rolInput = UserRoleEnum::COACH->value;
        }

        return new self(
            email: strtolower(trim((string) $request->validated('email'))),
            password: (string) $request->validated('password'),
            deviceName: $deviceName,
            rolEsperado: $rolInput ? UserRoleEnum::from($rolInput) : null,
        );
    }
}
