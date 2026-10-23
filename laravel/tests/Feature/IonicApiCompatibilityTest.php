<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IonicApiCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_perfil_endpoint_returns_expected_ionic_contract(): void
    {
        $response = $this->getJson('/api/perfil');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'nombre_completo',
                    'email',
                    'club_actual',
                    'disciplina',
                    'rol',
                    'rango',
                    'estado',
                    'horarios_bloqueados' => [
                        '*' => ['id', 'dia_semana', 'hora_inicio', 'hora_fin']
                    ]
                ]
            ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertEquals('Javier Benavides', $response->json('data.nombre_completo'));
        $this->assertEquals('javier@email.com', $response->json('data.email'));
    }

    public function test_agenda_endpoint_returns_expected_ionic_contract(): void
    {
        $response = $this->getJson('/api/agenda');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_partido',
                        'fecha_hora',
                        'liga',
                        'equipo_local',
                        'equipo_visitante',
                        'lugar',
                        'estado'
                    ]
                ]
            ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_citaciones_endpoint_returns_expected_ionic_contract(): void
    {
        $response = $this->getJson('/api/citaciones');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_citacion',
                        'id_partido',
                        'fecha_hora',
                        'equipo_rival',
                        'liga',
                        'mensaje_coach',
                        'estado_respuesta'
                    ]
                ]
            ]);

        $this->assertEquals('success', $response->json('status'));
    }

    public function test_bloqueos_crud_endpoints_work_correctly(): void
    {
        // 1. Crear bloqueo
        $createResponse = $this->postJson('/api/bloqueos', [
            'dia_semana' => 'Jueves',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00'
        ]);

        $createResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Horario bloqueado exitosamente'
            ]);

        $bloqueoId = $createResponse->json('id');
        $this->assertNotNull($bloqueoId);

        // 2. Eliminar bloqueo
        $deleteResponse = $this->deleteJson("/api/bloqueos/{$bloqueoId}");
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Horario desbloqueado exitosamente'
            ]);
    }
}
