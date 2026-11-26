<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthenticatedUserResource;
use App\Models\User;
use App\Application\Services\Auth\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Controlador de Autenticación del API Gateway para Ionic (Jugador) y Flutter (Coach).
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * POST /api/auth/login
     * Valida credenciales y emite un Bearer token de Laravel Sanctum junto con el rol y perfil.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $resultado = $this->authService->login($request->toDTO());
        } catch (AuthenticationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (HttpExceptionInterface $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Autenticación exitosa.',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $resultado['token'],
                'abilities' => $resultado['abilities'],
                'user' => (new AuthenticatedUserResource($resultado['user']))->resolve(),
            ],
        ]);
    }

    /**
     * GET /api/auth/me (y alias GET /api/auth/verify)
     * Verifica la validez del token actual y retorna los datos del usuario autenticado y sus permisos.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->authService->cargarPerfilConContexto($request->user());
        $accessToken = $user->currentAccessToken();

        $abilities = $accessToken instanceof PersonalAccessToken
            ? ($accessToken->abilities ?? $user->defaultTokenAbilities())
            : $user->defaultTokenAbilities();

        return response()->json([
            'status' => 'success',
            'data' => [
                'authenticated' => true,
                'abilities' => $abilities,
                'user' => (new AuthenticatedUserResource($user))->resolve(),
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     * Revoca el token del dispositivo actual.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->logoutDispositivoActual($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente en este dispositivo.',
        ]);
    }

    /**
     * POST /api/auth/logout-all
     * Revoca todos los tokens activos del usuario en cualquier dispositivo.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $revocados = $this->authService->logoutTodosLosDispositivos($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Se cerraron todas las sesiones activas del usuario.',
            'tokens_revocados' => $revocados,
        ]);
    }
}
