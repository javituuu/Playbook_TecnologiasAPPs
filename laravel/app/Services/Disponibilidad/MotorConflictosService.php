<?php

namespace App\Services\Disponibilidad;

use App\DTOs\Disponibilidad\ConflictoDetectadoDTO;
use App\DTOs\Disponibilidad\VentanaSugeridaDTO;
use App\DTOs\Torneos\ProgramarPartidoDTO;
use App\Enums\DiaSemanaEnum;
use App\Enums\EstadoCitacionEnum;
use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\FormatoSerieEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\TipoConflictoEnum;
use App\Models\BloqueoHorario;
use App\Models\Equipo;
use App\Models\EquipoJugador;
use App\Models\Liga;
use App\Models\Partido;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Motor central de disponibilidad, cruce de horarios (recurrentes + unitarios),
 * evaluación de quórum (titulares + suplentes) y validación de fixture (RF.02, RF.05, RF.06, RF.08, RF.09).
 */
class MotorConflictosService
{
    /**
     * Calcula la duración total de una serie competitiva: n * minutosPorPartida.
     */
    public function calcularDuracionSerie(
        FormatoSerieEnum $formato = FormatoSerieEnum::BO1,
        int $minutosPorPartida = FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA,
    ): int {
        return $formato->calcularDuracionMinutos($minutosPorPartida);
    }

    /**
     * Evalúa qué integrantes del equipo están disponibles u ocupados en la ventana [inicio, fin)
     * cruzando:
     *  1. Bloqueos horarios RECURRENTES (clases semanales) y UNITARIOS (fecha específica).
     *  2. Partidos oficiales o prácticas en otra liga/equipo que solapen con la ventana.
     *  3. Cobertura de posiciones requeridas usando titulares (mains) y suplentes disponibles.
     *
     * @return array{
     *     equipo_id: int,
     *     equipo_nombre: string,
     *     disciplina: string,
     *     titulares_requeridos: int,
     *     quorum_minimo_practica: int,
     *     total_plantel_activo: int,
     *     disponibles_count: int,
     *     titulares_disponibles_count: int,
     *     suplentes_disponibles_count: int,
     *     cumple_quorum_practica: bool,
     *     cumple_quorum_oficial: bool,
     *     posiciones_cubiertas: array<string, array{jugador_id: int, nombre: string, rol_original: string, es_reemplazo_suplente: bool}>,
     *     posiciones_sin_cubrir: list<string>,
     *     jugadores_disponibles: list<array<string, mixed>>,
     *     jugadores_ausentes: list<array<string, mixed>>,
     *     conflictos: Collection<int, ConflictoDetectadoDTO>
     * }
     */
    public function evaluarDisponibilidadEquipoEnVentana(
        Equipo $equipo,
        CarbonInterface $inicio,
        CarbonInterface $fin,
        ?int $ignorarPartidoId = null,
    ): array {
        $equipo->loadMissing(['disciplina', 'membresias.jugador']);

        $inicioImm = CarbonImmutable::instance($inicio);
        $finImm = CarbonImmutable::instance($fin);
        $horaInicioStr = $inicioImm->format('H:i:s');
        $horaFinStr = $finImm->format('H:i:s');

        // Plantel con invitación aceptada (titulares, suplentes y en prueba)
        $membresiasActivas = $equipo->membresias
            ->filter(fn (EquipoJugador $m) => $m->estado_invitacion === EstadoInvitacionEnum::ACEPTADA && $m->jugador !== null)
            ->values();

        $userIds = $membresiasActivas->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        // 1. Bloqueos horarios (recurrentes del día ISO + unitarios de la fecha exacta)
        $bloqueos = $userIds === []
            ? collect()
            : BloqueoHorario::query()
                ->whereIn('user_id', $userIds)
                ->paraFecha($inicioImm)
                ->solapaConHora($horaInicioStr, $horaFinStr)
                ->get()
                ->groupBy('user_id');

        // 2. Otros partidos que ocupan agenda en esa misma ventana
        $partidosSolapados = $userIds === []
            ? collect()
            : Partido::query()
                ->ocupanAgenda()
                ->when($ignorarPartidoId !== null, fn ($q) => $q->where('id', '!=', $ignorarPartidoId))
                ->solapaCon($inicioImm, $finImm)
                ->whereHas('citaciones', function ($q) use ($userIds) {
                    $q->whereIn('user_id', $userIds)
                        ->where('estado_respuesta', '!=', EstadoCitacionEnum::RECHAZADO->value);
                })
                ->with(['liga', 'citaciones' => fn ($q) => $q->whereIn('user_id', $userIds)])
                ->get();

        $disponibles = [];
        $ausentes = [];
        /** @var Collection<int, ConflictoDetectadoDTO> $conflictos */
        $conflictos = collect();

        foreach ($membresiasActivas as $membresia) {
            $jugador = $membresia->jugador;
            $uid = (int) $jugador->id;

            $bloqueosJugador = $bloqueos->get($uid, collect());
            $partidosJugador = $partidosSolapados->filter(
                fn (Partido $p) => $p->citaciones->contains(fn ($c) => (int) $c->user_id === $uid)
            );

            $motivosAusencia = [];

            foreach ($partidosJugador as $partidoChoque) {
                $rangoChoque = sprintf(
                    '%s %s-%s',
                    $partidoChoque->fecha_hora_inicio?->format('Y-m-d'),
                    $partidoChoque->fecha_hora_inicio?->format('H:i'),
                    $partidoChoque->fecha_hora_fin?->format('H:i'),
                );
                $nombreTorneo = $partidoChoque->nombreLigaOPractica();

                $motivosAusencia[] = [
                    'origen' => 'PARTIDO_MULTI_LIGA',
                    'tipo_bloqueo' => 'partido',
                    'etiqueta' => "Partido {$nombreTorneo} ({$partidoChoque->fecha_hora_inicio?->format('H:i')})",
                    'detalle' => "Compromiso en {$nombreTorneo} ({$rangoChoque})",
                    'partido_id' => $partidoChoque->id,
                ];

                $conflictos->push(new ConflictoDetectadoDTO(
                    tipo: TipoConflictoEnum::VIOLACION_DURA,
                    codigoRegla: 'CHOQUE_MULTI_LIGA',
                    mensaje: sprintf(
                        'El jugador %s (%s - %s) tiene un partido %s en %s el %s (%s - %s).',
                        $jugador->name,
                        $equipo->nombre,
                        $membresia->posicion_principal ?? 'Plantel',
                        $partidoChoque->estado->value,
                        $nombreTorneo,
                        $partidoChoque->fecha_hora_inicio?->format('d/m/Y'),
                        $partidoChoque->fecha_hora_inicio?->format('H:i'),
                        $partidoChoque->fecha_hora_fin?->format('H:i'),
                    ),
                    userId: $uid,
                    nombreJugador: $jugador->name,
                    equipoId: $equipo->id,
                    nombreEquipo: $equipo->nombre,
                    partidoConflictoId: $partidoChoque->id,
                    rangoHorario: $rangoChoque,
                    puntosPenalizacion: 25,
                ));
            }

            foreach ($bloqueosJugador as $bloqueo) {
                $horaIniBloq = substr((string) $bloqueo->hora_inicio, 0, 5);
                $horaFinBloq = substr((string) $bloqueo->hora_fin, 0, 5);
                $tipoFrecuencia = $bloqueo->es_recurrente ? 'Recurrente semanal' : 'Unitario ('.$bloqueo->fecha_especifica?->format('d/m/Y').')';
                $motivoTexto = $bloqueo->motivo ?? 'Académico / Personal';

                $motivosAusencia[] = [
                    'origen' => $bloqueo->es_recurrente ? 'BLOQUEO_RECURRENTE' : 'BLOQUEO_UNITARIO',
                    'tipo_bloqueo' => $bloqueo->es_recurrente ? 'recurrente' : 'unitario',
                    'etiqueta' => "{$motivoTexto} ({$horaIniBloq}-{$horaFinBloq})",
                    'detalle' => "Bloqueo {$tipoFrecuencia}: {$motivoTexto}".($bloqueo->ubicacion ? " en {$bloqueo->ubicacion}" : ''),
                    'bloqueo_id' => $bloqueo->id,
                ];

                // Si es titular y no hay suplente aún, registramos el conflicto de horario del jugador
                $conflictos->push(new ConflictoDetectadoDTO(
                    tipo: $membresia->rol_membresia === RolMembresiaEnum::TITULAR
                        ? TipoConflictoEnum::VIOLACION_DURA
                        : TipoConflictoEnum::ADVERTENCIA_BLANDA,
                    codigoRegla: $bloqueo->es_recurrente ? 'BLOQUEO_HORARIO_RECURRENTE' : 'BLOQUEO_HORARIO_UNITARIO',
                    mensaje: sprintf(
                        'El jugador %s (%s - %s) registra bloqueo %s (%s) de %s a %s.',
                        $jugador->name,
                        $equipo->nombre,
                        $membresia->rol_membresia->value,
                        $bloqueo->es_recurrente ? 'recurrente' : 'unitario',
                        $motivoTexto,
                        $horaIniBloq,
                        $horaFinBloq,
                    ),
                    userId: $uid,
                    nombreJugador: $jugador->name,
                    equipoId: $equipo->id,
                    nombreEquipo: $equipo->nombre,
                    partidoConflictoId: null,
                    rangoHorario: "{$inicioImm->format('Y-m-d')} {$horaIniBloq}-{$horaFinBloq}",
                    puntosPenalizacion: $membresia->rol_membresia === RolMembresiaEnum::TITULAR ? 10 : 3,
                ));
            }

            $fichaJugador = [
                'user_id' => $uid,
                'nombre' => $jugador->name,
                'email' => $jugador->email,
                'rol_membresia' => $membresia->rol_membresia->value,
                'posicion_principal' => $membresia->posicion_principal,
                'posicion_secundaria' => $membresia->posicion_secundaria,
                'rango' => $membresia->rango_declarativo,
                'membresia' => $membresia,
            ];

            if ($motivosAusencia === []) {
                $disponibles[] = $fichaJugador;
            } else {
                $fichaJugador['motivos_ausencia'] = $motivosAusencia;
                $ausentes[] = $fichaJugador;
            }
        }

        // 3. Intentar armar alineación cubriendo posiciones con Titulares (Mains) y luego Suplentes/En Prueba disponibles
        $posicionesRequeridas = $equipo->disciplina?->posiciones_validas ?? [];
        $titularesRequeridos = $equipo->disciplina?->titulares_requeridos ?? 5;
        $quorumPractica = $equipo->disciplina?->quorum_minimo_practica ?? 4;

        $cobertura = $this->resolverCoberturaDePosiciones($posicionesRequeridas, collect($disponibles));

        $titularesDisponiblesCount = collect($disponibles)
            ->filter(fn (array $j) => $j['rol_membresia'] === RolMembresiaEnum::TITULAR->value)
            ->count();

        $suplentesDisponiblesCount = collect($disponibles)
            ->filter(fn (array $j) => $j['rol_membresia'] !== RolMembresiaEnum::TITULAR->value)
            ->count();

        $cumpleQuorumPractica = count($disponibles) >= $quorumPractica;
        $cumpleQuorumOficial = count($disponibles) >= $titularesRequeridos
            && $cobertura['posiciones_sin_cubrir'] === [];

        // Si un titular tenía bloqueo horario pero un suplente disponible logró cubrir su posición,
        // degradamos el bloqueo horario de VIOLACION_DURA a ADVERTENCIA_BLANDA (cubierto por suplente).
        if ($cumpleQuorumOficial) {
            $conflictos = $conflictos->map(function (ConflictoDetectadoDTO $c) {
                if (in_array($c->codigoRegla, ['BLOQUEO_HORARIO_RECURRENTE', 'BLOQUEO_HORARIO_UNITARIO'], true)
                    && $c->tipo === TipoConflictoEnum::VIOLACION_DURA) {
                    return new ConflictoDetectadoDTO(
                        tipo: TipoConflictoEnum::ADVERTENCIA_BLANDA,
                        codigoRegla: $c->codigoRegla.'_CUBIERTO_POR_SUPLENTE',
                        mensaje: $c->mensaje.' (Posición cubierta por suplente disponible).',
                        userId: $c->userId,
                        nombreJugador: $c->nombreJugador,
                        equipoId: $c->equipoId,
                        nombreEquipo: $c->nombreEquipo,
                        partidoConflictoId: $c->partidoConflictoId,
                        rangoHorario: $c->rangoHorario,
                        puntosPenalizacion: 5,
                    );
                }

                return $c;
            });
        }

        return [
            'equipo_id' => $equipo->id,
            'equipo_nombre' => $equipo->nombre,
            'disciplina' => $equipo->disciplina?->nombre ?? 'Sin disciplina',
            'titulares_requeridos' => $titularesRequeridos,
            'quorum_minimo_practica' => $quorumPractica,
            'total_plantel_activo' => $membresiasActivas->count(),
            'disponibles_count' => count($disponibles),
            'titulares_disponibles_count' => $titularesDisponiblesCount,
            'suplentes_disponibles_count' => $suplentesDisponiblesCount,
            'cumple_quorum_practica' => $cumpleQuorumPractica,
            'cumple_quorum_oficial' => $cumpleQuorumOficial,
            'posiciones_cubiertas' => $cobertura['posiciones_cubiertas'],
            'posiciones_sin_cubrir' => $cobertura['posiciones_sin_cubrir'],
            'jugadores_disponibles' => array_map(function (array $j) {
                unset($j['membresia']);

                return $j;
            }, $disponibles),
            'jugadores_ausentes' => array_map(function (array $j) {
                unset($j['membresia']);

                return $j;
            }, $ausentes),
            'conflictos' => $conflictos,
        ];
    }

    /**
     * Genera la Matriz / Grilla Semanal de Disponibilidad para un equipo (RF.05).
     * Permite configurar el formato de serie (Bo1/Bo3/Bo5) y los minutos por partida para evaluar cada bloque.
     *
     * @param  list<string>  $horasBloques  Horas de inicio a evaluar por día (ej. ['15:00', '16:00', ..., '22:00'])
     * @return array{
     *     equipo_id: int,
     *     equipo_nombre: string,
     *     disciplina: string,
     *     semana_inicio: string,
     *     semana_fin: string,
     *     formato_serie: string,
     *     minutos_por_partida: int,
     *     duracion_bloque_minutos: int,
     *     titulares_requeridos: int,
     *     quorum_minimo_practica: int,
     *     resumen: array{total_bloques: int, bloques_optimos_oficial: int, bloques_aptos_practica: int, bloques_bloqueados: int},
     *     grilla: array<string, array<string, array<string, mixed>>>
     * }
     */
    public function generarGrillaDisponibilidadSemanalEquipo(
        Equipo $equipo,
        CarbonInterface $fechaEnSemana,
        FormatoSerieEnum $formatoSerie = FormatoSerieEnum::BO1,
        int $minutosPorPartida = FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA,
        array $horasBloques = ['15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00'],
    ): array {
        $lunes = CarbonImmutable::instance($fechaEnSemana)->startOfWeek(CarbonInterface::MONDAY);
        $domingo = $lunes->endOfWeek(CarbonInterface::SUNDAY);
        $duracionMinutos = $this->calcularDuracionSerie($formatoSerie, $minutosPorPartida);

        $grilla = [];
        $totalBloques = 0;
        $bloquesOptimosOficial = 0;
        $bloquesAptosPractica = 0;
        $bloquesBloqueados = 0;

        for ($offset = 0; $offset < 7; $offset++) {
            $fechaDia = $lunes->addDays($offset);
            $diaEnum = DiaSemanaEnum::fromIso($fechaDia->dayOfWeekIso);
            $claveDia = $diaEnum->value;
            $grilla[$claveDia] = [];

            foreach ($horasBloques as $horaStr) {
                [$h, $m] = array_pad(explode(':', $horaStr), 2, '00');
                $inicioBloque = $fechaDia->setTime((int) $h, (int) $m, 0);
                $finBloque = $inicioBloque->addMinutes($duracionMinutos);

                $evaluacion = $this->evaluarDisponibilidadEquipoEnVentana($equipo, $inicioBloque, $finBloque);
                $totalBloques++;

                $estadoCelda = 'BLOQUEADO';
                if ($evaluacion['cumple_quorum_oficial'] && ! $evaluacion['conflictos']->contains(fn (ConflictoDetectadoDTO $c) => $c->bloqueaPublicacion())) {
                    $estadoCelda = 'OPTIMO_OFICIAL';
                    $bloquesOptimosOficial++;
                    $bloquesAptosPractica++;
                } elseif ($evaluacion['cumple_quorum_practica']) {
                    $estadoCelda = 'APTO_PRACTICA';
                    $bloquesAptosPractica++;
                } else {
                    $bloquesBloqueados++;
                }

                $grilla[$claveDia][$horaStr] = [
                    'fecha' => $fechaDia->toDateString(),
                    'dia_semana' => $claveDia,
                    'hora_inicio' => $inicioBloque->format('H:i'),
                    'hora_fin' => $finBloque->format('H:i'),
                    'ratio_disponibilidad' => sprintf(
                        '%d/%d',
                        min($evaluacion['disponibles_count'], $evaluacion['titulares_requeridos']),
                        $evaluacion['titulares_requeridos']
                    ),
                    'disponibles_total' => $evaluacion['disponibles_count'],
                    'titulares_disponibles' => $evaluacion['titulares_disponibles_count'],
                    'suplentes_disponibles' => $evaluacion['suplentes_disponibles_count'],
                    'cumple_quorum_practica' => $evaluacion['cumple_quorum_practica'],
                    'cumple_quorum_oficial' => $evaluacion['cumple_quorum_oficial'],
                    'estado_celda' => $estadoCelda,
                    'posiciones_sin_cubrir' => $evaluacion['posiciones_sin_cubrir'],
                    'ausentes' => $evaluacion['jugadores_ausentes'],
                    'disponibles' => $evaluacion['jugadores_disponibles'],
                ];
            }
        }

        return [
            'equipo_id' => $equipo->id,
            'equipo_nombre' => $equipo->nombre,
            'disciplina' => $equipo->disciplina?->nombre ?? 'Sin disciplina',
            'semana_inicio' => $lunes->toDateString(),
            'semana_fin' => $domingo->toDateString(),
            'formato_serie' => $formatoSerie->value,
            'minutos_por_partida' => $minutosPorPartida,
            'duracion_bloque_minutos' => $duracionMinutos,
            'titulares_requeridos' => $equipo->disciplina?->titulares_requeridos ?? 5,
            'quorum_minimo_practica' => $equipo->disciplina?->quorum_minimo_practica ?? 4,
            'resumen' => [
                'total_bloques' => $totalBloques,
                'bloques_optimos_oficial' => $bloquesOptimosOficial,
                'bloques_aptos_practica' => $bloquesAptosPractica,
                'bloques_bloqueados' => $bloquesBloqueados,
            ],
            'grilla' => $grilla,
        ];
    }

    /**
     * Cruza la disponibilidad de DOS equipos (Local vs Visitante) a lo largo de una semana
     * para que el Administrador de Liga y los Coaches vean qué horarios están disponibles
     * y cuáles están bloqueados considerando titulares (mains) y suplentes de ambos planteles.
     *
     * Devuelve una colección de VentanaSugeridaDTO.
     *
     * @param  list<string>  $horasCandidatas
     * @return Collection<int, VentanaSugeridaDTO>
     */
    public function sugerirVentanasParaEnfrentamiento(
        Equipo $equipoLocal,
        Equipo $equipoVisitante,
        CarbonInterface $fechaEnSemana,
        FormatoSerieEnum $formatoSerie = FormatoSerieEnum::BO1,
        int $minutosPorPartida = FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA,
        array $horasCandidatas = ['15:00', '17:00', '19:00', '20:00', '21:00'],
        ?Liga $liga = null,
        ?int $ignorarPartidoId = null,
        bool $soloDisponibles = false,
    ): Collection {
        $lunes = CarbonImmutable::instance($fechaEnSemana)->startOfWeek(CarbonInterface::MONDAY);
        $duracionMinutos = $this->calcularDuracionSerie($formatoSerie, $minutosPorPartida);

        /** @var Collection<int, VentanaSugeridaDTO> $ventanas */
        $ventanas = collect();

        for ($offset = 0; $offset < 7; $offset++) {
            $fechaDia = $lunes->addDays($offset);

            foreach ($horasCandidatas as $horaStr) {
                [$h, $m] = array_pad(explode(':', $horaStr), 2, '00');
                $inicio = $fechaDia->setTime((int) $h, (int) $m, 0);
                $fin = $inicio->addMinutes($duracionMinutos);

                $dtoPropuesta = new ProgramarPartidoDTO(
                    ligaId: $liga?->id,
                    equipoLocalId: $equipoLocal->id,
                    equipoVisitanteId: $equipoVisitante->id,
                    fechaHoraInicio: $inicio,
                    fechaHoraFin: $fin,
                    formatoSerie: $formatoSerie,
                    minutosPorPartida: $minutosPorPartida,
                );

                $conflictos = $this->evaluarPropuestaPartido($dtoPropuesta, $ignorarPartidoId);
                $tieneViolacionDura = $conflictos->contains(fn (ConflictoDetectadoDTO $c) => $c->bloqueaPublicacion());
                $tieneAdvertencias = $conflictos->isNotEmpty();

                $disponible = ! $tieneViolacionDura;
                $etiqueta = match (true) {
                    $tieneViolacionDura => 'BLOQUEADO (Conflicto Duro)',
                    $tieneAdvertencias => 'DISPONIBLE CON ADVERTENCIA (Suplente / Descanso)',
                    default => 'DISPONIBLE ÓPTIMO (Mains y Suplentes Libres)',
                };

                if ($soloDisponibles && ! $disponible) {
                    continue;
                }

                $ventanas->push(new VentanaSugeridaDTO(
                    fecha: $fechaDia->toDateString(),
                    horaInicio: $inicio->format('H:i'),
                    horaFin: $fin->format('H:i'),
                    disponible: $disponible,
                    etiquetaEstado: $etiqueta,
                    conflictos: $conflictos,
                ));
            }
        }

        return $ventanas;
    }

    /**
     * Evalúa una propuesta de partido (ProgramarPartidoDTO) antes de guardarla o al simular un cambio de horario.
     *
     * Reglas evaluadas:
     *  1. Choque multi-liga de jugadores convocados o del plantel (VIOLACION_DURA).
     *  2. Bloqueos horarios recurrentes o unitarios sin suplente disponible para cubrir el rol (VIOLACION_DURA)
     *     o cubiertos por un suplente (ADVERTENCIA_BLANDA).
     *  3. Falta de quórum / posiciones sin cubrir en el roster aun sumando suplentes (VIOLACION_DURA).
     *  4. Descanso mínimo entre jornadas consecutivas del mismo equipo (< 24h por defecto) (ADVERTENCIA_BLANDA).
     *
     * @return Collection<int, ConflictoDetectadoDTO>
     */
    public function evaluarPropuestaPartido(
        ProgramarPartidoDTO $dto,
        ?int $ignorarPartidoId = null,
    ): Collection {
        /** @var Collection<int, ConflictoDetectadoDTO> $conflictos */
        $conflictos = collect();

        $equiposIds = array_values(array_filter([$dto->equipoLocalId, $dto->equipoVisitanteId]));
        $equipos = Equipo::query()
            ->whereIn('id', $equiposIds)
            ->with(['disciplina', 'membresias.jugador'])
            ->get()
            ->keyBy('id');

        $liga = $dto->ligaId ? Liga::find($dto->ligaId) : null;
        $descansoMinimoHoras = $liga?->descanso_minimo_horas ?? 24;

        foreach ($equiposIds as $equipoId) {
            /** @var Equipo|null $equipo */
            $equipo = $equipos->get($equipoId);
            if (! $equipo) {
                continue;
            }

            $evalEquipo = $this->evaluarDisponibilidadEquipoEnVentana(
                $equipo,
                $dto->fechaHoraInicio,
                $dto->fechaHoraFin,
                $ignorarPartidoId
            );

            $conflictos = $conflictos->concat($evalEquipo['conflictos']);

            // Verificar si aun usando suplentes disponibles queda alguna posición descubierta
            if ($dto->ligaId !== null && ! $evalEquipo['cumple_quorum_oficial']) {
                $faltantes = implode(', ', $evalEquipo['posiciones_sin_cubrir']);
                $conflictos->push(new ConflictoDetectadoDTO(
                    tipo: TipoConflictoEnum::VIOLACION_DURA,
                    codigoRegla: 'SIN_QUORUM_NI_SUPLENTE_DISPONIBLE',
                    mensaje: sprintf(
                        'El equipo %s no alcanza alineación completa (%d/%d disponibles). Posiciones sin titular ni suplente disponible en este bloque: %s.',
                        $equipo->nombre,
                        $evalEquipo['disponibles_count'],
                        $evalEquipo['titulares_requeridos'],
                        $faltantes !== '' ? $faltantes : 'Quórum insuficiente',
                    ),
                    userId: null,
                    nombreJugador: null,
                    equipoId: $equipo->id,
                    nombreEquipo: $equipo->nombre,
                    partidoConflictoId: null,
                    rangoHorario: sprintf('%s %s-%s', $dto->fechaHoraInicio->format('Y-m-d'), $dto->fechaHoraInicio->format('H:i'), $dto->fechaHoraFin->format('H:i')),
                    puntosPenalizacion: 20,
                ));
            }

            // Verificar regla de descanso mínimo entre jornadas para este equipo
            $conflictosDescanso = $this->evaluarDescansoMinimoEquipo(
                $equipo,
                $dto->fechaHoraInicio,
                $dto->fechaHoraFin,
                $descansoMinimoHoras,
                $ignorarPartidoId
            );

            $conflictos = $conflictos->concat($conflictosDescanso);
        }

        return $conflictos->values();
    }

    /**
     * Evalúa un Partido ya persistido en la base de datos (RF.09).
     * Revisa tanto a los jugadores explícitamente citados al partido como las reglas de descanso del equipo.
     *
     * @return Collection<int, ConflictoDetectadoDTO>
     */
    public function validarPartidoExistente(Partido $partido): Collection
    {
        $partido->loadMissing(['liga', 'equipoLocal.disciplina', 'equipoVisitante.disciplina', 'citaciones.jugador', 'citaciones.equipo']);

        $inicio = CarbonImmutable::instance($partido->fecha_hora_inicio);
        $fin = CarbonImmutable::instance($partido->fecha_hora_fin);
        $horaIni = $inicio->format('H:i:s');
        $horaFin = $fin->format('H:i:s');

        /** @var Collection<int, ConflictoDetectadoDTO> $conflictos */
        $conflictos = collect();

        $citacionesActivas = $partido->citaciones
            ->filter(fn ($c) => $c->estado_respuesta !== EstadoCitacionEnum::RECHAZADO && $c->jugador !== null);

        // Si el partido aún no tiene citaciones individuales creadas, evaluamos usando el plantel de ambos equipos
        if ($citacionesActivas->isEmpty()) {
            $formato = $partido->formato_serie ?? FormatoSerieEnum::BO1;
            $minPorPartida = $partido->minutos_por_partida ?? FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA;

            return $this->evaluarPropuestaPartido(new ProgramarPartidoDTO(
                ligaId: $partido->liga_id,
                equipoLocalId: $partido->equipo_local_id,
                equipoVisitanteId: $partido->equipo_visitante_id,
                fechaHoraInicio: $inicio,
                fechaHoraFin: $fin,
                tipo: $partido->tipo,
                estado: $partido->estado,
                jornadaNumero: $partido->jornada_numero,
                bloqueEtiqueta: $partido->bloque_etiqueta,
                lugar: $partido->lugar,
                formatoSerie: $formato,
                minutosPorPartida: $minPorPartida,
            ), $partido->id);
        }

        $userIds = $citacionesActivas->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        // 1. Cruce con otros partidos en la misma ventana horaria (Choque Multi-Liga)
        $otrosPartidos = Partido::query()
            ->ocupanAgenda()
            ->where('id', '!=', $partido->id)
            ->solapaCon($inicio, $fin)
            ->whereHas('citaciones', function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)
                    ->where('estado_respuesta', '!=', EstadoCitacionEnum::RECHAZADO->value);
            })
            ->with(['liga', 'citaciones' => fn ($q) => $q->whereIn('user_id', $userIds)])
            ->get();

        foreach ($citacionesActivas as $citacion) {
            $jugador = $citacion->jugador;
            $equipoNombre = $citacion->equipo?->nombre ?? $partido->equipoLocal?->nombre ?? 'Equipo';

            foreach ($otrosPartidos as $otro) {
                $citadoEnOtro = $otro->citaciones->contains(fn ($c) => (int) $c->user_id === (int) $jugador->id);
                if (! $citadoEnOtro) {
                    continue;
                }

                $conflictos->push(new ConflictoDetectadoDTO(
                    tipo: TipoConflictoEnum::VIOLACION_DURA,
                    codigoRegla: 'CHOQUE_MULTI_LIGA',
                    mensaje: sprintf(
                        'El jugador %s (%s) tiene un partido %s en %s el %s (%s - %s) — solapa con %s.',
                        $jugador->name,
                        $equipoNombre,
                        $otro->estado->value,
                        $otro->nombreLigaOPractica(),
                        $otro->fecha_hora_inicio?->format('d/m/Y'),
                        $otro->fecha_hora_inicio?->format('H:i'),
                        $otro->fecha_hora_fin?->format('H:i'),
                        $partido->nombreLigaOPractica(),
                    ),
                    userId: $jugador->id,
                    nombreJugador: $jugador->name,
                    equipoId: $citacion->equipo_id,
                    nombreEquipo: $equipoNombre,
                    partidoConflictoId: $otro->id,
                    rangoHorario: sprintf(
                        '%s %s-%s',
                        $otro->fecha_hora_inicio?->format('Y-m-d'),
                        $otro->fecha_hora_inicio?->format('H:i'),
                        $otro->fecha_hora_fin?->format('H:i'),
                    ),
                    puntosPenalizacion: 25,
                ));
            }
        }

        // 2. Cruce con bloqueos horarios (recurrentes o unitarios) de los jugadores citados
        $bloqueos = BloqueoHorario::query()
            ->whereIn('user_id', $userIds)
            ->paraFecha($inicio)
            ->solapaConHora($horaIni, $horaFin)
            ->get()
            ->groupBy('user_id');

        foreach ($citacionesActivas as $citacion) {
            $jugador = $citacion->jugador;
            $equipoNombre = $citacion->equipo?->nombre ?? $partido->equipoLocal?->nombre ?? 'Equipo';
            $bloqueosJugador = $bloqueos->get((int) $jugador->id, collect());

            foreach ($bloqueosJugador as $bloqueo) {
                $hIni = substr((string) $bloqueo->hora_inicio, 0, 5);
                $hFin = substr((string) $bloqueo->hora_fin, 0, 5);

                $conflictos->push(new ConflictoDetectadoDTO(
                    tipo: TipoConflictoEnum::VIOLACION_DURA,
                    codigoRegla: $bloqueo->es_recurrente ? 'BLOQUEO_HORARIO_RECURRENTE' : 'BLOQUEO_HORARIO_UNITARIO',
                    mensaje: sprintf(
                        'El jugador citado %s (%s) tiene un bloqueo %s (%s) de %s a %s.',
                        $jugador->name,
                        $equipoNombre,
                        $bloqueo->es_recurrente ? 'recurrente' : 'unitario',
                        $bloqueo->motivo ?? 'Académico',
                        $hIni,
                        $hFin,
                    ),
                    userId: $jugador->id,
                    nombreJugador: $jugador->name,
                    equipoId: $citacion->equipo_id,
                    nombreEquipo: $equipoNombre,
                    partidoConflictoId: $partido->id,
                    rangoHorario: "{$inicio->format('Y-m-d')} {$hIni}-{$hFin}",
                    puntosPenalizacion: 15,
                ));
            }
        }

        // 3. Descanso mínimo entre jornadas para equipo local y visitante
        $descansoMinimoHoras = $partido->liga?->descanso_minimo_horas ?? 24;
        foreach (array_filter([$partido->equipoLocal, $partido->equipoVisitante]) as $equipo) {
            $conflictos = $conflictos->concat(
                $this->evaluarDescansoMinimoEquipo($equipo, $inicio, $fin, $descansoMinimoHoras, $partido->id)
            );
        }

        return $conflictos->values();
    }

    /**
     * Valida el Fixture completo de una Liga (como en los mockups de Figma de Admin de Liga):
     *  - Detecta todas las violaciones duras y advertencias blandas por partido.
     *  - Calcula métricas de calidad del fixture (equilibrio de localía, descansos mínimos y penalización total).
     *  - Indica si el fixture puede publicarse (`puede_publicarse = false` si existe al menos 1 violación dura).
     *  - Opcionalmente sincroniza el estado `conflicto` en los partidos tentativos que presenten violación dura.
     *
     * @return array{
     *     liga_id: int,
     *     liga_nombre: string,
     *     temporada: string,
     *     tolerancia_penalizacion_max: int,
     *     penalizacion_total: int,
     *     dentro_de_tolerancia: bool,
     *     violaciones_duras_count: int,
     *     advertencias_blandas_count: int,
     *     puede_publicarse: bool,
     *     metricas: array{equilibrio_localia_pct: int, descansos_minimos_pct: int},
     *     conflictos: list<array<string, mixed>>,
     *     partidos_evaluados: list<array<string, mixed>>
     * }
     */
    public function validarFixtureLiga(Liga $liga, bool $sincronizarEstados = false): array
    {
        $liga->loadMissing(['disciplina', 'partidos.equipoLocal', 'partidos.equipoVisitante', 'partidos.citaciones.jugador', 'partidos.citaciones.equipo']);

        /** @var Collection<int, ConflictoDetectadoDTO> $todosLosConflictos */
        $todosLosConflictos = collect();
        $partidosEvaluados = [];
        $partidosSinDescansoInsuficiente = 0;

        foreach ($liga->partidos as $partido) {
            $conflictosPartido = $this->validarPartidoExistente($partido);

            // Evitar duplicar la misma advertencia de descanso en ambos sentidos (Jornada A -> B y Jornada B -> A)
            $conflictosFiltrados = $conflictosPartido->reject(function (ConflictoDetectadoDTO $c) use ($partido) {
                return $c->codigoRegla === 'DESCANSO_INSUFICIENTE'
                    && $c->partidoConflictoId !== null
                    && $c->partidoConflictoId > $partido->id;
            })->values();

            $tieneViolacionDura = $conflictosFiltrados->contains(fn (ConflictoDetectadoDTO $c) => $c->bloqueaPublicacion());
            $tieneFaltaDescanso = $conflictosFiltrados->contains(fn (ConflictoDetectadoDTO $c) => $c->codigoRegla === 'DESCANSO_INSUFICIENTE');

            if (! $tieneFaltaDescanso) {
                $partidosSinDescansoInsuficiente++;
            }

            if ($sincronizarEstados && in_array($partido->estado, [EstadoPartidoEnum::TENTATIVO, EstadoPartidoEnum::CONFLICTO], true)) {
                $nuevoEstado = $tieneViolacionDura ? EstadoPartidoEnum::CONFLICTO : EstadoPartidoEnum::TENTATIVO;
                if ($partido->estado !== $nuevoEstado) {
                    $partido->update(['estado' => $nuevoEstado]);
                }
            }

            $todosLosConflictos = $todosLosConflictos->concat($conflictosFiltrados);

            $partidosEvaluados[] = [
                'partido_id' => $partido->id,
                'jornada' => $partido->jornada_numero,
                'local' => $partido->equipoLocal?->nombre,
                'visitante' => $partido->equipoVisitante?->nombre,
                'fecha_hora_inicio' => $partido->fecha_hora_inicio?->format('Y-m-d H:i'),
                'fecha_hora_fin' => $partido->fecha_hora_fin?->format('Y-m-d H:i'),
                'formato_serie' => ($partido->formato_serie ?? FormatoSerieEnum::BO1)->value,
                'duracion_minutos' => (int) $partido->fecha_hora_inicio?->diffInMinutes($partido->fecha_hora_fin),
                'estado' => $partido->estado->value,
                'tiene_violacion_dura' => $tieneViolacionDura,
                'conflictos' => $conflictosFiltrados->map(fn (ConflictoDetectadoDTO $c) => $c->toArray())->all(),
            ];
        }

        // Evaluar advertencia blanda de desequilibrio de localía (ej. equipo con >= 2 partidos seguidos de visita/local)
        $advertenciasLocalia = $this->evaluarEquilibrioLocaliaLiga($liga);
        $todosLosConflictos = $todosLosConflictos->concat($advertenciasLocalia['conflictos']);

        $violacionesDuras = $todosLosConflictos->filter(fn (ConflictoDetectadoDTO $c) => $c->bloqueaPublicacion())->values();
        $advertenciasBlandas = $todosLosConflictos->reject(fn (ConflictoDetectadoDTO $c) => $c->bloqueaPublicacion())->values();

        $penalizacionTotal = (int) $todosLosConflictos->sum(fn (ConflictoDetectadoDTO $c) => $c->puntosPenalizacion);
        $totalPartidos = max(1, $liga->partidos->count());
        $descansosPct = (int) round(($partidosSinDescansoInsuficiente / $totalPartidos) * 100);

        return [
            'liga_id' => $liga->id,
            'liga_nombre' => $liga->nombre,
            'temporada' => $liga->temporada,
            'tolerancia_penalizacion_max' => $liga->tolerancia_penalizacion_max,
            'penalizacion_total' => $penalizacionTotal,
            'dentro_de_tolerancia' => $penalizacionTotal <= $liga->tolerancia_penalizacion_max,
            'violaciones_duras_count' => $violacionesDuras->count(),
            'advertencias_blandas_count' => $advertenciasBlandas->count(),
            'puede_publicarse' => $violacionesDuras->isEmpty(),
            'metricas' => [
                'equilibrio_localia_pct' => $advertenciasLocalia['porcentaje_equilibrio'],
                'descansos_minimos_pct' => $descansosPct,
            ],
            'conflictos' => $todosLosConflictos->map(fn (ConflictoDetectadoDTO $c) => $c->toArray())->values()->all(),
            'partidos_evaluados' => $partidosEvaluados,
        ];
    }

    /**
     * Resuelve qué jugador disponible cubre cada posición oficial de la disciplina:
     *  - Prioridad 1: Jugador TITULAR (Main) disponible cuya posición principal coincida.
     *  - Prioridad 2: Jugador SUPLENTE o EN_PRUEBA disponible cuya posición principal o secundaria cubra el puesto.
     *
     * @param  list<string>  $posicionesRequeridas
     * @param  Collection<int, array<string, mixed>>  $jugadoresDisponibles
     * @return array{
     *     posiciones_cubiertas: array<string, array{jugador_id: int, nombre: string, rol_original: string, es_reemplazo_suplente: bool}>,
     *     posiciones_sin_cubrir: list<string>
     * }
     */
    private function resolverCoberturaDePosiciones(array $posicionesRequeridas, Collection $jugadoresDisponibles): array
    {
        if ($posicionesRequeridas === []) {
            return [
                'posiciones_cubiertas' => [],
                'posiciones_sin_cubrir' => [],
            ];
        }

        $usados = [];
        $cubiertas = [];
        $sinCubrir = [];

        // Pasada 1: Asignar Titulares (Mains) disponibles en su posición principal
        foreach ($posicionesRequeridas as $posicion) {
            $posUpper = strtoupper($posicion);

            $titular = $jugadoresDisponibles->first(function (array $j) use ($posUpper, $usados) {
                return ! in_array($j['user_id'], $usados, true)
                    && $j['rol_membresia'] === RolMembresiaEnum::TITULAR->value
                    && strtoupper((string) $j['posicion_principal']) === $posUpper;
            });

            if ($titular !== null) {
                $usados[] = $titular['user_id'];
                $cubiertas[$posUpper] = [
                    'jugador_id' => $titular['user_id'],
                    'nombre' => $titular['nombre'],
                    'rol_original' => $titular['rol_membresia'],
                    'es_reemplazo_suplente' => false,
                ];
            }
        }

        // Pasada 2: Cubrir vacantes o ausencias de titulares con Suplentes / En Prueba disponibles
        foreach ($posicionesRequeridas as $posicion) {
            $posUpper = strtoupper($posicion);
            if (isset($cubiertas[$posUpper])) {
                continue;
            }

            $reemplazo = $jugadoresDisponibles->first(function (array $j) use ($posUpper, $usados) {
                if (in_array($j['user_id'], $usados, true)) {
                    return false;
                }

                /** @var EquipoJugador $membresia */
                $membresia = $j['membresia'];

                return $membresia->puedeCubrirPosicion($posUpper);
            });

            if ($reemplazo !== null) {
                $usados[] = $reemplazo['user_id'];
                $cubiertas[$posUpper] = [
                    'jugador_id' => $reemplazo['user_id'],
                    'nombre' => $reemplazo['nombre'],
                    'rol_original' => $reemplazo['rol_membresia'],
                    'es_reemplazo_suplente' => true,
                ];
            } else {
                $sinCubrir[] = $posUpper;
            }
        }

        return [
            'posiciones_cubiertas' => $cubiertas,
            'posiciones_sin_cubrir' => $sinCubrir,
        ];
    }

    /**
     * Verifica si un equipo tiene otro partido oficial a menos de $descansoMinimoHoras de distancia.
     *
     * @return Collection<int, ConflictoDetectadoDTO>
     */
    private function evaluarDescansoMinimoEquipo(
        Equipo $equipo,
        CarbonImmutable $inicio,
        CarbonImmutable $fin,
        int $descansoMinimoHoras,
        ?int $ignorarPartidoId = null,
    ): Collection {
        if ($descansoMinimoHoras <= 0) {
            return collect();
        }

        $ventanaDesde = $inicio->subHours($descansoMinimoHoras);
        $ventanaHasta = $fin->addHours($descansoMinimoHoras);

        $partidosCercanos = Partido::query()
            ->ocupanAgenda()
            ->delEquipo($equipo->id)
            ->when($ignorarPartidoId !== null, fn ($q) => $q->where('id', '!=', $ignorarPartidoId))
            ->where('fecha_hora_inicio', '<', $ventanaHasta)
            ->where('fecha_hora_fin', '>', $ventanaDesde)
            ->get();

        /** @var Collection<int, ConflictoDetectadoDTO> $advertencias */
        $advertencias = collect();

        foreach ($partidosCercanos as $otro) {
            $otroInicio = CarbonImmutable::instance($otro->fecha_hora_inicio);
            $otroFin = CarbonImmutable::instance($otro->fecha_hora_fin);

            // Si se solapan directamente, eso ya es un choque duro; aquí medimos descanso entre partidos distintos
            if ($otroInicio < $fin && $otroFin > $inicio) {
                continue;
            }

            // Medimos horas entre inicios de jornada (como en el mockup: Mar 20:00 a Mié 19:00 = 23h < 24h)
            $horasEntreInicios = (int) abs($inicio->diffInHours($otroInicio));

            if ($horasEntreInicios < $descansoMinimoHoras) {
                $advertencias->push(new ConflictoDetectadoDTO(
                    tipo: TipoConflictoEnum::ADVERTENCIA_BLANDA,
                    codigoRegla: 'DESCANSO_INSUFICIENTE',
                    mensaje: sprintf(
                        '%s: descanso insuficiente entre partidos (%s y %s) — solo %dh, mínimo recomendado %dh.',
                        $equipo->nombre,
                        $otroInicio->format('d/m H:i'),
                        $inicio->format('d/m H:i'),
                        $horasEntreInicios,
                        $descansoMinimoHoras,
                    ),
                    userId: null,
                    nombreJugador: null,
                    equipoId: $equipo->id,
                    nombreEquipo: $equipo->nombre,
                    partidoConflictoId: $otro->id,
                    rangoHorario: sprintf('%s / %s', $otroInicio->format('Y-m-d H:i'), $inicio->format('Y-m-d H:i')),
                    puntosPenalizacion: 14,
                ));
            }
        }

        return $advertencias;
    }

    /**
     * Evalúa el equilibrio de localía en una liga.
     *
     * @return array{porcentaje_equilibrio: int, conflictos: Collection<int, ConflictoDetectadoDTO>}
     */
    private function evaluarEquilibrioLocaliaLiga(Liga $liga): array
    {
        $partidos = $liga->partidos->sortBy('fecha_hora_inicio')->values();
        if ($partidos->isEmpty()) {
            return ['porcentaje_equilibrio' => 100, 'conflictos' => collect()];
        }

        /** @var Collection<int, ConflictoDetectadoDTO> $conflictos */
        $conflictos = collect();
        $rachasPorEquipo = [];

        foreach ($partidos as $partido) {
            foreach ([
                [$partido->equipo_local_id, $partido->equipoLocal?->nombre, 'local'],
                [$partido->equipo_visitante_id, $partido->equipoVisitante?->nombre, 'visitante'],
            ] as [$eqId, $eqNombre, $condicion]) {
                if (! $eqId) {
                    continue;
                }

                $anterior = $rachasPorEquipo[$eqId] ?? ['condicion' => null, 'conteo' => 0];
                if ($anterior['condicion'] === $condicion) {
                    $anterior['conteo']++;
                } else {
                    $anterior = ['condicion' => $condicion, 'conteo' => 1];
                }
                $rachasPorEquipo[$eqId] = $anterior;

                if ($anterior['conteo'] >= 3) {
                    $conflictos->push(new ConflictoDetectadoDTO(
                        tipo: TipoConflictoEnum::ADVERTENCIA_BLANDA,
                        codigoRegla: 'DESEQUILIBRIO_LOCALIA',
                        mensaje: sprintf(
                            '%s acumula %d partidos consecutivos como %s.',
                            $eqNombre ?? "Equipo #{$eqId}",
                            $anterior['conteo'],
                            $condicion
                        ),
                        equipoId: (int) $eqId,
                        nombreEquipo: $eqNombre,
                        partidoConflictoId: $partido->id,
                        puntosPenalizacion: 5,
                    ));
                }
            }
        }

        $porcentaje = max(70, 100 - ($conflictos->count() * 10));

        return [
            'porcentaje_equilibrio' => $porcentaje,
            'conflictos' => $conflictos,
        ];
    }
}
