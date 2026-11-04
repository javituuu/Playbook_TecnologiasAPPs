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
            <div class="bloque__titulo">
                <h2 id="titulares">Titulares</h2>
                <span>5 posiciones oficiales · Puedes bajar un titular a la banca o asignar directamente un suplente disponible</span>
            </div>
            <ul class="posiciones">
                @foreach ($posiciones as $posicion)
                    @php
                        $titular = $titulares->get(strtoupper($posicion));
                        $candidatos = $titular ? collect() : $resto->filter(fn ($m) => $m->estado_invitacion !== \App\Enums\EstadoInvitacionEnum::RECHAZADA
                            && $m->puedeCubrirPosicion($posicion));
                    @endphp
                    <li class="posicion {{ $titular ? '' : 'posicion--libre' }}">
                        <div class="posicion__cabecera">
                            <span class="posicion__rol">{{ $posicion }}</span>
                            @if ($titular)
                                <x-estado :valor="$titular->rol_membresia" />
                            @else
                                <span class="estado estado--error">Vacante</span>
                            @endif
                        </div>

                        @if ($titular)
                            <strong>{{ $titular->jugador?->name }}</strong>
                            <span class="tenue">{{ $titular->rango_declarativo ?? 'Sin rango' }}</span>

                            @if ($puedeGestionarRoster ?? true)
                                <div class="posicion__acciones">
                                    <form method="POST" action="{{ route('equipos.roster.update', [$equipo, $titular->user_id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="posicion_principal" value="{{ $titular->posicion_principal }}">
                                        <input type="hidden" name="rol_membresia" value="{{ \App\Enums\RolMembresiaEnum::SUPLENTE->value }}">
                                        <input type="hidden" name="estado_invitacion" value="{{ $titular->estado_invitacion->value }}">
                                        <button type="submit" class="boton boton--secundario boton--compacto boton--ancho">
                                            Pasar a Suplente
                                        </button>
                                    </form>
                                </div>
                            @endif
                        @else
                            <strong>Sin titular</strong>
                            @if ($candidatos->isNotEmpty())
                                <span class="tenue">Puede cubrir: {{ $candidatos->map(fn ($m) => $m->jugador?->name)->filter()->join(', ') }}</span>

                                @if ($puedeGestionarRoster ?? true)
                                    <div class="posicion__acciones">
                                        @foreach ($candidatos as $candidato)
                                            <form method="POST" action="{{ route('equipos.roster.update', [$equipo, $candidato->user_id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="posicion_principal" value="{{ $posicion }}">
                                                <input type="hidden" name="rol_membresia" value="{{ \App\Enums\RolMembresiaEnum::TITULAR->value }}">
                                                <input type="hidden" name="estado_invitacion" value="{{ \App\Enums\EstadoInvitacionEnum::ACEPTADA->value }}">
                                                <button type="submit" class="boton boton--primario boton--compacto boton--ancho">
                                                    Subir a {{ $candidato->jugador?->name }}
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="bloque" aria-labelledby="plantel">
        <div class="bloque__titulo">
            <h2 id="plantel">Suplentes, en prueba e invitaciones</h2>
            <span>Acciones rápidas en 1 clic o edición avanzada de posición</span>
        </div>

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
                            <th scope="col">Cambio Rápido / Gestión</th>
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
                                <td data-label="Gestión">
                                    @if ($membresia->jugador && ($puedeGestionarRoster ?? true))
                                        <div class="acciones-roster">
                                            {{-- Botón en 1 clic para subir a Titular (y aceptar invitación si estaba pendiente) --}}
                                            @if ($membresia->rol_membresia !== \App\Enums\RolMembresiaEnum::TITULAR || $membresia->estado_invitacion !== \App\Enums\EstadoInvitacionEnum::ACEPTADA)
                                                <form method="POST" action="{{ route('equipos.roster.update', [$equipo, $membresia->user_id]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="posicion_principal" value="{{ $membresia->posicion_principal ?? ($posiciones[0] ?? 'SUPPORT') }}">
                                                    <input type="hidden" name="rol_membresia" value="{{ \App\Enums\RolMembresiaEnum::TITULAR->value }}">
                                                    <input type="hidden" name="estado_invitacion" value="{{ \App\Enums\EstadoInvitacionEnum::ACEPTADA->value }}">
                                                    <button type="submit" class="boton boton--primario boton--compacto">
                                                        Hacer Titular ({{ $membresia->posicion_principal ?? 'Asignar' }})
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Formulario desplegable en línea para cambiar posición, rol o invitación --}}
                                            <form method="POST" action="{{ route('equipos.roster.update', [$equipo, $membresia->user_id]) }}" class="form-roster-inline">
                                                @csrf
                                                @method('PATCH')
                                                <select name="posicion_principal" class="control control--compacto" aria-label="Posición principal">
                                                    @foreach ($posiciones as $pos)
                                                        <option value="{{ $pos }}" @selected(strtoupper((string) $membresia->posicion_principal) === strtoupper($pos))>
                                                            {{ $pos }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <select name="rol_membresia" class="control control--compacto" aria-label="Rol en el equipo">
                                                    @foreach (\App\Enums\RolMembresiaEnum::cases() as $rolCase)
                                                        <option value="{{ $rolCase->value }}" @selected($membresia->rol_membresia === $rolCase)>
                                                            {{ ucfirst(str_replace('_', ' ', $rolCase->value)) }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <select name="estado_invitacion" class="control control--compacto" aria-label="Estado de invitación">
                                                    @foreach (\App\Enums\EstadoInvitacionEnum::cases() as $invCase)
                                                        <option value="{{ $invCase->value }}" @selected($membresia->estado_invitacion === $invCase)>
                                                            {{ ucfirst($invCase->value) }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <button type="submit" class="boton boton--secundario boton--compacto">Guardar</button>
                                            </form>

                                            <form method="POST" action="{{ route('equipos.roster.destroy', [$equipo, $membresia->user_id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="boton-texto boton-texto--peligro">
                                                    Retirar del equipo
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="tenue">Solo lectura</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- =======================================================================
         FORMULARIO PARA INVITAR / REGISTRAR NUEVO JUGADOR EN EL ROSTER (RF.07)
         ======================================================================= --}}
    @if ($puedeGestionarRoster ?? true)
        <section id="invitar-jugador" class="bloque" aria-labelledby="titulo-invitar-jugador">
            <div class="bloque__titulo">
                <h2 id="titulo-invitar-jugador">+ Invitar o Registrar Jugador en {{ $equipo->nombre }} (RF.07)</h2>
                <span>Selecciona un alumno existente o registra un nuevo correo universitario en la plataforma</span>
            </div>

            <form method="POST" action="{{ route('equipos.roster.store', $equipo) }}" class="rejilla-form">
                @csrf

                <div class="campo">
                    <label for="select-user-id">Alumno ya registrado (Opcional)</label>
                    <select id="select-user-id" name="user_id" class="control">
                        <option value="">— O crear alumno nuevo abajo —</option>
                        @foreach ($jugadoresDisponiblesParaInvitar ?? [] as $jugDisp)
                            <option value="{{ $jugDisp->id }}">{{ $jugDisp->name }} ({{ $jugDisp->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="input-nuevo-nombre">Nombre (si es alumno nuevo)</label>
                    <input id="input-nuevo-nombre" type="text" name="nuevo_nombre" placeholder="Ej. Lucas Ruiz" class="control">
                </div>

                <div class="campo">
                    <label for="input-nuevo-email">Correo (si es alumno nuevo)</label>
                    <input id="input-nuevo-email" type="email" name="nuevo_email" placeholder="lucas.ruiz@udd.cl" class="control">
                </div>

                <div class="campo">
                    <label for="select-pos-principal">Posición Principal</label>
                    <select id="select-pos-principal" name="posicion_principal" required class="control">
                        @foreach ($posiciones as $pos)
                            <option value="{{ $pos }}">{{ $pos }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="select-pos-secundaria">Posición Secundaria</label>
                    <select id="select-pos-secundaria" name="posicion_secundaria" class="control">
                        <option value="">Ninguna</option>
                        @foreach ($posiciones as $pos)
                            <option value="{{ $pos }}">{{ $pos }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="input-rango">Rango Declarativo</label>
                    <input id="input-rango" type="text" name="rango_declarativo" placeholder="Ej. Diamante 2" class="control">
                </div>

                <div class="campo">
                    <label for="select-rol-membresia">Rol Inicial</label>
                    <select id="select-rol-membresia" name="rol_membresia" class="control">
                        <option value="suplente">Suplente</option>
                        <option value="en_prueba">En prueba</option>
                        <option value="titular">Titular</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="select-estado-inv">Estado Invitación</label>
                    <select id="select-estado-inv" name="estado_invitacion" class="control">
                        <option value="aceptada">Aceptada</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>

                <div class="campo campo--check">
                    <button type="submit" class="boton boton--primario">+ Incorporar al Roster</button>
                </div>
            </form>
        </section>
    @endif

    {{-- =======================================================================
         INSCRIPCIÓN DEL EQUIPO EN LIGAS / TORNEOS UNIVERSITARIOS (RF.07)
         ======================================================================= --}}
    <section id="inscripciones-liga" class="bloque" aria-labelledby="titulo-inscripciones">
        <div class="bloque__titulo bloque__titulo--entre">
            <div>
                <h2 id="titulo-inscripciones">Torneos y Ligas Inscritas</h2>
                <span>Competencias oficiales donde participa {{ $equipo->nombre }}</span>
            </div>

            @if (($puedeGestionarRoster ?? true) && isset($ligasDisponibles) && $ligasDisponibles->isNotEmpty())
                <form method="POST" action="{{ route('equipos.inscripciones.store', $equipo) }}" class="form-en-linea">
                    @csrf
                    <select name="liga_id" required class="control control--compacto" aria-label="Seleccionar liga para inscribir">
                        @foreach ($ligasDisponibles as $ligaDisp)
                            <option value="{{ $ligaDisp->id }}">{{ $ligaDisp->nombre }} ({{ $ligaDisp->temporada }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="boton boton--primario boton--compacto">+ Inscribir en Liga</button>
                </form>
            @endif
        </div>

        @if ($equipo->inscripcionesLiga->isEmpty())
            <div class="vacio">
                <x-icono nombre="calendar-outline" />
                <p>Este equipo aún no está inscrito en ninguna liga.</p>
            </div>
        @else
            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Liga / Torneo</th>
                            <th scope="col">Temporada</th>
                            <th scope="col">Código Invitación</th>
                            <th scope="col">Estado Inscripción</th>
                            <th scope="col">Validez de Roster</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equipo->inscripcionesLiga as $inscripcion)
                            <tr>
                                <td class="fuerte">{{ $inscripcion->liga?->nombre ?? 'Liga' }}</td>
                                <td data-label="Temporada">{{ $inscripcion->liga?->temporada ?? '—' }}</td>
                                <td class="num tenue" data-label="Código">{{ $inscripcion->codigo_invitacion }}</td>
                                <td data-label="Inscripción"><x-estado :valor="$inscripcion->estado_invitacion" /></td>
                                <td data-label="Validez de Roster">
                                    @if ($faltantes === [])
                                        <span class="estado estado--ok">Válido (5/5)</span>
                                    @else
                                        <span class="estado estado--error">Incompleto (Falta {{ \Illuminate\Support\Arr::join($faltantes, ', ', ' y ') }})</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- =======================================================================
         PASO 3: MATRIZ SEMANAL DE DISPONIBILIDAD DEL EQUIPO (MAINS + SUPLENTES)
         ======================================================================= --}}
    @if (isset($grillaDisponibilidad))
        <section id="matriz-disponibilidad" class="bloque" aria-labelledby="titulo-matriz">
            <div class="bloque__titulo bloque__titulo--entre">
                <div>
                    <h2 id="titulo-matriz">Matriz de Disponibilidad Semanal ({{ $grillaDisponibilidad['semana_inicio'] }} al {{ $grillaDisponibilidad['semana_fin'] }})</h2>
                    <span>
                        Serie {{ $formatoSeleccionado->label() }} ({{ $grillaDisponibilidad['duracion_bloque_minutos'] }} min) ·
                        Óptimos (5/5): {{ $grillaDisponibilidad['resumen']['bloques_optimos_oficial'] }} ·
                        Aptos Práctica (&ge;{{ $grillaDisponibilidad['quorum_minimo_practica'] }}/5): {{ $grillaDisponibilidad['resumen']['bloques_aptos_practica'] }} ·
                        Bloqueados: {{ $grillaDisponibilidad['resumen']['bloques_bloqueados'] }}
                    </span>
                </div>

                <form method="GET" action="{{ route('equipos.show', $equipo) }}#matriz-disponibilidad" class="form-en-linea">
                    <input type="date" name="semana" value="{{ $semanaSeleccionada }}" class="control control--compacto" aria-label="Semana">
                    <select name="formato_serie" class="control control--compacto" aria-label="Formato de serie">
                        @foreach (\App\Enums\FormatoSerieEnum::cases() as $fmt)
                            <option value="{{ $fmt->value }}" @selected($fmt === $formatoSeleccionado)>
                                {{ strtoupper($fmt->value) }} ({{ $fmt->maxPartidas() * $minutosSeleccionados }}m)
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="boton boton--secundario boton--compacto">Actualizar Grilla</button>
                </form>
            </div>

            @php
                $diasSemana = array_keys($grillaDisponibilidad['grilla']);
                $horasMatriz = $diasSemana !== [] ? array_keys($grillaDisponibilidad['grilla'][$diasSemana[0]]) : [];
            @endphp

            <div class="tabla">
                <table class="tabla-heatmap">
                    <thead>
                        <tr>
                            <th scope="col">Hora ({{ $grillaDisponibilidad['duracion_bloque_minutos'] }}m)</th>
                            @foreach ($diasSemana as $dia)
                                <th scope="col">{{ $dia }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($horasMatriz as $hora)
                            <tr>
                                <td class="num fuerte" data-label="Hora">{{ $hora }}</td>
                                @foreach ($diasSemana as $dia)
                                    @php
                                        $celda = $grillaDisponibilidad['grilla'][$dia][$hora];
                                        $tonoCelda = match ($celda['estado_celda']) {
                                            'OPTIMO_OFICIAL' => 'ok',
                                            'APTO_PRACTICA' => 'aviso',
                                            default => 'error',
                                        };
                                        $nombresAusentes = collect($celda['ausentes'])
                                            ->map(fn ($a) => $a['nombre'] . ' (' . ($a['motivos_ausencia'][0]['etiqueta'] ?? 'Ocupado') . ')')
                                            ->join(', ');
                                    @endphp
                                    <td data-label="{{ $dia }}" title="{{ $nombresAusentes !== '' ? 'Ausentes: ' . $nombresAusentes : 'Plantel disponible' }}">
                                        <span class="estado estado--{{ $tonoCelda }}">
                                            {{ $celda['ratio_disponibilidad'] }}
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
