<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware híbrido para el API Gateway:
 *
 * 1. Si el cliente (Ionic / Flutter) envía "Authorization: Bearer <token>", valida
 *    el token con Laravel Sanctum y autentica al usuario correspondiente.
 *    Si el token es inválido, revocado o expirado, responde 401 inmediatamente.
 * 2. Si NO se envía encabezado Authorization y la app está en entorno local/testing,
 *    hace fallback al usuario demo (javier@email.com) para mantener 100% de
 *    retrocompatibilidad con la app Ionic del Certamen 1 mientras se integra el login.
 */
class ResolveApiUserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Si el cliente envió encabezado Authorization (Bearer token)
        if ($request->hasHeader('Authorization') || $request->bearerToken() !== null) {
            $plainTextToken = (string) $request->bearerToken();
            $accessToken = $plainTextToken !== '' ? PersonalAccessToken::findToken($plainTextToken) : null;

            if (
                ! $accessToken ||
                ! ($accessToken->tokenable instanceof User) ||
                ($accessToken->expires_at && $accessToken->expires_at->isPast())
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token de autenticación inválido, revocado o expirado.',
                ], 401);
            }

            /** @var User $user */
            $user = $accessToken->tokenable->withAccessToken($accessToken);

            if (! $user->activo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La cuenta de usuario se encuentra desactivada.',
                ], 403);
            }

            $request->setUserResolver(fn () => $user);

            return $next($request);
        }

        // 2. Soporte para Sanctum::actingAs($user) o sesión activa en pruebas
        $existingUser = $request->user('sanctum') ?? $request->user();
        if ($existingUser instanceof User) {
            if (! $existingUser->activo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La cuenta de usuario se encuentra desactivada.',
                ], 403);
            }

            $request->setUserResolver(fn () => $existingUser);

            return $next($request);
        }

        // 3. Fallback en entorno local/testing cuando aún no se envía el header Authorization
        if (app()->environment(['local', 'testing'])) {
            $fallbackUser = User::where('email', 'javier@email.com')->first();

            if ($fallbackUser) {
                $request->setUserResolver(fn () => $fallbackUser);

                return $next($request);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No autenticado. Envía un token Bearer válido en el encabezado Authorization.',
        ], 401);
    }
}
