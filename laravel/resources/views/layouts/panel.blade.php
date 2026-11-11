<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('titulo') · Playbook</title>
    <link rel="preload" href="{{ asset('fonts/inter/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/space-grotesk/space-grotesk-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}?v={{ filemtime(public_path('css/panel.css')) }}">
</head>
<body>
    <a class="saltar" href="#contenido">Ir al contenido</a>

    <div class="marco">
        <aside class="lateral">
            <header class="lateral__cabecera">
                <a class="marca" href="{{ route('fixture') }}">
                    <span>Playbook</span>
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

            <div class="lateral__feria">
                <span class="nav__eyebrow">Modo Feria</span>

                <button type="button" class="boton boton--secundario boton--compacto boton--ancho" onclick="document.getElementById('modal-qr-feria').showModal()">
                    <x-icono nombre="qr-code-outline" />
                    <span>QR App Jugador</span>
                </button>

                <form method="POST" action="{{ route('demo.reset') }}">
                    @csrf
                    <button type="submit" class="boton boton--secundario boton--compacto boton--ancho" title="Restaura partidos, rosters y citaciones al estado inicial en milisegundos">
                        <x-icono nombre="refresh-outline" />
                        <span>Reiniciar Demo</span>
                    </button>
                </form>
            </div>

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

    @php
        $urlJugador = rtrim(config('app.public_url') ?: request()->getSchemeAndHttpHost(), '/') . '/jugador/';
    @endphp
    <dialog id="modal-qr-feria" class="modal-qr" aria-labelledby="titulo-modal-qr">
        <div class="modal-qr__tarjeta">
            <header class="modal-qr__cabecera">
                <div>
                    <span class="nav__eyebrow" style="padding: 0;">Feria de Ciencias</span>
                    <h2 id="titulo-modal-qr">Vista Web del Jugador</h2>
                </div>
                <button type="button" class="boton boton--secundario boton--compacto" onclick="document.getElementById('modal-qr-feria').close()">
                    Cerrar
                </button>
            </header>

            <p class="tenue modal-qr__subtexto">
                Escanea el código QR con tu celular para abrir la aplicación del jugador (agenda, citaciones y bloqueos horarios) conectada en vivo a este panel.
            </p>

            <div class="modal-qr__lienzo">
                <img
                    id="img-qr-jugador"
                    src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=10&data={{ urlencode($urlJugador) }}"
                    alt="Código QR hacia {{ $urlJugador }}"
                    width="240"
                    height="240"
                >
            </div>

            <div class="campo">
                <label for="input-url-jugador" class="label-bold">Enlace público (Cloudflare Tunnel / Red local)</label>
                <input
                    id="input-url-jugador"
                    type="url"
                    class="control font-grotesk"
                    value="{{ $urlJugador }}"
                    oninput="const v = this.value.trim(); if (v) { document.getElementById('img-qr-jugador').src = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=10&data=' + encodeURIComponent(v); document.getElementById('link-abrir-jugador').href = v; }"
                >
                <span class="tenue" style="font-size: 12px;">
                    Cuenta demo: <code>javier@email.com</code> · Clave: <code>password</code> (o botón de 1 clic en el login)
                </span>
            </div>

            <div class="modal-qr__acciones">
                <a id="link-abrir-jugador" href="{{ $urlJugador }}" target="_blank" rel="noopener" class="boton boton--primario boton--ancho">
                    <x-icono nombre="phone-portrait-outline" />
                    <span>Abrir App del Jugador en nueva pestaña</span>
                </a>
            </div>
        </div>
    </dialog>
</body>
</html>
