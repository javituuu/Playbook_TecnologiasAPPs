<?php

namespace Tests\Feature;

use App\Domain\Enums\EstadoCitacionEnum;
use App\Domain\Enums\EstadoPartidoEnum;
use App\Domain\Enums\RolMembresiaEnum;
use App\Models\Citacion;
use App\Models\Equipo;
use App\Models\Partido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachFlutterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_flutter_con_alias_coach_o_correo_real_entrega_contrato_compatible_con_flutter(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'coach@udd.cl',
            'password' => 'password',
            'device_name' => 'playbook_coach_flutter',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'coach')
            ->assertJsonPath('data.user.name', 'Coach Martínez')
            ->assertJsonPath('data.user.equipos_dirigidos.0.nombre', 'UDD Titans')
            ->assertJsonPath('data.user.equipos_dirigidos.0.quorum_minimo', 4);

        // Si un jugador intenta ingresar con device_name de playbook_coach_flutter, debe recibir 403
        $jugadorResponse = $this->postJson('/api/auth/login', [
            'email' => 'jugador@udd.cl',
            'password' => 'password',
            'device_name' => 'playbook_coach_flutter',
        ]);

        $jugadorResponse->assertStatus(403);
    }

    public function test_coach_obtiene_heatmap_semanal_con_ausencias_y_cruces_de_partido(): void
    {
        $coach = User::where('email', 'coach.martinez@udd.cl')->firstOrFail();
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();

        // Semana 2026-W45 contiene el martes 2026-11-03 (choque multi-liga de Diego Pérez a las 20:00)
        // y jueves 2026-11-05 (bloqueo académico de Cata Ríos a las 19:00)
        $response = $this->actingAs($coach, 'sanctum')
            ->getJson("/api/coach/equipos/{$titans->id}/heatmap?semana=2026-W45");

        $response->assertOk()
            ->assertJsonPath('data.equipo_id', $titans->id)
            ->assertJsonPath('data.semana_iso', '2026-W45')
            ->assertJsonPath('data.quorum_minimo', 4);

        $bloques = collect($response->json('data.bloques'));
        $this->assertCount(56, $bloques); // 8 horas * 7 días

        $jueves19 = $bloques->first(fn (array $b) => $b['dia_semana'] === 4 && $b['hora'] === '19:00');
        $this->assertNotNull($jueves19);
        $nombresAusentesJueves = array_column($jueves19['ausencias'], 'nombre_jugador');
        $this->assertContains('Cata Ríos', $nombresAusentesJueves);

        $martes20 = $bloques->first(fn (array $b) => $b['dia_semana'] === 2 && $b['hora'] === '20:00');
        $this->assertNotNull($martes20);
        $ausenciaDiego = collect($martes20['ausencias'])->firstWhere('nombre_jugador', 'Diego Pérez');
        $this->assertNotNull($ausenciaDiego);
        $this->assertTrue($ausenciaDiego['es_cruce_partido']);
    }

    public function test_coach_puede_listar_agendar_y_cancelar_practicas(): void
    {
        $coach = User::where('email', 'coach.martinez@udd.cl')->firstOrFail();
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();

        // 1. Listar prácticas existentes del equipo
        $listResponse = $this->actingAs($coach, 'sanctum')
            ->getJson("/api/coach/equipos/{$titans->id}/practicas");

        $listResponse->assertOk();
        $initialCount = count($listResponse->json('data'));
        $this->assertGreaterThanOrEqual(1, $initialCount);

        // 2. Agendar una nueva práctica desde Flutter
        $storeResponse = $this->actingAs($coach, 'sanctum')
            ->postJson('/api/coach/practicas', [
                'equipo_id' => $titans->id,
                'semana_iso' => '2026-W46',
                'dia_semana' => 4,
                'fecha' => '2026-11-12T00:00:00.000',
                'hora_inicio' => '19:00',
                'hora_fin' => '21:00',
                'serie' => 'bo3',
                'rival' => 'PUCV Esports',
                'modalidad' => 'Scrim competitivo',
                'servidor' => 'Discord + Server LAS',
                'notas' => 'Revisar draft fase 1',
                'citar_disponibles' => true,
                'jugadores_citados' => ['Javier Benavides', 'Diego Pérez', 'Tomás Herrera'],
            ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('data.equipo_id', $titans->id)
            ->assertJsonPath('data.rival', 'PUCV Esports')
            ->assertJsonPath('data.serie', 'bo3')
            ->assertJsonPath('data.hora_inicio', '19:00')
            ->assertJsonPath('data.hora_fin', '21:00')
            ->assertJsonPath('data.estado', 'programada');

        $practicaId = (int) $storeResponse->json('data.id');
        $this->assertCount(3, $storeResponse->json('data.jugadores_citados'));

        // 3. Cancelar la práctica agendada
        $cancelResponse = $this->actingAs($coach, 'sanctum')
            ->patchJson("/api/coach/practicas/{$practicaId}/cancelar", [
                'motivo' => 'Rival reprogramó para la próxima semana',
            ]);

        $cancelResponse->assertOk()
            ->assertJsonPath('data.id', $practicaId)
            ->assertJsonPath('data.estado', 'cancelada')
            ->assertJsonPath('data.motivo_cancelacion', 'Rival reprogramó para la próxima semana');

        $this->assertDatabaseHas('partidos', [
            'id' => $practicaId,
            'estado' => EstadoPartidoEnum::CANCELADO->value,
        ]);
    }

    public function test_coach_puede_consultar_nomina_intercambiar_titular_con_suplente_y_actualizar_citacion(): void
    {
        $coach = User::where('email', 'coach.martinez@udd.cl')->firstOrFail();
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();
        $diego = User::where('email', 'diego.perez@udd.cl')->firstOrFail();
        $cata = User::where('email', 'cata.rios@udd.cl')->firstOrFail();

        // 1. Consultar nómina de eventos del equipo
        $nominaResponse = $this->actingAs($coach, 'sanctum')
            ->getJson("/api/coach/equipos/{$titans->id}/nomina");

        $nominaResponse->assertOk();
        $eventos = collect($nominaResponse->json('data'));
        $this->assertNotEmpty($eventos);

        // Buscamos el partido del martes 2026-11-03 (Jornada 3 con choque de Diego Pérez)
        $partidoConflicto = Partido::where('equipo_local_id', $titans->id)
            ->where('jornada_numero', 3)
            ->firstOrFail();

        $eventoJ3 = $eventos->firstWhere('id', $partidoConflicto->id);
        $this->assertNotNull($eventoJ3);

        // Diego debe aparecer con su conflicto_horario detectado
        $citacionDiego = collect($eventoJ3['citaciones'])->firstWhere('jugador_id', $diego->id);
        $this->assertNotNull($citacionDiego);
        $this->assertTrue($citacionDiego['es_titular']);
        $this->assertNotNull($citacionDiego['conflicto_horario']);

        // Cata Ríos (suplente del plantel) debe aparecer disponible como suplente para intercambiar
        $citacionCata = collect($eventoJ3['citaciones'])->firstWhere('jugador_id', $cata->id);
        $this->assertNotNull($citacionCata);
        $this->assertFalse($citacionCata['es_titular']);

        // 2. Intercambiar a Diego (titular) por Cata (suplente)
        $swapResponse = $this->actingAs($coach, 'sanctum')
            ->postJson("/api/coach/nomina/{$partidoConflicto->id}/intercambiar", [
                'titular_id' => $diego->id,
                'suplente_id' => $cata->id,
            ]);

        $swapResponse->assertOk();
        $citacionesPostSwap = collect($swapResponse->json('data.citaciones'));
        $this->assertFalse($citacionesPostSwap->firstWhere('jugador_id', $diego->id)['es_titular']);
        $this->assertTrue($citacionesPostSwap->firstWhere('jugador_id', $cata->id)['es_titular']);

        $this->assertDatabaseHas('citaciones', [
            'partido_id' => $partidoConflicto->id,
            'user_id' => $diego->id,
            'tipo_convocatoria' => RolMembresiaEnum::SUPLENTE->value,
        ]);
        $this->assertDatabaseHas('citaciones', [
            'partido_id' => $partidoConflicto->id,
            'user_id' => $cata->id,
            'tipo_convocatoria' => RolMembresiaEnum::TITULAR->value,
        ]);

        // 3. Actualizar estado de citación de Diego a rechazado
        $patchCitacion = $this->actingAs($coach, 'sanctum')
            ->patchJson("/api/coach/nomina/{$partidoConflicto->id}/citaciones/{$diego->id}", [
                'estado_respuesta' => 'rechazado',
            ]);

        $patchCitacion->assertOk();
        $this->assertSame(
            'rechazado',
            collect($patchCitacion->json('data.citaciones'))->firstWhere('jugador_id', $diego->id)['estado_respuesta']
        );

        $this->assertDatabaseHas('citaciones', [
            'partido_id' => $partidoConflicto->id,
            'user_id' => $diego->id,
            'estado_respuesta' => EstadoCitacionEnum::RECHAZADO->value,
        ]);
    }

    public function test_coach_obtiene_plantel_con_posiciones_requeridas_y_roles_de_membresia(): void
    {
        $coach = User::where('email', 'coach.martinez@udd.cl')->firstOrFail();
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();

        $response = $this->actingAs($coach, 'sanctum')
            ->getJson("/api/coach/equipos/{$titans->id}/plantel");

        $response->assertOk()
            ->assertJsonPath('data.equipo_id', $titans->id)
            ->assertJsonPath('data.posiciones_requeridas', ['Top', 'Jungla', 'Mid', 'ADC', 'Support']);

        $miembros = collect($response->json('data.miembros'));
        $this->assertGreaterThanOrEqual(5, $miembros->count());

        $javier = $miembros->firstWhere('nombre', 'Javier Benavides');
        $this->assertNotNull($javier);
        $this->assertSame('titular', $javier['rol_membresia']);
        $this->assertSame('Top', $javier['posicion_principal']);
        $this->assertTrue($javier['es_capitan']);

        // Un coach no puede consultar ni modificar el plantel de un equipo dirigido por otro coach
        $nova = Equipo::where('nombre', 'UDD Nova')->firstOrFail();
        $forbidden = $this->actingAs($coach, 'sanctum')
            ->getJson("/api/coach/equipos/{$nova->id}/plantel");
        $forbidden->assertStatus(403);
    }
}
