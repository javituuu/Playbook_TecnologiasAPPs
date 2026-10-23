<?php

namespace Tests\Feature;

use App\Models\Equipo;
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

    public function test_listado_de_equipos_muestra_titulares_confirmados(): void
    {
        $this->get('/equipos')
            ->assertOk()
            ->assertSee('UDD Titans')
            ->assertSee('falta MID y SUPPORT');
    }

    public function test_roster_avisa_las_posiciones_sin_titular(): void
    {
        $titans = Equipo::where('nombre', 'UDD Titans')->firstOrFail();

        $this->get("/equipos/{$titans->id}")
            ->assertOk()
            ->assertSee('Faltan titulares en MID y SUPPORT.')
            ->assertSee('Puede cubrir: Valentina Soto, Cata Ríos')
            ->assertSeeInOrder(['Titulares', 'Javier Benavides', 'Diego Pérez'])
            ->assertSeeInOrder(['Suplentes, en prueba e invitaciones', 'Valentina Soto', 'Cata Ríos', 'Felipe Araya']);
    }

    public function test_roster_de_equipo_inexistente_responde_404(): void
    {
        $this->get('/equipos/9999')->assertNotFound();
    }
}
