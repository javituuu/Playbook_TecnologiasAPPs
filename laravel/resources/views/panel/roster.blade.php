@extends('layouts.panel')

@section('titulo', $equipo->nombre)

@section('contenido')
    <a class="volver" href="{{ route('equipos') }}"><x-icono nombre="arrow-back-outline" /> Equipos</a>

    <header class="cabecera">
        <h1>{{ $equipo->nombre }}</h1>
        <p>
            {{ $equipo->club?->nombreConSiglas() }} · {{ $equipo->disciplina?->nombre ?? 'Sin disciplina' }}
            @if ($equipo->coach) · a cargo de {{ $equipo->coach->name }} @endif
        </p>
    </header>

    @if ($faltantes !== [])
        <div class="aviso aviso--error" role="status">
            <x-icono nombre="alert-circle-outline" />
            <p>
                <strong>Faltan titulares en {{ \Illuminate\Support\Arr::join($faltantes, ', ', ' y ') }}.</strong>
                Sin ellos no puede jugar partidos oficiales.
            </p>
        </div>
    @elseif ($posiciones !== [])
        <div class="aviso aviso--ok" role="status">
            <x-icono nombre="checkmark-circle-outline" />
            <p><strong>Roster completo.</strong></p>
        </div>
    @endif

    @if ($posiciones !== [])
        <section class="bloque" aria-labelledby="titulares">
            <div class="bloque__titulo"><h2 id="titulares">Titulares</h2></div>
            <ul class="posiciones">
                @foreach ($posiciones as $posicion)
                    @php
                        $titular = $titulares->get(strtoupper($posicion));
                        $candidatos = $titular ? collect() : $resto->filter(fn ($m) => $m->estado_invitacion !== \App\Enums\EstadoInvitacionEnum::RECHAZADA
                            && $m->puedeCubrirPosicion($posicion));
                    @endphp
                    <li class="posicion {{ $titular ? '' : 'posicion--libre' }}">
                        <span class="posicion__rol">{{ $posicion }}</span>
                        @if ($titular)
                            <strong>{{ $titular->jugador?->name }}</strong>
                            <span class="tenue">{{ $titular->rango_declarativo ?? 'Sin rango' }}</span>
                        @else
                            <strong>Sin titular</strong>
                            @if ($candidatos->isNotEmpty())
                                <span class="tenue">Puede cubrir: {{ $candidatos->map(fn ($m) => $m->jugador?->name)->filter()->join(', ') }}</span>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="bloque" aria-labelledby="plantel">
        <div class="bloque__titulo"><h2 id="plantel">Suplentes, en prueba e invitaciones</h2></div>

        @if ($resto->isEmpty())
            <div class="vacio">
                <x-icono nombre="people-outline" />
                <p>Sin suplentes ni invitaciones pendientes.</p>
            </div>
        @else
            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Jugador</th>
                            <th scope="col">Posición</th>
                            <th scope="col">También juega</th>
                            <th scope="col">Rango</th>
                            <th scope="col">Rol</th>
                            <th scope="col">Invitación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resto as $membresia)
                            <tr>
                                <td class="fuerte">{{ $membresia->jugador?->name ?? 'Jugador eliminado' }}</td>
                                <td data-label="Posición">{{ $membresia->posicion_principal ?? '—' }}</td>
                                <td class="tenue" data-label="También juega">{{ $membresia->posicion_secundaria ?? '—' }}</td>
                                <td data-label="Rango">{{ $membresia->rango_declarativo ?? '—' }}</td>
                                <td data-label="Rol"><x-estado :valor="$membresia->rol_membresia" /></td>
                                <td data-label="Invitación"><x-estado :valor="$membresia->estado_invitacion" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
