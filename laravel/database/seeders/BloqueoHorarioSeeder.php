<?php

namespace Database\Seeders;

use App\Domain\Enums\DiaSemanaEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RF.02: Bloqueos horarios (clases/estudio) de los jugadores de demostración.
 */
class BloqueoHorarioSeeder extends Seeder
{
    public function run(): void
    {
        // Bloqueos originales de javier@email.com (usados por la app Ionic).
        $this->recurrente('javier@email.com', DiaSemanaEnum::LUNES, '08:00', '14:00', 'Académico');
        $this->recurrente('javier@email.com', DiaSemanaEnum::MIERCOLES, '16:00', '18:00', 'Académico');

        // Mockup Ionic: "Bloqueo Académico: Certamen" viernes 15:00–18:00 en Sala 402.
        $this->puntual('diego.perez@udd.cl', '2026-11-06', '15:00', '18:00', 'Certamen', 'Sala 402, Campus UDD');
        $this->recurrente('diego.perez@udd.cl', DiaSemanaEnum::MARTES, '08:30', '12:00', 'Académico');

        // Mockup Flutter: Cata Ríos ausente el jueves 19:00 por motivo académico.
        $this->recurrente('cata.rios@udd.cl', DiaSemanaEnum::JUEVES, '19:00', '21:00', 'Académico');

        $this->recurrente('valentina.soto@udd.cl', DiaSemanaEnum::MARTES, '18:00', '20:00', 'Académico');
        $this->recurrente('tomas.herrera@udd.cl', DiaSemanaEnum::VIERNES, '17:00', '20:00', 'Trabajo');
    }

    private function recurrente(string $email, DiaSemanaEnum $dia, string $inicio, string $fin, string $motivo): void
    {
        $this->insertar($email, $dia, null, $inicio, $fin, $motivo, null, true);
    }

    private function puntual(string $email, string $fecha, string $inicio, string $fin, string $motivo, ?string $ubicacion): void
    {
        $dia = DiaSemanaEnum::fromIso((int) date('N', strtotime($fecha)));
        $this->insertar($email, $dia, $fecha, $inicio, $fin, $motivo, $ubicacion, false);
    }

    private function insertar(
        string $email,
        DiaSemanaEnum $dia,
        ?string $fecha,
        string $inicio,
        string $fin,
        string $motivo,
        ?string $ubicacion,
        bool $recurrente,
    ): void {
        DB::table('bloqueos_horarios')->insert([
            'user_id' => DB::table('users')->where('email', $email)->value('id'),
            'dia_semana' => $dia->value,
            'dia_semana_iso' => $dia->iso(),
            'fecha_especifica' => $fecha,
            'hora_inicio' => $inicio.':00',
            'hora_fin' => $fin.':00',
            'motivo' => $motivo,
            'ubicacion' => $ubicacion,
            'es_recurrente' => $recurrente,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
