<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear el único usuario
        $user = User::factory()->create([
            'name' => 'Javier Benavides',
            'email' => 'javier@email.com',
            'password' => bcrypt('password')
        ]);

        // Insertar bloqueos de horario
        DB::table('bloqueos_horarios')->insert([
            ['user_id' => $user->id, 'dia_semana' => 'Lunes', 'hora_inicio' => '08:00:00', 'hora_fin' => '14:00:00'],
            ['user_id' => $user->id, 'dia_semana' => 'Miércoles', 'hora_inicio' => '16:00:00', 'hora_fin' => '18:00:00'],
        ]);

        // Insertar partidos
        $partido1 = DB::table('partidos')->insertGetId([
            'liga' => 'Liga Universitaria', 'equipo_local' => 'UDD', 'equipo_visitante' => 'UNAB', 
            'lugar' => 'Estadio Municipal · Cancha 2', 'fecha_hora_inicio' => '2026-10-02 16:00:00', 'estado' => 'programado'
        ]);
        
        $partido2 = DB::table('partidos')->insertGetId([
            'liga' => 'Torneo Esports', 'equipo_local' => 'UDD', 'equipo_visitante' => 'INACAP', 
            'lugar' => 'Arena Gaming Central', 'fecha_hora_inicio' => '2026-10-03 20:30:00', 'estado' => 'programado'
        ]);

        $partido3 = DB::table('partidos')->insertGetId([
            'liga' => 'Liga Universitaria', 'equipo_local' => 'UDD', 'equipo_visitante' => 'USM Gaming', 
            'lugar' => 'Server NA', 'fecha_hora_inicio' => '2026-10-04 19:00:00', 'estado' => 'programado'
        ]);

        $partido4 = DB::table('partidos')->insertGetId([
            'liga' => 'Torneo Interfacultades LoL', 'equipo_local' => 'UDD', 'equipo_visitante' => 'Derecho FC', 
            'lugar' => 'Server BR', 'fecha_hora_inicio' => '2026-10-09 20:00:00', 'estado' => 'programado'
        ]);

        // Insertar citaciones
        DB::table('citaciones')->insert([
            // Partidos confirmados (Agenda)
            ['user_id' => $user->id, 'partido_id' => $partido1, 'mensaje_coach' => 'Vamos con todo!', 'estado_respuesta' => 'confirmado'],
            ['user_id' => $user->id, 'partido_id' => $partido2, 'mensaje_coach' => 'Necesitamos ganar.', 'estado_respuesta' => 'confirmado'],
            
            // Partidos pendientes (Citaciones pendientes a responder)
            ['user_id' => $user->id, 'partido_id' => $partido3, 'mensaje_coach' => '¿Cuento contigo para este finde?', 'estado_respuesta' => 'pendiente'],
            ['user_id' => $user->id, 'partido_id' => $partido4, 'mensaje_coach' => 'Torneo importante, preparate.', 'estado_respuesta' => 'pendiente']
        ]);
    }
}
