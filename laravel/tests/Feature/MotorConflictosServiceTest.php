<?php

namespace Tests\Feature;

use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\FormatoSerieEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\TipoConflictoEnum;
use App\Models\Equipo;
use App\Models\EquipoJugador;
use App\Models\Liga;
use App\Models\Partido;
use App\Models\User;
use App\Services\Disponibilidad\MotorConflictosService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotorConflictosServiceTest extends TestCase
{
    use RefreshDatabase;

    private MotorConflictosService $motor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->motor = app(MotorConflictosService::class);
    }

    public function test_calcula_duracion_estandar_por_formato_bo1_bo3_y_bo5(): void
    {
        // Estándar 50 min por partida: Bo1 = 50m, Bo3 = 150m (3*50), Bo5 = 250m (5*50)
        $this->assertSame(50, $this->motor->calcularDuracionSerie(FormatoSerieEnum::BO1, 50));
        $this->assertSame(150, $this->motor->calcularDuracionSerie(FormatoSerieEnum::BO3, 50));
        $this->assertSame(250, $this->motor->calcularDuracionSerie(FormatoSerieEnum::BO5, 50));

        // Duración personalizada (ej. 45 min por partida en Bo3 = 135m)
        $this->assertSame(135, $this->motor->calcularDuracionSerie(FormatoSerieEnum::BO3, 45));
    }

    public function test_detecta_bloqueo_recurrente_y_bloqueo_unitario_de_jugadores(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();

        // 1. Bloqueo RECURRENTE: Lunes 08:00-14:00 (Javier Benavides - TOP tiene clases todos los lunes)
        $lunesCualquiera = CarbonImmutable::parse('2026-11-16 10:00:00');
        $evalRecurrente = $this->motor->evaluarDisponibilidadEquipoEnVentana(
            $titans,
            $lunesCualquiera,
            $lunesCualquiera->addMinutes(50)
        );

        $codigosRecurrente = $evalRecurrente['conflictos']->pluck('codigoRegla')->all();
        $this->assertContains('BLOQUEO_HORARIO_RECURRENTE', $codigosRecurrente);

        // 2. Bloqueo UNITARIO: Viernes 2026-11-06 15:00-18:00 (Diego Pérez tiene Certamen puntual ese viernes)
        $viernesCertamen = CarbonImmutable::parse('2026-11-06 15:30:00');
        $evalUnitario = $this->motor->evaluarDisponibilidadEquipoEnVentana(
            $titans,
            $viernesCertamen,
            $viernesCertamen->addMinutes(150) // Bo3 = 150 min
        );

        $codigosUnitario = $evalUnitario['conflictos']->pluck('codigoRegla')->all();
        $this->assertContains('BLOQUEO_HORARIO_UNITARIO', $codigosUnitario);

        // El viernes siguiente (2026-11-13) a la misma hora 15:30 Diego NO debe tener ese bloqueo unitario
        $viernesSiguiente = CarbonImmutable::parse('2026-11-13 15:30:00');
        $evalViernesSiguiente = $this->motor->evaluarDisponibilidadEquipoEnVentana(
            $titans,
            $viernesSiguiente,
            $viernesSiguiente->addMinutes(50) // 15:30 a 16:20
        );

        $bloqueosDiegoViernesSiguiente = $evalViernesSiguiente['conflictos']
            ->filter(fn ($c) => $c->nombreJugador === 'Diego Pérez');
        $this->assertTrue($bloqueosDiegoViernesSiguiente->isEmpty());
    }

    public function test_suplente_disponible_cubre_posicion_de_titular_con_bloqueo_horario(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();
        $felipe = User::where('email', 'felipe.araya@udd.cl')->firstOrFail();

        // Aceptamos la invitación de Felipe Araya (SUPPORT) para que el equipo tenga cobertura de las 5 posiciones
        EquipoJugador::where('equipo_id', $titans->id)
            ->where('user_id', $felipe->id)
            ->update([
                'rol_membresia' => RolMembresiaEnum::TITULAR->value,
                'estado_invitacion' => EstadoInvitacionEnum::ACEPTADA->value,
            ]);

        // Valentina Soto pasa a TITULAR en MID, y Cata Ríos queda como SUPLENTE que cubre MID y SUPPORT
        $valentina = User::where('email', 'valentina.soto@udd.cl')->firstOrFail();
        EquipoJugador::where('equipo_id', $titans->id)
            ->where('user_id', $valentina->id)
            ->update(['rol_membresia' => RolMembresiaEnum::TITULAR->value]);

        // Martes 2026-11-03 de 18:00 a 19:30: Valentina (TITULAR MID) tiene bloqueo académico recurrente (18:00-20:00),
        // pero Cata Ríos (SUPLENTE MID/SUPPORT) está libre a esa hora.
        $inicio = CarbonImmutable::parse('2026-11-03 18:00:00');
        $fin = $inicio->addMinutes(50);

        $eval = $this->motor->evaluarDisponibilidadEquipoEnVentana($titans->fresh(), $inicio, $fin);

        $this->assertTrue($eval['cumple_quorum_oficial']);
        $this->assertEmpty($eval['posiciones_sin_cubrir']);
        $this->assertSame('Cata Ríos', $eval['posiciones_cubiertas']['MID']['nombre']);
        $this->assertTrue($eval['posiciones_cubiertas']['MID']['es_reemplazo_suplente']);

        // El bloqueo de Valentina se degrada a ADVERTENCIA_BLANDA porque Cata Ríos cubre la posición
        $conflictoValentina = $eval['conflictos']->firstWhere('nombreJugador', 'Valentina Soto');
        $this->assertNotNull($conflictoValentina);
        $this->assertSame(TipoConflictoEnum::ADVERTENCIA_BLANDA, $conflictoValentina->tipo);
        $this->assertSame('BLOQUEO_HORARIO_RECURRENTE_CUBIERTO_POR_SUPLENTE', $conflictoValentina->codigoRegla);
    }

    public function test_valida_fixture_liga_detectando_violacion_dura_multi_liga_y_advertencia_descanso(): void
    {
        $ligaLol = Liga::where('nombre', 'Liga Universitaria LoL 2026')->firstOrFail();

        $reporte = $this->motor->validarFixtureLiga($ligaLol, sincronizarEstados: true);

        // No puede publicarse porque Diego Pérez tiene choque multi-liga el martes 03-11-2026 a las 20:00 con Valorant
        $this->assertFalse($reporte['puede_publicarse']);
        $this->assertGreaterThanOrEqual(1, $reporte['violaciones_duras_count']);
        $this->assertGreaterThanOrEqual(1, $reporte['advertencias_blandas_count']);

        $codigos = array_column($reporte['conflictos'], 'codigo_regla');
        $this->assertContains('CHOQUE_MULTI_LIGA', $codigos);
        $this->assertContains('DESCANSO_INSUFICIENTE', $codigos);

        // El partido del martes 03-11-2026 debe haber quedado marcado en estado CONFLICTO
        $partidoConflicto = Partido::where('liga_id', $ligaLol->id)
            ->where('jornada_numero', 3)
            ->firstOrFail();
        $this->assertSame(EstadoPartidoEnum::CONFLICTO, $partidoConflicto->estado);
    }

    public function test_genera_grilla_semanal_y_sugiere_ventanas_entre_dos_equipos_con_formato_bo3(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();
        $paladins = Equipo::where('nombre', 'PUC Paladins')->firstOrFail();
        $fecha = CarbonImmutable::parse('2026-11-03');

        // Grilla semanal en Bo3 (3 * 50 = 150 min)
        $grilla = $this->motor->generarGrillaDisponibilidadSemanalEquipo(
            equipo: $titans,
            fechaEnSemana: $fecha,
            formatoSerie: FormatoSerieEnum::BO3,
            minutosPorPartida: 50,
            horasBloques: ['16:00', '19:00', '20:00']
        );

        $this->assertSame(150, $grilla['duracion_bloque_minutos']);
        $this->assertArrayHasKey('Martes', $grilla['grilla']);
        $this->assertArrayHasKey('20:00', $grilla['grilla']['Martes']);
        // El martes a las 20:00 Diego Pérez tiene choque con Valorant
        $this->assertNotEmpty($grilla['grilla']['Martes']['20:00']['ausentes']);

        // Cruce entre UDD Titans y PUC Paladins
        $ventanas = $this->motor->sugerirVentanasParaEnfrentamiento(
            equipoLocal: $titans,
            equipoVisitante: $paladins,
            fechaEnSemana: $fecha,
            formatoSerie: FormatoSerieEnum::BO3,
            minutosPorPartida: 50,
            horasCandidatas: ['16:00', '20:00'],
        );

        $this->assertCount(14, $ventanas); // 7 días * 2 bloques
        $martes20 = $ventanas->first(fn ($v) => $v->fecha === '2026-11-03' && $v->horaInicio === '20:00');
        $this->assertNotNull($martes20);
        $this->assertFalse($martes20->disponible);
        $this->assertSame('22:30', $martes20->horaFin); // 20:00 + 150 min (Bo3)
    }

    public function test_comando_cli_motor_conflictos_se_ejecuta_correctamente(): void
    {
        $this->artisan('playbook:motor-conflictos', [
            '--equipo' => 'UDD Titans',
            '--rival' => 'PUC Paladins',
            '--liga' => 'Liga Universitaria LoL 2026',
            '--semana' => '2026-11-03',
            '--formato' => 'bo3',
            '--minutos' => 50,
        ])->assertExitCode(0);
    }
}
