<?php

namespace Tests\Feature;

use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\RolMembresiaEnum;
use App\Models\Club;
use App\Models\Disciplina;
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
            ->assertSee('Validación del fixture')
            ->assertSee('Equilibrio local / visita')
            ->assertSee('Descanso mínimo')
            ->assertSee('Penalización')
            ->assertSee('Conflicto · choque multi liga')
            ->assertSee('Advertencia · descanso insuficiente');
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
            ->assertSeeInOrder(['Suplentes y jugadores a prueba', 'Valentina Soto', 'Cata Ríos', 'Felipe Araya'])
            ->assertSee('Disponibilidad semanal del equipo');
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
            ->assertSee('Iniciar sesión')
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

        // 2.5. Eliminar partido funciona correctamente
        $partidoPractica = Partido::whereNull('liga_id')->firstOrFail();
        $this->delete(route('partidos.destroy', $partidoPractica))
            ->assertRedirect(route('fixture'))
            ->assertSessionHas('ok');
        $this->assertDatabaseMissing('partidos', ['id' => $partidoPractica->id]);

        // 2.6. Cerrar sesión
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        // 3. Iniciar sesión como Admin de Club redirige a Equipos (/equipos)
        $this->post('/login', [
            'email' => 'directiva.club@udd.cl',
            'password' => 'password',
        ])->assertRedirect(route('equipos'));

        $this->assertAuthenticated();
    }

    public function test_admin_club_puede_crear_equipo_invitar_jugador_inscribir_en_liga_y_filtrar(): void
    {
        $clubUdd = Club::where('siglas', 'UDD')->firstOrFail();
        $lol = Disciplina::where('slug', 'lol')->firstOrFail();
        $ligaLol = Liga::where('nombre', 'Liga Universitaria LoL 2026')->firstOrFail();

        // 1. Filtrar equipos por búsqueda
        $this->get(route('equipos', ['q' => 'Titans']))
            ->assertOk()
            ->assertSee('UDD Titans')
            ->assertDontSee('UNAB Sharks');

        // 2. Crear un nuevo equipo
        $this->post(route('equipos.store'), [
            'nombre' => 'UDD Valkyries',
            'club_id' => $clubUdd->id,
            'disciplina_id' => $lol->id,
        ])->assertSessionHas('ok');

        $nuevoEquipo = Equipo::where('nombre', 'UDD Valkyries')->firstOrFail();

        // 3. Registrar e incorporar un jugador al roster
        $this->post(route('equipos.roster.store', $nuevoEquipo), [
            'nuevo_nombre' => 'Lucas Ruiz',
            'nuevo_email' => 'lucas.ruiz@udd.cl',
            'posicion_principal' => 'TOP',
            'posicion_secundaria' => 'MID',
            'rango_declarativo' => 'Diamante 1',
            'rol_membresia' => RolMembresiaEnum::TITULAR->value,
            'estado_invitacion' => EstadoInvitacionEnum::ACEPTADA->value,
        ])->assertRedirect(route('equipos.show', $nuevoEquipo))
            ->assertSessionHas('ok');

        $lucas = User::where('email', 'lucas.ruiz@udd.cl')->firstOrFail();
        $this->assertDatabaseHas('equipo_jugador', [
            'equipo_id' => $nuevoEquipo->id,
            'user_id' => $lucas->id,
            'posicion_principal' => 'TOP',
        ]);

        // 4. Inscribir el equipo en una liga de su disciplina
        $this->post(route('equipos.inscripciones.store', $nuevoEquipo), [
            'liga_id' => $ligaLol->id,
        ])->assertRedirect(route('equipos.show', $nuevoEquipo))
            ->assertSessionHas('ok');

        $this->assertDatabaseHas('inscripciones_liga', [
            'equipo_id' => $nuevoEquipo->id,
            'liga_id' => $ligaLol->id,
        ]);

        // 5. Retirar al jugador del equipo
        $this->delete(route('equipos.roster.destroy', [$nuevoEquipo, $lucas->id]))
            ->assertRedirect(route('equipos.show', $nuevoEquipo))
            ->assertSessionHas('ok');

        $this->assertDatabaseMissing('equipo_jugador', [
            'equipo_id' => $nuevoEquipo->id,
            'user_id' => $lucas->id,
        ]);
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

    public function test_vista_equipos_renderiza_estetica_consistente_con_ionic(): void
    {
        $response = $this->get('/equipos');
        $response->assertOk()
            ->assertDontSee('Liga Universitaria · Gestión de Escuadras')
            ->assertSee('Equipos')
            ->assertSee('search-outline')
            ->assertSee('filter-outline')
            ->assertSee('pildora-titulares')
            ->assertSee('Registrar nuevo equipo')
            ->assertDontSee('(RF.07)')
            ->assertSee('Crear equipo');
    }

    public function test_vista_login_renderiza_estetica_consistente_con_ionic(): void
    {
        $response = $this->get('/login');
        $response->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertDontSee('· Panel Web')
            ->assertSee('Credenciales')
            ->assertDontSee('Acceso administrativo oficial')
            ->assertSee('Cuentas de prueba para ingresar directo')
            ->assertDontSee('(1 Clic)')
            ->assertSee('tarjeta-cuenta')
            ->assertSee('tarjeta-cuenta__avatar')
            ->assertSee('log-in-outline')
            ->assertSee('directiva.liga@playbook.cl');
    }

    public function test_vista_roster_renderiza_estetica_y_textos_humanizados(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();

        $response = $this->get("/equipos/{$titans->id}");
        $response->assertOk()
            ->assertSee('Volver a equipos')
            ->assertDontSee('Roster Oficial ·')
            ->assertDontSee('PASO 3: MATRIZ')
            ->assertSee('Faltan titulares en MID y SUPPORT')
            ->assertSee('Posición vacante')
            ->assertSee('Asignar a')
            ->assertSee('Bajar a suplente')
            ->assertSee('Suplentes y jugadores a prueba')
            ->assertSee('Agregar jugador al equipo')
            ->assertDontSee('(RF.07)')
            ->assertSee('Ligas inscritas')
            ->assertSee('Disponibilidad semanal del equipo')
            ->assertSee('tabla-heatmap')
            ->assertSee('celda-heatmap-badge');
    }

    public function test_vista_fixture_renderiza_estetica_y_textos_humanizados(): void
    {
        $response = $this->get('/');
        $response->assertOk()
            ->assertSee('Fixture')
            ->assertDontSee('Competición Oficial · Temporada 2026')
            ->assertDontSee('VALIDACIÓN DE REGLAS')
            ->assertDontSee('PASO 1: PANEL DE VALIDACIÓN')
            ->assertDontSee('PASO 2: PROGRAMADOR')
            ->assertSee('Validación del fixture')
            ->assertSee('Equilibrio local / visita')
            ->assertSee('Descanso mínimo')
            ->assertSee('Penalización')
            ->assertSee('Revalidar')
            ->assertSee('Publicar fixture')
            ->assertSee('Programar partido')
            ->assertSee('Evaluar horarios')
            ->assertSee('programador-partidos');
    }
}
