<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('titulo') · Playbook</title>
    <link rel="preload" href="{{ asset('fonts/inter/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/space-grotesk/space-grotesk-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/playbook-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}?v={{ filemtime(public_path('css/panel.css')) }}">
</head>
<body>
    <a class="saltar" href="#contenido">Ir al contenido</a>

    <div class="marco">
        <aside class="lateral">
            <header class="lateral__cabecera">
                <a class="marca" href="{{ route('fixture') }}">
                    <img src="{{ asset('images/playbook-mark.svg') }}" width="32" height="32" alt="">
                    <span>Play<span class="marca__ligera">book</span></span>
                </a>
            </header>

            <nav class="nav" aria-label="Navegación principal">
                <a href="{{ route('fixture') }}" @if (request()->routeIs('fixture')) aria-current="page" @endif>
                    <x-icono nombre="calendar-outline" />
                    <span>Fixture</span>
                </a>
                <a href="{{ route('equipos') }}" @if (request()->routeIs('equipos*')) aria-current="page" @endif>
                    <x-icono nombre="people-outline" />
                    <span>Equipos</span>
                </a>
            </nav>

            <div class="lateral__pie">
                @auth
                    <div class="usuario-sesion usuario-sesion--card">
                        <div class="usuario-sesion__encabezado">
                            <x-avatar :foto="auth()->user()->avatar_url" :nombre="auth()->user()->name" tamano="40" />
                            <div class="usuario-sesion__datos">
                                <strong class="usuario-sesion__nombre" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</strong>
                                <div class="usuario-sesion__rol">
                                    <x-estado :valor="auth()->user()->role ?? 'Usuario'" />
                                </div>
                            </div>
                        </div>
                        <span class="tenue usuario-sesion__email" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="usuario-sesion__form">
                            @csrf
                            <button type="submit" class="boton boton--secundario boton--compacto boton--ancho">
                                <x-icono nombre="log-out-outline" />
                                <span>Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="usuario-sesion usuario-sesion--card">
                        <div class="usuario-sesion__encabezado">
                            <x-avatar nombre="Modo de prueba" tamano="40" />
                            <div class="usuario-sesion__datos">
                                <strong class="usuario-sesion__nombre">Modo de prueba</strong>
                                <span class="tenue usuario-sesion__subtexto">Acceso libre sin restricción</span>
                            </div>
                        </div>
                        <a href="{{ route('login') }}" class="boton boton--primario boton--compacto boton--ancho">
                            <x-icono nombre="log-in-outline" />
                            <span>Iniciar sesión</span>
                        </a>
                    </div>
                @endauth
            </div>
        </aside>

        <main id="contenido" class="contenido">
            @if (session('ok'))
                <div class="aviso aviso--ok aviso--flash" role="status">
                    <x-icono nombre="checkmark-circle-outline" />
                    <div class="aviso__cuerpo">
                        <p>{{ is_string(session('ok')) ? session('ok') : 'Cambios guardados correctamente.' }}</p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="aviso aviso--error aviso--flash" role="alert">
                    <x-icono nombre="alert-circle-outline" />
                    <div class="aviso__cuerpo">
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="aviso aviso--error aviso--flash" role="alert">
                    <x-icono nombre="alert-circle-outline" />
                    <div class="aviso__cuerpo">
                        <p><strong>Revisa la información ingresada:</strong> {{ $errors->first() }}</p>
                    </div>
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>
</body>
</html>
