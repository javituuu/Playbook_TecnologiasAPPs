<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de disciplinas con sus reglas de roster y quórum.
 */
class DisciplinaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('disciplinas')->insert([
            [
                'nombre' => 'League of Legends',
                'slug' => 'lol',
                'titulares_requeridos' => 5,
                'quorum_minimo_practica' => 4,
                'duracion_partido_min' => 90,
                'posiciones_validas' => json_encode(['TOP', 'JUNGLA', 'MID', 'ADC', 'SUPPORT']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Valorant',
                'slug' => 'valorant',
                'titulares_requeridos' => 5,
                'quorum_minimo_practica' => 4,
                'duracion_partido_min' => 90,
                'posiciones_validas' => json_encode(['DUELISTA', 'INICIADOR', 'CONTROLADOR', 'CENTINELA', 'FLEX']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
