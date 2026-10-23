<?php

namespace Database\Seeders;

use App\Enums\EstadoInvitacionEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Usuarios de los 4 roles, clubes, equipos y rosters.
 *
 * Casos de demostración incluidos (ver mockups de Figma):
 *  - UDD Titans (LoL): roster incompleto, falta SUPPORT (laravel-admin-club-roster.png).
 *  - Diego Pérez juega en UDD Titans (LoL) y UDD Nova (Valorant): base del choque multi-liga.
 *  - javier@email.com se mantiene como jugador principal que consume la app Ionic.
 *
 * Todas las cuentas usan la contraseña "password".
 */
class UsuarioClubEquipoSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Usuarios administrativos ----------
        $adminLiga = $this->usuario('Directiva Liga', 'directiva.liga@playbook.cl', UserRoleEnum::ADMIN_LIGA);
        $adminClubUdd = $this->usuario('Directiva Club UDD', 'directiva.club@udd.cl', UserRoleEnum::ADMIN_CLUB);
        $coachMartinez = $this->usuario('Coach Martínez', 'coach.martinez@udd.cl', UserRoleEnum::COACH);
        $coachRojas = $this->usuario('Coach Rojas', 'coach.rojas@udd.cl', UserRoleEnum::COACH);

        // ---------- Clubes ----------
        $clubes = [
            'UDD' => $this->club('Universidad del Desarrollo', 'UDD', $adminClubUdd->id),
            'UNAB' => $this->club('Universidad Andrés Bello', 'UNAB'),
            'INACAP' => $this->club('INACAP', 'INACAP'),
            'USM' => $this->club('Universidad Técnica Federico Santa María', 'USM'),
            'PUC' => $this->club('Pontificia Universidad Católica', 'PUC'),
        ];

        $lol = (int) DB::table('disciplinas')->where('slug', 'lol')->value('id');
        $valorant = (int) DB::table('disciplinas')->where('slug', 'valorant')->value('id');

        // ---------- UDD Titans (LoL) ----------
        $titans = $this->equipo($clubes['UDD'], $lol, 'UDD Titans', $coachMartinez->id);

        $javier = $this->usuario('Javier Benavides', 'javier@email.com', UserRoleEnum::JUGADOR);
        $diego = $this->usuario('Diego Pérez', 'diego.perez@udd.cl', UserRoleEnum::JUGADOR);
        $valentina = $this->usuario('Valentina Soto', 'valentina.soto@udd.cl', UserRoleEnum::JUGADOR);
        $tomas = $this->usuario('Tomás Herrera', 'tomas.herrera@udd.cl', UserRoleEnum::JUGADOR);
        $cata = $this->usuario('Cata Ríos', 'cata.rios@udd.cl', UserRoleEnum::JUGADOR);
        $felipe = $this->usuario('Felipe Araya', 'felipe.araya@udd.cl', UserRoleEnum::JUGADOR);

        $this->miembro($titans, $javier->id, 'TOP', null, 'Hierro 4', RolMembresiaEnum::TITULAR, principal: true);
        $this->miembro($titans, $diego->id, 'JUNGLA', null, 'Platino 2', RolMembresiaEnum::TITULAR, principal: true);
        $this->miembro($titans, $valentina->id, 'MID', null, 'Oro 1', RolMembresiaEnum::EN_PRUEBA, principal: true);
        $this->miembro($titans, $tomas->id, 'ADC', null, 'Diamante 4', RolMembresiaEnum::TITULAR, principal: true);
        // SUPPORT vacante a propósito → roster NO VÁLIDO (3/5 titulares).
        $this->miembro($titans, $cata->id, 'MID', 'SUPPORT', 'Oro 3', RolMembresiaEnum::SUPLENTE, principal: true);
        $this->miembro(
            $titans, $felipe->id, 'SUPPORT', null, 'Plata 1', RolMembresiaEnum::SUPLENTE,
            principal: true, estado: EstadoInvitacionEnum::PENDIENTE,
        );

        // ---------- UDD Nova (Valorant) — Diego Pérez juega en dos disciplinas ----------
        $nova = $this->equipo($clubes['UDD'], $valorant, 'UDD Nova', $coachRojas->id);
        $this->miembro($nova, $diego->id, 'DUELISTA', null, 'Ascendente 1', RolMembresiaEnum::TITULAR);
        $this->plantelGenerico($nova, ['INICIADOR', 'CONTROLADOR', 'CENTINELA', 'FLEX'], 'nova', 'udd.cl');

        // ---------- Derecho FC (LoL, interfacultades UDD) ----------
        $derecho = $this->equipo($clubes['UDD'], $lol, 'Derecho FC');
        $this->plantelGenerico($derecho, ['TOP', 'JUNGLA', 'MID', 'ADC', 'SUPPORT'], 'derecho', 'udd.cl');

        // ---------- Rivales ----------
        $rivales = [
            ['UNAB', $lol, 'UNAB Sharks', 'sharks', 'unab.cl'],
            ['USM', $lol, 'USM Gaming', 'usmgaming', 'usm.cl'],
            ['PUC', $lol, 'PUC Paladins', 'paladins', 'uc.cl'],
            ['INACAP', $lol, 'INACAP Esports', 'inacaplol', 'inacap.cl'],
            ['INACAP', $valorant, 'INACAP Wolves', 'wolves', 'inacap.cl'],
        ];

        foreach ($rivales as [$siglas, $disciplinaId, $nombre, $slug, $dominio]) {
            $equipoId = $this->equipo($clubes[$siglas], $disciplinaId, $nombre);
            $posiciones = $disciplinaId === $lol
                ? ['TOP', 'JUNGLA', 'MID', 'ADC', 'SUPPORT']
                : ['DUELISTA', 'INICIADOR', 'CONTROLADOR', 'CENTINELA', 'FLEX'];
            $this->plantelGenerico($equipoId, $posiciones, $slug, $dominio);
        }
    }

    private function usuario(string $nombre, string $email, UserRoleEnum $rol): User
    {
        return User::factory()->role($rol)->create([
            'name' => $nombre,
            'email' => $email,
        ]);
    }

    private function club(string $nombre, string $siglas, ?int $adminId = null): int
    {
        return DB::table('clubes')->insertGetId([
            'nombre' => $nombre,
            'siglas' => $siglas,
            'admin_user_id' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function equipo(int $clubId, int $disciplinaId, string $nombre, ?int $coachId = null): int
    {
        return DB::table('equipos')->insertGetId([
            'club_id' => $clubId,
            'disciplina_id' => $disciplinaId,
            'coach_user_id' => $coachId,
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function miembro(
        int $equipoId,
        int $userId,
        ?string $posicion,
        ?string $posicionSecundaria,
        ?string $rango,
        RolMembresiaEnum $rol,
        bool $principal = false,
        EstadoInvitacionEnum $estado = EstadoInvitacionEnum::ACEPTADA,
    ): void {
        DB::table('equipo_jugador')->insert([
            'equipo_id' => $equipoId,
            'user_id' => $userId,
            'posicion_principal' => $posicion,
            'posicion_secundaria' => $posicionSecundaria,
            'rango_declarativo' => $rango,
            'rol_membresia' => $rol->value,
            'estado_invitacion' => $estado->value,
            'es_principal' => $principal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Crea un jugador titular por posición con nombre y correo reproducibles.
     *
     * @param  list<string>  $posiciones
     */
    private function plantelGenerico(int $equipoId, array $posiciones, string $slug, string $dominio): void
    {
        foreach ($posiciones as $i => $posicion) {
            $n = $i + 1;
            $jugador = $this->usuario(
                Str::headline($slug)." Jugador {$n}",
                "{$slug}.{$n}@{$dominio}",
                UserRoleEnum::JUGADOR,
            );
            $this->miembro($equipoId, $jugador->id, $posicion, null, null, RolMembresiaEnum::TITULAR, principal: true);
        }
    }
}
