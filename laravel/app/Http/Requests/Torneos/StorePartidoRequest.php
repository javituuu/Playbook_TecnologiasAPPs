<?php

namespace App\Http\Requests\Torneos;

use App\Application\DTOs\Torneos\ProgramarPartidoDTO;
use App\Domain\Enums\EstadoPartidoEnum;
use App\Domain\Enums\FormatoSerieEnum;
use App\Domain\Enums\TipoPartidoEnum;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para programar un partido oficial de liga o una práctica interna (RF.08).
 */
class StorePartidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'liga_id' => ['nullable', 'integer', 'exists:ligas,id'],
            'equipo_local_id' => ['required', 'integer', 'exists:equipos,id'],
            'equipo_visitante_id' => [
                'nullable',
                'integer',
                'exists:equipos,id',
                'different:equipo_local_id',
            ],
            'tipo' => ['nullable', 'string', Rule::in(TipoPartidoEnum::values())],
            'formato_serie' => ['nullable', 'string', Rule::in(FormatoSerieEnum::values())],
            'minutos_por_partida' => ['nullable', 'integer', 'min:15', 'max:120'],
            'fecha_hora_inicio' => ['required', 'date'],
            'fecha_hora_fin' => ['nullable', 'date', 'after:fecha_hora_inicio'],
            'duracion_minutos' => ['nullable', 'integer', 'min:30', 'max:360'],
            'jornada_numero' => ['nullable', 'integer', 'min:1', 'max:50'],
            'bloque_etiqueta' => ['nullable', 'string', 'max:50'],
            'lugar' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function toDTO(): ProgramarPartidoDTO
    {
        $formatoSerie = $this->filled('formato_serie')
            ? FormatoSerieEnum::from((string) $this->input('formato_serie'))
            : FormatoSerieEnum::BO1;

        $minutosPorPartida = (int) $this->input(
            'minutos_por_partida',
            FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA
        );

        $duracionCalculada = $this->filled('duracion_minutos')
            ? (int) $this->input('duracion_minutos')
            : ($this->filled('formato_serie') || $this->filled('minutos_por_partida')
                ? $formatoSerie->calcularDuracionMinutos($minutosPorPartida)
                : 90);

        $inicio = CarbonImmutable::parse((string) $this->input('fecha_hora_inicio'));
        $fin = $this->filled('fecha_hora_fin')
            ? CarbonImmutable::parse((string) $this->input('fecha_hora_fin'))
            : $inicio->addMinutes($duracionCalculada);

        $tipo = $this->filled('tipo')
            ? TipoPartidoEnum::from((string) $this->input('tipo'))
            : ($this->filled('liga_id') ? TipoPartidoEnum::OFICIAL : TipoPartidoEnum::PRACTICA);

        return new ProgramarPartidoDTO(
            ligaId: $this->filled('liga_id') ? (int) $this->input('liga_id') : null,
            equipoLocalId: (int) $this->input('equipo_local_id'),
            equipoVisitanteId: $this->filled('equipo_visitante_id') ? (int) $this->input('equipo_visitante_id') : null,
            fechaHoraInicio: $inicio,
            fechaHoraFin: $fin,
            tipo: $tipo,
            estado: EstadoPartidoEnum::TENTATIVO,
            jornadaNumero: $this->filled('jornada_numero') ? (int) $this->input('jornada_numero') : null,
            bloqueEtiqueta: $this->input('bloque_etiqueta'),
            lugar: $this->input('lugar'),
            formatoSerie: $formatoSerie,
            minutosPorPartida: $minutosPorPartida,
        );
    }
}
