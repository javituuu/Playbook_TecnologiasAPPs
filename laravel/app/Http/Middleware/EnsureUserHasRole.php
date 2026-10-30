<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware RBAC: Verifica que el usuario autenticado tenga uno de los roles permitidos.
 * Uso en rutas: ->middleware('role:jugador') o ->middleware('role:coach,admin_club')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autenticado. Envía un token Bearer válido en el encabezado Authorization.',
            ], 401);
        }

        if (! $user->hasRole(...$roles)) {
            return response()->json([
                'status' => 'error',
                'message' => sprintf(
                    'Acceso denegado. Se requiere uno de los siguientes roles: %s.',
                    implode(', ', $roles)
                ),
                'current_role' => $user->role?->value,
            ], 403);
        }

        return $next($request);
    }
}
