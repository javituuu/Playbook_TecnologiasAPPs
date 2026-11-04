@extends('layouts.panel')

@section('titulo', 'Equipos')

@section('contenido')
    <header class="cabecera cabecera--equipos">
        <h1>Equipos</h1>
        @if ($equipos->isNotEmpty())
            @php $incompletos = collect($faltantes)->filter()->count(); @endphp
            <p>
                {{ $equipos->count() }} {{ trans_choice('equipo registrado|equipos registrados', $equipos->count()) }} ·
                @if ($incompletos > 0)
                    {{ $incompletos }} {{ trans_choice('con titulares pendientes|con titulares pendientes', $incompletos) }}
                @else
                    todos con titulares completos
                @endif
            </p>
        @else
            <p>0 equipos registrados</p>
        @endif
    </header>

    {{-- Buscador y filtros de equipos por club y disciplina --}}
    <section class="bloque" aria-label="Filtros y búsqueda de equipos">
        <form method="GET" action="{{ route('equipos') }}" class="rejilla-form">
            <div class="campo">
                <label for="filtro-q">Buscar</label>
                <div class="campo-con-icono">
                    <x-icono nombre="search-outline" class="campo-con-icono__icono" />
                    <input
                        id="filtro-q"
                        type="search"
                        name="q"
                        value="{{ $filtroBuscar ?? '' }}"
                        placeholder="Buscar por nombre o club..."
                        class="control control--con-icono"
                    >
                </div>
            </div>

            <div class="campo">
                <label for="filtro-club">Club</label>
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
                    <button type="submit" class="boton boton--secundario">
                        <x-icono nombre="filter-outline" />
                        <span>Filtrar</span>
                    </button>
                    @if (($filtroBuscar ?? '') !== '' || ($filtroClubId ?? null) !== null || ($filtroDisciplinaId ?? null) !== null)
                        <a href="{{ route('equipos') }}" class="enlace-accion enlace-accion--icono" title="Restablecer filtros">
                            <x-icono nombre="refresh-outline" />
                            <span>Limpiar</span>
                        </a>
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
                            $completo = $falta === [] && $equipo->titulares_count >= $requeridos;
                        @endphp
                        <tr class="enlazada">
                            <td class="fuerte">
                                <div class="equipo-nombre-celda">
                                    <span class="avatar avatar--sm avatar-equipo" aria-hidden="true">
                                        <x-icono nombre="shield-outline" />
                                    </span>
                                    <div class="equipo-nombre-info">
                                        <a href="{{ route('equipos.show', $equipo) }}" class="equipo-enlace">{{ $equipo->nombre }}</a>
                                        @unless ($equipo->activo)
                                            <x-estado valor="inactivo" />
                                        @endunless
                                    </div>
                                </div>
                            </td>
                            <td data-label="Club">
                                @if ($equipo->club?->siglas)
                                    <span class="badge-club" title="{{ $equipo->club->nombre }}">{{ $equipo->club->siglas }}</span>
                                @else
                                    <span class="tenue">—</span>
                                @endif
                            </td>
                            <td data-label="Disciplina">
                                @if ($equipo->disciplina)
                                    <span class="badge-disciplina">
                                        <x-icono nombre="trophy-outline" class="icono-chico" />
                                        <span>{{ $equipo->disciplina->nombre }}</span>
                                    </span>
                                @else
                                    <span class="tenue">—</span>
                                @endif
                            </td>
                            <td data-label="Titulares">
                                <span @class([
                                    'pildora-titulares',
                                    'font-grotesk',
                                    'pildora-titulares--completo' => $completo,
                                    'pildora-titulares--incompleto' => ! $completo,
                                ])>
                                    <span class="pildora-titulares__conteo">{{ $equipo->titulares_count }}/{{ $requeridos }}</span>
                                    @if ($falta !== [])
                                        <span class="pildora-titulares__detalle">· falta {{ \Illuminate\Support\Arr::join($falta, ', ', ' y ') }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="num font-grotesk" data-label="Plantel">
                                <span class="plantel-conteo" title="{{ $equipo->membresias_count }} miembros en plantilla">
                                    <x-icono nombre="people-outline" class="icono-chico" />
                                    <span>{{ $equipo->membresias_count }}</span>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Formulario de registro de nuevo equipo --}}
    <section id="registrar-equipo" class="bloque" aria-labelledby="titulo-nuevo-equipo">
        <div class="bloque__titulo">
            <h2 id="titulo-nuevo-equipo">Registrar nuevo equipo</h2>
            <span>Crea un equipo para un club y disciplina específica</span>
        </div>

        <form method="POST" action="{{ route('equipos.store') }}" class="rejilla-form">
            @csrf

            <div class="campo">
                <label for="nombre-equipo">Nombre del equipo</label>
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
                <label for="nuevo-club-id">Club</label>
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
                <label for="nuevo-coach-id">Coach (opcional)</label>
                <select id="nuevo-coach-id" name="coach_user_id" class="control">
                    <option value="">Sin coach asignado</option>
                    @foreach ($coaches ?? [] as $coach)
                        <option value="{{ $coach->id }}">{{ $coach->name }} ({{ $coach->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="campo campo--check">
                <button type="submit" class="boton boton--primario">
                    <x-icono nombre="add-outline" />
                    <span>Crear equipo</span>
                </button>
            </div>
        </form>
    </section>
@endsection
