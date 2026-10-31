<?php

namespace App\Http\Controllers\Panel;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Controlador de Autenticación del Panel Web de Playbook (Admin de Liga, Admin de Club y Coach).
 */
class PanelAuthController extends Controller
{
    /**
     * GET /login
     * Muestra la pantalla de inicio de sesión del Panel Web con botones de acceso rápido para demos.
     */
    public function showLoginForm(): View
    {
        $cuentasDemo = User::query()
            ->whereIn('role', [
                UserRoleEnum::ADMIN_LIGA->value,
                UserRoleEnum::ADMIN_CLUB->value,
                UserRoleEnum::COACH->value,
            ])
            ->with(['clubesAdministrados', 'ligasOrganizadas', 'equiposDirigidos'])
            ->orderByRaw("CASE role WHEN 'admin_liga' THEN 1 WHEN 'admin_club' THEN 2 ELSE 3 END")
            ->get();

        return view('panel.login', [
            'cuentasDemo' => $cuentasDemo,
        ]);
    }

    /**
     * POST /login
     * Valida credenciales, verifica que el usuario tenga permisos para el panel web y abre sesión.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Las credenciales ingresadas no son válidas.']);
        }

        if (! $user->activo) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Esta cuenta se encuentra desactivada.']);
        }

        if ($user->role === UserRoleEnum::JUGADOR) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Las cuentas de Jugador ingresan únicamente a través de la App Móvil (Ionic).']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $rutaDestino = $user->role === UserRoleEnum::ADMIN_CLUB
            ? route('equipos')
            : route('fixture');

        return redirect($rutaDestino)->with('ok', sprintf(
            'Sesión iniciada como %s (%s).',
            $user->name,
            $user->role->label()
        ));
    }

    /**
     * POST /logout
     * Cierra la sesión actual en el Panel Web.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Sesión cerrada correctamente.');
    }
}
