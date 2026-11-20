@extends('layouts.panel')

@section('titulo', $equipo->nombre)

@section('contenido')
    <a class="volver" href="{{ route('equipos') }}">
        <x-icono nombre="arrow-back-outline" />
        <span>Volver a equipos</span>
    </a>

    <header class="cabecera">
        <h1>{{ $equipo->nombre }}</h1>
        <p>
            {{ $equipo->club?->nombreConSiglas() }} · {{ $equipo->disciplina?->nombre ?? 'Sin disciplina' }}
            @if ($equipo->coach) · Coach: {{ $equipo->coach->name }} @endif
        </p>
    </header>

    @if ($faltantes !== [])
        <div class="aviso aviso--error aviso--completo" role="status">
            <x-icono nombre="alert-circle-outline" />
            <div class="aviso__cuerpo">
                <p>
                    <strong>Faltan titulares en {{ \Illuminate\Support\Arr::join($faltantes, ', ', ' y ') }}.</strong>
                    Faltan titulares en {{ \Illuminate\Support\Arr::join($faltantes, ', ', ' y ') }} para competir en partidos oficiales.
                </p>
            </div>
        </div>
    @elseif ($posiciones !== [])
        <div class="aviso aviso--ok aviso--completo" role="status">
            <x-icono nombre="checkmark-circle-outline" />
            <div class="aviso__cuerpo">
                <p><strong>Roster completo.</strong> Roster completo con los 5 titulares listos para competir en partidos oficiales.</p>
            </div>
        </div>
    @endif

    @if ($posiciones !== [])
        <section class="bloque" aria-labelledby="titulares">
            <div class="bloque__titulo bloque__titulo--entre">
                <div>
                    <h2 id="titulares">Titulares</h2>
                    <span>5 posiciones oficiales para competir</span>
                </div>
                <span class="resumen-chip">
                    <strong>{{ $titulares->count() }}</strong>/{{ count($posiciones) }} cubiertas
                </span>
            </div>
            <ul class="posiciones">
                @foreach ($posiciones as $posicion)
                    @php
                        $titular = $titulares->get(strtoupper($posicion));
                        $candidatos = $titular ? collect() : $resto->filter(fn ($m) => $m->estado_invitacion !== \App\Enums\EstadoInvitacionEnum::RECHAZADA
                            && $m->puedeCubrirPosicion($posicion));

                        $nombreJugador = $titular?->jugador?->name;
                        $fotoJugador = null;
                        if ($nombreJugador) {
                            if (str_contains($nombreJugador, 'Diego')) {
                                $fotoJugador = asset('images/player-diaz.jpg');
                            } elseif (str_contains($nombreJugador, 'Martina')) {
                                $fotoJugador = asset('images/player-martina.jpg');
                            } elseif (str_contains($nombreJugador, 'Soto') || str_contains($nombreJugador, 'Sofia') || str_contains($nombreJugador, 'Valentina')) {
                                $fotoJugador = asset('images/player-sofia.jpg');
                            } elseif (str_contains($nombreJugador, 'Vega') || str_contains($nombreJugador, 'Benavides') || str_contains($nombreJugador, 'Javier')) {
                                $fotoJugador = asset('images/player-vega.jpg');
                            }
                        }
                        $dorsal = $titular ? sprintf('%02d', $titular->user_id) : null;
                    @endphp
                    <li class="posicion {{ $titular ? '' : 'posicion--libre' }}">
                        <div class="posicion__cabecera">
                            <span class="posicion__rol">{{ strtoupper($posicion) }}</span>
                            @if ($titular)
                                <x-estado :valor="$titular->rol_membresia" />
                            @else
                                <span class="estado estado--error">Vacante</span>
                            @endif
                        </div>

                        <div class="posicion__jugador">
                            @if ($titular)
                                <x-avatar :nombre="$titular->jugador?->name" :dorsal="$dorsal" :foto="$fotoJugador" size="md" />
                                <div class="posicion__datos">
                                    <strong>{{ $titular->jugador?->name }}</strong>
                                    <span class="tenue rango-grotesk">{{ $titular->rango_declarativo ?? 'Sin rango' }}</span>
                                </div>
                            @else
                                <x-avatar nombre="?" size="md" />
                                <div class="posicion__datos">
                                    <strong>Posición vacante</strong>
                                    <span class="tenue">Sin titular asignado</span>
                                </div>
                            @endif
                        </div>

                        @if ($titular)
                            @if ($puedeGestionarRoster ?? true)
                                <div class="posicion__acciones">
                                    <form method="POST" action="{{ route('equipos.roster.update', [$equipo, $titular->user_id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="posicion_principal" value="{{ $titular->posicion_principal }}">
                                        <input type="hidden" name="rol_membresia" value="{{ \App\Enums\RolMembresiaEnum::SUPLENTE->value }}">
                                        <input type="hidden" name="estado_invitacion" value="{{ $titular->estado_invitacion->value }}">
                                        <button type="submit" class="boton boton--secundario boton--compacto boton--ancho">
                                            Bajar a suplente
                                        </button>
                                    </form>
                                </div>
                            @endif
                        @else
                            @if ($candidatos->isNotEmpty())
                                <div class="posicion__candidatos">
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
                                                        Asignar a {{ $candidato->jugador?->name }}
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="posicion__acciones posicion__acciones--vacia">
                                    <span class="tenue">Sin suplentes para esta posición</span>
                                </div>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="bloque" aria-labelledby="plantel">
        <div class="bloque__titulo bloque__titulo--entre">
            <div>
                <h2 id="plantel">Suplentes y jugadores a prueba</h2>
                <span>Jugadores en banca, aspirantes y convocatorias pendientes</span>
            </div>
            @if ($resto->isNotEmpty())
                <span class="resumen-chip">
                    <strong>{{ $resto->count() }}</strong> {{ $resto->count() === 1 ? 'jugador' : 'jugadores' }}
                </span>
            @endif
        </div>

        @if ($resto->isEmpty())
            <div class="vacio">
                <x-icono nombre="people-outline" />
                <p>No hay suplentes ni invitaciones pendientes en este equipo.</p>
            </div>
        @else
            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Jugador</th>
                            <th scope="col">Posición</th>
                            <th scope="col">Posición secundaria</th>
                            <th scope="col">Rango</th>
                            <th scope="col">Rol</th>
                            <th scope="col">Invitación</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resto as $membresia)
                            @php
                                $nombreR = $membresia->jugador?->name;
                                $fotoR = null;
                                if ($nombreR) {
                                    if (str_contains($nombreR, 'Diego')) {
                                        $fotoR = asset('images/player-diaz.jpg');
                                    } elseif (str_contains($nombreR, 'Martina')) {
                                        $fotoR = asset('images/player-martina.jpg');
                                    } elseif (str_contains($nombreR, 'Soto') || str_contains($nombreR, 'Sofia') || str_contains($nombreR, 'Valentina')) {
                                        $fotoR = asset('images/player-sofia.jpg');
                                    } elseif (str_contains($nombreR, 'Vega') || str_contains($nombreR, 'Benavides') || str_contains($nombreR, 'Javier')) {
                                        $fotoR = asset('images/player-vega.jpg');
                                    }
                                }
                                $dorsalR = $membresia->user_id ? sprintf('%02d', $membresia->user_id) : null;
                            @endphp
                            <tr>
                                <td class="fuerte">
                                    <div class="celda-jugador">
                                        <x-avatar :nombre="$nombreR ?? 'Jugador'" :dorsal="$dorsalR" :foto="$fotoR" size="sm" />
                                        <div class="celda-jugador__datos">
                                            <strong>{{ $nombreR ?? 'Jugador eliminado' }}</strong>
                                            @if ($membresia->jugador?->email)
                                                <span class="tenue celda-jugador__email">{{ $membresia->jugador->email }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Posición">
                                    <span class="codigo-mono">{{ $membresia->posicion_principal ?? '—' }}</span>
                                </td>
                                <td class="tenue" data-label="Posición secundaria">{{ $membresia->posicion_secundaria ?? '—' }}</td>
                                <td data-label="Rango" class="rango-grotesk">{{ $membresia->rango_declarativo ?? '—' }}</td>
                                <td data-label="Rol"><x-estado :valor="$membresia->rol_membresia" /></td>
                                <td data-label="Invitación"><x-estado :valor="$membresia->estado_invitacion" /></td>
                                <td data-label="Acciones">
                                    @if ($membresia->jugador && ($puedeGestionarRoster ?? true))
                                        <div class="acciones-roster">
                                            @if ($membresia->rol_membresia !== \App\Enums\RolMembresiaEnum::TITULAR || $membresia->estado_invitacion !== \App\Enums\EstadoInvitacionEnum::ACEPTADA)
                                                <form method="POST" action="{{ route('equipos.roster.update', [$equipo, $membresia->user_id]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="posicion_principal" value="{{ $membresia->posicion_principal ?? ($posiciones[0] ?? 'SUPPORT') }}">
                                                    <input type="hidden" name="rol_membresia" value="{{ \App\Enums\RolMembresiaEnum::TITULAR->value }}">
                                                    <input type="hidden" name="estado_invitacion" value="{{ \App\Enums\EstadoInvitacionEnum::ACEPTADA->value }}">
                                                    <button type="submit" class="boton boton--primario boton--compacto">
                                                        Subir a titular ({{ $membresia->posicion_principal ?? 'Asignar' }})
                                                    </button>
                                                </form>
                                            @endif

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

    @if ($puedeGestionarRoster ?? true)
        <section id="invitar-jugador" class="bloque" aria-labelledby="titulo-invitar-jugador">
            <div class="bloque__titulo">
                <h2 id="titulo-invitar-jugador">Agregar jugador al equipo</h2>
                <span>Suma un alumno ya registrado en la plataforma o invita a uno nuevo con su correo institucional</span>
            </div>

            <form method="POST" action="{{ route('equipos.roster.store', $equipo) }}" class="rejilla-form rejilla-form--roster">
                @csrf

                <div class="campo">
                    <label for="select-user-id">Alumno ya registrado (opcional)</label>
                    <select id="select-user-id" name="user_id" class="control">
                        <option value="">— O registrar alumno nuevo —</option>
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
                    <label for="select-pos-principal">Posición principal</label>
                    <select id="select-pos-principal" name="posicion_principal" required class="control">
                        @foreach ($posiciones as $pos)
                            <option value="{{ $pos }}">{{ $pos }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="select-pos-secundaria">Posición secundaria</label>
                    <select id="select-pos-secundaria" name="posicion_secundaria" class="control">
                        <option value="">Ninguna</option>
                        @foreach ($posiciones as $pos)
                            <option value="{{ $pos }}">{{ $pos }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="input-rango">Rango competitivo</label>
                    <input id="input-rango" type="text" name="rango_declarativo" placeholder="Ej. Diamante 2" class="control">
                </div>

                <div class="campo">
                    <label for="select-rol-membresia">Rol en el equipo</label>
                    <select id="select-rol-membresia" name="rol_membresia" class="control">
                        <option value="suplente">Suplente</option>
                        <option value="en_prueba">En prueba</option>
                        <option value="titular">Titular</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="select-estado-inv">Estado de invitación</label>
                    <select id="select-estado-inv" name="estado_invitacion" class="control">
                        <option value="aceptada">Aceptada</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>

                <div class="campo campo--acciones-roster">
                    <button type="submit" class="boton boton--primario">
                        <x-icono nombre="add-outline" />
                        <span>Agregar al equipo</span>
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section id="inscripciones-liga" class="bloque" aria-labelledby="titulo-inscripciones">
        <div class="bloque__titulo bloque__titulo--entre">
            <div>
                <h2 id="titulo-inscripciones">Ligas inscritas</h2>
                <span>Competencias oficiales en las que participa {{ $equipo->nombre }}</span>
            </div>

            @if (($puedeGestionarRoster ?? true) && isset($ligasDisponibles) && $ligasDisponibles->isNotEmpty())
                <form method="POST" action="{{ route('equipos.inscripciones.store', $equipo) }}" class="form-en-linea">
                    @csrf
                    <select name="liga_id" required class="control control--compacto" aria-label="Seleccionar liga para inscribir">
                        @foreach ($ligasDisponibles as $ligaDisp)
                            <option value="{{ $ligaDisp->id }}">{{ $ligaDisp->nombre }} ({{ $ligaDisp->temporada }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="boton boton--primario boton--compacto">
                        <x-icono nombre="trophy-outline" />
                        <span>Inscribir en liga</span>
                    </button>
                </form>
            @endif
        </div>

        @if ($equipo->inscripcionesLiga->isEmpty())
            <div class="vacio">
                <x-icono nombre="calendar-outline" />
                <p>Este equipo aún no está inscrito en ninguna liga oficial.</p>
            </div>
        @else
            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Liga o torneo</th>
                            <th scope="col">Temporada</th>
                            <th scope="col">Código de invitación</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Validez del roster</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equipo->inscripcionesLiga as $inscripcion)
                            <tr>
                                <td class="fuerte">
                                    <div class="celda-liga">
                                        <x-icono nombre="trophy-outline" />
                                        <span>{{ $inscripcion->liga?->nombre ?? 'Liga' }}</span>
                                    </div>
                                </td>
                                <td data-label="Temporada" class="rango-grotesk">{{ $inscripcion->liga?->temporada ?? '—' }}</td>
                                <td data-label="Código">
                                    <span class="codigo-mono">{{ $inscripcion->codigo_invitacion }}</span>
                                </td>
                                <td data-label="Estado"><x-estado :valor="$inscripcion->estado_invitacion" /></td>
                                <td data-label="Validez del roster">
                                    @if ($faltantes === [])
                                        <span class="estado estado--ok">Válido (5/5)</span>
                                    @else
                                        <span class="estado estado--error">Incompleto (falta {{ \Illuminate\Support\Arr::join($faltantes, ', ', ' y ') }})</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if (isset($grillaDisponibilidad))
        <section id="matriz-disponibilidad" class="bloque" aria-labelledby="titulo-matriz">
            <div class="bloque__titulo bloque__titulo--entre">
                <div>
                    <h2 id="titulo-matriz">Disponibilidad semanal del equipo</h2>
                    <span>Semana del {{ $grillaDisponibilidad['semana_inicio'] }} al {{ $grillaDisponibilidad['semana_fin'] }} · Bloques para entrenamientos y partidos oficiales</span>
                    <div class="resumen-matriz">
                        <span class="resumen-chip">Serie {{ $formatoSeleccionado->label() }} ({{ $grillaDisponibilidad['duracion_bloque_minutos'] }} min)</span>
                        <span class="resumen-chip resumen-chip--ok">
                            <strong>{{ $grillaDisponibilidad['resumen']['bloques_optimos_oficial'] }}</strong> completos (5/5)
                        </span>
                        <span class="resumen-chip resumen-chip--aviso">
                            <strong>{{ $grillaDisponibilidad['resumen']['bloques_aptos_practica'] }}</strong> aptos para práctica (&ge;{{ $grillaDisponibilidad['quorum_minimo_practica'] }}/5)
                        </span>
                        <span class="resumen-chip resumen-chip--error">
                            <strong>{{ $grillaDisponibilidad['resumen']['bloques_bloqueados'] }}</strong> bloqueados
                        </span>
                    </div>
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
                    <button type="submit" class="boton boton--secundario boton--compacto">Actualizar horario</button>
                </form>
            </div>

            @php
                $diasSemana = array_keys($grillaDisponibilidad['grilla']);
                $horasMatriz = $diasSemana !== [] ? array_keys($grillaDisponibilidad['grilla'][$diasSemana[0]]) : [];
            @endphp

            <div class="tabla" tabindex="0" role="region" aria-label="Matriz desplazable de disponibilidad semanal">
                <table class="tabla-heatmap" aria-label="Disponibilidad semanal del equipo">
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
                                <td class="num fuerte rango-grotesk" data-label="Hora">{{ $hora }}</td>
                                @foreach ($diasSemana as $dia)
                                    @php
                                        $celda = $grillaDisponibilidad['grilla'][$dia][$hora];
                                        $tonoCelda = match ($celda['estado_celda']) {
                                            'OPTIMO_OFICIAL' => 'ok',
                                            'APTO_PRACTICA' => 'aviso',
                                            default => 'error',
                                        };
                                        $etiquetaEstado = match ($celda['estado_celda']) {
                                            'OPTIMO_OFICIAL' => 'Roster completo (5/5)',
                                            'APTO_PRACTICA' => 'Apto para práctica',
                                            default => 'Bloqueado',
                                        };
                                        $ausentesCol = collect($celda['ausentes']);
                                        $detalleAusentes = $ausentesCol->isNotEmpty()
                                            ? 'Ausentes: ' . $ausentesCol->map(fn ($a) => $a['nombre'] . ' (' . ($a['motivos_ausencia'][0]['etiqueta'] ?? 'Ocupado') . ')')->join(', ')
                                            : 'Todos los jugadores disponibles';
                                        $descripcionCelda = "{$dia} {$hora} · {$celda['ratio_disponibilidad']} disponibles · {$etiquetaEstado}. {$detalleAusentes}";
                                    @endphp
                                    <td data-label="{{ $dia }}" title="{{ $descripcionCelda }}">
                                        <span class="estado estado--{{ $tonoCelda }} celda-heatmap-badge" aria-label="{{ $descripcionCelda }}">
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
