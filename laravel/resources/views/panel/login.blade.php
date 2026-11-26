@extends('layouts.panel')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <header class="cabecera cabecera--acceso">
        <h1>Iniciar sesión</h1>
        <p>
            Ingresa con tu cuenta de organizador o directiva de club para gestionar ligas, partidos y planteles.
        </p>
        <img class="login-visual" src="{{ asset('images/playbook-panel.webp') }}"
            width="900" height="720" loading="lazy" alt="">
    </header>

    <div class="login-layout">
        {{-- Formulario de login tradicional --}}
        <section class="bloque" aria-labelledby="titulo-form-login">
            <div class="bloque__titulo">
                <h2 id="titulo-form-login">Credenciales</h2>
            </div>

            <div class="tarjeta-login">
                <form method="POST" action="{{ route('login.submit') }}" class="form-login">
                    @csrf

                    <div class="campo">
                        <label for="email" class="label-bold">Correo electrónico</label>
                        <div class="campo-con-icono">
                            <x-icono nombre="mail-outline" class="campo-con-icono__icono" />
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email', 'directiva.liga@playbook.cl') }}"
                                required
                                autocomplete="username"
                                class="control control--con-icono"
                                placeholder="directiva.liga@playbook.cl"
                            >
                        </div>
                    </div>

                    <div class="campo">
                        <label for="password" class="label-bold">Contraseña</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            value="password"
                            required
                            class="control"
                            placeholder="••••••••"
                        >
                        <span class="tenue nota-demo">Clave de prueba: <code>password</code></span>
                    </div>

                    <button type="submit" class="boton boton--primario boton--ancho">
                        <x-icono nombre="log-in-outline" />
                        <span>Iniciar sesión</span>
                    </button>
                </form>
            </div>
        </section>

        {{-- Cuentas de prueba para ingresar directo --}}
        <section class="bloque" aria-labelledby="titulo-demo-login">
            <div class="bloque__titulo">
                <h2 id="titulo-demo-login">Cuentas de prueba para ingresar directo</h2>
                <span>Selecciona un perfil para ingresar según sus permisos</span>
            </div>

            <div class="tarjetas-cuentas">
                @foreach ($cuentasDemo as $cuenta)
                    @php
                        $iniciales = collect(explode(' ', $cuenta->name))
                            ->map(fn($p) => mb_substr($p, 0, 1))
                            ->take(2)
                            ->join('');
                        $iconoRol = match ($cuenta->role) {
                            \App\Domain\Enums\UserRoleEnum::ADMIN_LIGA => 'trophy-outline',
                            \App\Domain\Enums\UserRoleEnum::ADMIN_CLUB => 'shield-outline',
                            default => 'person-circle-outline',
                        };
                    @endphp
                    <div class="tarjeta-cuenta">
                        <div class="avatar avatar--md tarjeta-cuenta__avatar" aria-hidden="true">
                            <x-icono :nombre="$iconoRol" />
                            <b class="avatar__dorsal">{{ $iniciales }}</b>
                        </div>

                        <div class="tarjeta-cuenta__info">
                            <div class="tarjeta-cuenta__cabecera">
                                <strong>{{ $cuenta->name }}</strong>
                                <x-estado :valor="$cuenta->role" />
                            </div>
                            <span class="tenue font-grotesk tarjeta-cuenta__email">{{ $cuenta->email }}</span>
                            <p class="tarjeta-cuenta__desc">
                                @if ($cuenta->role === \App\Domain\Enums\UserRoleEnum::ADMIN_LIGA)
                                    Crea y programa ligas universitarias, resuelve choques de horario y publica el fixture oficial.
                                @elseif ($cuenta->role === \App\Domain\Enums\UserRoleEnum::ADMIN_CLUB)
                                    Administra {{ $cuenta->clubesAdministrados->pluck('nombre')->join(', ') ?: 'su club' }}, gestiona los planteles y define titulares y suplentes.
                                @else
                                    Coordina {{ $cuenta->equiposDirigidos->pluck('nombre')->join(', ') ?: 'su equipo' }}, revisa la disponibilidad semanal y confirma la alineación.
                                @endif
                            </p>

                            <form method="POST" action="{{ route('login.submit') }}" class="tarjeta-cuenta__accion">
                                @csrf
                                <input type="hidden" name="email" value="{{ $cuenta->email }}">
                                <input type="hidden" name="password" value="password">
                                <button type="submit" class="boton boton--secundario tarjeta-cuenta__boton">
                                    Entrar como {{ $cuenta->name }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
