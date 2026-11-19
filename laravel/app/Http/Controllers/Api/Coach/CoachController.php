<?php

namespace App\Http\Controllers\Api\Coach;

use App\Enums\DiaSemanaEnum;
use App\Enums\EstadoCitacionEnum;
use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\FormatoSerieEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\TipoPartidoEnum;
use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\BloqueoHorario;
use App\Models\Citacion;
use App\Models\Equipo;
use App\Models\EquipoJugador;
use App\Models\Partido;
use App\Models\User;
use App\Services\Disponibilidad\MotorConflictosService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controlador de API REST para la aplicación Flutter de Playbook Coach.
 *
 * Cubre los 4 módulos de la app:
 *  - Heatmap de Disponibilidad Semanal
 *  - Prácticas Programadas (listar, agendar, cancelar)
 *  - Nómina y Citaciones (listar eventos, intercambiar titular/suplente, actualizar estado)
 *  - Plantel del Equipo (cobertura de posiciones y miembros por rol)
 */
class CoachController extends Controller
{
    private const MESES_CORTOS = [
        1 => 'ene',
        2 => 'feb',
        3 => 'mar',
        4 => 'abr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'ago',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'dic',
    ];

    public function __construct(
        private readonly MotorConflictosService $motorConflictos,
    ) {}

    /**
     * GET /api/coach/equipos/{equipoId}/heatmap?semana=YYYY-Www
     */
    public function heatmap(int $equipoId, Request $request): JsonResponse
    {
        $equipo = $this->resolverEquipoAutorizado($equipoId, $request);
        if ($equipo instanceof JsonResponse) {
            return $equipo;
        }

        $equipo->loadMissing(['disciplina', 'membresias.jugador']);

        $semanaParam = trim((string) $request->query('semana', ''));
        $lunes = $this->parsearSemanaIsoALunes($semanaParam);
        $semanaIso = $this->formatearSemanaIso($lunes);
        $semanaLabel = $this->formatearRangoSemana($lunes);

        $quorumMinimo = (int) ($equipo->disciplina?->quorum_minimo_practica ?? 4);
        $titularesRequeridos = (int) ($equipo->disciplina?->titulares_requeridos ?? 5);

        $membresiasAceptadas = $equipo->membresias
            ->filter(fn (EquipoJugador $m) => $m->estado_invitacion === EstadoInvitacionEnum::ACEPTADA && $m->jugador !== null)
            ->values();

        $totalJugadores = max($titularesRequeridos, $membresiasAceptadas->count());

        $horas = ['15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00'];
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $bloques = [];

        foreach ($horas as $horaStr) {
            [$h, $m] = array_pad(explode(':', $horaStr), 2, '00');

            for ($offset = 0; $offset < 7; $offset++) {
                $fechaDia = $lunes->addDays($offset);
                $diaSemana = $offset + 1;
                $diaNombre = $dias[$offset];

                $inicioBloque = $fechaDia->setTime((int) $h, (int) $m, 0);
                $finBloque = $inicioBloque->addMinutes(60);

                $evaluacion = $this->motorConflictos->evaluarDisponibilidadEquipoEnVentana(
                    $equipo,
                    $inicioBloque,
                    $finBloque,
                );

                $disponibles = array_map(function (array $j): array {
                    return [
                        'id' => (int) $j['user_id'],
                        'nombre' => (string) $j['nombre'],
                        'nickname' => null,
                        'rol' => $this->formatearPosicionLegible((string) ($j['posicion_principal'] ?? 'Flex')),
                    ];
                }, $evaluacion['jugadores_disponibles']);

                $ausencias = [];
                foreach ($evaluacion['jugadores_ausentes'] as $jAusente) {
                    $motivos = $jAusente['motivos_ausencia'] ?? [];
                    $primerMotivo = $motivos[0] ?? null;
                    $esCruce = ($primerMotivo['tipo_bloqueo'] ?? '') === 'partido';

                    if ($esCruce) {
                        $motivoTexto = (string) ($primerMotivo['detalle'] ?? 'Cruce con partido oficial');
                        $badge = strtoupper((string) ($primerMotivo['etiqueta'] ?? 'PARTIDO OFICIAL'));
                    } else {
                        $etiquetaOriginal = (string) ($primerMotivo['etiqueta'] ?? 'Académico');
                        $motivoLimpio = trim(preg_replace('/\s*\([^)]*\)$/', '', $etiquetaOriginal) ?? $etiquetaOriginal);
                        $motivoTexto = (string) ($primerMotivo['detalle'] ?? 'Falta de disponibilidad');
                        $badge = strtoupper($motivoLimpio !== '' ? $motivoLimpio : 'ACADÉMICO');
                    }

                    $ausencias[] = [
                        'jugador_id' => (int) $jAusente['user_id'],
                        'nombre_jugador' => (string) $jAusente['nombre'],
                        'motivo' => $motivoTexto,
                        'categoria_badge' => $badge,
                        'es_cruce_partido' => $esCruce,
                    ];
                }

                $bloques[] = [
                    'id' => "{$semanaIso}-{$diaSemana}-{$horaStr}",
                    'dia_semana' => $diaSemana,
                    'dia_nombre' => $diaNombre,
                    'hora' => $horaStr,
                    'fecha' => $fechaDia->toDateString(),
                    'disponibles_count' => count($disponibles),
                    'total_jugadores' => $totalJugadores,
                    'quorum_minimo' => $quorumMinimo,
                    'disponibles' => $disponibles,
                    'ausencias' => $ausencias,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'equipo_id' => $equipo->id,
                'semana_iso' => $semanaIso,
                'semana_label' => $semanaLabel,
                'fecha_inicio' => $lunes->toDateString(),
                'quorum_minimo' => $quorumMinimo,
                'total_jugadores' => $totalJugadores,
                'horas' => $horas,
                'dias' => $dias,
                'bloques' => $bloques,
            ],
        ]);
    }

    /**
     * GET /api/coach/equipos/{equipoId}/practicas?semana=YYYY-Www
     */
    public function listarPracticas(int $equipoId, Request $request): JsonResponse
    {
        $equipo = $this->resolverEquipoAutorizado($equipoId, $request);
        if ($equipo instanceof JsonResponse) {
            return $equipo;
        }

        $equipo->loadMissing('disciplina');

        $semanaParam = trim((string) $request->query('semana', ''));

        $practicas = Partido::query()
            ->where('tipo', TipoPartidoEnum::PRACTICA->value)
            ->where(function ($q) use ($equipo) {
                $q->where('equipo_local_id', $equipo->id)
                    ->orWhere('equipo_visitante_id', $equipo->id);
            })
            ->with(['equipoLocal.disciplina', 'equipoVisitante', 'citaciones.jugador'])
            ->orderBy('fecha_hora_inicio')
            ->get();

        if ($semanaParam !== '') {
            $practicas = $practicas
                ->filter(fn (Partido $p) => $this->formatearSemanaIso(CarbonImmutable::instance($p->fecha_hora_inicio)) === $semanaParam)
                ->values();
        }

        $payload = $practicas
            ->map(fn (Partido $p) => $this->serializarPractica($p, $equipo))
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    /**
     * POST /api/coach/practicas
     */
    public function agendarPractica(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
            'semana_iso' => ['nullable', 'string', 'max:20'],
            'dia_semana' => ['nullable', 'integer', 'min:1', 'max:7'],
            'fecha' => ['required', 'string'],
            'hora_inicio' => ['required', 'string', 'regex:/^\d{1,2}:\d{2}$/'],
            'hora_fin' => ['nullable', 'string', 'regex:/^\d{1,2}:\d{2}$/'],
            'serie' => ['nullable', 'string', Rule::in(FormatoSerieEnum::values())],
            'rival' => ['required', 'string', 'max:120'],
            'modalidad' => ['nullable', 'string', 'max:100'],
            'servidor' => ['nullable', 'string', 'max:150'],
            'notas' => ['nullable', 'string', 'max:500'],
            'citar_disponibles' => ['nullable', 'boolean'],
            'jugadores_citados' => ['nullable', 'array'],
            'jugadores_citados.*' => ['string'],
        ]);

        $equipo = $this->resolverEquipoAutorizado((int) $validated['equipo_id'], $request);
        if ($equipo instanceof JsonResponse) {
            return $equipo;
        }

        $equipo->loadMissing(['disciplina', 'membresias.jugador']);

        $serieEnum = isset($validated['serie'])
            ? (FormatoSerieEnum::tryFrom(strtolower($validated['serie'])) ?? FormatoSerieEnum::BO1)
            : FormatoSerieEnum::BO1;

        $duracionHoras = match ($serieEnum) {
            FormatoSerieEnum::BO1 => 1,
            FormatoSerieEnum::BO3 => 2,
            FormatoSerieEnum::BO5 => 3,
        };

        $fechaBase = CarbonImmutable::parse($validated['fecha']);
        [$hIni, $mIni] = array_pad(explode(':', $validated['hora_inicio']), 2, '00');
        $inicio = $fechaBase->setTime((int) $hIni, (int) $mIni, 0);

        if (! empty($validated['hora_fin'])) {
            [$hFin, $mFin] = array_pad(explode(':', $validated['hora_fin']), 2, '00');
            $fin = $fechaBase->setTime((int) $hFin, (int) $mFin, 0);
            if ($fin->lessThanOrEqualTo($inicio)) {
                $fin = $inicio->addHours($duracionHoras);
            }
        } else {
            $fin = $inicio->addHours($duracionHoras);
        }

        $rivalNombre = trim($validated['rival']);
        $modalidad = trim((string) ($validated['modalidad'] ?? 'Scrim competitivo'));
        $servidor = trim((string) ($validated['servidor'] ?? 'Discord + Sala Privada'));
        $notas = isset($validated['notas']) && trim($validated['notas']) !== ''
            ? trim($validated['notas'])
            : null;
        $citarDisponibles = (bool) ($validated['citar_disponibles'] ?? true);

        // Si el rival coincide con un equipo existente distinto, vinculamos equipo_visitante_id
        $equipoRival = Equipo::query()
            ->where('id', '!=', $equipo->id)
            ->whereRaw('LOWER(nombre) = ?', [strtolower($rivalNombre)])
            ->first();

        $metadataEtiqueta = mb_substr("{$rivalNombre} || {$modalidad}", 0, 50);

        $partido = DB::transaction(function () use (
            $equipo,
            $equipoRival,
            $serieEnum,
            $duracionHoras,
            $metadataEtiqueta,
            $servidor,
            $inicio,
            $fin,
            $notas,
            $citarDisponibles,
            $validated
        ): Partido {
            $nuevoPartido = Partido::create([
                'liga_id' => null,
                'equipo_local_id' => $equipo->id,
                'equipo_visitante_id' => $equipoRival?->id,
                'tipo' => TipoPartidoEnum::PRACTICA,
                'formato_serie' => $serieEnum,
                'minutos_por_partida' => (int) (($duracionHoras * 60) / max(1, $serieEnum->maxPartidas())),
                'jornada_numero' => null,
                'bloque_etiqueta' => $metadataEtiqueta,
                'lugar' => $servidor,
                'fecha_hora_inicio' => $inicio,
                'fecha_hora_fin' => $fin,
                'estado' => EstadoPartidoEnum::PROGRAMADO,
            ]);

            if ($citarDisponibles) {
                $nombresSolicitados = array_map(
                    fn (string $n) => strtolower(trim($n)),
                    $validated['jugadores_citados'] ?? []
                );

                $membresiasAceptadas = $equipo->membresias
                    ->filter(fn (EquipoJugador $m) => $m->estado_invitacion === EstadoInvitacionEnum::ACEPTADA && $m->jugador !== null)
                    ->values();

                if ($nombresSolicitados !== []) {
                    $seleccionados = $membresiasAceptadas->filter(function (EquipoJugador $m) use ($nombresSolicitados): bool {
                        $nombreJugador = strtolower(trim((string) $m->jugador?->name));
                        foreach ($nombresSolicitados as $solicitado) {
                            if ($solicitado === $nombreJugador || str_contains($solicitado, $nombreJugador) || str_contains($nombreJugador, $solicitado)) {
                                return true;
                            }
                        }

                        return false;
                    });

                    if ($seleccionados->isEmpty()) {
                        $seleccionados = $membresiasAceptadas;
                    }
                } else {
                    $evaluacion = $this->motorConflictos->evaluarDisponibilidadEquipoEnVentana($equipo, $inicio, $fin, $nuevoPartido->id);
                    $idsDisponibles = array_column($evaluacion['jugadores_disponibles'], 'user_id');
                    $seleccionados = $membresiasAceptadas->filter(
                        fn (EquipoJugador $m) => in_array((int) $m->user_id, $idsDisponibles, true)
                    );
                    if ($seleccionados->isEmpty()) {
                        $seleccionados = $membresiasAceptadas;
                    }
                }

                foreach ($seleccionados as $membresia) {
                    $tipoConvocatoria = $membresia->rol_membresia === RolMembresiaEnum::SUPLENTE
                        ? RolMembresiaEnum::SUPLENTE
                        : RolMembresiaEnum::TITULAR;

                    Citacion::create([
                        'partido_id' => $nuevoPartido->id,
                        'user_id' => $membresia->user_id,
                        'equipo_id' => $equipo->id,
                        'tipo_convocatoria' => $tipoConvocatoria,
                        'mensaje_coach' => $notas,
                        'estado_respuesta' => EstadoCitacionEnum::CONFIRMADO,
                        'respondido_en' => now(),
                    ]);
                }
            }

            return $nuevoPartido->load(['equipoLocal.disciplina', 'equipoVisitante', 'citaciones.jugador']);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Práctica agendada exitosamente.',
            'data' => $this->serializarPractica($partido, $equipo, $rivalNombre, $modalidad, $notas),
        ], 201);
    }

    /**
     * PATCH /api/coach/practicas/{practicaId}/cancelar
     */
    public function cancelarPractica(int $practicaId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $practica = Partido::query()
            ->where('id', $practicaId)
            ->where('tipo', TipoPartidoEnum::PRACTICA->value)
            ->with(['equipoLocal.disciplina', 'equipoVisitante', 'citaciones.jugador'])
            ->first();

        if (! $practica) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró la práctica solicitada.',
            ], 404);
        }

        $autorizacion = $this->resolverEquipoAutorizado((int) $practica->equipo_local_id, $request);
        if ($autorizacion instanceof JsonResponse) {
            return $autorizacion;
        }

        $motivo = isset($validated['motivo']) && trim($validated['motivo']) !== ''
            ? trim($validated['motivo'])
            : 'Cancelada por el coach';

        $practica->update([
            'estado' => EstadoPartidoEnum::CANCELADO,
        ]);

        if ($practica->citaciones->isNotEmpty()) {
            Citacion::where('partido_id', $practica->id)->update([
                'mensaje_coach' => $motivo,
            ]);
            $practica->load('citaciones.jugador');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Práctica cancelada correctamente.',
            'data' => $this->serializarPractica($practica, $autorizacion, motivoCancelacionOverride: $motivo),
        ]);
    }

    /**
     * GET /api/coach/equipos/{equipoId}/nomina
     */
    public function listarNomina(int $equipoId, Request $request): JsonResponse
    {
        $equipo = $this->resolverEquipoAutorizado($equipoId, $request);
        if ($equipo instanceof JsonResponse) {
            return $equipo;
        }

        $equipo->loadMissing(['disciplina', 'membresias.jugador']);

        $eventos = Partido::query()
            ->delEquipo($equipo->id)
            ->whereNotIn('estado', [
                EstadoPartidoEnum::BORRADOR->value,
                EstadoPartidoEnum::CANCELADO->value,
            ])
            ->with([
                'liga',
                'equipoLocal',
                'equipoVisitante',
                'citaciones' => fn ($q) => $q->where('equipo_id', $equipo->id)->with('jugador'),
            ])
            ->orderBy('fecha_hora_inicio')
            ->get();

        $data = $eventos
            ->map(fn (Partido $p) => $this->serializarEventoNomina($p, $equipo))
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * POST /api/coach/nomina/{eventoId}/intercambiar
     */
    public function intercambiarNomina(int $eventoId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titular_id' => ['required', 'integer'],
            'suplente_id' => ['required', 'integer', 'different:titular_id'],
        ]);

        $partido = Partido::with(['liga', 'equipoLocal', 'equipoVisitante', 'citaciones.jugador'])
            ->find($eventoId);

        if (! $partido) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró el evento solicitado.',
            ], 404);
        }

        $equipo = $this->resolverEquipoDeEventoParaCoach($partido, $request);
        if ($equipo instanceof JsonResponse) {
            return $equipo;
        }

        $equipo->loadMissing(['disciplina', 'membresias.jugador']);

        $titularId = (int) $validated['titular_id'];
        $suplenteId = (int) $validated['suplente_id'];

        $citacionTitular = Citacion::query()
            ->where('partido_id', $partido->id)
            ->where('user_id', $titularId)
            ->first();

        $citacionSuplente = Citacion::query()
            ->where('partido_id', $partido->id)
            ->where('user_id', $suplenteId)
            ->first();

        // Si el suplente pertenece al plantel del equipo pero aún no tenía registro en citaciones, lo creamos
        if (! $citacionSuplente) {
            $esMiembro = $equipo->membresias->firstWhere('user_id', $suplenteId);
            if ($esMiembro) {
                $citacionSuplente = Citacion::create([
                    'partido_id' => $partido->id,
                    'user_id' => $suplenteId,
                    'equipo_id' => $equipo->id,
                    'tipo_convocatoria' => RolMembresiaEnum::SUPLENTE,
                    'mensaje_coach' => $citacionTitular?->mensaje_coach,
                    'estado_respuesta' => EstadoCitacionEnum::CONFIRMADO,
                    'respondido_en' => now(),
                ]);
            }
        }

        if (! $citacionTitular || ! $citacionSuplente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Selecciona un titular y un suplente válidos para intercambiar.',
            ], 422);
        }

        DB::transaction(function () use ($citacionTitular, $citacionSuplente): void {
            $citacionTitular->update([
                'tipo_convocatoria' => RolMembresiaEnum::SUPLENTE,
            ]);

            $citacionSuplente->update([
                'tipo_convocatoria' => RolMembresiaEnum::TITULAR,
            ]);
        });

        $partido->load([
            'liga',
            'equipoLocal',
            'equipoVisitante',
            'citaciones' => fn ($q) => $q->where('equipo_id', $equipo->id)->with('jugador'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Intercambio de nómina aplicado correctamente.',
            'data' => $this->serializarEventoNomina($partido, $equipo),
        ]);
    }

    /**
     * PATCH /api/coach/nomina/{eventoId}/citaciones/{jugadorId}
     */
    public function actualizarEstadoCitacion(int $eventoId, int $jugadorId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'estado_respuesta' => ['required', 'string', Rule::in(EstadoCitacionEnum::values())],
        ]);

        $partido = Partido::with(['liga', 'equipoLocal', 'equipoVisitante'])->find($eventoId);

        if (! $partido) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró el evento solicitado.',
            ], 404);
        }

        $equipo = $this->resolverEquipoDeEventoParaCoach($partido, $request);
        if ($equipo instanceof JsonResponse) {
            return $equipo;
        }

        $equipo->loadMissing(['disciplina', 'membresias.jugador']);

        $citacion = Citacion::query()
            ->where('partido_id', $partido->id)
            ->where('user_id', $jugadorId)
            ->first();

        if (! $citacion) {
            $esMiembro = $equipo->membresias->firstWhere('user_id', $jugadorId);
            if (! $esMiembro) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jugador no encontrado en la citación.',
                ], 404);
            }

            $citacion = Citacion::create([
                'partido_id' => $partido->id,
                'user_id' => $jugadorId,
                'equipo_id' => $equipo->id,
                'tipo_convocatoria' => $esMiembro->rol_membresia === RolMembresiaEnum::TITULAR
                    ? RolMembresiaEnum::TITULAR
                    : RolMembresiaEnum::SUPLENTE,
                'estado_respuesta' => EstadoCitacionEnum::from($validated['estado_respuesta']),
                'respondido_en' => now(),
            ]);
        } else {
            $nuevoEstado = EstadoCitacionEnum::from($validated['estado_respuesta']);
            $citacion->update([
                'estado_respuesta' => $nuevoEstado,
                'respondido_en' => $nuevoEstado === EstadoCitacionEnum::PENDIENTE ? null : now(),
            ]);
        }

        $partido->load([
            'liga',
            'equipoLocal',
            'equipoVisitante',
            'citaciones' => fn ($q) => $q->where('equipo_id', $equipo->id)->with('jugador'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Estado de citación actualizado.',
            'data' => $this->serializarEventoNomina($partido, $equipo),
        ]);
    }

    /**
     * GET /api/coach/equipos/{equipoId}/plantel
     */
    public function plantel(int $equipoId, Request $request): JsonResponse
    {
        $equipo = $this->resolverEquipoAutorizado($equipoId, $request);
        if ($equipo instanceof JsonResponse) {
            return $equipo;
        }

        $equipo->loadMissing(['disciplina', 'membresias.jugador']);

        $posicionesRequeridas = array_map(
            fn (string $pos) => $this->formatearPosicionLegible($pos),
            $equipo->disciplina?->posiciones_validas ?? []
        );

        // Consideramos miembros con invitación aceptada o en plantel para que el coach vea todo su roster
        $membresias = $equipo->membresias
            ->filter(fn (EquipoJugador $m) => $m->jugador !== null && $m->estado_invitacion !== EstadoInvitacionEnum::RECHAZADA)
            ->sortBy(function (EquipoJugador $m): int {
                return match ($m->rol_membresia) {
                    RolMembresiaEnum::TITULAR => 1,
                    RolMembresiaEnum::SUPLENTE => 2,
                    default => 3,
                };
            })
            ->values();

        $primerTitularId = $membresias
            ->firstWhere('rol_membresia', RolMembresiaEnum::TITULAR)
            ?->user_id;

        $miembros = $membresias->map(function (EquipoJugador $m) use ($primerTitularId): array {
            $rolStr = $m->rol_membresia instanceof RolMembresiaEnum
                ? $m->rol_membresia->value
                : (string) $m->rol_membresia;

            return [
                'id' => (int) $m->user_id,
                'nombre' => (string) $m->jugador->name,
                'nickname' => null,
                'rol_membresia' => $rolStr,
                'posicion_principal' => $this->formatearPosicionLegible((string) ($m->posicion_principal ?? 'Flex')),
                'posicion_secundaria' => $m->posicion_secundaria
                    ? $this->formatearPosicionLegible((string) $m->posicion_secundaria)
                    : null,
                'rango' => (string) ($m->rango_declarativo ?: 'Sin clasificar'),
                'es_capitan' => $primerTitularId !== null && (int) $m->user_id === (int) $primerTitularId,
            ];
        })->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'equipo_id' => $equipo->id,
                'posiciones_requeridas' => $posicionesRequeridas,
                'miembros' => $miembros,
            ],
        ]);
    }

    /**
     * Verifica que el equipo exista y que el usuario autenticado tenga permisos para gestionarlo.
     */
    private function resolverEquipoAutorizado(int $equipoId, Request $request): Equipo|JsonResponse
    {
        $equipo = Equipo::find($equipoId);
        if (! $equipo) {
            return response()->json([
                'status' => 'error',
                'message' => 'El equipo solicitado no existe.',
            ], 404);
        }

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autenticado.',
            ], 401);
        }

        if ($user->role === UserRoleEnum::COACH && $equipo->coach_user_id !== null && (int) $equipo->coach_user_id !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permisos para gestionar un equipo dirigido por otro coach.',
            ], 403);
        }

        return $equipo;
    }

    /**
     * Resuelve cuál de los equipos involucrados en el partido pertenece al coach autenticado.
     */
    private function resolverEquipoDeEventoParaCoach(Partido $partido, Request $request): Equipo|JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && $user->role === UserRoleEnum::COACH) {
            if ($partido->equipoLocal && (int) $partido->equipoLocal->coach_user_id === (int) $user->id) {
                return $partido->equipoLocal;
            }
            if ($partido->equipoVisitante && (int) $partido->equipoVisitante->coach_user_id === (int) $user->id) {
                return $partido->equipoVisitante;
            }
        }

        $equipoId = (int) ($partido->equipo_local_id ?: $partido->equipo_visitante_id);

        return $this->resolverEquipoAutorizado($equipoId, $request);
    }

    /**
     * Serializa un modelo Partido (tipo=practica) al contrato JSON esperado por Practica.fromJson en Flutter.
     *
     * @return array<string, mixed>
     */
    private function serializarPractica(
        Partido $partido,
        Equipo $equipoContexto,
        ?string $rivalOverride = null,
        ?string $modalidadOverride = null,
        ?string $notasOverride = null,
        ?string $motivoCancelacionOverride = null,
    ): array {
        $inicio = CarbonImmutable::instance($partido->fecha_hora_inicio);
        $fin = CarbonImmutable::instance($partido->fecha_hora_fin);
        $diaEnum = DiaSemanaEnum::fromIso($inicio->dayOfWeekIso);

        $rival = $rivalOverride;
        $modalidad = $modalidadOverride;

        if (($rival === null || $modalidad === null) && $partido->bloque_etiqueta) {
            if (str_contains($partido->bloque_etiqueta, '||')) {
                [$parsedRival, $parsedModalidad] = array_map('trim', explode('||', $partido->bloque_etiqueta, 2));
                $rival ??= ($parsedRival !== '' ? $parsedRival : null);
                $modalidad ??= ($parsedModalidad !== '' ? $parsedModalidad : null);
            } else {
                $rival ??= trim($partido->bloque_etiqueta);
            }
        }

        if ($rival === null || $rival === '') {
            $rival = $partido->nombreRivalPara($equipoContexto->id);
        }

        $modalidad ??= 'Scrim competitivo';

        $serieStr = $partido->formato_serie instanceof FormatoSerieEnum
            ? $partido->formato_serie->value
            : (string) ($partido->formato_serie ?: 'bo1');

        $estadoPartido = $partido->estado instanceof EstadoPartidoEnum
            ? $partido->estado
            : EstadoPartidoEnum::tryFrom((string) $partido->estado) ?? EstadoPartidoEnum::PROGRAMADO;

        $estadoPractica = match ($estadoPartido) {
            EstadoPartidoEnum::CANCELADO => 'cancelada',
            EstadoPartidoEnum::FINALIZADO => 'completada',
            default => 'programada',
        };

        $citacionesEquipo = $partido->citaciones
            ->filter(fn (Citacion $c) => $c->equipo_id === null || (int) $c->equipo_id === (int) $equipoContexto->id)
            ->values();

        $jugadoresCitados = $citacionesEquipo
            ->map(fn (Citacion $c) => $c->jugador?->name)
            ->filter()
            ->values()
            ->all();

        $primerMensaje = $citacionesEquipo->first()?->mensaje_coach;
        $notas = $notasOverride ?? ($estadoPractica !== 'cancelada' ? $primerMensaje : null);
        $motivoCancelacion = $motivoCancelacionOverride ?? ($estadoPractica === 'cancelada' ? ($primerMensaje ?: 'Cancelada por el coach') : null);

        $disciplinaNombre = $equipoContexto->disciplina?->nombre ?? '';
        $equipoDisplay = $disciplinaNombre !== ''
            ? "{$equipoContexto->nombre} — {$disciplinaNombre}"
            : $equipoContexto->nombre;

        return [
            'id' => $partido->id,
            'equipo_id' => $equipoContexto->id,
            'equipo_nombre' => $equipoDisplay,
            'semana_iso' => $this->formatearSemanaIso($inicio),
            'dia_semana' => $inicio->dayOfWeekIso,
            'dia_nombre' => $diaEnum->value,
            'fecha' => $inicio->toDateString(),
            'hora_inicio' => $inicio->format('H:i'),
            'hora_fin' => $fin->format('H:i'),
            'serie' => $serieStr,
            'rival' => $rival,
            'modalidad' => $modalidad,
            'servidor' => $partido->lugar ?: 'Discord + Sala Privada',
            'notas' => $notas,
            'jugadores_citados' => $jugadoresCitados,
            'estado' => $estadoPractica,
            'motivo_cancelacion' => $motivoCancelacion,
        ];
    }

    /**
     * Serializa un Partido (oficial o práctica) al contrato EventoNomina esperado por Flutter.
     * Incluye titulares, suplentes del plantel y detección de conflictos horarios en la ventana del partido.
     *
     * @return array<string, mixed>
     */
    private function serializarEventoNomina(Partido $partido, Equipo $equipo): array
    {
        $inicio = CarbonImmutable::instance($partido->fecha_hora_inicio);
        $fin = CarbonImmutable::instance($partido->fecha_hora_fin);

        $esPractica = $partido->tipo === TipoPartidoEnum::PRACTICA;
        $tipoStr = $esPractica ? 'practica' : 'partido';

        if ($esPractica) {
            $rival = $partido->nombreRivalPara($equipo->id);
            if ($partido->bloque_etiqueta && str_contains($partido->bloque_etiqueta, '||')) {
                [$parsedRival, $parsedModalidad] = array_map('trim', explode('||', $partido->bloque_etiqueta, 2));
                $titulo = "Scrim vs {$parsedRival}";
                $ligaOCompetencia = $parsedModalidad ?: 'Práctica de equipo';
            } else {
                $titulo = $rival !== 'Práctica interna' ? "Scrim vs {$rival}" : "Práctica · {$equipo->nombre}";
                $ligaOCompetencia = 'Sesión de entrenamiento';
            }
        } else {
            $localNombre = $partido->equipoLocal?->nombre ?? $equipo->nombre;
            $visitanteNombre = $partido->equipoVisitante?->nombre ?? 'Rival';
            $titulo = "{$localNombre} vs {$visitanteNombre}";
            $ligaNombre = $partido->liga?->nombre ?? 'Competencia Oficial';
            $ligaOCompetencia = $partido->jornada_numero
                ? "{$ligaNombre} · Jornada {$partido->jornada_numero}"
                : $ligaNombre;
        }

        $membresiasPorUser = $equipo->membresias
            ->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA)
            ->keyBy('user_id');

        $citacionesExistentes = $partido->citaciones
            ->filter(fn (Citacion $c) => $c->equipo_id === null || (int) $c->equipo_id === (int) $equipo->id)
            ->keyBy('user_id');

        $mensajeCoach = $citacionesExistentes->first()?->mensaje_coach;

        // Evaluamos conflictos reales en la ventana del evento para enriquecer cada citación
        $conflictosPorJugador = $this->detectarConflictosDeJugadoresEnEvento($equipo, $partido, $inicio, $fin);

        $citacionesPayload = [];

        foreach ($citacionesExistentes as $userId => $citacion) {
            $jugador = $citacion->jugador;
            if (! $jugador) {
                continue;
            }
            /** @var EquipoJugador|null $membresia */
            $membresia = $membresiasPorUser->get($userId);

            $tipoConvocatoria = $citacion->tipo_convocatoria instanceof RolMembresiaEnum
                ? $citacion->tipo_convocatoria
                : RolMembresiaEnum::tryFrom((string) $citacion->tipo_convocatoria) ?? RolMembresiaEnum::TITULAR;

            $esTitular = $tipoConvocatoria === RolMembresiaEnum::TITULAR;
            $estadoResp = $citacion->estado_respuesta instanceof EstadoCitacionEnum
                ? $citacion->estado_respuesta->value
                : (string) $citacion->estado_respuesta;

            $conflictoInfo = $conflictosPorJugador[(int) $userId] ?? null;

            $citacionesPayload[] = [
                'jugador_id' => (int) $jugador->id,
                'nombre' => (string) $jugador->name,
                'nickname' => null,
                'posicion' => $this->formatearPosicionLegible((string) ($membresia?->posicion_principal ?? 'Flex')),
                'rol_membresia' => $esTitular ? 'Titular' : 'Suplente',
                'es_titular' => $esTitular,
                'estado_respuesta' => $estadoResp,
                'motivo_respuesta' => $conflictoInfo['motivo'] ?? null,
                'conflicto_horario' => $conflictoInfo['badge'] ?? null,
            ];
        }

        // Incluimos también a los suplentes aceptados del plantel que aún no tengan fila en `citaciones`
        // para que el coach pueda promoverlos/intercambiarlos desde la UI de Flutter.
        foreach ($membresiasPorUser as $userId => $membresia) {
            if ($citacionesExistentes->has($userId) || ! $membresia->jugador) {
                continue;
            }

            $esTitular = false;
            $conflictoInfo = $conflictosPorJugador[(int) $userId] ?? null;

            $citacionesPayload[] = [
                'jugador_id' => (int) $membresia->user_id,
                'nombre' => (string) $membresia->jugador->name,
                'nickname' => null,
                'posicion' => $this->formatearPosicionLegible((string) ($membresia->posicion_principal ?? 'Flex')),
                'rol_membresia' => 'Suplente',
                'es_titular' => $esTitular,
                'estado_respuesta' => EstadoCitacionEnum::CONFIRMADO->value,
                'motivo_respuesta' => $conflictoInfo['motivo'] ?? null,
                'conflicto_horario' => $conflictoInfo['badge'] ?? null,
            ];
        }

        return [
            'id' => $partido->id,
            'equipo_id' => $equipo->id,
            'tipo' => $tipoStr,
            'titulo' => $titulo,
            'liga_o_competencia' => $ligaOCompetencia,
            'fecha' => $inicio->toDateString(),
            'hora_inicio' => $inicio->format('H:i'),
            'hora_fin' => $fin->format('H:i'),
            'lugar_o_servidor' => $partido->lugar ?: 'Online · Servidor LAS',
            'mensaje_coach' => $mensajeCoach,
            'citaciones' => array_values($citacionesPayload),
        ];
    }

    /**
     * Detecta si algún jugador del equipo tiene choque con otro partido o bloqueo horario
     * durante la ventana del evento.
     *
     * @return array<int, array{badge: string, motivo: string}>
     */
    private function detectarConflictosDeJugadoresEnEvento(
        Equipo $equipo,
        Partido $eventoActual,
        CarbonImmutable $inicio,
        CarbonImmutable $fin,
    ): array {
        $userIds = $equipo->membresias->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        if ($userIds === []) {
            return [];
        }

        $resultado = [];

        // 1. Choques con otros partidos
        $otrosPartidos = Partido::query()
            ->ocupanAgenda()
            ->where('id', '!=', $eventoActual->id)
            ->solapaCon($inicio, $fin)
            ->whereHas('citaciones', fn ($q) => $q->whereIn('user_id', $userIds))
            ->with(['liga.disciplina', 'citaciones' => fn ($q) => $q->whereIn('user_id', $userIds)])
            ->get();

        foreach ($otrosPartidos as $otro) {
            $disciplinaChoque = strtoupper((string) ($otro->liga?->disciplina?->nombre ?? $otro->nombreLigaOPractica()));
            $horaChoque = $otro->fecha_hora_inicio?->format('H:i') ?? '20:00';

            foreach ($otro->citaciones as $cit) {
                $uid = (int) $cit->user_id;
                $resultado[$uid] = [
                    'badge' => "PARTIDO {$disciplinaChoque} {$horaChoque}",
                    'motivo' => 'Cruce horario con '.$otro->nombreLigaOPractica(),
                ];
            }
        }

        // 2. Bloqueos horarios académicos/personales
        $bloqueos = BloqueoHorario::query()
            ->whereIn('user_id', $userIds)
            ->paraFecha($inicio)
            ->solapaConHora($inicio->format('H:i:s'), $fin->format('H:i:s'))
            ->get();

        foreach ($bloqueos as $bloq) {
            $uid = (int) $bloq->user_id;
            if (! isset($resultado[$uid])) {
                $resultado[$uid] = [
                    'badge' => strtoupper((string) ($bloq->motivo ?: 'ACADÉMICO')),
                    'motivo' => (string) ($bloq->motivo ?: 'Bloqueo de horario'),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Convierte "2026-W46" o una fecha ISO al lunes 00:00 de esa semana.
     */
    private function parsearSemanaIsoALunes(string $semanaParam): CarbonImmutable
    {
        if (preg_match('/^(\d{4})-W(\d{1,2})$/i', $semanaParam, $matches)) {
            $year = (int) $matches[1];
            $week = max(1, min(53, (int) $matches[2]));

            return CarbonImmutable::now()
                ->setISODate($year, $week, CarbonInterface::MONDAY)
                ->startOfDay();
        }

        if ($semanaParam !== '') {
            try {
                return CarbonImmutable::parse($semanaParam)
                    ->startOfWeek(CarbonInterface::MONDAY)
                    ->startOfDay();
            } catch (\Throwable) {
                // Fallback a la semana actual
            }
        }

        return CarbonImmutable::now()
            ->startOfWeek(CarbonInterface::MONDAY)
            ->startOfDay();
    }

    private function formatearSemanaIso(CarbonImmutable $fecha): string
    {
        return sprintf('%04d-W%02d', $fecha->isoWeekYear, $fecha->isoWeek);
    }

    private function formatearRangoSemana(CarbonImmutable $lunes): string
    {
        $domingo = $lunes->addDays(6);
        $mesInicio = self::MESES_CORTOS[$lunes->month] ?? 'nov';
        $mesFin = self::MESES_CORTOS[$domingo->month] ?? 'nov';

        if ($lunes->year !== $domingo->year) {
            return sprintf(
                '%d %s %d – %d %s %d',
                $lunes->day,
                $mesInicio,
                $lunes->year,
                $domingo->day,
                $mesFin,
                $domingo->year
            );
        }

        return sprintf(
            '%d %s – %d %s %d',
            $lunes->day,
            $mesInicio,
            $domingo->day,
            $mesFin,
            $domingo->year
        );
    }

    private function formatearPosicionLegible(string $posicion): string
    {
        $upper = strtoupper(trim($posicion));
        if ($upper === 'ADC') {
            return 'ADC';
        }

        return mb_convert_case(mb_strtolower($upper), MB_CASE_TITLE, 'UTF-8');
    }
}
