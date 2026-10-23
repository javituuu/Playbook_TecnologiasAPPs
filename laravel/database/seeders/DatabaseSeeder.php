<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra el entorno de demostración completo de Playbook.
     *
     * Cuentas (contraseña "password"):
     *  - admin_liga: directiva.liga@playbook.cl
     *  - admin_club: directiva.club@udd.cl
     *  - coach:      coach.martinez@udd.cl (UDD Titans), coach.rojas@udd.cl (UDD Nova)
     *  - jugador:    javier@email.com (usuario de la app Ionic), diego.perez@udd.cl, ...
     */
    public function run(): void
    {
        $this->call([
            DisciplinaSeeder::class,
            UsuarioClubEquipoSeeder::class,
            LigaFixtureSeeder::class,
            BloqueoHorarioSeeder::class,
        ]);
    }
}
