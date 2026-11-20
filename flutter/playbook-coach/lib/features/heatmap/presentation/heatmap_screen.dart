import 'package:material_ui/material_ui.dart';

import '../../../app/theme/playbook_theme.dart';
import '../../auth/domain/equipo_dirigido.dart';
import '../../practicas/domain/practica.dart';
import '../../practicas/presentation/practicas_controller.dart';
import '../../practicas/presentation/widgets/agendar_practica_sheet.dart';
import '../../practicas/presentation/widgets/cancelar_practica_dialog.dart';
import '../domain/bloque_disponibilidad.dart';
import '../domain/matriz_disponibilidad.dart';
import '../domain/serie_duracion.dart';
import 'heatmap_controller.dart';
import 'widgets/block_detail_panel.dart';
import 'widgets/heatmap_grid.dart';
import 'widgets/heatmap_legend.dart';
import 'widgets/quorum_threshold_banner.dart';

/// Pantalla principal de la Matriz de Disponibilidad Semanal y Detalle del Bloque.
///
/// En pantallas anchas (`>= 1000px`) muestra la matriz y el panel derecho lado a lado,
/// fiel a `flutter-coach-heatmap.png`. En móvil muestra un panel resumen inferior
/// y abre el detalle completo en una hoja modal inferior.
class HeatmapScreen extends StatelessWidget {
  const HeatmapScreen({
    super.key,
    required this.heatmapController,
    required this.practicasController,
    required this.equipo,
    this.onOpenPracticas,
  });

  static const double splitLayoutBreakpoint = 1000;

  final HeatmapController heatmapController;
  final PracticasController practicasController;
  final EquipoDirigido? equipo;
  final VoidCallback? onOpenPracticas;

  Future<void> _openAgendarSheet(
    BuildContext context,
    BloqueDisponibilidad bloque,
  ) async {
    final activeEquipo = equipo;
    if (activeEquipo == null || !bloque.cumpleQuorum) {
      return;
    }

    final created = await showAgendarPracticaSheet(
      context: context,
      equipo: activeEquipo,
      semanaIso: heatmapController.semanaIso,
      bloque: bloque,
      initialSerie: heatmapController.selectedSerie,
      practicasController: practicasController,
    );

    if (created != null && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Práctica agendada: ${created.diaNombre} ${created.horaInicio} vs ${created.rival}.',
          ),
          action: onOpenPracticas == null
              ? null
              : SnackBarAction(
                  label: 'Ver prácticas',
                  onPressed: onOpenPracticas!,
                ),
        ),
      );
    }
  }

  Future<void> _handleCancelarPractica(
    BuildContext context,
    Practica practica,
  ) async {
    final ok = await showCancelarPracticaDialog(
      context: context,
      practica: practica,
      controller: practicasController,
    );
    if (ok && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Práctica vs ${practica.rival} cancelada.'),
        ),
      );
    }
  }

  Future<void> _openMobileDetailSheet(
    BuildContext context,
    BloqueDisponibilidad bloque,
  ) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (sheetContext) {
        return ListenableBuilder(
          listenable: Listenable.merge(
            <Listenable>[heatmapController, practicasController],
          ),
          builder: (context, _) {
            final activeEquipoId = equipo?.id ?? 0;
            final scheduled = practicasController.findScheduledForBlock(
              equipoId: activeEquipoId,
              semanaIso: heatmapController.semanaIso,
              diaSemana: bloque.diaSemana,
              hora: bloque.hora,
            );

            return SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
              child: BlockDetailPanel(
                bloque: bloque,
                serie: heatmapController.selectedSerie,
                scheduledPractica: scheduled,
                showCloseButton: true,
                onCancelarPractica: (p) async {
                  Navigator.of(sheetContext).pop();
                  await _handleCancelarPractica(context, p);
                },
                onAgendar: () async {
                  Navigator.of(sheetContext).pop();
                  await _openAgendarSheet(context, bloque);
                },
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return ListenableBuilder(
      listenable: Listenable.merge(
        <Listenable>[heatmapController, practicasController],
      ),
      builder: (context, _) {
        final matriz = heatmapController.matriz;
        final selectedBlock = heatmapController.selectedBlock;
        final activeEquipoId = equipo?.id ?? matriz?.equipoId ?? 0;

        if (heatmapController.isLoading && matriz == null) {
          return const Center(child: CircularProgressIndicator());
        }

        if (heatmapController.errorMessage != null && matriz == null) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: <Widget>[
                      Icon(Icons.cloud_off_rounded, size: 36, color: colors.error),
                      const SizedBox(height: 10),
                      Text(
                        heatmapController.errorMessage!,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: colors.onSurface,
                        ),
                      ),
                      const SizedBox(height: 14),
                      FilledButton(
                        onPressed: equipo == null
                            ? null
                            : () => heatmapController.loadHeatmap(
                                  equipoId: equipo!.id,
                                ),
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          );
        }

        if (matriz == null) {
          return Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Image.asset(
                    'assets/brand/disponibilidad-vacia.png',
                    width: 112,
                    height: 84,
                    excludeFromSemantics: true,
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'No hay datos de disponibilidad para mostrar.',
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          );
        }

        final scheduledForSelected = selectedBlock == null
            ? null
            : practicasController.findScheduledForBlock(
                equipoId: activeEquipoId,
                semanaIso: matriz.semanaIso,
                diaSemana: selectedBlock.diaSemana,
                hora: selectedBlock.hora,
              );

        return LayoutBuilder(
          builder: (context, constraints) {
            final isSplit =
                constraints.maxWidth >= HeatmapScreen.splitLayoutBreakpoint;

            final compact = constraints.maxWidth < 580;
            final pinSummary = constraints.maxHeight >= 400;
            final summary = selectedBlock == null
                ? null
                : _MobileBottomSummaryBar(
                    bloque: selectedBlock,
                    onOpenDetail: () =>
                        _openMobileDetailSheet(context, selectedBlock),
                    onAgendar: selectedBlock.cumpleQuorum
                        ? () => _openAgendarSheet(context, selectedBlock)
                        : null,
                  );
            final matrixSection = SingleChildScrollView(
              padding: EdgeInsets.all(compact ? 12 : 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: <Widget>[
                  _HeatmapHeader(matriz: matriz, showLegend: !compact),
                  SizedBox(height: compact ? 8 : 16),
                  _ControlsToolbar(
                    controller: heatmapController,
                    scheduledCount: practicasController.programadas.length,
                    onOpenPracticas: onOpenPracticas,
                  ),
                  SizedBox(height: compact ? 8 : 16),
                  HeatmapGrid(
                    matriz: matriz,
                    selectedBlock: selectedBlock,
                    isSlotScheduled: (block) {
                      return practicasController.findScheduledForBlock(
                            equipoId: activeEquipoId,
                            semanaIso: matriz.semanaIso,
                            diaSemana: block.diaSemana,
                            hora: block.hora,
                          ) !=
                          null;
                    },
                    onSelectBlock: (block) {
                      heatmapController.selectBlock(block);
                      if (!isSplit) {
                        _openMobileDetailSheet(context, block);
                      }
                    },
                  ),
                  const SizedBox(height: 12),
                  if (compact) ...[
                    HeatmapLegend(totalJugadores: matriz.totalJugadores),
                    const SizedBox(height: 12),
                  ],
                  QuorumThresholdBanner(
                    quorumMinimo: matriz.quorumMinimo,
                    totalJugadores: matriz.totalJugadores,
                  ),
                  if (!isSplit && !pinSummary && summary != null) ...[
                    const SizedBox(height: 12),
                    summary,
                  ],
                ],
              ),
            );

            if (isSplit) {
              return Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: <Widget>[
                  Expanded(child: matrixSection),
                  const VerticalDivider(width: 1),
                  SizedBox(
                    width: 350,
                    child: Material(
                      color: colors.surface,
                      child: selectedBlock == null
                          ? const Center(
                              child: Text('Selecciona un bloque horario.'),
                            )
                          : SingleChildScrollView(
                              padding: const EdgeInsets.all(20),
                              child: BlockDetailPanel(
                                bloque: selectedBlock,
                                serie: heatmapController.selectedSerie,
                                scheduledPractica: scheduledForSelected,
                                onCancelarPractica: (p) =>
                                    _handleCancelarPractica(context, p),
                                onAgendar: () =>
                                    _openAgendarSheet(context, selectedBlock),
                              ),
                            ),
                    ),
                  ),
                ],
              );
            }

            if (!pinSummary || summary == null) return matrixSection;

            return Column(
              children: [
                Expanded(child: matrixSection),
                summary,
              ],
            );
          },
        );
      },
    );
  }
}

class _HeatmapHeader extends StatelessWidget {
  const _HeatmapHeader({required this.matriz, required this.showLegend});

  final MatrizDisponibilidad matriz;
  final bool showLegend;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Wrap(
      alignment: WrapAlignment.spaceBetween,
      crossAxisAlignment: WrapCrossAlignment.end,
      spacing: 16,
      runSpacing: 12,
      children: <Widget>[
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Matriz de Disponibilidad Semanal',
              style: TextStyle(
                fontFamily: 'League Spartan',
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: colors.onSurface,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              'Quórum mínimo para práctica: ${matriz.quorumMinimo} jugadores',
              style: TextStyle(fontSize: 13.5, color: colors.onSurfaceVariant),
            ),
          ],
        ),
        if (showLegend) HeatmapLegend(totalJugadores: matriz.totalJugadores),
      ],
    );
  }
}

class _ControlsToolbar extends StatelessWidget {
  const _ControlsToolbar({
    required this.controller,
    required this.scheduledCount,
    this.onOpenPracticas,
  });

  final HeatmapController controller;
  final int scheduledCount;
  final VoidCallback? onOpenPracticas;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: colors.outlineVariant),
      ),
      child: Wrap(
        alignment: WrapAlignment.spaceBetween,
        crossAxisAlignment: WrapCrossAlignment.center,
        spacing: 12,
        runSpacing: 10,
        children: <Widget>[
          Row(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              IconButton(
                key: const Key('week_prev_button'),
                tooltip: 'Semana anterior',
                onPressed: controller.isLoading
                    ? null
                    : () => controller.changeWeek(-1),
                icon: const Icon(Icons.chevron_left_rounded),
              ),
              Flexible(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    Text(
                      controller.semanaLabel,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w700,
                        color: colors.onSurface,
                      ),
                    ),
                    Text(
                      controller.semanaIso,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 11.5,
                        color: colors.onSurfaceVariant,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                key: const Key('week_next_button'),
                tooltip: 'Semana siguiente',
                onPressed: controller.isLoading
                    ? null
                    : () => controller.changeWeek(1),
                icon: const Icon(Icons.chevron_right_rounded),
              ),
              if (!controller.isCurrentWeek)
                TextButton(
                  onPressed:
                      controller.isLoading ? null : controller.goToCurrentWeek,
                  child: const Text('Hoy'),
                ),
            ],
          ),
          Wrap(
            crossAxisAlignment: WrapCrossAlignment.center,
            spacing: 8,
            runSpacing: 6,
            children: <Widget>[
              Text(
                'Serie:',
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                  color: colors.primary,
                ),
              ),
              for (final serie in SerieDuracion.values)
                ChoiceChip(
                  key: Key('serie_chip_${serie.apiValue}'),
                  label: Text('${serie.label} · ${serie.shortDetail}'),
                  selected: controller.selectedSerie == serie,
                  visualDensity: VisualDensity.compact,
                  onSelected: (_) => controller.selectSerie(serie),
                ),
              if (onOpenPracticas != null)
                ActionChip(
                  avatar: Icon(
                    Icons.event_available_rounded,
                    size: 16,
                    color: colors.primary,
                  ),
                  label: Text('Prácticas ($scheduledCount)'),
                  onPressed: onOpenPracticas,
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MobileBottomSummaryBar extends StatelessWidget {
  const _MobileBottomSummaryBar({
    required this.bloque,
    required this.onOpenDetail,
    required this.onAgendar,
  });

  final BloqueDisponibilidad bloque;
  final VoidCallback onOpenDetail;
  final VoidCallback? onAgendar;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final cumple = bloque.cumpleQuorum;

    return Container(
      padding: const EdgeInsets.fromLTRB(12, 4, 12, 4),
      decoration: BoxDecoration(
        color: colors.surface,
        border: Border(top: BorderSide(color: colors.outlineVariant)),
      ),
      child: Row(
        children: <Widget>[
          Expanded(
            flex: 3,
            child: InkWell(
              key: const Key('mobile_block_summary_tap'),
              onTap: onOpenDetail,
              borderRadius: BorderRadius.circular(8),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    Text(
                      '${bloque.tituloDetalle} · ${bloque.ratioLabel}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: colors.onSurface,
                      ),
                    ),
                    Text(
                      cumple
                          ? 'Cumple quórum · Ver detalle'
                          : 'Sin quórum · Ver detalle',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: cumple ? PlaybookTheme.success(colors) : colors.error,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Flexible(
            flex: 2,
            child: FilledButton(
              key: const Key('mobile_agendar_aqui_button'),
              onPressed: onAgendar,
              style: FilledButton.styleFrom(
                minimumSize: const Size(0, 48),
                padding: const EdgeInsets.symmetric(horizontal: 12),
              ),
              child: const Text('Agendar aquí', textAlign: TextAlign.center),
            ),
          ),
        ],
      ),
    );
  }
}
