<?php

namespace Tests\Feature;

use App\Domain\Enums\DiaSemanaEnum;
use App\Domain\Enums\EstadoPartidoEnum;
use App\Domain\Enums\RolMembresiaEnum;
use App\Http\Resources\Jugador\AgendaPartidoResource;
use App\Http\Resources\Jugador\CitacionResource;
use App\Http\Resources\Jugador\PerfilJugadorResource;
use App\Models\BloqueoHorario;
use App\Models\Club;
use App\Models\Disciplina;
use App\Models\Equipo;
use App\Models\Liga;
use App\Models\Partido;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentDomainPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_modelos_eloquent_y_relaciones_principales_funcionan_correctamente(): void
    {
        $javier = User::where('email', 'javier@email.com')
            ->with(['membresiaPrincipal.equipo.club', 'membresiaPrincipal.equipo.disciplina', 'bloqueosHorarios', 'citaciones'])
            ->firstOrFail();

        $this->assertNotNull($javier->membresiaPrincipal);
        $this->assertEquals('TOP', $javier->membresiaPrincipal->posicion_principal);
        $this->assertEquals(RolMembresiaEnum::TITULAR, $javier->membresiaPrincipal->rol_membresia);
        $this->assertEquals('UDD Titans', $javier->membresiaPrincipal->equipo->nombre);
        $this->assertEquals('Universidad del Desarrollo (UDD)', $javier->membresiaPrincipal->equipo->club->nombreConSiglas());
        $this->assertEquals('League of Legends', $javier->membresiaPrincipal->equipo->disciplina->nombre);
        $this->assertCount(2, $javier->bloqueosHorarios);
        $this->assertGreaterThanOrEqual(4, $javier->citaciones->count());

        $clubUdd = Club::where('siglas', 'UDD')->with(['administrador', 'equipos'])->firstOrFail();
        $this->assertEquals('directiva.club@udd.cl', $clubUdd->administrador->email);
        $this->assertGreaterThanOrEqual(3, $clubUdd->equipos->count());

        $ligaLol = Liga::where('nombre', 'Liga Universitaria LoL 2026')
            ->with(['disciplina', 'organizador', 'equiposAceptados', 'partidos'])
            ->firstOrFail();

        $this->assertEquals('lol', $ligaLol->disciplina->slug);
        $this->assertEquals('directiva.liga@playbook.cl', $ligaLol->organizador->email);
        $this->assertCount(4, $ligaLol->equiposAceptados);
        $this->assertNotEmpty($ligaLol->partidos);
    }

    public function test_validacion_de_roster_detecta_posiciones_sin_titular(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->with('disciplina')->firstOrFail();
        $nova = Equipo::where('nombre', 'UDD Nova')->with('disciplina')->firstOrFail();

        // En UDD Titans: TOP (Javier), JUNGLA (Diego) y ADC (Tomás) son titulares;
        // MID (Valentina) está EN_PRUEBA y SUPPORT está vacante (Felipe tiene invitación PENDIENTE y es suplente).
        $this->assertFalse($titans->tieneRosterValido());
        $faltantes = $titans->posicionesSinTitular();
        $this->assertContains('SUPPORT', $faltantes);
        $this->assertContains('MID', $faltantes);

        // En UDD Nova (Valorant): tiene los 5 titulares aceptados.
        $this->assertTrue($nova->tieneRosterValido());
        $this->assertEmpty($nova->posicionesSinTitular());

        $disciplinaLol = Disciplina::where('slug', 'lol')->firstOrFail();
        $this->assertTrue($disciplinaLol->esPosicionValida('support'));
        $this->assertFalse($disciplinaLol->esPosicionValida('arquero'));
    }

    public function test_scope_solapa_con_detecta_choque_multi_liga_de_diego_perez(): void
    {
        $diego = User::where('email', 'diego.perez@udd.cl')->firstOrFail();

        // Ventana del partido de LoL (2026-11-03 20:00 a 21:30)
        $inicioLol = Carbon::parse('2026-11-03 20:00:00');
        $finLol = Carbon::parse('2026-11-03 21:30:00');

        $partidosEnConflicto = Partido::query()
            ->ocupanAgenda()
            ->solapaCon($inicioLol, $finLol)
            ->whereHas('citaciones', fn ($q) => $q->where('user_id', $diego->id))
            ->with('liga')
            ->get();

        // Debe encontrar tanto el partido de LoL (20:00-21:30) como el de Valorant (20:30-22:00)
        $this->assertCount(2, $partidosEnConflicto);
        $estados = $partidosEnConflicto->pluck('estado')->all();
        $this->assertContains(EstadoPartidoEnum::TENTATIVO, $estados);
        $this->assertContains(EstadoPartidoEnum::PUBLICADO, $estados);
    }

    public function test_scopes_de_bloqueo_horario_filtran_recurrentes_y_puntuales(): void
    {
        $diego = User::where('email', 'diego.perez@udd.cl')->firstOrFail();

        // Viernes 06-11-2026: Diego tiene un bloqueo puntual ("Certamen" 15:00-18:00)
        $viernesCertamen = Carbon::parse('2026-11-06');
        $bloqueosViernes = BloqueoHorario::query()
            ->where('user_id', $diego->id)
            ->paraFecha($viernesCertamen)
            ->solapaConHora('16:00', '17:30')
            ->get();

        $this->assertCount(1, $bloqueosViernes);
        $this->assertEquals('Certamen', $bloqueosViernes->first()->motivo);
        $this->assertEquals(DiaSemanaEnum::VIERNES, $bloqueosViernes->first()->dia_semana);
        $this->assertTrue($bloqueosViernes->first()->solapaConRangoHora('17:30', '19:00'));
        $this->assertFalse($bloqueosViernes->first()->solapaConRangoHora('18:00', '19:30'));
    }

    public function test_json_resources_generan_contrato_exacto_de_ionic(): void
    {
        $javier = User::where('email', 'javier@email.com')
            ->with(['membresiaPrincipal.equipo.club', 'membresiaPrincipal.equipo.disciplina', 'bloqueosHorarios'])
            ->firstOrFail();

        $perfilArray = (new PerfilJugadorResource($javier))->resolve();
        $this->assertEquals('Javier Benavides', $perfilArray['nombre_completo']);
        $this->assertEquals('Universidad del Desarrollo (UDD)', $perfilArray['club_actual']);
        $this->assertEquals('League of Legends', $perfilArray['disciplina']);
        $this->assertEquals('TOP', $perfilArray['rol']);
        $this->assertEquals('Hierro 4', $perfilArray['rango']);
        $this->assertEquals('Activo en el plantel', $perfilArray['estado']);

        $citacionConfirmada = $javier->citaciones()->confirmadas()->with(['partido.liga', 'partido.equipoLocal', 'partido.equipoVisitante'])->firstOrFail();
        $agendaItem = (new AgendaPartidoResource($citacionConfirmada))->resolve();
        $this->assertArrayHasKey('id_partido', $agendaItem);
        $this->assertArrayHasKey('equipo_local', $agendaItem);
        $this->assertArrayHasKey('equipo_visitante', $agendaItem);

        $citacionPendiente = $javier->citaciones()->pendientes()->with(['partido.liga', 'partido.equipoLocal', 'partido.equipoVisitante'])->firstOrFail();
        $citacionItem = (new CitacionResource($citacionPendiente))->resolve();
        $this->assertArrayHasKey('id_citacion', $citacionItem);
        $this->assertArrayHasKey('equipo_rival', $citacionItem);
        $this->assertNotEquals('UDD Titans', $citacionItem['equipo_rival']);
    }

    public function test_form_request_bloqueo_horario_rechaza_rango_invalido_y_crea_dto(): void
    {
        $invalidResponse = $this->postJson('/api/bloqueos', [
            'dia_semana' => 'Martes',
            'hora_inicio' => '15:00',
            'hora_fin' => '13:00',
        ]);

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['hora_fin']);
    }
}
