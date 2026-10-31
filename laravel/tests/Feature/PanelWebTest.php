<?php

namespace Tests\Feature;

use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\RolMembresiaEnum;
use App\Models\Equipo;
use App\Models\Liga;
use App\Models\Partido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_fixture_muestra_partidos_agrupados_por_liga(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Liga Universitaria LoL 2026', 'UDD Titans', 'UNAB Sharks', 'Prácticas internas'])
            ->assertSee('2 por confirmar')
            ->assertDontSee('Tentativo');
    }

    public function test_fixture_muestra_panel_de_validacion_automatica_kpis_y_alertas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Panel de Validación Automática')
            ->assertSee('EQUILIBRIO DE LOCALÍA')
            ->assertSee('DESCANSOS MÍNIMOS')
            ->assertSee('PENALIZACIÓN TOTAL')
            ->assertSee('CHOQUE_MULTI_LIGA')
            ->assertSee('DESCANSO_INSUFICIENTE');
    }

    public function test_publicar_fixture_se_bloquea_cuando_existen_violaciones_duras(): void
    {
        $ligaLol = Liga::where('nombre', 'Liga Universitaria LoL 2026')->firstOrFail();

        $this->post(route('ligas.publicar', $ligaLol))
            ->assertRedirect(route('fixture', ['liga_id' => $ligaLol->id]))
            ->assertSessionHas('error');

        // El partido con conflicto debe haber quedado marcado en estado CONFLICTO
        $partidoJ3 = Partido::where('liga_id', $ligaLol->id)->where('jornada_numero', 3)->firstOrFail();
        $this->assertSame(EstadoPartidoEnum::CONFLICTO, $partidoJ3->estado);
    }

    public function test_reprogramar_partido_en_conflicto_a_ventana_libre_resuelve_el_choque(): void
    {
        $ligaLol = Liga::where('nombre', 'Liga Universitaria LoL 2026')->firstOrFail();
        $partidoJ3 = Partido::where('liga_id', $ligaLol->id)->where('jornada_numero', 3)->firstOrFail();

        // Reprogramamos del martes 2026-11-03 20:00 (choque con Valorant) al lunes 2026-11-02 20:00 (libre en Bo3)
        $this->patch(route('partidos.reprogramar', $partidoJ3), [
            'fecha_hora_inicio' => '2026-11-02 20:00:00',
            'formato_serie' => 'bo3',
            'minutos_por_partida' => 50,
        ])
            ->assertRedirect(route('fixture', ['liga_id' => $ligaLol->id]))
            ->assertSessionHas('ok');

        $partidoJ3->refresh();
        $this->assertSame(EstadoPartidoEnum::TENTATIVO, $partidoJ3->estado);
        $this->assertSame('2026-11-02 20:00', $partidoJ3->fecha_hora_inicio->format('Y-m-d H:i'));
        $this->assertSame('2026-11-02 22:30', $partidoJ3->fecha_hora_fin->format('Y-m-d H:i'));
    }

    public function test_listado_de_equipos_muestra_titulares_confirmados(): void
    {
        $this->get('/equipos')
            ->assertOk()
            ->assertSee('UDD Titans')
            ->assertSee('falta MID y SUPPORT');
    }

    public function test_roster_avisa_las_posiciones_sin_titular_y_muestra_matriz_disponibilidad(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();

        $this->get("/equipos/{$titans->id}")
            ->assertOk()
            ->assertSee('Faltan titulares en MID y SUPPORT.')
            ->assertSee('Puede cubrir: Valentina Soto, Cata Ríos')
            ->assertSeeInOrder(['Titulares', 'Javier Benavides', 'Diego Pérez'])
            ->assertSeeInOrder(['Suplentes, en prueba e invitaciones', 'Valentina Soto', 'Cata Ríos', 'Felipe Araya'])
            ->assertSee('Matriz de Disponibilidad Semanal');
    }

    public function test_actualizar_miembros_del_roster_completa_las_5_posiciones_titulares(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();
        $valentina = User::where('email', 'valentina.soto@udd.cl')->firstOrFail();
        $felipe = User::where('email', 'felipe.araya@udd.cl')->firstOrFail();

        // 1. Promover a Valentina Soto (MID) de En prueba a Titular
        $this->patch(route('equipos.roster.update', [$titans, $valentina->id]), [
            'posicion_principal' => 'MID',
            'rol_membresia' => RolMembresiaEnum::TITULAR->value,
            'estado_invitacion' => EstadoInvitacionEnum::ACEPTADA->value,
        ])->assertRedirect(route('equipos.show', $titans));

        // 2. Confirmar invitación de Felipe Araya (SUPPORT) y pasarlo a Titular
        $this->patch(route('equipos.roster.update', [$titans, $felipe->id]), [
            'posicion_principal' => 'SUPPORT',
            'rol_membresia' => RolMembresiaEnum::TITULAR->value,
            'estado_invitacion' => EstadoInvitacionEnum::ACEPTADA->value,
        ])->assertRedirect(route('equipos.show', $titans));

        $this->get(route('equipos.show', $titans))
            ->assertOk()
            ->assertSee('Roster completo.');

        // 3. Si subimos a Cata Ríos como Titular en MID, Valentina Soto pasa automáticamente a Suplente (intercambio limpio)
        $cata = User::where('email', 'cata.rios@udd.cl')->firstOrFail();
        $this->patch(route('equipos.roster.update', [$titans, $cata->id]), [
            'posicion_principal' => 'MID',
            'rol_membresia' => RolMembresiaEnum::TITULAR->value,
            'estado_invitacion' => EstadoInvitacionEnum::ACEPTADA->value,
        ])->assertRedirect(route('equipos.show', $titans));

        $this->assertDatabaseHas('equipo_jugador', [
            'equipo_id' => $titans->id,
            'user_id' => $cata->id,
            'rol_membresia' => RolMembresiaEnum::TITULAR->value,
            'posicion_principal' => 'MID',
        ]);
        $this->assertDatabaseHas('equipo_jugador', [
            'equipo_id' => $titans->id,
            'user_id' => $valentina->id,
            'rol_membresia' => RolMembresiaEnum::SUPLENTE->value,
        ]);
    }

    public function test_roster_de_equipo_inexistente_responde_404(): void
    {
        $this->get('/equipos/9999')->assertNotFound();
    }

    public function test_login_web_muestra_cuentas_demo_y_permite_iniciar_sesion_por_rol(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Iniciar Sesión · Panel Web')
            ->assertSee('directiva.liga@playbook.cl')
            ->assertSee('directiva.club@udd.cl');

        // 1. Iniciar sesión como Admin de Liga redirige a Fixture (/)
        $this->post('/login', [
            'email' => 'directiva.liga@playbook.cl',
            'password' => 'password',
        ])->assertRedirect(route('fixture'));

        $this->assertAuthenticated();
        $this->get('/')
            ->assertOk()
            ->assertSee('Directiva Liga')
            ->assertSee('Administrador de Liga');

        // 2. Cerrar sesión
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        // 3. Iniciar sesión como Admin de Club redirige a Equipos (/equipos)
        $this->post('/login', [
            'email' => 'directiva.club@udd.cl',
            'password' => 'password',
        ])->assertRedirect(route('equipos'));

        $this->assertAuthenticated();
    }

    public function test_login_web_rechaza_cuentas_de_jugador_movil(): void
    {
        $this->post('/login', [
            'email' => 'javier@email.com',
            'password' => 'password',
        ])
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }
}
