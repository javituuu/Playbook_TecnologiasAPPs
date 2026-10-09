<?php

namespace Database\Seeders;

use App\Models\Partido;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Javier Benavides',
            'email' => 'javier@email.com',
            'password' => bcrypt('password'),
        ]);

        $user->bloqueos()->createMany([
            ['dia_semana' => 'Lunes', 'hora_inicio' => '08:00:00', 'hora_fin' => '14:00:00'],
            ['dia_semana' => 'Miércoles', 'hora_inicio' => '16:00:00', 'hora_fin' => '18:00:00'],
        ]);

        $partidos = [
            ['Liga Universitaria', 'UNAB', 'Estadio Municipal · Cancha 2', '2026-10-02 16:00:00', 'Vamos con todo!', 'confirmado'],
            ['Torneo Esports', 'INACAP', 'Arena Gaming Central', '2026-10-03 20:30:00', 'Necesitamos ganar.', 'confirmado'],
            ['Liga Universitaria', 'USM Gaming', 'Server NA', '2026-10-04 19:00:00', '¿Cuento contigo para este finde?', 'pendiente'],
            ['Torneo Interfacultades LoL', 'Derecho FC', 'Server BR', '2026-10-09 20:00:00', 'Torneo importante, preparate.', 'pendiente'],
        ];

        foreach ($partidos as [$liga, $rival, $lugar, $inicio, $mensaje, $estado]) {
            $partido = Partido::create([
                'liga' => $liga, 'equipo_local' => 'UDD', 'equipo_visitante' => $rival,
                'lugar' => $lugar, 'fecha_hora_inicio' => $inicio, 'estado' => 'programado',
            ]);
            $partido->citaciones()->create([
                'user_id' => $user->id, 'mensaje_coach' => $mensaje, 'estado_respuesta' => $estado,
            ]);
        }
    }
}
