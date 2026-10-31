@extends('layouts.panel')

@section('titulo', 'Iniciar Sesión')

@section('contenido')
    <header class="cabecera">
        <h1>Iniciar Sesión · Panel Web</h1>
        <p>
            Ingresa con tu cuenta de <strong>Organizador de Liga</strong> o <strong>Directiva de Club</strong> para gestionar fixtures, validar conflictos horarios y administrar los rosters.
        </p>
    </header>

    <div class="login-layout">
        {{-- Formulario de Login tradicional --}}
        <section class="bloque tarjeta-login" aria-labelledby="titulo-form-login">
            <div class="bloque__titulo">
                <h2 id="titulo-form-login">Credenciales de Acceso</h2>
            </div>

            <form method="POST" action="{{ route('login.submit') }}" class="form-login">
                @csrf

                <div class="campo">
                    <label for="email">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', 'directiva.liga@playbook.cl') }}"
                        required
                        autofocus
                        class="control"
                        placeholder="directiva.liga@playbook.cl"
                    >
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        value="password"
                        required
                        class="control"
                        placeholder="••••••••"
                    >
                    <span class="tenue" style="font-size: 12px;">En ambiente de desarrollo todas las cuentas usan: <code>password</code></span>
                </div>

                <button type="submit" class="boton boton--primario boton--ancho">
                    Ingresar al Panel
                </button>
            </form>
        </section>

        {{-- Tarjetas de Acceso Rápido en 1 Clic para Demostración --}}
        <section class="bloque" aria-labelledby="titulo-demo-login">
            <div class="bloque__titulo">
                <h2 id="titulo-demo-login">Acceso Rápido para Demostración (1 Clic)</h2>
                <span>Selecciona un perfil para ingresar directamente con su rol y permisos</span>
            </div>

            <div class="tarjetas-cuentas">
                @foreach ($cuentasDemo as $cuenta)
                    <div class="tarjeta-cuenta">
                        <div class="tarjeta-cuenta__info">
                            <div class="tarjeta-cuenta__cabecera">
                                <strong>{{ $cuenta->name }}</strong>
                                <span class="estado {{ $cuenta->role === \App\Enums\UserRoleEnum::ADMIN_LIGA ? 'estado--ok' : ($cuenta->role === \App\Enums\UserRoleEnum::ADMIN_CLUB ? 'estado--aviso' : 'estado--neutro') }}">
                                    {{ $cuenta->role->label() }}
                                </span>
                            </div>
                            <span class="tenue">{{ $cuenta->email }}</span>
                            <p class="tarjeta-cuenta__desc">
                                @if ($cuenta->role === \App\Enums\UserRoleEnum::ADMIN_LIGA)
                                    Organiza ligas universitarias, valida choques multi-liga, reprograma partidos en Bo1/Bo3/Bo5 y publica el Fixture oficial.
                                @elseif ($cuenta->role === \App\Enums\UserRoleEnum::ADMIN_CLUB)
                                    Administra {{ $cuenta->clubesAdministrados->pluck('nombre')->join(', ') ?: 'el Club Universitario' }}, promueve titulares/suplentes y confirma invitaciones al roster.
                                @else
                                    Dirige {{ $cuenta->equiposDirigidos->pluck('nombre')->join(', ') ?: 'su equipo' }}, revisa la Matriz Semanal de Disponibilidad y gestiona alineaciones.
                                @endif
                            </p>
                        </div>

                        <form method="POST" action="{{ route('login.submit') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $cuenta->email }}">
                            <input type="hidden" name="password" value="password">
                            <button type="submit" class="boton boton--secundario">
                                Entrar como {{ $cuenta->name }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
