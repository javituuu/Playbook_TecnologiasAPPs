<?php

use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\RolMembresiaEnum;
use App\Models\Equipo;
use App\Models\EquipoJugador;
use App\Models\Partido;
use Illuminate\Support\Facades\Route;

/**
 * Panel web de solo lectura (Blade).
 * Muestra el fixture y los rosters directo desde la base de datos, sin pasar por la API.
 */

// Fixture: todos los partidos agrupados por liga; las prácticas quedan al final.
Route::get('/', function () {
    $partidos = Partido::query()
        ->with(['liga.disciplina', 'equipoLocal', 'equipoVisitante'])
        ->orderBy('fecha_hora_inicio')
        ->get();

    return view('panel.fixture', [
        'grupos' => $partidos->groupBy(fn (Partido $p) => $p->nombreLigaOPractica())
            ->sortBy(fn ($grupo) => $grupo->first()->liga === null),
        'tentativos' => $partidos->where('estado', EstadoPartidoEnum::TENTATIVO)->count(),
    ]);
})->name('fixture');

Route::get('/equipos', function () {
    $equipos = Equipo::query()
        ->with(['club', 'disciplina'])
        ->withCount([
            'membresias',
            'membresias as titulares_count' => fn ($q) => $q
                ->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA)
                ->where('rol_membresia', RolMembresiaEnum::TITULAR),
        ])
        ->orderBy('nombre')
        ->get();

    // Una consulta por equipo; son pocos y así la regla de roster válido vive solo en el modelo.
    return view('panel.equipos', [
        'equipos' => $equipos,
        'faltantes' => $equipos->mapWithKeys(fn (Equipo $e) => [$e->id => $e->posicionesSinTitular()])->all(),
    ]);
})->name('equipos');

// Roster: un titular por posición de la disciplina; el resto del plantel va a la tabla.
Route::get('/equipos/{equipo}', function (Equipo $equipo) {
    $equipo->load(['club', 'disciplina', 'coach', 'membresias.jugador']);

    $titulares = $equipo->membresias
        ->filter(fn (EquipoJugador $m) => $m->estado_invitacion === EstadoInvitacionEnum::ACEPTADA
            && $m->rol_membresia === RolMembresiaEnum::TITULAR
            && $m->posicion_principal !== null)
        ->unique(fn (EquipoJugador $m) => strtoupper($m->posicion_principal))
        ->keyBy(fn (EquipoJugador $m) => strtoupper($m->posicion_principal));

    return view('panel.roster', [
        'equipo' => $equipo,
        'posiciones' => $equipo->disciplina?->posiciones_validas ?? [],
        'titulares' => $titulares,
        'resto' => $equipo->membresias->diff($titulares->values()),
        'faltantes' => $equipo->posicionesSinTitular(),
    ]);
})->name('equipos.show');
