<?php

namespace App\Services\Demo;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio de reinicio rápido del estado de demostración (Feria de Ciencias).
 * Vacía las tablas de negocio y vuelve a sembrar el DatabaseSeeder en milisegundos,
 * sin ejecutar DDL de migraciones completas y preservando la sesión web activa si se indica.
 */
class DemoResetService
{
    /**
     * Tablas de dominio que se limpian en cada ciclo de demostración.
     *
     * @var list<string>
     */
    private const TABLAS_NEGOCIO = [
        'citaciones',
        'partidos',
        'inscripciones_liga',
        'ligas',
        'bloqueos_horarios',
        'equipo_jugador',
        'equipos',
        'clubes',
        'disciplinas',
        'personal_access_tokens',
        'users',
    ];

    /**
     * Restaura la base de datos al estado limpio de fábrica de la demo.
     */
    public function reset(): float
    {
        $inicio = microtime(true);

        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::TABLAS_NEGOCIO as $tabla) {
                if (Schema::hasTable($tabla)) {
                    DB::table($tabla)->delete();
                }
            }

            // En MySQL reiniciamos los autoincrementales para que los IDs sean siempre predecibles (1, 2, 3...)
            if (DB::getDriverName() === 'mysql') {
                foreach (self::TABLAS_NEGOCIO as $tabla) {
                    if (Schema::hasTable($tabla)) {
                        DB::statement("ALTER TABLE `{$tabla}` AUTO_INCREMENT = 1");
                    }
                }
            } elseif (DB::getDriverName() === 'sqlite') {
                if (Schema::hasTable('sqlite_sequence')) {
                    DB::table('sqlite_sequence')->whereIn('name', self::TABLAS_NEGOCIO)->delete();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        app(DatabaseSeeder::class)->run();

        return round((microtime(true) - $inicio) * 1000, 1);
    }
}
