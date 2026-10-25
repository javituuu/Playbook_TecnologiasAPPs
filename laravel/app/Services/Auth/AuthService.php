<?php

namespace App\Services\Auth;

use App\DTOs\Auth\LoginCredentialsDTO;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Servicio central de autenticación del API Gateway (Laravel Sanctum).
 */
class AuthService
{
    /**
     * Valida credenciales, estado de cuenta y rol opcional, y emite un token Sanctum.
     *
     * @return array{token: string, abilities: list<string>, user: User}
     *
     * @throws AuthenticationException
     */
    public function login(LoginCredentialsDTO $dto): array
    {
        $user = User::query()
            ->where('email', $dto->email)
            ->with([
                'membresiaPrincipal.equipo.club',
                'membresiaPrincipal.equipo.disciplina',
                'equiposDirigidos.club',
                'equiposDirigidos.disciplina',
                'clubesAdministrados',
                'ligasOrganizadas',
            ])
            ->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new AuthenticationException('Las credenciales proporcionadas son incorrectas.');
        }

        if (! $user->activo) {
            throw new HttpException(403, 'La cuenta de usuario se encuentra desactivada.');
        }

        if ($dto->rolEsperado !== null && ! $user->hasRole($dto->rolEsperado)) {
            throw new AccessDeniedHttpException(
                sprintf(
                    'Tu cuenta tiene rol "%s", pero esta aplicación requiere el rol "%s".',
                    $user->role->value,
                    $dto->rolEsperado->value
                )
            );
        }

        $abilities = $user->defaultTokenAbilities();
        $newAccessToken = $user->createToken($dto->deviceName, $abilities);

        return [
            'token' => $newAccessToken->plainTextToken,
            'abilities' => $abilities,
            'user' => $user,
        ];
    }

    /**
     * Carga las relaciones de contexto del usuario autenticado para /api/auth/me.
     */
    public function cargarPerfilConContexto(User $user): User
    {
        return $user->loadMissing([
            'membresiaPrincipal.equipo.club',
            'membresiaPrincipal.equipo.disciplina',
            'equiposDirigidos.club',
            'equiposDirigidos.disciplina',
            'clubesAdministrados',
            'ligasOrganizadas',
        ]);
    }

    /**
     * Revoca únicamente el token con el que se realizó la petición actual.
     */
    public function logoutDispositivoActual(User $user): void
    {
        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }
    }

    /**
     * Revoca todos los tokens activos del usuario en todos sus dispositivos.
     */
    public function logoutTodosLosDispositivos(User $user): int
    {
        return (int) $user->tokens()->delete();
    }
}
