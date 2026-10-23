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
        </aside>

        <main id="contenido" class="contenido">
            @yield('contenido')
        </main>
    </div>
</body>
</html>
