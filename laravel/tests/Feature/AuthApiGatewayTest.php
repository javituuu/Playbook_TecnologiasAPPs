<?php

namespace Tests\Feature;

use App\Domain\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_jugador_puede_iniciar_sesion_y_recibir_token_y_contexto(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'javier@email.com',
            'password' => 'password',
            'device_name' => 'ionic-android',
            'rol_esperado' => 'jugador',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token_type',
                    'access_token',
                    'abilities',
                    'user' => [
                        'id',
                        'nombre_completo',
                        'email',
                        'role',
                        'role_label',
                        'activo',
                        'contexto' => [
                            'equipo_principal_id',
                            'equipo_principal',
                            'club_actual',
                            'disciplina',
                            'posicion',
                            'rango',
                        ],
                    ],
                ],
            ]);

        $this->assertSame('Bearer', $response->json('data.token_type'));
        $this->assertSame('jugador', $response->json('data.user.role'));
        $this->assertSame('UDD Titans', $response->json('data.user.contexto.equipo_principal'));
        $this->assertContains('role:jugador', $response->json('data.abilities'));
    }

    public function test_coach_puede_iniciar_sesion_y_recibe_equipos_dirigidos(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'coach.martinez@udd.cl',
            'password' => 'password',
            'device_name' => 'flutter-tablet',
            'rol_esperado' => 'coach',
        ]);

        $response->assertOk();
        $this->assertSame('coach', $response->json('data.user.role'));
        $this->assertContains('role:coach', $response->json('data.abilities'));
        $this->assertNotEmpty($response->json('data.user.contexto.equipos_dirigidos'));
        $this->assertSame('UDD Titans', $response->json('data.user.contexto.equipos_dirigidos.0.nombre'));
    }

    public function test_login_rechaza_credenciales_incorrectas_con_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'javier@email.com',
            'password' => 'clave-incorrecta',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_login_rechaza_rol_incorrecto_cuando_app_envia_rol_esperado(): void
    {
        // Un jugador intentando entrar a la app exclusiva de Coach (Flutter)
        $response = $this->postJson('/api/auth/login', [
            'email' => 'javier@email.com',
            'password' => 'password',
            'rol_esperado' => UserRoleEnum::COACH->value,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_login_rechaza_usuario_inactivo(): void
    {
        User::where('email', 'javier@email.com')->update(['activo' => false]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'javier@email.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'La cuenta de usuario se encuentra desactivada.',
            ]);
    }

    public function test_endpoint_me_y_verify_validan_token_bearer_y_retornan_usuario(): void
    {
        $login = $this->postJson('/api/auth/login', [
            'email' => 'diego.perez@udd.cl',
            'password' => 'password',
        ]);

        $token = $login->json('data.access_token');

        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $meResponse->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.user.email', 'diego.perez@udd.cl');

        $verifyResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/verify');

        $verifyResponse->assertOk()
            ->assertJsonPath('data.user.nombre_completo', 'Diego Pérez');
    }

    public function test_endpoints_de_jugador_resuelven_dinamicamente_al_usuario_del_token_bearer(): void
    {
        $loginDiego = $this->postJson('/api/auth/login', [
            'email' => 'diego.perez@udd.cl',
            'password' => 'password',
        ]);

        $tokenDiego = $loginDiego->json('data.access_token');

        // Al consultar /api/perfil con el token de Diego, debe devolver a Diego Pérez (Jungla, Platino 2)
        $perfilDiego = $this->withHeader('Authorization', "Bearer {$tokenDiego}")
            ->getJson('/api/perfil');

        $perfilDiego->assertOk()
            ->assertJsonPath('data.nombre_completo', 'Diego Pérez')
            ->assertJsonPath('data.email', 'diego.perez@udd.cl')
            ->assertJsonPath('data.rol', 'JUNGLA')
            ->assertJsonPath('data.rango', 'Platino 2');

        // Un jugador no puede eliminar un bloqueo horario que pertenece a otro jugador (Javier)
        $bloqueoDeJavierId = $this->withoutHeader('Authorization')
            ->getJson('/api/perfil')
            ->json('data.horarios_bloqueados.0.id');

        $intentoBorrarAjeno = $this->withHeader('Authorization', "Bearer {$tokenDiego}")
            ->deleteJson("/api/bloqueos/{$bloqueoDeJavierId}");

        $intentoBorrarAjeno->assertStatus(403);
    }

    public function test_token_invalido_es_rechazado_con_401_en_rutas_de_recursos(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer token-falso-invalido')
            ->getJson('/api/perfil');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_logout_revoca_el_token_actual(): void
    {
        $login = $this->postJson('/api/auth/login', [
            'email' => 'javier@email.com',
            'password' => 'password',
        ]);

        $token = $login->json('data.access_token');

        $logout = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $logout->assertOk()
            ->assertJsonPath('status', 'success');

        // Verificar en base de datos que el token fue eliminado
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_all_revoca_multiples_dispositivos_del_usuario(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'javier@email.com',
            'password' => 'password',
            'device_name' => 'telefono-android',
        ]);

        $loginTablet = $this->postJson('/api/auth/login', [
            'email' => 'javier@email.com',
            'password' => 'password',
            'device_name' => 'tablet-ios',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $tokenTablet = $loginTablet->json('data.access_token');

        $response = $this->withHeader('Authorization', "Bearer {$tokenTablet}")
            ->postJson('/api/auth/logout-all');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('tokens_revocados', 2);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
