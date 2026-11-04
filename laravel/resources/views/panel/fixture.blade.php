@extends('layouts.panel')

@section('titulo', 'Fixture')

@section('contenido')
    <header class="cabecera cabecera--acciones">
        <div>
            <h1>Fixture</h1>
            @if ($grupos->isNotEmpty())
                <p>
                    {{ trans_choice(':count partido|:count partidos', $grupos->sum->count()) }}
                    @if ($tentativos > 0) · {{ $tentativos }} por confirmar @endif
                </p>
            @endif
        </div>

        @if ($ligaActiva)
            <div class="barra-acciones">
                <form method="GET" action="{{ route('fixture') }}" class="form-en-linea">
                    <label for="selector-liga" class="sr-only">Liga a validar</label>
                    <select id="selector-liga" name="liga_id" class="control" onchange="this.form.submit()">
                        @foreach ($ligas as $l)
                            <option value="{{ $l->id }}" @selected($l->id === $ligaActiva->id)>
                                {{ $l->nombre }}
                            </option>
                        @endforeach
                    </select>
                </form>

                @if ($puedeGestionarLiga ?? true)
                    <form method="POST" action="{{ route('ligas.revalidar', $ligaActiva) }}">
                        @csrf
                        <button type="submit" class="boton boton--secundario">Revalidar</button>
                    </form>

                    <form method="POST" action="{{ route('ligas.publicar', $ligaActiva) }}">
                        @csrf
                        <button
                            type="submit"
                            class="boton boton--primario"
                            @disabled(! ($reporteActivo['puede_publicarse'] ?? false))
                            title="{{ ($reporteActivo['puede_publicarse'] ?? false) ? 'Publicar todos los partidos de la liga' : 'Resuelve primero las violaciones duras para poder publicar' }}"
                        >
                            Publicar Fixture
                        </button>
                    </form>
                @else
                    <span class="estado estado--neutro">Modo consulta ({{ auth()->user()?->role?->label() }})</span>
                @endif
            </div>
        @endif
    </header>

    {{-- =======================================================================
         PASO 1: PANEL DE VALIDACIÓN AUTOMÁTICA Y KPIS DE LA LIGA ACTIVA
         ======================================================================= --}}
    @if ($ligaActiva && $reporteActivo)
        <section class="bloque panel-validacion" aria-labelledby="titulo-validacion">
            <div class="bloque__titulo bloque__titulo--entre">
                <div>
                    <h2 id="titulo-validacion">Panel de Validación Automática — {{ $ligaActiva->nombre }}</h2>
                    <span>Temporada {{ $ligaActiva->temporada }} · {{ $ligaActiva->disciplina?->nombre }}</span>
                </div>
                @if ($reporteActivo['violaciones_duras_count'] > 0)
                    <span class="estado estado--error">
                        {{ $reporteActivo['violaciones_duras_count'] }} {{ trans_choice('violación dura|violaciones duras', $reporteActivo['violaciones_duras_count']) }}
                    </span>
                @else
                    <span class="estado estado--ok">Sin violaciones duras · Listo para publicar</span>
                @endif
            </div>

            {{-- Tarjetas de KPIs (Equilibrio de localía, Descansos mínimos y Penalización total) --}}
            <div class="kpis">
                <article class="kpi">
                    <span class="kpi__titulo">EQUILIBRIO DE LOCALÍA</span>
                    <strong class="kpi__valor">{{ $reporteActivo['metricas']['equilibrio_localia_pct'] }}%</strong>
                    <div class="kpi__barra">
                        <div class="kpi__relleno" style="width: {{ min(100, $reporteActivo['metricas']['equilibrio_localia_pct']) }}%"></div>
                    </div>
                    <span class="kpi__meta">Meta: &gt;90%</span>
                </article>

                <article class="kpi">
                    <span class="kpi__titulo">DESCANSOS MÍNIMOS (&ge;{{ $ligaActiva->descanso_minimo_horas }}H)</span>
                    <strong class="kpi__valor">{{ $reporteActivo['metricas']['descansos_minimos_pct'] }}%</strong>
                    <div class="kpi__barra">
                        <div class="kpi__relleno" style="width: {{ min(100, $reporteActivo['metricas']['descansos_minimos_pct']) }}%"></div>
                    </div>
                    <span class="kpi__meta">Meta: &gt;90%</span>
                </article>

                <article class="kpi">
                    <span class="kpi__titulo">PENALIZACIÓN TOTAL</span>
                    <div class="kpi__fila">
                        <strong class="kpi__valor">{{ $reporteActivo['penalizacion_total'] }} pts</strong>
                        <span class="estado {{ $reporteActivo['dentro_de_tolerancia'] ? 'estado--ok' : 'estado--aviso' }}">
                            Tolerancia máx: {{ $reporteActivo['tolerancia_penalizacion_max'] }} pts
                        </span>
                    </div>
                    <span class="kpi__meta">
                        {{ $reporteActivo['violaciones_duras_count'] }} duras · {{ $reporteActivo['advertencias_blandas_count'] }} advertencias blandas
                    </span>
                </article>
            </div>

            {{-- Listado de Alertas Reglamentarias (Violaciones Duras y Advertencias Blandas) --}}
            @if ($reporteActivo['conflictos'] !== [])
                <div class="lista-alertas">
                    @foreach ($reporteActivo['conflictos'] as $alerta)
                        @if ($alerta['bloquea_publicacion'])
                            <div class="aviso aviso--error aviso--completo" role="alert">
                                <x-icono nombre="alert-circle-outline" />
                                <div class="aviso__cuerpo">
                                    <strong>Violación dura ({{ $alerta['codigo_regla'] }})</strong>
                                    <p>{{ $alerta['mensaje'] }}</p>
                                </div>
                            </div>
                        @else
                            <div class="aviso aviso--aviso aviso--completo" role="status">
                                <x-icono nombre="alert-circle-outline" />
                                <div class="aviso__cuerpo">
                                    <strong>Advertencia blanda ({{ $alerta['codigo_regla'] }})</strong>
                                    <p>{{ $alerta['mensaje'] }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="aviso aviso--ok aviso--completo" role="status">
                    <x-icono nombre="checkmark-circle-outline" />
                    <p><strong>No se detectaron conflictos reglamentarios en esta liga.</strong> Todos los partidos cumplen disponibilidad y descanso.</p>
                </div>
            @endif
        </section>
    @endif

    {{-- =======================================================================
         TABLAS DE PARTIDOS AGRUPADOS POR LIGA (CON ACCIÓN DE REPROGRAMACIÓN)
         ======================================================================= --}}
    @forelse ($grupos as $nombre => $partidos)
        @php $liga = $partidos->first()->liga; @endphp
        <section class="bloque" aria-labelledby="grupo-{{ $loop->index }}">
            <div class="bloque__titulo">
                <h2 id="grupo-{{ $loop->index }}">{{ $liga ? $nombre : 'Prácticas internas' }}</h2>
                @if ($liga)
                    <span>{{ $liga->disciplina?->nombre }} · Temporada {{ $liga->temporada }}</span>
                @endif
            </div>

            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Partido</th>
                            <th scope="col">Jornada</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Hora</th>
                            <th scope="col">Formato</th>
                            <th scope="col">Lugar</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($partidos as $partido)
                            {{-- Los partidos que ya pasaron van atenuados para que los próximos destaquen --}}
                            <tr @class(['pasado' => $partido->fecha_hora_inicio?->isPast()])>
                                <td class="fuerte">
                                    @if ($partido->equipoLocal)
                                        <a class="equipo" href="{{ route('equipos.show', $partido->equipoLocal) }}">{{ $partido->equipoLocal->nombre }}</a>
                                    @else
                                        Sin equipo
                                    @endif
                                    @if ($partido->equipoVisitante)
                                        <span class="vs">vs</span>
                                        <a class="equipo" href="{{ route('equipos.show', $partido->equipoVisitante) }}">{{ $partido->equipoVisitante->nombre }}</a>
                                    @endif
                                </td>
                                <td class="num" data-label="Jornada">{{ $partido->jornada_numero ?? '—' }}</td>
                                <td data-label="Fecha">{{ $partido->fecha_hora_inicio ? str_replace('.', '', $partido->fecha_hora_inicio->locale('es')->isoFormat('ddd D MMM')) : 'Sin fecha' }}</td>
                                <td class="num" data-label="Hora">{{ $partido->fecha_hora_inicio?->format('H:i') ?? '—' }}</td>
                                <td class="num" data-label="Formato">
                                    {{ strtoupper(($partido->formato_serie?->value) ?? 'bo1') }}
                                    <span class="tenue">({{ (int) $partido->fecha_hora_inicio?->diffInMinutes($partido->fecha_hora_fin) }}m)</span>
                                </td>
                                <td class="tenue" data-label="Lugar">{{ $partido->lugar ?? 'Por definir' }}</td>
                                <td data-label="Estado"><x-estado :valor="$partido->estado" /></td>
                                <td data-label="Acciones">
                                    <div class="barra-acciones">
                                        <a
                                            class="enlace-accion"
                                            href="{{ route('fixture', [
                                                'liga_id' => $partido->liga_id ?? $ligaActiva?->id,
                                                'reprogramar_id' => $partido->id,
                                                'semana' => $partido->fecha_hora_inicio?->toDateString() ?? '2026-11-03',
                                                'formato_serie' => $partido->formato_serie?->value ?? 'bo3',
                                            ]) }}#programador-partidos"
                                        >
                                            Reprogramar
                                        </a>

                                        @if ($puedeGestionarLiga ?? true)
                                            <form method="POST" action="{{ route('partidos.destroy', $partido) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="boton-texto boton-texto--peligro">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="vacio">
            <x-icono nombre="calendar-outline" />
            <p>Todavía no hay partidos cargados.</p>
        </div>
    @endforelse

    {{-- =======================================================================
         PASO 2: PROGRAMADOR Y GRILLA DE HORARIOS DISPONIBLES ENTRE 2 EQUIPOS
         ======================================================================= --}}
    <section id="programador-partidos" class="bloque panel-programador" aria-labelledby="titulo-programador">
        <div class="bloque__titulo bloque__titulo--entre">
            <div>
                <h2 id="titulo-programador">
                    @if ($partidoReprogramar)
                        Reprogramar Partido #{{ $partidoReprogramar->id }} ({{ $partidoReprogramar->equipoLocal?->nombre }} vs {{ $partidoReprogramar->equipoVisitante?->nombre }})
                    @else
                        Programador y Cruce de Horarios Disponibles (Admin de Liga + Coach)
                    @endif
                </h2>
                <span>
                    Evalúa titulares (mains) y suplentes disponibles + bloqueos recurrentes/unitarios · Serie {{ $formatoSeleccionado->label() }} = {{ $duracionCalculada }} min ({{ $formatoSeleccionado->maxPartidas() }} &times; {{ $minutosSeleccionados }}m)
                </span>
            </div>
            @if ($partidoReprogramar)
                <a href="{{ route('fixture', ['liga_id' => $ligaActiva?->id]) }}#programador-partidos" class="enlace-accion">
                    Cancelar modo reprogramación
                </a>
            @endif
        </div>

        {{-- Filtro de Cruce de Equipos y Duración de Serie (Bo1 / Bo3 / Bo5) --}}
        <form method="GET" action="{{ route('fixture') }}#programador-partidos" class="rejilla-form">
            @if ($ligaActiva)
                <input type="hidden" name="liga_id" value="{{ $ligaActiva->id }}">
            @endif
            @if ($partidoReprogramar)
                <input type="hidden" name="reprogramar_id" value="{{ $partidoReprogramar->id }}">
            @endif

            <div class="campo">
                <label for="local_id">Equipo Local</label>
                <select id="local_id" name="local_id" class="control" @disabled($partidoReprogramar !== null)>
                    @foreach ($equipos as $eq)
                        <option value="{{ $eq->id }}" @selected($equipoLocalSeleccionado && $eq->id === $equipoLocalSeleccionado->id)>
                            {{ $eq->nombre }} ({{ $eq->disciplina?->nombre }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="visitante_id">Equipo Visitante</label>
                <select id="visitante_id" name="visitante_id" class="control" @disabled($partidoReprogramar !== null)>
                    @foreach ($equipos as $eq)
                        <option value="{{ $eq->id }}" @selected($equipoVisitanteSeleccionado && $eq->id === $equipoVisitanteSeleccionado->id)>
                            {{ $eq->nombre }} ({{ $eq->disciplina?->nombre }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="semana">Semana a evaluar</label>
                <input id="semana" type="date" name="semana" value="{{ $semanaSeleccionada }}" class="control">
            </div>

            <div class="campo">
                <label for="formato_serie">Formato de Serie</label>
                <select id="formato_serie" name="formato_serie" class="control">
                    @foreach (\App\Enums\FormatoSerieEnum::cases() as $fmt)
                        <option value="{{ $fmt->value }}" @selected($fmt === $formatoSeleccionado)>
                            {{ $fmt->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="minutos_por_partida">Minutos / Partida</label>
                <input id="minutos_por_partida" type="number" name="minutos_por_partida" min="15" max="120" value="{{ $minutosSeleccionados }}" class="control">
            </div>

            <div class="campo campo--check">
                <label class="check-label">
                    <input type="checkbox" name="solo_disponibles" value="1" @checked($soloDisponibles)>
                    Mostrar solo horarios disponibles
                </label>
                <button type="submit" class="boton boton--secundario">Evaluar Horarios</button>
            </div>
        </form>

        {{-- Tabla / Grilla de Ventanas Sugeridas con Botón de Aprobación / Agendamiento Directo --}}
        @if ($ventanasSugeridas->isNotEmpty())
            <div class="tabla tabla--espaciada">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Ventana ({{ $duracionCalculada }} min)</th>
                            <th scope="col">Disponibilidad (Mains + Suplentes)</th>
                            <th scope="col">Diagnóstico del Motor</th>
                            <th scope="col">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ventanasSugeridas as $ventana)
                            <tr>
                                <td class="fuerte" data-label="Fecha">{{ $ventana->fecha }}</td>
                                <td class="num" data-label="Ventana">{{ $ventana->horaInicio }} – {{ $ventana->horaFin }}</td>
                                <td data-label="Disponibilidad">
                                    @if ($ventana->disponible && $ventana->conflictos->isEmpty())
                                        <span class="estado estado--ok">Disponible Óptimo</span>
                                    @elseif ($ventana->disponible)
                                        <span class="estado estado--aviso">Disponible (Suplente / Descanso)</span>
                                    @else
                                        <span class="estado estado--error">Bloqueado</span>
                                    @endif
                                </td>
                                <td class="tenue" data-label="Diagnóstico">
                                    @if ($ventana->conflictos->isEmpty())
                                        Titulares y suplentes habilitados sin topes horarios.
                                    @else
                                        {{ $ventana->conflictos->first()->mensaje }}
                                    @endif
                                </td>
                                <td data-label="Acción">
                                    @if ($partidoReprogramar)
                                        <form method="POST" action="{{ route('partidos.reprogramar', $partidoReprogramar) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="fecha_hora_inicio" value="{{ $ventana->fecha }} {{ $ventana->horaInicio }}:00">
                                            <input type="hidden" name="formato_serie" value="{{ $formatoSeleccionado->value }}">
                                            <input type="hidden" name="minutos_por_partida" value="{{ $minutosSeleccionados }}">
                                            <button type="submit" class="boton {{ $ventana->disponible ? 'boton--primario' : 'boton--secundario' }}">
                                                Mover a este bloque
                                            </button>
                                        </form>
                                    @elseif ($equipoLocalSeleccionado && $equipoVisitanteSeleccionado)
                                        <form method="POST" action="{{ route('partidos.store') }}">
                                            @csrf
                                            @if ($ligaActiva)
                                                <input type="hidden" name="liga_id" value="{{ $ligaActiva->id }}">
                                            @endif
                                            <input type="hidden" name="equipo_local_id" value="{{ $equipoLocalSeleccionado->id }}">
                                            <input type="hidden" name="equipo_visitante_id" value="{{ $equipoVisitanteSeleccionado->id }}">
                                            <input type="hidden" name="fecha_hora_inicio" value="{{ $ventana->fecha }} {{ $ventana->horaInicio }}:00">
                                            <input type="hidden" name="formato_serie" value="{{ $formatoSeleccionado->value }}">
                                            <input type="hidden" name="minutos_por_partida" value="{{ $minutosSeleccionados }}">
                                            <input type="hidden" name="jornada_numero" value="5">
                                            <input type="hidden" name="lugar" value="Servidor LAS-1">
                                            <button type="submit" class="boton {{ $ventana->disponible ? 'boton--primario' : 'boton--secundario' }}">
                                                Agendar aquí
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
