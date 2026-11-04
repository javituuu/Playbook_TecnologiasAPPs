@extends('layouts.panel')

@section('titulo', 'Equipos')

@section('contenido')
    <header class="cabecera">
        <h1>Equipos</h1>
        @if ($equipos->isNotEmpty())
            @php $incompletos = collect($faltantes)->filter()->count(); @endphp
            <p>
                {{ trans_choice(':count equipo|:count equipos', $equipos->count()) }}
                @if ($incompletos > 0) · {{ $incompletos }} con titulares pendientes @endif
            </p>
        @endif
    </header>

    {{-- Buscador y Filtros de Equipos por Club y Disciplina (Acta: Tablas con filtros y búsqueda) --}}
    <section class="bloque" aria-label="Filtros y búsqueda de equipos">
        <form method="GET" action="{{ route('equipos') }}" class="rejilla-form">
            <div class="campo">
                <label for="filtro-q">Buscar equipo o club</label>
                <input
                    id="filtro-q"
                    type="search"
                    name="q"
                    value="{{ $filtroBuscar ?? '' }}"
                    placeholder="Ej. Titans, UDD, Sharks..."
                    class="control"
                >
            </div>

            <div class="campo">
                <label for="filtro-club">Club Universitario</label>
                <select id="filtro-club" name="club_id" class="control">
                    <option value="">Todos los clubes</option>
                    @foreach ($clubes ?? [] as $club)
                        <option value="{{ $club->id }}" @selected(($filtroClubId ?? null) === $club->id)>
                            {{ $club->nombreConSiglas() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="filtro-disciplina">Disciplina</label>
                <select id="filtro-disciplina" name="disciplina_id" class="control">
                    <option value="">Todas las disciplinas</option>
                    @foreach ($disciplinas ?? [] as $disc)
                        <option value="{{ $disc->id }}" @selected(($filtroDisciplinaId ?? null) === $disc->id)>
                            {{ $disc->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo campo--check">
                <div class="barra-acciones">
                    <button type="submit" class="boton boton--secundario">Filtrar</button>
                    @if (($filtroBuscar ?? '') !== '' || ($filtroClubId ?? null) !== null || ($filtroDisciplinaId ?? null) !== null)
                        <a href="{{ route('equipos') }}" class="enlace-accion">Limpiar</a>
                    @endif
                </div>
            </div>
        </form>
    </section>

    @if ($equipos->isEmpty())
        <div class="vacio">
            <x-icono nombre="people-outline" />
            <p>No se encontraron equipos con los filtros seleccionados.</p>
        </div>
    @else
        <div class="tabla tabla--espaciada">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Equipo</th>
                        <th scope="col">Club</th>
                        <th scope="col">Disciplina</th>
                        <th scope="col">Titulares</th>
                        <th scope="col">Plantel</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equipos as $equipo)
                        @php
                            $requeridos = $equipo->disciplina?->titulares_requeridos ?? 5;
                            $falta = $faltantes[$equipo->id] ?? [];
                        @endphp
                        <tr class="enlazada">
                            <td class="fuerte">
                                <a href="{{ route('equipos.show', $equipo) }}">{{ $equipo->nombre }}</a>
                                @unless ($equipo->activo)
                                    <x-estado valor="inactivo" />
                                @endunless
                            </td>
                            <td class="tenue" data-label="Club">{{ $equipo->club?->siglas ?? '—' }}</td>
                            <td data-label="Disciplina">{{ $equipo->disciplina?->nombre ?? '—' }}</td>
                            <td data-label="Titulares">
                                <span @class(['num', 'falta' => $falta !== []])>
                                    {{ $equipo->titulares_count }}/{{ $requeridos }}@if ($falta !== []) · falta {{ \Illuminate\Support\Arr::join($falta, ', ', ' y ') }}@endif
                                </span>
                            </td>
                            <td class="num" data-label="Plantel">{{ $equipo->membresias_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Formulario de Registro de Nuevo Equipo Competitivo (RF.07) --}}
    <section id="registrar-equipo" class="bloque" aria-labelledby="titulo-nuevo-equipo">
        <div class="bloque__titulo">
            <h2 id="titulo-nuevo-equipo">Registrar Nuevo Equipo (RF.07)</h2>
            <span>Crea una nueva escuadra universitaria para un club y disciplina</span>
        </div>

        <form method="POST" action="{{ route('equipos.store') }}" class="rejilla-form">
            @csrf

            <div class="campo">
                <label for="nombre-equipo">Nombre del Equipo</label>
                <input
                    id="nombre-equipo"
                    type="text"
                    name="nombre"
                    required
                    placeholder="Ej. UDD Valkyries"
                    class="control"
                >
            </div>

            <div class="campo">
                <label for="nuevo-club-id">Club Universitario</label>
                <select id="nuevo-club-id" name="club_id" required class="control">
                    @foreach ($clubes ?? [] as $club)
                        <option value="{{ $club->id }}">{{ $club->nombreConSiglas() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="nueva-disciplina-id">Disciplina</label>
                <select id="nueva-disciplina-id" name="disciplina_id" required class="control">
                    @foreach ($disciplinas ?? [] as $disc)
                        <option value="{{ $disc->id }}">{{ $disc->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="nuevo-coach-id">Coach Asignado (Opcional)</label>
                <select id="nuevo-coach-id" name="coach_user_id" class="control">
                    <option value="">Sin coach asignado</option>
                    @foreach ($coaches ?? [] as $coach)
                        <option value="{{ $coach->id }}">{{ $coach->name }} ({{ $coach->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="campo campo--check">
                <button type="submit" class="boton boton--primario">+ Crear Equipo</button>
            </div>
        </form>
    </section>
@endsection
