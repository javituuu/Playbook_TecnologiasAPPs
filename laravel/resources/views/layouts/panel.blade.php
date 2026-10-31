<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('titulo') · Playbook</title>
    <link rel="preload" href="{{ asset('fonts/inter/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}?v={{ filemtime(public_path('css/panel.css')) }}">
</head>
<body>
    <a class="saltar" href="#contenido">Ir al contenido</a>

    <div class="marco">
        <aside class="lateral">
            <a class="marca" href="{{ route('fixture') }}">Playbook</a>

            <nav class="nav" aria-label="Secciones">
                <a href="{{ route('fixture') }}" @if (request()->routeIs('fixture')) aria-current="page" @endif>
                    <x-icono nombre="calendar-outline" /> Fixture
                </a>
                <a href="{{ route('equipos') }}" @if (request()->routeIs('equipos*')) aria-current="page" @endif>
                    <x-icono nombre="people-outline" /> Equipos
                </a>
            </nav>

            <div class="lateral__pie">
                @auth
                    <div class="usuario-sesion">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span class="tenue">{{ auth()->user()->role?->label() }}</span>
                        <span class="tenue usuario-sesion__email">{{ auth()->user()->email }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="boton boton--secundario boton--compacto boton--ancho">
                            Cerrar sesión
                        </button>
                    </form>
                @else
                    <div class="usuario-sesion">
                        <strong>Modo Demo Abierto</strong>
                        <span class="tenue">Vista sin restricción de rol</span>
                    </div>
                    <a href="{{ route('login') }}" class="boton boton--primario boton--compacto boton--ancho">
                        Iniciar sesión
                    </a>
                @endauth
            </div>
        </aside>

        <main id="contenido" class="contenido">
            @if (session('ok'))
                <div class="aviso aviso--ok aviso--flash" role="status">
                    <x-icono nombre="checkmark-circle-outline" />
                    <p><strong>{{ session('ok') }}</strong></p>
                </div>
            @endif

            @if (session('error'))
                <div class="aviso aviso--error aviso--flash" role="alert">
                    <x-icono nombre="alert-circle-outline" />
                    <p><strong>{{ session('error') }}</strong></p>
                </div>
            @endif

            @if ($errors->any())
                <div class="aviso aviso--error aviso--flash" role="alert">
                    <x-icono nombre="alert-circle-outline" />
                    <p><strong>Revisa los datos enviados:</strong> {{ $errors->first() }}</p>
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>
</body>
</html>
