<?php

namespace Database\Seeders;

use App\Enums\EstadoCitacionEnum;
use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoLigaEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\TipoPartidoEnum;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ligas, inscripciones, partidos y citaciones.
 *
 * Incluye:
 *  - Los 4 partidos/citaciones originales de javier@email.com (compatibilidad con Ionic).
 *  - VIOLACIÓN DURA: Diego Pérez citado a LoL 20:00 y Valorant 20:30 el mismo día.
 *  - ADVERTENCIA BLANDA: UDD Titans juega dos jornadas con menos de 24h de descanso.
 */
class LigaFixtureSeeder extends Seeder
{
    public function run(): void
    {
        $adminLiga = $this->userId('directiva.liga@playbook.cl');
        $lol = (int) DB::table('disciplinas')->where('slug', 'lol')->value('id');
        $valorant = (int) DB::table('disciplinas')->where('slug', 'valorant')->value('id');

        $titans = $this->equipoId('UDD Titans');
        $nova = $this->equipoId('UDD Nova');
        $derecho = $this->equipoId('Derecho FC');
        $sharks = $this->equipoId('UNAB Sharks');
        $usm = $this->equipoId('USM Gaming');
        $paladins = $this->equipoId('PUC Paladins');
        $inacapLol = $this->equipoId('INACAP Esports');
        $wolves = $this->equipoId('INACAP Wolves');

        // ---------- Ligas e inscripciones ----------
        $ligaUniversitaria = $this->liga('Liga Universitaria LoL 2026', $lol, $adminLiga);
        $torneoEsports = $this->liga('Torneo Esports 2026', $lol, $adminLiga);
        $interfacultades = $this->liga('Torneo Interfacultades LoL', $lol, $adminLiga);
        $ligaValorant = $this->liga('Liga Valorant Metropolitana 2026', $valorant, $adminLiga);

        $this->inscribir($ligaUniversitaria, [$titans, $sharks, $usm, $paladins]);
        $this->inscribir($torneoEsports, [$titans, $inacapLol]);
        $this->inscribir($interfacultades, [$titans, $derecho]);
        $this->inscribir($ligaValorant, [$nova, $wolves]);

        $javier = $this->userId('javier@email.com');
        $diego = $this->userId('diego.perez@udd.cl');

        // ---------- Partidos originales de la app Ionic (Certamen 1) ----------
        $p1 = $this->partido($ligaUniversitaria, $titans, $sharks, '2026-10-02 16:00', 'Estadio Municipal · Cancha 2', EstadoPartidoEnum::PROGRAMADO, jornada: 1);
        $p2 = $this->partido($torneoEsports, $titans, $inacapLol, '2026-10-03 20:30', 'Arena Gaming Central', EstadoPartidoEnum::PROGRAMADO, jornada: 1);
        $p3 = $this->partido($ligaUniversitaria, $titans, $usm, '2026-10-04 19:00', 'Server NA', EstadoPartidoEnum::PROGRAMADO, jornada: 2);
        $p4 = $this->partido($interfacultades, $titans, $derecho, '2026-10-09 20:00', 'Server BR', EstadoPartidoEnum::PROGRAMADO, jornada: 1);

        $this->citar($p1, [$titans, $sharks], 'Vamos con todo!', [$javier => EstadoCitacionEnum::CONFIRMADO]);
        $this->citar($p2, [$titans, $inacapLol], 'Necesitamos ganar.', [$javier => EstadoCitacionEnum::CONFIRMADO]);
        $this->citar($p3, [$titans, $usm], '¿Cuento contigo para este finde?', [$javier => EstadoCitacionEnum::PENDIENTE]);
        $this->citar($p4, [$titans, $derecho], 'Torneo importante, preparate.', [$javier => EstadoCitacionEnum::PENDIENTE]);

        // ---------- Caso VIOLACIÓN DURA (martes 03-11-2026) ----------
        $lolConflicto = $this->partido($ligaUniversitaria, $titans, $paladins, '2026-11-03 20:00', 'Servidor LAS-1', EstadoPartidoEnum::TENTATIVO, jornada: 3, bloque: 'Bloque B 20:00');
        $valorantPublicado = $this->partido($ligaValorant, $nova, $wolves, '2026-11-03 20:30', 'Servidor LAS-2', EstadoPartidoEnum::PUBLICADO, jornada: 2, bloque: 'Bloque B 20:30');

        $this->citar($lolConflicto, [$titans, $paladins], 'Jornada 3, clave para clasificar.', [
            $javier => EstadoCitacionEnum::PENDIENTE,
            $diego => EstadoCitacionEnum::PENDIENTE,
        ]);
        $this->citar($valorantPublicado, [$nova, $wolves], 'Partido publicado, no faltar.', [
            $diego => EstadoCitacionEnum::CONFIRMADO,
        ]);

        // ---------- Caso ADVERTENCIA BLANDA: < 24h de descanso para UDD Titans ----------
        $jornada4 = $this->partido($ligaUniversitaria, $usm, $titans, '2026-11-04 19:00', 'Servidor LAS-1', EstadoPartidoEnum::TENTATIVO, jornada: 4, bloque: 'Bloque A 19:00');
        $this->citar($jornada4, [$usm, $titans], 'Doble fecha esta semana.');

        // ---------- Práctica interna (sin liga) ----------
        $practica = $this->partido(null, $titans, null, '2026-11-05 19:00', 'Discord · Canal de Voz #Entrenamientos', EstadoPartidoEnum::PROGRAMADO, tipo: TipoPartidoEnum::PRACTICA, duracion: 120);
        $this->citar($practica, [$titans], 'Práctica de draft y early game.');
    }

    private function userId(string $email): int
    {
        return (int) DB::table('users')->where('email', $email)->value('id');
    }

    private function equipoId(string $nombre): int
    {
        return (int) DB::table('equipos')->where('nombre', $nombre)->value('id');
    }

    private function liga(string $nombre, int $disciplinaId, int $organizadorId): int
    {
        return DB::table('ligas')->insertGetId([
            'disciplina_id' => $disciplinaId,
            'organizador_user_id' => $organizadorId,
            'nombre' => $nombre,
            'temporada' => '2026-2',
            'estado' => EstadoLigaEnum::EN_CURSO->value,
            'descanso_minimo_horas' => 24,
            'tolerancia_penalizacion_max' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param  list<int>  $equipos */
    private function inscribir(int $ligaId, array $equipos): void
    {
        foreach ($equipos as $equipoId) {
            DB::table('inscripciones_liga')->insert([
                'liga_id' => $ligaId,
                'equipo_id' => $equipoId,
                'codigo_invitacion' => Str::upper(Str::random(12)),
                'estado_invitacion' => EstadoInvitacionEnum::ACEPTADA->value,
                'roster_validado' => false,
                'respondido_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function partido(
        ?int $ligaId,
        int $localId,
        ?int $visitanteId,
        string $inicio,
        string $lugar,
        EstadoPartidoEnum $estado,
        ?int $jornada = null,
        ?string $bloque = null,
        TipoPartidoEnum $tipo = TipoPartidoEnum::OFICIAL,
        int $duracion = 90,
    ): int {
        $fechaInicio = Carbon::parse($inicio);

        return DB::table('partidos')->insertGetId([
            'liga_id' => $ligaId,
            'equipo_local_id' => $localId,
            'equipo_visitante_id' => $visitanteId,
            'tipo' => $tipo->value,
            'jornada_numero' => $jornada,
            'bloque_etiqueta' => $bloque,
            'lugar' => $lugar,
            'fecha_hora_inicio' => $fechaInicio,
            'fecha_hora_fin' => $fechaInicio->copy()->addMinutes($duracion),
            'estado' => $estado->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Cita a titulares y jugadores en prueba (con invitación aceptada) de los equipos dados.
     *
     * @param  list<int>  $equipos
     * @param  array<int, EstadoCitacionEnum>  $estados  Estado específico por user_id (por defecto: confirmado).
     */
    private function citar(int $partidoId, array $equipos, string $mensaje, array $estados = []): void
    {
        $miembros = DB::table('equipo_jugador')
            ->whereIn('equipo_id', $equipos)
            ->where('estado_invitacion', EstadoInvitacionEnum::ACEPTADA->value)
            ->whereIn('rol_membresia', [RolMembresiaEnum::TITULAR->value, RolMembresiaEnum::EN_PRUEBA->value])
            ->get(['equipo_id', 'user_id']);

        foreach ($miembros as $miembro) {
            $estado = $estados[$miembro->user_id] ?? EstadoCitacionEnum::CONFIRMADO;

            DB::table('citaciones')->insert([
                'partido_id' => $partidoId,
                'user_id' => $miembro->user_id,
                'equipo_id' => $miembro->equipo_id,
                'tipo_convocatoria' => RolMembresiaEnum::TITULAR->value,
                'mensaje_coach' => $mensaje,
                'estado_respuesta' => $estado->value,
                'respondido_en' => $estado === EstadoCitacionEnum::PENDIENTE ? null : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
