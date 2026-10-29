<?php

namespace App\Console\Commands;

use App\Enums\FormatoSerieEnum;
use App\Models\Equipo;
use App\Models\Liga;
use App\Services\Disponibilidad\MotorConflictosService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Comando CLI de diagnóstico y demostración del Motor de Conflictos y Disponibilidad (RF.02, RF.05, RF.08, RF.09).
 */
class EvaluarMotorConflictosCommand extends Command
{
    protected $signature = 'playbook:motor-conflictos
        {--equipo=UDD Titans : Nombre o ID del equipo principal a evaluar}
        {--rival=PUC Paladins : Nombre o ID del equipo rival para cruce de horarios}
        {--liga=Liga Universitaria LoL 2026 : Nombre o ID de la liga a validar}
        {--semana=2026-11-03 : Fecha (YYYY-MM-DD) dentro de la semana a evaluar}
        {--formato=bo3 : Formato de serie (bo1, bo3, bo5)}
        {--minutos=50 : Duración estimada en minutos por cada partida (n * minutos)}
        {--solo-disponibles : Mostrar únicamente las ventanas horarias libres en el cruce de enfrentamiento}';

    protected $description = 'Ejecuta y demuestra por CLI el Motor de Conflictos, Grilla Semanal de Disponibilidad y Validador de Fixture';

    public function handle(MotorConflictosService $motor): int
    {
        $formatoValue = strtolower((string) $this->option('formato'));
        $formato = FormatoSerieEnum::tryFrom($formatoValue) ?? FormatoSerieEnum::BO1;
        $minutosPorPartida = max(15, (int) $this->option('minutos'));
        $fechaSemana = CarbonImmutable::parse((string) $this->option('semana'));
        $duracionTotal = $motor->calcularDuracionSerie($formato, $minutosPorPartida);

        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=cyan;options=bold>PLAYBOOK · MOTOR DE CONFLICTOS Y DISPONIBILIDAD</>',
            sprintf(
                '<fg=yellow>%s · %d min/partida = %d min totales (%d x %d)</>',
                $formato->label(),
                $minutosPorPartida,
                $duracionTotal,
                $formato->maxPartidas(),
                $minutosPorPartida
            )
        );
        $this->line(str_repeat('─', 100));

        // 1. Resolver Equipo Principal
        $equipoOpt = (string) $this->option('equipo');
        $equipo = is_numeric($equipoOpt)
            ? Equipo::with(['disciplina', 'membresias.jugador'])->find((int) $equipoOpt)
            : Equipo::with(['disciplina', 'membresias.jugador'])->where('nombre', $equipoOpt)->first();

        if (! $equipo) {
            $this->components->error("No se encontró el equipo '{$equipoOpt}'. Ejecuta primero: php artisan migrate:fresh --seed");

            return self::FAILURE;
        }

        // =========================================================================
        // SECCIÓN 1: GRILLA DE DISPONIBILIDAD SEMANAL DEL EQUIPO (Titulares + Suplentes)
        // =========================================================================
        $this->newLine();
        $this->info("1) MATRIZ DE DISPONIBILIDAD SEMANAL — {$equipo->nombre} ({$equipo->disciplina?->nombre})");

        $horasEvaluadas = ['15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];
        $matriz = $motor->generarGrillaDisponibilidadSemanalEquipo(
            equipo: $equipo,
            fechaEnSemana: $fechaSemana,
            formatoSerie: $formato,
            minutosPorPartida: $minutosPorPartida,
            horasBloques: $horasEvaluadas,
        );

        $this->comment(sprintf(
            '   Semana: %s al %s | Duración bloque (%s): %d min | Quórum Práctica: >=%d/%d | Quórum Oficial: %d/%d posiciones cubiertas',
            $matriz['semana_inicio'],
            $matriz['semana_fin'],
            strtoupper($formato->value),
            $matriz['duracion_bloque_minutos'],
            $matriz['quorum_minimo_practica'],
            $matriz['titulares_requeridos'],
            $matriz['titulares_requeridos'],
            $matriz['titulares_requeridos'],
        ));

        $dias = array_keys($matriz['grilla']);
        $headers = array_merge(['Hora Inicio (Duración)'], $dias);
        $filasGrilla = [];

        foreach ($horasEvaluadas as $hora) {
            $fila = ["{$hora} ({$duracionTotal}m)"];
            foreach ($dias as $dia) {
                $celda = $matriz['grilla'][$dia][$hora];
                $ratio = $celda['ratio_disponibilidad'];
                $dispTotal = $celda['disponibles_total'];

                $fila[] = match ($celda['estado_celda']) {
                    'OPTIMO_OFICIAL' => "<fg=green>✔ {$ratio} (Total:{$dispTotal})</>",
                    'APTO_PRACTICA' => "<fg=yellow>~ {$ratio} (Práctica)</>",
                    default => "<fg=red>✖ {$ratio} (Bloq)</>",
                };
            }
            $filasGrilla[] = $fila;
        }

        $this->table($headers, $filasGrilla);

        // Mostrar detalle de ausencias (recurrentes, unitarios y choques de partido) detectadas en la semana
        $this->line('<fg=cyan;options=bold>   Detalle de Bloqueos (Recurrentes / Unitarios) y Cruces de Partido detectados en la semana:</>');
        $eventosMostrados = [];
        foreach ($matriz['grilla'] as $dia => $horas) {
            foreach ($horas as $hora => $celda) {
                foreach ($celda['ausentes'] as $ausente) {
                    foreach ($ausente['motivos_ausencia'] as $motivo) {
                        $claveUnica = "{$dia}|{$ausente['user_id']}|{$motivo['etiqueta']}";
                        if (isset($eventosMostrados[$claveUnica])) {
                            continue;
                        }
                        $eventosMostrados[$claveUnica] = true;

                        $tagTipo = match ($motivo['origen']) {
                            'PARTIDO_MULTI_LIGA' => '<fg=red;options=bold>[CHOQUE PARTIDO]</>',
                            'BLOQUEO_UNITARIO' => '<fg=magenta;options=bold>[BLOQUEO UNITARIO]</>',
                            default => '<fg=yellow>[BLOQUEO RECURRENTE]</>',
                        };

                        $this->line(sprintf(
                            '   • %s <options=bold>%s (%s)</> el %s (%s): %s',
                            $tagTipo,
                            $ausente['nombre'],
                            $ausente['posicion_principal'] ?? $ausente['rol_membresia'],
                            $dia,
                            $celda['fecha'],
                            $motivo['detalle']
                        ));
                    }
                }
            }
        }

        // =========================================================================
        // SECCIÓN 2: CRUCE DE HORARIOS ENTRE 2 EQUIPOS (Sugeridor para Admin de Liga y Coach)
        // =========================================================================
        $rivalOpt = (string) $this->option('rival');
        $rival = is_numeric($rivalOpt)
            ? Equipo::with(['disciplina', 'membresias.jugador'])->find((int) $rivalOpt)
            : Equipo::with(['disciplina', 'membresias.jugador'])->where('nombre', $rivalOpt)->first();

        $ligaOpt = (string) $this->option('liga');
        $liga = is_numeric($ligaOpt)
            ? Liga::find((int) $ligaOpt)
            : Liga::where('nombre', $ligaOpt)->first();

        if ($rival) {
            $this->newLine();
            $this->info("2) CRUCE DE HORARIOS PARA ENFRENTAMIENTO: {$equipo->nombre} vs {$rival->nombre} ({$formato->label()} = {$duracionTotal} min)");

            $soloDisponibles = (bool) $this->option('solo-disponibles');
            $ventanas = $motor->sugerirVentanasParaEnfrentamiento(
                equipoLocal: $equipo,
                equipoVisitante: $rival,
                fechaEnSemana: $fechaSemana,
                formatoSerie: $formato,
                minutosPorPartida: $minutosPorPartida,
                horasCandidatas: ['16:00', '18:00', '20:00'],
                liga: $liga,
                soloDisponibles: $soloDisponibles,
            );

            $filasVentanas = [];
            foreach ($ventanas as $v) {
                $motivoResumen = $v->conflictos->isEmpty()
                    ? 'Sin topes horarios (Titulares y Suplentes habilitados)'
                    : $v->conflictos->map(fn ($c) => "[{$c->codigoRegla}] {$c->mensaje}")->first();

                $estadoColor = $v->disponible
                    ? ($v->conflictos->isEmpty() ? "<fg=green>{$v->etiquetaEstado}</>" : "<fg=yellow>{$v->etiquetaEstado}</>")
                    : "<fg=red>{$v->etiquetaEstado}</>";

                $filasVentanas[] = [
                    $v->fecha,
                    "{$v->horaInicio} - {$v->horaFin}",
                    $estadoColor,
                    $motivoResumen,
                ];
            }

            $this->table(
                ['Fecha', 'Ventana ('.$duracionTotal.'m)', 'Estado de Aprobación', 'Diagnóstico del Motor (Mains + Suplentes)'],
                array_slice($filasVentanas, 0, 12)
            );
        }

        // =========================================================================
        // SECCIÓN 3: AUDITORÍA Y VALIDACIÓN AUTOMÁTICA DEL FIXTURE DE LA LIGA (RF.09)
        // =========================================================================
        if ($liga) {
            $this->newLine();
            $this->info("3) VALIDACIÓN AUTOMÁTICA DE FIXTURE — {$liga->nombre} (Temporada {$liga->temporada})");

            $reporte = $motor->validarFixtureLiga($liga);

            $this->components->twoColumnDetail(
                'Estado de Publicación del Fixture',
                $reporte['puede_publicarse']
                    ? '<fg=green;options=bold>HABILITADO PARA PUBLICAR (0 Violaciones Duras)</>'
                    : sprintf('<fg=red;options=bold>BLOQUEADO (%d Violación Dura detectada)</>', $reporte['violaciones_duras_count'])
            );
            $this->components->twoColumnDetail(
                'Métricas de Calidad',
                sprintf(
                    'Equilibrio Localía: %d%% | Descansos Mínimos (>=24h): %d%% | Penalización: %d/%d pts',
                    $reporte['metricas']['equilibrio_localia_pct'],
                    $reporte['metricas']['descansos_minimos_pct'],
                    $reporte['penalizacion_total'],
                    $reporte['tolerancia_penalizacion_max']
                )
            );

            $filasPartidos = [];
            foreach ($reporte['partidos_evaluados'] as $p) {
                $filasPartidos[] = [
                    "#{$p['partido_id']} (J{$p['jornada']})",
                    "{$p['local']} vs {$p['visitante']}",
                    "{$p['fecha_hora_inicio']} -> ".substr((string) $p['fecha_hora_fin'], 11),
                    strtoupper((string) $p['formato_serie'])." ({$p['duracion_minutos']}m)",
                    $p['tiene_violacion_dura'] ? '<fg=red;options=bold>CONFLICTO DURO</>' : '<fg=green>OK / TENTATIVO</>',
                    count($p['conflictos']),
                ];
            }

            $this->table(
                ['Partido / Jornada', 'Enfrentamiento', 'Horario', 'Formato / Duración', 'Validación', 'Alertas'],
                $filasPartidos
            );

            if ($reporte['conflictos'] !== []) {
                $this->line('<fg=yellow;options=bold>   Alertas Reglamentarias Detectadas en el Fixture:</>');
                foreach ($reporte['conflictos'] as $alerta) {
                    $badge = $alerta['bloquea_publicacion']
                        ? '<fg=red;options=bold>[VIOLACIÓN DURA - BLOQUEA PUBLICACIÓN]</>'
                        : '<fg=yellow;options=bold>[ADVERTENCIA BLANDA - INFORMATIVA]</>';

                    $this->line("   • {$badge} ({$alerta['codigo_regla']}): {$alerta['mensaje']}");
                }
            }
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
