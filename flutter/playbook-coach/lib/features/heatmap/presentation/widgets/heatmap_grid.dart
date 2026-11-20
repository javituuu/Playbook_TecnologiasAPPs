import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';
import '../../domain/bloque_disponibilidad.dart';
import '../../domain/matriz_disponibilidad.dart';

/// Matriz interactiva `15:00–22:00` × `Lunes–Domingo` con resaltado de selección.
class HeatmapGrid extends StatefulWidget {
  const HeatmapGrid({
    super.key,
    required this.matriz,
    required this.selectedBlock,
    required this.onSelectBlock,
    this.isSlotScheduled,
  });

  final MatrizDisponibilidad matriz;
  final BloqueDisponibilidad? selectedBlock;
  final ValueChanged<BloqueDisponibilidad> onSelectBlock;
  final bool Function(BloqueDisponibilidad block)? isSlotScheduled;

  @override
  State<HeatmapGrid> createState() => _HeatmapGridState();
}

class _HeatmapGridState extends State<HeatmapGrid> {
  final _scrollController = ScrollController();

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final heatmapTheme =
        Theme.of(context).extension<PlaybookHeatmapTheme>() ??
        PlaybookHeatmapTheme.standard;

    return LayoutBuilder(
      builder: (context, constraints) {
        final textScaler = MediaQuery.textScalerOf(context);
        final minWidth =
            560.0 + (textScaler.scale(14) - 14).clamp(0, double.infinity) * 20;
        final gridWidth = constraints.maxWidth < minWidth
            ? minWidth
            : constraints.maxWidth;

        final tableContent = SizedBox(
          width: gridWidth,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: colors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: colors.outlineVariant),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  _buildHeaderRow(context),
                  const Divider(height: 1),
                  for (var r = 0; r < widget.matriz.horas.length; r++) ...<Widget>[
                    _buildHourRow(
                      context,
                      hora: widget.matriz.horas[r],
                      heatmapTheme: heatmapTheme,
                    ),
                    if (r < widget.matriz.horas.length - 1) const Divider(height: 1),
                  ],
                ],
              ),
            ),
          ),
        );

        if (gridWidth == constraints.maxWidth) {
          return tableContent;
        }

        return Scrollbar(
          controller: _scrollController,
          thumbVisibility: true,
          scrollbarOrientation: ScrollbarOrientation.top,
          child: SingleChildScrollView(
            controller: _scrollController,
            padding: const EdgeInsets.only(top: 12),
            scrollDirection: Axis.horizontal,
            child: tableContent,
          ),
        );
      },
    );
  }

  Widget _buildHeaderRow(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.surfaceContainer,
      padding: const EdgeInsets.symmetric(vertical: 11),
      child: Row(
        children: <Widget>[
          SizedBox(
            width: MediaQuery.textScalerOf(context).scale(13) * 5.25,
            child: Center(
              child: Text(
                'HORA',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                  letterSpacing: 0.6,
                ),
              ),
            ),
          ),
          for (final dia in widget.matriz.dias)
            Expanded(
              child: Center(
                child: Text(
                  dia,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildHourRow(
    BuildContext context, {
    required String hora,
    required PlaybookHeatmapTheme heatmapTheme,
  }) {
    return SizedBox(
      height:
          50 +
          (MediaQuery.textScalerOf(context).scale(14) - 14).clamp(
            0,
            double.infinity,
          ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: <Widget>[
          SizedBox(
            width: MediaQuery.textScalerOf(context).scale(13) * 5.25,
            child: Center(
              child: Text(
                hora,
                style: TextStyle(
                  fontSize: 12.5,
                  fontFeatures: const [FontFeature.tabularFigures()],
                  fontWeight: FontWeight.w600,
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
            ),
          ),
          for (var d = 0; d < widget.matriz.dias.length; d++)
            Expanded(
              child: _buildCell(
                context,
                diaSemana: d + 1,
                hora: hora,
                heatmapTheme: heatmapTheme,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildCell(
    BuildContext context, {
    required int diaSemana,
    required String hora,
    required PlaybookHeatmapTheme heatmapTheme,
  }) {
    final block = widget.matriz.bloqueAt(diaSemana, hora);
    if (block == null) {
      return ColoredBox(color: Theme.of(context).colorScheme.surfaceContainer);
    }

    final tone = heatmapTheme.forAvailability(
      block.disponiblesCount,
      total: block.totalJugadores,
    );
    final isSelected = widget.selectedBlock?.id == block.id;
    final scheduled = widget.isSlotScheduled?.call(block) ?? false;

    return Material(
      color: tone.background,
      child: InkWell(
        key: Key('heatmap_cell_${block.diaSemana}_${block.hora}'),
        onTap: () => widget.onSelectBlock(block),
        child: AnimatedContainer(
          duration: MediaQuery.disableAnimationsOf(context)
              ? Duration.zero
              : const Duration(milliseconds: 140),
          decoration: BoxDecoration(
            border: isSelected
                ? Border.all(color: tone.foreground, width: 2.5)
                : Border.all(color: tone.border),
          ),
          child: Stack(
            children: <Widget>[
              Center(
                child: Text(
                  block.ratioLabel,
                  semanticsLabel:
                      '${block.tituloDetalle}, ${block.ratioLabel} disponibles',
                  style: TextStyle(
                    color: tone.foreground,
                    fontSize: 13.5,
                    fontFeatures: const [FontFeature.tabularFigures()],
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              if (scheduled)
                Positioned(
                  top: 4,
                  right: 4,
                  child: Tooltip(
                    message: 'Práctica programada',
                    child: Container(
                      padding: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.primary,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Icon(
                        Icons.event_available_rounded,
                        size: 11,
                        color: Theme.of(context).colorScheme.onPrimary,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
