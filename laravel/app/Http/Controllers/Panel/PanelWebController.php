<?php

namespace App\Http\Controllers\Panel;

use App\DTOs\Torneos\ProgramarPartidoDTO;
use App\Enums\EstadoCitacionEnum;
use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\FormatoSerieEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clubes\UpdateRosterMemberRequest;
use App\Http\Requests\Torneos\StorePartidoRequest;
use App\Models\Citacion;
use App\Models\Club;
use App\Models\Disciplina;
use App\Models\Equipo;
use App\Models\EquipoJugador;
use App\Models\InscripcionLiga;
use App\Models\Liga;
use App\Models\Partido;
use App\Models\User;
use App\Services\Disponibilidad\MotorConflictosService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador del panel web de Playbook (administración de ligas, clubes y equipos).
 * Conecta las vistas Blade con el motor de disponibilidad y gestión de partidos.
 */
class PanelWebController extends Controller
{
    public function __construct(
        private readonly MotorConflictosService $motor,
    ) {}

    /**
     * GET /
     * Muestra el fixture por liga con validación de reglas y el programador de horarios.
     */
    public function fixture(Request $request): View
    {
        $partidos = Partido::query()
            ->with(['liga.disciplina', 'equipoLocal', 'equipoVisitante'])
            ->orderBy('fecha_hora_inicio')
            ->get();

        $ligas = Liga::query()
            ->with(['disciplina', 'partidos.equipoLocal', 'partidos.equipoVisitante', 'partidos.citaciones.jugador', 'partidos.citaciones.equipo'])
            ->orderBy('id')
            ->get();

        // Liga activa para validar (por defecto la primera o la seleccionada en el filtro)
        $ligaSeleccionadaId = (int) $request->query('liga_id', $ligas->first()?->id ?? 0);
        $ligaActiva = $ligas->firstWhere('id', $ligaSeleccionadaId) ?? $ligas->first();

        $reportesPorLiga = [];
        foreach ($ligas as $liga) {
            $reportesPorLiga[$liga->id] = $this->motor->validarFixtureLiga($liga, sincronizarEstados: false);
        }

        $reporteActivo = $ligaActiva ? ($reportesPorLiga[$ligaActiva->id] ?? null) : null;

        // Parámetros para buscar ventanas de horario entre dos equipos
        $equipos = Equipo::query()->with(['club', 'disciplina'])->orderBy('nombre')->get();
        $localId = (int) $request->query('local_id', $equipos->firstWhere('nombre', 'UDD Titans')?->id ?? $equipos->first()?->id ?? 0);
        $visitanteId = (int) $request->query('visitante_id', $equipos->firstWhere('nombre', 'PUC Paladins')?->id ?? $equipos->skip(1)->first()?->id ?? 0);
        $semanaStr = (string) $request->query('semana', '2026-11-03');
        $formatoStr = strtolower((string) $request->query('formato_serie', FormatoSerieEnum::BO3->value));
        $formatoSerie = FormatoSerieEnum::tryFrom($formatoStr) ?? FormatoSerieEnum::BO3;
        $minutosPorPartida = max(15, min(120, (int) $request->query('minutos_por_partida', FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA)));
        $soloDisponibles = $request->boolean('solo_disponibles', false);
        $partidoReprogramarId = $request->filled('reprogramar_id') ? (int) $request->query('reprogramar_id') : null;
        $partidoReprogramar = $partidoReprogramarId ? Partido::with(['equipoLocal', 'equipoVisitante', 'liga'])->find($partidoReprogramarId) : null;

        if ($partidoReprogramar) {
            $localId = $partidoReprogramar->equipo_local_id;
            $visitanteId = (int) $partidoReprogramar->equipo_visitante_id;
            if ($partidoReprogramar->liga_id) {
                $ligaActiva = $ligas->firstWhere('id', $partidoReprogramar->liga_id) ?? $ligaActiva;
                $reporteActivo = $ligaActiva ? ($reportesPorLiga[$ligaActiva->id] ?? null) : null;
            }
        }

        $equipoLocal = $equipos->firstWhere('id', $localId);
        $equipoVisitante = $equipos->firstWhere('id', $visitanteId);

        $ventanasSugeridas = collect();
        if ($equipoLocal && $equipoVisitante && $equipoLocal->id !== $equipoVisitante->id) {
            $ventanasSugeridas = $this->motor->sugerirVentanasParaEnfrentamiento(
                equipoLocal: $equipoLocal,
                equipoVisitante: $equipoVisitante,
                fechaEnSemana: CarbonImmutable::parse($semanaStr),
                formatoSerie: $formatoSerie,
                minutosPorPartida: $minutosPorPartida,
                horasCandidatas: ['16:00', '18:00', '20:00'],
                liga: $ligaActiva,
                ignorarPartidoId: $partidoReprogramar?->id,
                soloDisponibles: $soloDisponibles,
            );
        }

        $usuarioActual = $request->user();
        $puedeGestionarLiga = $this->puedeGestionarLiga($usuarioActual);

        return view('panel.fixture', [
            'grupos' => $partidos->groupBy(fn (Partido $p) => $p->nombreLigaOPractica())
                ->sortBy(fn ($grupo) => $grupo->first()->liga === null),
            'tentativos' => $partidos->where('estado', EstadoPartidoEnum::TENTATIVO)->count(),
            'ligas' => $ligas,
            'ligaActiva' => $ligaActiva,
            'reporteActivo' => $reporteActivo,
            'reportesPorLiga' => $reportesPorLiga,
            'equipos' => $equipos,
            'equipoLocalSeleccionado' => $equipoLocal,
            'equipoVisitanteSeleccionado' => $equipoVisitante,
            'semanaSeleccionada' => $semanaStr,
            'formatoSeleccionado' => $formatoSerie,
            'minutosSeleccionados' => $minutosPorPartida,
            'duracionCalculada' => $this->motor->calcularDuracionSerie($formatoSerie, $minutosPorPartida),
            'soloDisponibles' => $soloDisponibles,
            'ventanasSugeridas' => $ventanasSugeridas,
            'partidoReprogramar' => $partidoReprogramar,
            'puedeGestionarLiga' => $puedeGestionarLiga,
        ]);
    }

    /**
     * POST /ligas/{liga}/revalidar
     * Ejecuta el motor de conflictos sincronizando el estado 'conflicto' / 'tentativo' en la BD.
     */
    public function revalidarLiga(Request $request, Liga $liga): RedirectResponse
    {
        if (! $this->puedeGestionarLiga($request->user())) {
            return redirect()->route('fixture', ['liga_id' => $liga->id])
                ->with('error', 'Solo el Administrador de Liga puede revalidar o publicar el fixture oficial.');
        }

        $reporte = $this->motor->validarFixtureLiga($liga, sincronizarEstados: true);

        if ($reporte['violaciones_duras_count'] > 0) {
            return redirect()
                ->route('fixture', ['liga_id' => $liga->id])
                ->with('error', sprintf(
                    'Se detectaron %d violación(es) dura(s) en %s. Resuelve los choques antes de publicar.',
                    $reporte['violaciones_duras_count'],
                    $liga->nombre
                ));
        }

        return redirect()
            ->route('fixture', ['liga_id' => $liga->id])
            ->with('ok', sprintf(
                'Fixture de %s validado sin violaciones duras. Listo para publicar.',
                $liga->nombre
            ));
    }

    /**
     * POST /ligas/{liga}/publicar
     * Publica oficialmente todos los partidos tentativos/programados de la liga si no existen violaciones duras.
     */
    public function publicarFixture(Request $request, Liga $liga): RedirectResponse
    {
        if (! $this->puedeGestionarLiga($request->user())) {
            return redirect()->route('fixture', ['liga_id' => $liga->id])
                ->with('error', 'Solo el Administrador de Liga puede publicar el fixture oficial.');
        }

        $reporte = $this->motor->validarFixtureLiga($liga, sincronizarEstados: true);

        if (! $reporte['puede_publicarse']) {
            return redirect()
                ->route('fixture', ['liga_id' => $liga->id])
                ->with('error', sprintf(
                    'No se puede publicar el fixture de %s: existen %d violación(es) dura(s) activas.',
                    $liga->nombre,
                    $reporte['violaciones_duras_count']
                ));
        }

        $actualizados = Partido::query()
            ->where('liga_id', $liga->id)
            ->whereIn('estado', [
                EstadoPartidoEnum::BORRADOR->value,
                EstadoPartidoEnum::TENTATIVO->value,
                EstadoPartidoEnum::PROGRAMADO->value,
            ])
            ->update(['estado' => EstadoPartidoEnum::PUBLICADO->value]);

        return redirect()
            ->route('fixture', ['liga_id' => $liga->id])
            ->with('ok', sprintf(
                'Fixture de %s publicado oficialmente (%d partidos publicados).',
                $liga->nombre,
                $actualizados
            ));
    }

    /**
     * POST /partidos
     * Programa un nuevo partido oficial o práctica validando choques con el Motor de Conflictos.
     */
    public function storePartido(StorePartidoRequest $request): RedirectResponse
    {
        $dto = $request->toDTO();
        $conflictos = $this->motor->evaluarPropuestaPartido($dto);
        $tieneViolacionDura = $conflictos->contains(fn ($c) => $c->bloqueaPublicacion());

        $estadoInicial = $tieneViolacionDura
            ? EstadoPartidoEnum::CONFLICTO
            : EstadoPartidoEnum::TENTATIVO;

        $partido = Partido::create([
            'liga_id' => $dto->ligaId,
            'equipo_local_id' => $dto->equipoLocalId,
            'equipo_visitante_id' => $dto->equipoVisitanteId,
            'tipo' => $dto->tipo->value,
            'formato_serie' => $dto->formatoSerie->value,
            'minutos_por_partida' => $dto->minutosPorPartida,
            'jornada_numero' => $dto->jornadaNumero,
            'bloque_etiqueta' => $dto->bloqueEtiqueta,
            'lugar' => $dto->lugar ?? 'Servidor LAS-1',
            'fecha_hora_inicio' => $dto->fechaHoraInicio,
            'fecha_hora_fin' => $dto->fechaHoraFin,
            'estado' => $estadoInicial->value,
        ]);

        // Notifica con citación a los titulares y jugadores a prueba de ambos equipos
        $this->citarPlantelActivo($partido);

        if ($tieneViolacionDura) {
            $primerMotivo = $conflictos->first(fn ($c) => $c->bloqueaPublicacion())?->mensaje;

            return redirect()
                ->route('fixture', ['liga_id' => $dto->ligaId])
                ->with('error', "Partido registrado en estado de CONFLICTO: {$primerMotivo}");
        }

        return redirect()
            ->route('fixture', ['liga_id' => $dto->ligaId])
            ->with('ok', sprintf(
                'Partido programado (%s · %d min) sin choques duros.',
                $dto->formatoSerie->label(),
                $dto->duracionTotalMinutos()
            ));
    }

    /**
     * PATCH /partidos/{partido}/reprogramar
     * Reprograma el horario y/o formato de serie de un partido existente y reevalúa conflictos.
     */
    public function reprogramarPartido(Request $request, Partido $partido): RedirectResponse
    {
        $validated = $request->validate([
            'fecha_hora_inicio' => ['required', 'date'],
            'formato_serie' => ['nullable', 'string'],
            'minutos_por_partida' => ['nullable', 'integer', 'min:15', 'max:120'],
            'lugar' => ['nullable', 'string', 'max:150'],
        ]);

        $formato = isset($validated['formato_serie'])
            ? (FormatoSerieEnum::tryFrom(strtolower((string) $validated['formato_serie'])) ?? $partido->formato_serie ?? FormatoSerieEnum::BO1)
            : ($partido->formato_serie ?? FormatoSerieEnum::BO1);

        $minutosPorPartida = isset($validated['minutos_por_partida'])
            ? (int) $validated['minutos_por_partida']
            : ($partido->minutos_por_partida ?? FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA);

        $duracion = $this->motor->calcularDuracionSerie($formato, $minutosPorPartida);
        $nuevoInicio = CarbonImmutable::parse((string) $validated['fecha_hora_inicio']);
        $nuevoFin = $nuevoInicio->addMinutes($duracion);

        $dto = new ProgramarPartidoDTO(
            ligaId: $partido->liga_id,
            equipoLocalId: $partido->equipo_local_id,
            equipoVisitanteId: $partido->equipo_visitante_id,
            fechaHoraInicio: $nuevoInicio,
            fechaHoraFin: $nuevoFin,
            tipo: $partido->tipo,
            estado: EstadoPartidoEnum::TENTATIVO,
            jornadaNumero: $partido->jornada_numero,
            bloqueEtiqueta: 'Bloque '.$nuevoInicio->format('H:i'),
            lugar: $validated['lugar'] ?? $partido->lugar,
            formatoSerie: $formato,
            minutosPorPartida: $minutosPorPartida,
        );

        $conflictos = $this->motor->evaluarPropuestaPartido($dto, $partido->id);
        $tieneViolacionDura = $conflictos->contains(fn ($c) => $c->bloqueaPublicacion());

        $partido->update([
            'fecha_hora_inicio' => $nuevoInicio,
            'fecha_hora_fin' => $nuevoFin,
            'formato_serie' => $formato->value,
            'minutos_por_partida' => $minutosPorPartida,
            'bloque_etiqueta' => 'Bloque '.$nuevoInicio->format('H:i'),
            'lugar' => $validated['lugar'] ?? $partido->lugar,
            'estado' => $tieneViolacionDura ? EstadoPartidoEnum::CONFLICTO : EstadoPartidoEnum::TENTATIVO,
        ]);

        if ($tieneViolacionDura) {
            $motivo = $conflictos->first(fn ($c) => $c->bloqueaPublicacion())?->mensaje;

            return redirect()
                ->route('fixture', ['liga_id' => $partido->liga_id])
                ->with('error', "El nuevo horario aún presenta conflicto duro: {$motivo}");
        }

        return redirect()
            ->route('fixture', ['liga_id' => $partido->liga_id])
            ->with('ok', sprintf(
                'Partido #%d reprogramado para el %s (%s - %s). Conflicto resuelto.',
                $partido->id,
                $nuevoInicio->format('d/m/Y'),
                $nuevoInicio->format('H:i'),
                $nuevoFin->format('H:i')
            ));
    }

    /**
     * DELETE /partidos/{partido}
     * Elimina un partido o práctica del fixture (y sus citaciones asociadas en cascada).
     */
    public function destroyPartido(Request $request, Partido $partido): RedirectResponse
    {
        if (! $this->puedeGestionarLiga($request->user())) {
            return redirect()->route('fixture', ['liga_id' => $partido->liga_id])
                ->with('error', 'Solo el Administrador de Liga o Coach puede eliminar un partido programado.');
        }

        $ligaId = $partido->liga_id;
        $etiqueta = sprintf(
            '%s vs %s',
            $partido->equipoLocal?->nombre ?? 'Equipo',
            $partido->equipoVisitante?->nombre ?? 'Práctica'
        );

        $partido->delete();

        return redirect()
            ->route('fixture', $ligaId ? ['liga_id' => $ligaId] : [])
            ->with('ok', "Partido eliminado correctamente ({$etiqueta}).");
    }

    /**
     * GET /equipos
     * Listado general de equipos con filtros por búsqueda, club y disciplina,
     * balance de titulares por posición y alta de nuevos equipos.
     */
    public function equipos(Request $request): View
    {
        $buscar = trim((string) $request->query('q', ''));
        $clubId = $request->filled('club_id') ? (int) $request->query('club_id') : null;
        $disciplinaId = $request->filled('disciplina_id') ? (int) $request->query('disciplina_id') : null;

        $equipos = Equipo::query()
            ->with(['club', 'disciplina', 'coach'])
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%")
                        ->orWhereHas('club', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%")->orWhere('siglas', 'like', "%{$buscar}%"));
                });
            })
            ->when($clubId !== null, fn ($q) => $q->where('club_id', $clubId))
            ->when($disciplinaId !== null, fn ($q) => $q->where('disciplina_id', $disciplinaId))
            ->withCount([
                'membresias',
                'membresias as titulares_count' => fn ($q) => $q
                    ->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA)
                    ->where('rol_membresia', RolMembresiaEnum::TITULAR),
            ])
            ->orderBy('nombre')
            ->get();

        $clubes = Club::orderBy('nombre')->get();
        $disciplinas = Disciplina::orderBy('nombre')->get();
        $coaches = User::where('role', UserRoleEnum::COACH->value)->orderBy('name')->get();

        return view('panel.equipos', [
            'equipos' => $equipos,
            'faltantes' => $equipos->mapWithKeys(fn (Equipo $e) => [$e->id => $e->posicionesSinTitular()])->all(),
            'clubes' => $clubes,
            'disciplinas' => $disciplinas,
            'coaches' => $coaches,
            'filtroBuscar' => $buscar,
            'filtroClubId' => $clubId,
            'filtroDisciplinaId' => $disciplinaId,
        ]);
    }

    /**
     * POST /equipos
     * Registra un nuevo equipo dentro de un club y disciplina.
     */
    public function storeEquipo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'club_id' => ['required', 'integer', 'exists:clubes,id'],
            'disciplina_id' => ['required', 'integer', 'exists:disciplinas,id'],
            'coach_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $equipo = Equipo::create([
            'nombre' => $validated['nombre'],
            'club_id' => (int) $validated['club_id'],
            'disciplina_id' => (int) $validated['disciplina_id'],
            'coach_user_id' => ! empty($validated['coach_user_id']) ? (int) $validated['coach_user_id'] : null,
            'activo' => true,
        ]);

        return redirect()
            ->route('equipos.show', $equipo)
            ->with('ok', "Equipo '{$equipo->nombre}' registrado exitosamente. Ahora puedes agregar e invitar jugadores a su roster.");
    }

    /**
     * GET /equipos/{equipo}
     * Muestra el roster del equipo, gestión de jugadores y la matriz semanal de disponibilidad.
     */
    public function showEquipo(Request $request, Equipo $equipo): View
    {
        $equipo->load(['club', 'disciplina', 'coach', 'membresias.jugador', 'inscripcionesLiga.liga']);

        $titulares = $equipo->membresias
            ->filter(fn (EquipoJugador $m) => $m->estado_invitacion === EstadoInvitacionEnum::ACEPTADA
                && $m->rol_membresia === RolMembresiaEnum::TITULAR
                && $m->posicion_principal !== null)
            ->unique(fn (EquipoJugador $m) => strtoupper($m->posicion_principal))
            ->keyBy(fn (EquipoJugador $m) => strtoupper($m->posicion_principal));

        $semanaStr = (string) $request->query('semana', '2026-11-03');
        $formatoStr = strtolower((string) $request->query('formato_serie', FormatoSerieEnum::BO3->value));
        $formatoSerie = FormatoSerieEnum::tryFrom($formatoStr) ?? FormatoSerieEnum::BO3;
        $minutosPorPartida = max(15, min(120, (int) $request->query('minutos_por_partida', FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA)));

        $grillaDisponibilidad = $this->motor->generarGrillaDisponibilidadSemanalEquipo(
            equipo: $equipo,
            fechaEnSemana: CarbonImmutable::parse($semanaStr),
            formatoSerie: $formatoSerie,
            minutosPorPartida: $minutosPorPartida,
            horasBloques: ['15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'],
        );

        // Jugadores disponibles que todavía no pertenecen a este equipo
        $miembrosUserIds = $equipo->membresias->pluck('user_id')->all();
        $jugadoresDisponiblesParaInvitar = User::query()
            ->where('role', UserRoleEnum::JUGADOR->value)
            ->whereNotIn('id', $miembrosUserIds)
            ->orderBy('name')
            ->get();

        // Ligas de la misma disciplina donde el equipo todavía no está inscrito
        $ligasInscritasIds = $equipo->inscripcionesLiga->pluck('liga_id')->all();
        $ligasDisponibles = Liga::query()
            ->where('disciplina_id', $equipo->disciplina_id)
            ->whereNotIn('id', $ligasInscritasIds)
            ->orderBy('nombre')
            ->get();

        return view('panel.roster', [
            'equipo' => $equipo,
            'posiciones' => $equipo->disciplina?->posiciones_validas ?? [],
            'titulares' => $titulares,
            'resto' => $equipo->membresias->diff($titulares->values()),
            'faltantes' => $equipo->posicionesSinTitular(),
            'grillaDisponibilidad' => $grillaDisponibilidad,
            'semanaSeleccionada' => $semanaStr,
            'formatoSeleccionado' => $formatoSerie,
            'minutosSeleccionados' => $minutosPorPartida,
            'puedeGestionarRoster' => $this->puedeGestionarRoster($request->user(), $equipo),
            'jugadoresDisponiblesParaInvitar' => $jugadoresDisponiblesParaInvitar,
            'ligasDisponibles' => $ligasDisponibles,
        ]);
    }

    /**
     * POST /equipos/{equipo}/inscripciones
     * Inscribe el equipo en una liga de su misma disciplina deportiva.
     */
    public function inscribirEquipoEnLiga(Request $request, Equipo $equipo): RedirectResponse
    {
        if (! $this->puedeGestionarRoster($request->user(), $equipo) && ! $this->puedeGestionarLiga($request->user())) {
            return redirect()->route('equipos.show', $equipo)
                ->with('error', 'No tienes permisos para inscribir este equipo en una liga.');
        }

        $validated = $request->validate([
            'liga_id' => ['required', 'integer', 'exists:ligas,id'],
        ]);

        $liga = Liga::findOrFail((int) $validated['liga_id']);

        if ((int) $liga->disciplina_id !== (int) $equipo->disciplina_id) {
            return redirect()->route('equipos.show', $equipo)
                ->with('error', 'El equipo solo puede inscribirse en ligas de su misma disciplina.');
        }

        InscripcionLiga::updateOrCreate(
            [
                'liga_id' => $liga->id,
                'equipo_id' => $equipo->id,
            ],
            [
                'codigo_invitacion' => Str::upper(Str::random(12)),
                'estado_invitacion' => EstadoInvitacionEnum::ACEPTADA->value,
                'roster_validado' => $equipo->tieneRosterValido(),
                'respondido_en' => now(),
            ]
        );

        return redirect()
            ->route('equipos.show', $equipo)
            ->with('ok', "Equipo '{$equipo->nombre}' inscrito exitosamente en {$liga->nombre}.");
    }

    /**
     * POST /equipos/{equipo}/roster
     * Invita o registra un jugador (existente o nuevo alumno) en el roster del equipo.
     */
    public function storeRosterMember(Request $request, Equipo $equipo): RedirectResponse
    {
        if (! $this->puedeGestionarRoster($request->user(), $equipo)) {
            return redirect()->route('equipos.show', $equipo)
                ->with('error', 'No tienes permisos para agregar jugadores a este equipo.');
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'nuevo_nombre' => ['nullable', 'required_without:user_id', 'string', 'max:100'],
            'nuevo_email' => ['nullable', 'required_without:user_id', 'email', 'max:150', 'unique:users,email'],
            'posicion_principal' => ['required', 'string', 'max:30'],
            'posicion_secundaria' => ['nullable', 'string', 'max:30'],
            'rango_declarativo' => ['nullable', 'string', 'max:50'],
            'rol_membresia' => ['required', 'string', Rule::in(RolMembresiaEnum::values())],
            'estado_invitacion' => ['nullable', 'string', Rule::in(EstadoInvitacionEnum::values())],
        ]);

        if (! empty($validated['user_id'])) {
            $jugador = User::findOrFail((int) $validated['user_id']);
        } else {
            $jugador = User::create([
                'name' => (string) $validated['nuevo_nombre'],
                'email' => (string) $validated['nuevo_email'],
                'password' => Hash::make('password'),
                'role' => UserRoleEnum::JUGADOR->value,
                'activo' => true,
            ]);
        }

        $rolMembresia = RolMembresiaEnum::from((string) $validated['rol_membresia']);
        $estadoInvitacion = ! empty($validated['estado_invitacion'])
            ? EstadoInvitacionEnum::from((string) $validated['estado_invitacion'])
            : EstadoInvitacionEnum::ACEPTADA;

        // Si entra directo como TITULAR ACEPTADO en una posición ocupada, bajamos al anterior a SUPLENTE
        if ($rolMembresia === RolMembresiaEnum::TITULAR && $estadoInvitacion === EstadoInvitacionEnum::ACEPTADA) {
            EquipoJugador::query()
                ->where('equipo_id', $equipo->id)
                ->where('user_id', '!=', $jugador->id)
                ->where('rol_membresia', RolMembresiaEnum::TITULAR->value)
                ->whereRaw('UPPER(posicion_principal) = ?', [strtoupper((string) $validated['posicion_principal'])])
                ->update(['rol_membresia' => RolMembresiaEnum::SUPLENTE->value]);
        }

        $tieneMembresiaPrincipal = EquipoJugador::where('user_id', $jugador->id)->where('es_principal', true)->exists();

        EquipoJugador::updateOrCreate(
            [
                'equipo_id' => $equipo->id,
                'user_id' => $jugador->id,
            ],
            [
                'posicion_principal' => strtoupper((string) $validated['posicion_principal']),
                'posicion_secundaria' => ! empty($validated['posicion_secundaria']) ? strtoupper((string) $validated['posicion_secundaria']) : null,
                'rango_declarativo' => $validated['rango_declarativo'] ?? 'Sin rango',
                'rol_membresia' => $rolMembresia->value,
                'estado_invitacion' => $estadoInvitacion->value,
                'es_principal' => ! $tieneMembresiaPrincipal,
            ]
        );

        return redirect()
            ->route('equipos.show', $equipo)
            ->with('ok', "Jugador {$jugador->name} incorporado al plantel de {$equipo->nombre}.");
    }

    /**
     * DELETE /equipos/{equipo}/roster/{user}
     * Retira a un jugador del roster del equipo.
     */
    public function destroyRosterMember(Request $request, Equipo $equipo, int $user): RedirectResponse
    {
        if (! $this->puedeGestionarRoster($request->user(), $equipo)) {
            return redirect()->route('equipos.show', $equipo)
                ->with('error', 'No tienes permisos para retirar jugadores de este equipo.');
        }

        $membresia = EquipoJugador::query()
            ->where('equipo_id', $equipo->id)
            ->where('user_id', $user)
            ->with('jugador')
            ->firstOrFail();

        $nombreJugador = $membresia->jugador?->name ?? 'Jugador';
        $membresia->delete();

        return redirect()
            ->route('equipos.show', $equipo)
            ->with('ok', "Jugador {$nombreJugador} retirado del roster de {$equipo->nombre}.");
    }

    /**
     * PATCH /equipos/{equipo}/roster/{user}
     * Actualiza el rol (Titular, Suplente, En prueba), posición principal/secundaria y estado de invitación de un jugador.
     */
    public function updateRosterMember(UpdateRosterMemberRequest $request, Equipo $equipo, int $user): RedirectResponse
    {
        if (! $this->puedeGestionarRoster($request->user(), $equipo)) {
            return redirect()->route('equipos.show', $equipo)
                ->with('error', 'No tienes permisos para modificar el roster de este equipo (requiere Administrador del Club o Coach del equipo).');
        }

        $membresia = EquipoJugador::query()
            ->where('equipo_id', $equipo->id)
            ->where('user_id', $user)
            ->firstOrFail();

        $dto = $request->toDTO($equipo->id, $user);

        $datosActualizar = [
            'posicion_principal' => $dto->posicionPrincipal ?? $membresia->posicion_principal,
            'posicion_secundaria' => $request->has('posicion_secundaria') ? $dto->posicionSecundaria : $membresia->posicion_secundaria,
            'rol_membresia' => $dto->rolMembresia->value,
            'rango_declarativo' => $dto->rangoDeclarativo ?? $membresia->rango_declarativo,
        ];

        if ($request->filled('estado_invitacion')) {
            $estadoInv = EstadoInvitacionEnum::tryFrom((string) $request->input('estado_invitacion'));
            if ($estadoInv) {
                $datosActualizar['estado_invitacion'] = $estadoInv->value;
            }
        }

        // Si el jugador pasa a ser TITULAR con invitación ACEPTADA en esa posición,
        // cualquier otro titular anterior en esa misma posición pasa automáticamente a SUPLENTE (intercambio limpio).
        $posicionObjetivo = $datosActualizar['posicion_principal'];
        $invitacionFinal = $datosActualizar['estado_invitacion'] ?? $membresia->estado_invitacion->value;

        if ($dto->rolMembresia === RolMembresiaEnum::TITULAR
            && $invitacionFinal === EstadoInvitacionEnum::ACEPTADA->value
            && $posicionObjetivo !== null) {
            EquipoJugador::query()
                ->where('equipo_id', $equipo->id)
                ->where('user_id', '!=', $user)
                ->where('rol_membresia', RolMembresiaEnum::TITULAR->value)
                ->whereRaw('UPPER(posicion_principal) = ?', [strtoupper((string) $posicionObjetivo)])
                ->update(['rol_membresia' => RolMembresiaEnum::SUPLENTE->value]);
        }

        $membresia->update($datosActualizar);

        return redirect()
            ->route('equipos.show', $equipo)
            ->with('ok', sprintf(
                'Roster actualizado: %s ahora figura como %s (%s).',
                $membresia->jugador?->name ?? 'Jugador',
                ucfirst(str_replace('_', ' ', $dto->rolMembresia->value)),
                $datosActualizar['posicion_principal'] ?? 'Sin posición'
            ));
    }

    /**
     * Crea citaciones para los titulares y jugadores en prueba de los equipos del partido.
     */
    private function citarPlantelActivo(Partido $partido): void
    {
        $equiposIds = array_values(array_filter([$partido->equipo_local_id, $partido->equipo_visitante_id]));
        $miembros = EquipoJugador::query()
            ->whereIn('equipo_id', $equiposIds)
            ->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA->value)
            ->whereIn('rol_membresia', [RolMembresiaEnum::TITULAR->value, RolMembresiaEnum::EN_PRUEBA->value])
            ->get();

        foreach ($miembros as $miembro) {
            Citacion::firstOrCreate(
                [
                    'partido_id' => $partido->id,
                    'user_id' => $miembro->user_id,
                ],
                [
                    'equipo_id' => $miembro->equipo_id,
                    'tipo_convocatoria' => RolMembresiaEnum::TITULAR->value,
                    'mensaje_coach' => 'Convocatoria emitida desde el Panel de Liga.',
                    'estado_respuesta' => EstadoCitacionEnum::PENDIENTE->value,
                ]
            );
        }
    }

    /**
     * Determina si el usuario actual puede gestionar el fixture de la liga.
     * Si no hay sesión iniciada (modo demo abierto), se permite; si hay sesión, requiere ADMIN_LIGA o COACH.
     */
    private function puedeGestionarLiga(?User $user): bool
    {
        if (! $user) {
            return true;
        }

        return in_array($user->role, [UserRoleEnum::ADMIN_LIGA, UserRoleEnum::COACH], true);
    }

    /**
     * Determina si el usuario actual puede modificar el roster de un equipo.
     * Si no hay sesión iniciada (modo demo abierto), se permite; si hay sesión, requiere ADMIN_CLUB o COACH.
     */
    private function puedeGestionarRoster(?User $user, Equipo $equipo): bool
    {
        if (! $user) {
            return true;
        }

        if ($user->role === UserRoleEnum::ADMIN_CLUB) {
            return $equipo->club?->admin_user_id === null || (int) $equipo->club->admin_user_id === (int) $user->id;
        }

        if ($user->role === UserRoleEnum::COACH) {
            return $equipo->coach_user_id === null || (int) $equipo->coach_user_id === (int) $user->id;
        }

        return false;
    }
}
