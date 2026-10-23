<?php

namespace App\Models;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'dia_semana', 'hora_inicio', 'hora_fin'])]
=======
use App\Enums\DiaSemanaEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Franja horaria no disponible de un jugador por motivos académicos o personales (RF.02).
 */
#[Fillable([
    'user_id',
    'dia_semana',
    'dia_semana_iso',
    'fecha_especifica',
    'hora_inicio',
    'hora_fin',
    'motivo',
    'ubicacion',
    'es_recurrente',
])]
>>>>>>> rama-laravel
class BloqueoHorario extends Model
{
    protected $table = 'bloqueos_horarios';

<<<<<<< HEAD
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
=======
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia_semana' => DiaSemanaEnum::class,
            'dia_semana_iso' => 'integer',
            'fecha_especifica' => 'date',
            'es_recurrente' => 'boolean',
        ];
    }

    /**
     * Jugador propietario del bloqueo horario.
     *
     * @return BelongsTo<User, $this>
     */
    public function jugador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Filtra los bloqueos que aplican en una fecha calendario específica:
     * - Recurrentes cuyo dia_semana_iso coincida con el día ISO de la fecha (1=Lunes..7=Domingo).
     * - O puntuales cuya fecha_especifica coincida con la fecha dada.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeParaFecha(Builder $query, CarbonInterface $fecha): Builder
    {
        $diaIso = $fecha->dayOfWeekIso;
        $fechaStr = $fecha->toDateString();

        return $query->where(function (Builder $q) use ($diaIso, $fechaStr) {
            $q->where(function (Builder $recurrente) use ($diaIso) {
                $recurrente->where('es_recurrente', true)
                    ->where('dia_semana_iso', $diaIso);
            })->orWhereDate('fecha_especifica', $fechaStr);
        });
    }

    /**
     * Filtra bloqueos cuya ventana [hora_inicio, hora_fin) solape con el rango horario dado.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSolapaConHora(Builder $query, string $horaInicio, string $horaFin): Builder
    {
        $inicioNormalizado = self::normalizarHora($horaInicio);
        $finNormalizado = self::normalizarHora($horaFin);

        return $query
            ->where('hora_inicio', '<', $finNormalizado)
            ->where('hora_fin', '>', $inicioNormalizado);
    }

    /**
     * Verifica en memoria si este bloqueo solapa con el intervalo [horaInicio, horaFin).
     */
    public function solapaConRangoHora(string $horaInicio, string $horaFin): bool
    {
        $bloqueoInicio = self::normalizarHora((string) $this->hora_inicio);
        $bloqueoFin = self::normalizarHora((string) $this->hora_fin);
        $rangoInicio = self::normalizarHora($horaInicio);
        $rangoFin = self::normalizarHora($horaFin);

        return $bloqueoInicio < $rangoFin && $bloqueoFin > $rangoInicio;
    }

    /**
     * Normaliza cadenas "H:i" o "H:i:s" al formato uniforme "HH:MM:SS".
     */
    public static function normalizarHora(string $hora): string
    {
        $partes = explode(':', trim($hora));
        $h = str_pad($partes[0] ?? '00', 2, '0', STR_PAD_LEFT);
        $m = str_pad($partes[1] ?? '00', 2, '0', STR_PAD_LEFT);
        $s = str_pad($partes[2] ?? '00', 2, '0', STR_PAD_LEFT);

        return "{$h}:{$m}:{$s}";
>>>>>>> rama-laravel
    }
}
