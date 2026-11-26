@extends('layouts.panel')

@section('titulo', 'Fixture')

@section('contenido')
    <header class="cabecera cabecera--acciones">
        <div>
            <h1>Fixture</h1>
            @if ($grupos->isNotEmpty())
                <p>
                    {{ trans_choice(':count partido|:count partidos', $grupos->sum->count()) }}
                    @if ($tentativos > 0)
                        · {{ $tentativos }} por confirmar
                    @endif
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
                        <button type="submit" class="boton boton--secundario">
                            <x-icono nombre="refresh-outline" /> Revalidar
                        </button>
                    </form>

                    <form method="POST" action="{{ route('ligas.publicar', $ligaActiva) }}">
                        @csrf
                        <button
                            type="submit"
                            class="boton boton--primario"
                            @disabled(! ($reporteActivo['puede_publicarse'] ?? false))
                            title="{{ ($reporteActivo['puede_publicarse'] ?? false) ? 'Publicar todos los partidos de la liga' : 'Resuelve primero los conflictos para poder publicar' }}"
                        >
                            <x-icono nombre="checkmark-outline" /> Publicar fixture
                        </button>
                    </form>
                @else
                    <span class="estado estado--neutro">Modo consulta ({{ auth()->user()?->role?->label() }})</span>
                @endif
            </div>
        @endif
    </header>

    @if ($ligaActiva && $reporteActivo)
        <section class="bloque panel-validacion" aria-labelledby="titulo-validacion">
            <div class="bloque__titulo bloque__titulo--entre">
                <div>
                    <h2 id="titulo-validacion">Validación del fixture — {{ $ligaActiva->nombre }}</h2>
                    <span class="tenue">{{ $ligaActiva->disciplina?->nombre }} · Temporada {{ $ligaActiva->temporada }}</span>
                </div>
                @if ($reporteActivo['violaciones_duras_count'] > 0)
                    <span class="estado estado--error">
                        <span class="estado__dot" aria-hidden="true"></span>
                        {{ $reporteActivo['violaciones_duras_count'] }} {{ trans_choice('conflicto que impide publicar|conflictos que impiden publicar', $reporteActivo['violaciones_duras_count']) }}
                    </span>
                @else
                    <span class="estado estado--ok">
                        <span class="estado__dot" aria-hidden="true"></span>
                        Sin conflictos reglamentarios
                    </span>
                @endif
            </div>

            <div class="kpis">
                <article class="kpi">
                    <span class="kpi__titulo">Equilibrio local / visita</span>
                    <div class="kpi__fila">
                        <strong class="kpi__valor">{{ $reporteActivo['metricas']['equilibrio_localia_pct'] }}%</strong>
                        <span class="kpi__meta">Meta: &gt;90%</span>
                    </div>
                    <x-progreso :valor="$reporteActivo['metricas']['equilibrio_localia_pct']" color="acento" />
                </article>

                <article class="kpi">
                    <span class="kpi__titulo">Descanso mínimo</span>
                    <div class="kpi__fila">
                        <strong class="kpi__valor">{{ $reporteActivo['metricas']['descansos_minimos_pct'] }}%</strong>
                        <span class="kpi__meta">Meta: &gt;90% (&ge;{{ $ligaActiva->descanso_minimo_horas }}h)</span>
                    </div>
                    <x-progreso :valor="$reporteActivo['metricas']['descansos_minimos_pct']" color="acento" />
                </article>

                <article class="kpi">
                    <span class="kpi__titulo">Penalización</span>
                    <div class="kpi__fila">
                        <strong class="kpi__valor">{{ $reporteActivo['penalizacion_total'] }} pts</strong>
                        <span class="estado {{ $reporteActivo['dentro_de_tolerancia'] ? 'estado--ok' : 'estado--aviso' }}">
                            Tolerancia: {{ $reporteActivo['tolerancia_penalizacion_max'] }} pts
                        </span>
                    </div>
                    <span class="kpi__meta">
                        {{ $reporteActivo['violaciones_duras_count'] }} {{ trans_choice('conflicto crítico|conflictos críticos', $reporteActivo['violaciones_duras_count']) }} · {{ $reporteActivo['advertencias_blandas_count'] }} {{ trans_choice('advertencia|advertencias', $reporteActivo['advertencias_blandas_count']) }}
                    </span>
                </article>
            </div>

            @if ($reporteActivo['conflictos'] !== [])
                <div class="lista-alertas">
                    @foreach ($reporteActivo['conflictos'] as $alerta)
                        @if ($alerta['bloquea_publicacion'])
                            <div class="aviso aviso--error aviso--completo" role="alert">
                                <x-icono nombre="alert-circle-outline" />
                                <div class="aviso__cuerpo">
                                    <div class="aviso__cabecera">
                                        <strong>Conflicto · {{ \Illuminate\Support\Str::of($alerta['codigo_regla'])->replace('_', ' ')->lower() }}</strong>
                                        @if (isset($alerta['partido_id']))
                                            <a
                                                class="enlace-accion enlace-accion--icono"
                                                href="{{ route('fixture', [
                                                    'liga_id' => $ligaActiva->id,
                                                    'reprogramar_id' => $alerta['partido_id'],
                                                ]) }}#programador-partidos"
                                            >
                                                <x-icono nombre="swap-horizontal-outline" /> Reprogramar partido #{{ $alerta['partido_id'] }}
                                            </a>
                                        @endif
                                    </div>
                                    <p>{{ $alerta['mensaje'] }}</p>
                                </div>
                            </div>
                        @else
                            <div class="aviso aviso--aviso aviso--completo" role="status">
                                <x-icono nombre="alert-circle-outline" />
                                <div class="aviso__cuerpo">
                                    <div class="aviso__cabecera">
                                        <strong>Advertencia · {{ \Illuminate\Support\Str::of($alerta['codigo_regla'])->replace('_', ' ')->lower() }}</strong>
                                        @if (isset($alerta['partido_id']))
                                            <a
                                                class="enlace-accion enlace-accion--icono"
                                                href="{{ route('fixture', [
                                                    'liga_id' => $ligaActiva->id,
                                                    'reprogramar_id' => $alerta['partido_id'],
                                                ]) }}#programador-partidos"
                                            >
                                                <x-icono nombre="swap-horizontal-outline" /> Reprogramar partido #{{ $alerta['partido_id'] }}
                                            </a>
                                        @endif
                                    </div>
                                    <p>{{ $alerta['mensaje'] }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="aviso aviso--ok aviso--completo" role="status">
                    <x-icono nombre="checkmark-circle-outline" />
                    <div class="aviso__cuerpo">
                        <p><strong>Sin conflictos reglamentarios en esta liga.</strong> Todos los partidos programados cumplen disponibilidad y descanso reglamentario.</p>
                    </div>
                </div>
            @endif
        </section>
    @endif

    @forelse ($grupos as $nombre => $partidos)
        @php $liga = $partidos->first()->liga; @endphp
        <section class="bloque" aria-labelledby="grupo-{{ $loop->index }}">
            <div class="bloque__titulo bloque__titulo--entre">
                <div>
                    <h2 id="grupo-{{ $loop->index }}">{{ $liga ? $nombre : 'Prácticas internas' }}</h2>
                    @if ($liga)
                        <span class="tenue">{{ $liga->disciplina?->nombre }} · Temporada {{ $liga->temporada }}</span>
                    @endif
                </div>
                <span class="resumen-chip">
                    <strong>{{ $partidos->count() }}</strong> {{ trans_choice('partido|partidos', $partidos->count()) }}
                </span>
            </div>

            <div class="tabla tabla--espaciada">
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
                            <th scope="col" style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($partidos as $partido)
                            <tr @class(['pasado' => $partido->fecha_hora_inicio?->isPast()])>
                                <td class="fuerte" data-label="Partido">
                                    <div class="partido-enfrentamiento">
                                        @if ($partido->equipoLocal)
                                            <a class="equipo" href="{{ route('equipos.show', $partido->equipoLocal) }}">{{ $partido->equipoLocal->nombre }}</a>
                                        @else
                                            <span class="tenue">Por definir</span>
                                        @endif
                                        <span class="vs">vs</span>
                                        @if ($partido->equipoVisitante)
                                            <a class="equipo" href="{{ route('equipos.show', $partido->equipoVisitante) }}">{{ $partido->equipoVisitante->nombre }}</a>
                                        @else
                                            <span class="tenue">Por definir</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="num rango-grotesk" data-label="Jornada">{{ $partido->jornada_numero ?? '—' }}</td>
                                <td data-label="Fecha">{{ $partido->fecha_hora_inicio ? ucfirst(str_replace('.', '', $partido->fecha_hora_inicio->locale('es')->isoFormat('ddd D MMM'))) : 'Sin fecha' }}</td>
                                <td class="num rango-grotesk" data-label="Hora">{{ $partido->fecha_hora_inicio?->format('H:i') ?? '—' }}</td>
                                <td class="num" data-label="Formato">
                                    <span class="formato-badge">{{ strtoupper(($partido->formato_serie?->value) ?? 'bo1') }}</span>
                                    <span class="tenue">({{ (int) $partido->fecha_hora_inicio?->diffInMinutes($partido->fecha_hora_fin) }}m)</span>
                                </td>
                                <td class="tenue" data-label="Lugar">{{ $partido->lugar ?? 'Por definir' }}</td>
                                <td data-label="Estado"><x-estado :valor="$partido->estado" /></td>
                                <td data-label="Acciones">
                                    <div class="barra-acciones" style="justify-content: flex-end; gap: 8px;">
                                        <a
                                            class="enlace-accion enlace-accion--icono"
                                            href="{{ route('fixture', [
                                                'liga_id' => $partido->liga_id ?? $ligaActiva?->id,
                                                'reprogramar_id' => $partido->id,
                                                'semana' => $partido->fecha_hora_inicio?->toDateString() ?? '2026-11-03',
                                                'formato_serie' => $partido->formato_serie?->value ?? 'bo3',
                                            ]) }}#programador-partidos"
                                        >
                                            <x-icono nombre="swap-horizontal-outline" /> Reprogramar
                                        </a>

                                        @if ($puedeGestionarLiga ?? true)
                                            <form method="POST" action="{{ route('partidos.destroy', $partido) }}" style="display: inline-flex; margin: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="boton-texto boton-texto--peligro">
                                                    <x-icono nombre="trash-outline" /> Eliminar
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
            <img src="{{ asset('images/fixture-vacio.svg') }}" width="96" height="72" alt="">
            <p>Todavía no hay partidos cargados.</p>
        </div>
    @endforelse

    <section id="programador-partidos" class="bloque panel-programador" aria-labelledby="titulo-programador">
        <div class="bloque__titulo bloque__titulo--entre">
            <div>
                <h2 id="titulo-programador">
                    @if ($partidoReprogramar)
                        Reprogramar partido #{{ $partidoReprogramar->id }}
                    @else
                        Programar partido
                    @endif
                </h2>
                <span class="tenue">
                    @if ($partidoReprogramar)
                        {{ $partidoReprogramar->equipoLocal?->nombre }} vs {{ $partidoReprogramar->equipoVisitante?->nombre }} · Serie {{ $formatoSeleccionado->label() }} ({{ $duracionCalculada }} min)
                    @else
                        Cruce de disponibilidad entre titulares y suplentes · Serie {{ $formatoSeleccionado->label() }} ({{ $duracionCalculada }} min)
                    @endif
                </span>
            </div>
            @if ($partidoReprogramar)
                <a href="{{ route('fixture', ['liga_id' => $ligaActiva?->id]) }}#programador-partidos" class="enlace-accion enlace-accion--icono">
                    <x-icono nombre="close-circle-outline" /> Cancelar reprogramación
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('fixture') }}#programador-partidos" class="rejilla-form">
            @if ($ligaActiva)
                <input type="hidden" name="liga_id" value="{{ $ligaActiva->id }}">
            @endif
            @if ($partidoReprogramar)
                <input type="hidden" name="reprogramar_id" value="{{ $partidoReprogramar->id }}">
            @endif

            <div class="campo">
                <label for="local_id">Equipo local</label>
                <select id="local_id" name="local_id" class="control" @disabled($partidoReprogramar !== null)>
                    @foreach ($equipos as $eq)
                        <option value="{{ $eq->id }}" @selected($equipoLocalSeleccionado && $eq->id === $equipoLocalSeleccionado->id)>
                            {{ $eq->nombre }} ({{ $eq->disciplina?->nombre }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="visitante_id">Equipo visitante</label>
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
                <label for="formato_serie">Formato de serie</label>
                <select id="formato_serie" name="formato_serie" class="control">
                    @foreach (\App\Domain\Enums\FormatoSerieEnum::cases() as $fmt)
                        <option value="{{ $fmt->value }}" @selected($fmt === $formatoSeleccionado)>
                            {{ $fmt->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="minutos_por_partida">Minutos por partida</label>
                <input id="minutos_por_partida" type="number" name="minutos_por_partida" min="15" max="120" value="{{ $minutosSeleccionados }}" class="control">
            </div>

            <div class="campo campo--check">
                <label class="check-label">
                    <input type="checkbox" name="solo_disponibles" value="1" @checked($soloDisponibles)>
                    Solo disponibles
                </label>
                <button type="submit" class="boton boton--secundario" style="width: 100%;">
                    <x-icono nombre="search-outline" /> Evaluar horarios
                </button>
            </div>
        </form>

        @if ($ventanasSugeridas->isNotEmpty())
            <div class="tabla tabla--espaciada">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Horario ({{ $duracionCalculada }} min)</th>
                            <th scope="col">Disponibilidad</th>
                            <th scope="col">Diagnóstico</th>
                            <th scope="col" style="text-align: right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ventanasSugeridas as $ventana)
                            <tr>
                                <td class="fuerte" data-label="Fecha">{{ ucfirst(str_replace('.', '', \Carbon\Carbon::parse($ventana->fecha)->locale('es')->isoFormat('ddd D MMM'))) }}</td>
                                <td class="num rango-grotesk" data-label="Horario">{{ $ventana->horaInicio }} – {{ $ventana->horaFin }}</td>
                                <td data-label="Disponibilidad">
                                    @if ($ventana->disponible && $ventana->conflictos->isEmpty())
                                        <span class="estado estado--ok">
                                            <span class="estado__dot" aria-hidden="true"></span>
                                            Disponible
                                        </span>
                                    @elseif ($ventana->disponible)
                                        <span class="estado estado--aviso">
                                            <span class="estado__dot" aria-hidden="true"></span>
                                            Con suplente
                                        </span>
                                    @else
                                        <span class="estado estado--error">
                                            <span class="estado__dot" aria-hidden="true"></span>
                                            Bloqueado
                                        </span>
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
                                    <div style="display: flex; justify-content: flex-end;">
                                        @if ($partidoReprogramar)
                                            <form method="POST" action="{{ route('partidos.reprogramar', $partidoReprogramar) }}" style="margin: 0;">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="fecha_hora_inicio" value="{{ $ventana->fecha }} {{ $ventana->horaInicio }}:00">
                                                <input type="hidden" name="formato_serie" value="{{ $formatoSeleccionado->value }}">
                                                <input type="hidden" name="minutos_por_partida" value="{{ $minutosSeleccionados }}">
                                                <button type="submit" class="boton {{ $ventana->disponible ? 'boton--primario' : 'boton--secundario' }} boton--compacto">
                                                    <x-icono nombre="swap-horizontal-outline" /> Mover a este horario
                                                </button>
                                            </form>
                                        @elseif ($equipoLocalSeleccionado && $equipoVisitanteSeleccionado)
                                            <form method="POST" action="{{ route('partidos.store') }}" style="margin: 0;">
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
                                                <button type="submit" class="boton {{ $ventana->disponible ? 'boton--primario' : 'boton--secundario' }} boton--compacto">
                                                    <x-icono nombre="calendar-outline" /> Agendar partido
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
        @endif
    </section>
@endsection
