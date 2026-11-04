<?php

namespace Tests\Feature;

use App\Enums\EstadoInvitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ComponentesVisualesTest extends TestCase
{
    use RefreshDatabase;

    public function test_componente_avatar_renderiza_iniciales_y_estilos(): void
    {
        $html = Blade::render('<x-avatar nombre="Diego Valenzuela" tamano="48px" />');

        $this->assertStringContainsString('avatar', $html);
        $this->assertStringContainsString('DV', $html);
        $this->assertStringContainsString('width: 48px', $html);
        $this->assertStringContainsString('height: 48px', $html);
    }

    public function test_componente_avatar_con_foto_renderiza_imagen(): void
    {
        $html = Blade::render('<x-avatar foto="https://example.com/avatar.jpg" nombre="Faker" />');

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="https://example.com/avatar.jpg"', $html);
        $this->assertStringContainsString('alt="Faker"', $html);
        $this->assertStringNotContainsString('avatar__iniciales', $html);
    }

    public function test_componente_avatar_con_dorsal_renderiza_etiqueta_flotante(): void
    {
        $html = Blade::render('<x-avatar nombre="Alexis Sánchez" dorsal="7" tamano="74px" />');

        $this->assertStringContainsString('avatar__dorsal', $html);
        $this->assertStringContainsString('7', $html);
        $this->assertStringContainsString('width: 74px', $html);
    }

    public function test_componente_progreso_renderiza_barra_con_porcentaje(): void
    {
        $html = Blade::render('<x-progreso :porcentaje="75" />');

        $this->assertStringContainsString('progreso', $html);
        $this->assertStringContainsString('progreso__pista', $html);
        $this->assertStringContainsString('progreso__barra', $html);
        $this->assertStringContainsString('width: 75%;', $html);
        $this->assertStringContainsString('aria-valuenow="75"', $html);
    }

    public function test_componente_estado_soporta_variados_estados_y_tonos(): void
    {
        // Estados confirmados y activos
        $htmlOk = Blade::render('<x-estado :valor="$valor" />', ['valor' => EstadoPartidoEnum::PUBLICADO]);
        $this->assertStringContainsString('estado--ok', $htmlOk);
        $this->assertStringContainsString('Publicado', $htmlOk);
        $this->assertStringContainsString('estado__dot', $htmlOk);

        $htmlTitular = Blade::render('<x-estado :valor="$valor" />', ['valor' => RolMembresiaEnum::TITULAR]);
        $this->assertStringContainsString('estado--ok', $htmlTitular);
        $this->assertStringContainsString('Titular', $htmlTitular);

        // Estados de aviso; 'tentativo' se muestra como 'Por confirmar'
        $htmlTentativo = Blade::render('<x-estado :valor="$valor" />', ['valor' => EstadoPartidoEnum::TENTATIVO]);
        $this->assertStringContainsString('estado--aviso', $htmlTentativo);
        $this->assertStringContainsString('Por confirmar', $htmlTentativo);
        $this->assertStringNotContainsString('Tentativo', $htmlTentativo);

        $htmlPrueba = Blade::render('<x-estado valor="en_prueba" />');
        $this->assertStringContainsString('estado--aviso', $htmlPrueba);
        $this->assertStringContainsString('En prueba', $htmlPrueba);

        // Estados de error o conflicto
        $htmlError = Blade::render('<x-estado :valor="$valor" />', ['valor' => EstadoPartidoEnum::CONFLICTO]);
        $this->assertStringContainsString('estado--error', $htmlError);
        $this->assertStringContainsString('Conflicto', $htmlError);

        $htmlInactivo = Blade::render('<x-estado valor="inactivo" />');
        $this->assertStringContainsString('estado--error', $htmlInactivo);
        $this->assertStringContainsString('Inactivo', $htmlInactivo);

        // Estados neutros
        $htmlBorrador = Blade::render('<x-estado valor="borrador" />');
        $this->assertStringContainsString('estado--neutro', $htmlBorrador);
        $this->assertStringContainsString('Borrador', $htmlBorrador);

        // Normalización de texto cuando viene en mayúsculas o con guiones bajos
        $htmlCaps = Blade::render('<x-estado valor="EN_PRUEBA" />');
        $this->assertStringContainsString('En prueba', $htmlCaps);
        $this->assertStringNotContainsString('EN_PRUEBA', $htmlCaps);

        $htmlBorradorCaps = Blade::render('<x-estado valor="BORRADOR" />');
        $this->assertStringContainsString('Borrador', $htmlBorradorCaps);
        $this->assertStringNotContainsString('BORRADOR', $htmlBorradorCaps);
    }

    public function test_layout_panel_contiene_preload_fuente_espacio_y_elementos_de_marca(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        // Precarga de la fuente Space Grotesk
        $response->assertSee('fonts/space-grotesk/space-grotesk-latin.woff2');

        // Logo limpio de Playbook
        $response->assertSee('Playbook');
        $response->assertDontSee('ESPORTS');
        $response->assertDontSee('PANEL DE CONTROL');

        // Navegación principal
        $response->assertSee('Fixture');
        $response->assertSee('Equipos');

        // Acceso para visitas sin sesión iniciada
        $response->assertSee('Modo de prueba');
        $response->assertSee('Acceso libre sin restricción');
        $response->assertSee('Iniciar sesión');
    }

    public function test_layout_panel_con_usuario_autenticado_muestra_tarjeta_perfil(): void
    {
        $user = User::factory()->create([
            'name' => 'Coach Master',
            'email' => 'coach@test.cl',
            'role' => UserRoleEnum::COACH,
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();

        $response->assertSee('Coach Master');
        $response->assertSee('coach@test.cl');
        $response->assertSee('Coach');
        $response->assertSee('Cerrar sesión');
        $response->assertSee('avatar');
    }
}
