import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';
import '../../domain/bloque_disponibilidad.dart';
import '../../domain/matriz_disponibilidad.dart';

/// Matriz interactiva `15:00–22:00` × `Lunes–Domingo` con resaltado de selección.
class HeatmapGrid extends StatelessWidget {
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
  Widget build(BuildContext context) {
    final heatmapTheme = Theme.of(context).extension<PlaybookHeatmapTheme>() ??
        PlaybookHeatmapTheme.standard;

    return LayoutBuilder(
      builder: (context, constraints) {
        final needsHorizontalScroll = constraints.maxWidth < 560;
        final gridWidth = needsHorizontalScroll ? 580.0 : constraints.maxWidth;

        final tableContent = SizedBox(
          width: gridWidth,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: PlaybookColors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: PlaybookColors.border),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  _buildHeaderRow(),
                  const Divider(height: 1),
                  for (var r = 0; r < matriz.horas.length; r++) ...<Widget>[
                    _buildHourRow(
                      hora: matriz.horas[r],
                      heatmapTheme: heatmapTheme,
                    ),
                    if (r < matriz.horas.length - 1)
                      const Divider(height: 1, color: Color(0xFFF1F5F9)),
                  ],
                ],
              ),
            ),
          ),
        );

        if (!needsHorizontalScroll) {
          return tableContent;
        }

        return SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: tableContent,
        );
      },
    );
  }

  Widget _buildHeaderRow() {
    return Container(
      color: PlaybookColors.surface,
      padding: const EdgeInsets.symmetric(vertical: 11),
      child: Row(
        children: <Widget>[
          const SizedBox(
            width: 68,
            child: Center(
              child: Text(
                'HORA',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: PlaybookTheme.coolSteel,
                  letterSpacing: 0.6,
                ),
              ),
            ),
          ),
          for (final dia in matriz.dias)
            Expanded(
              child: Center(
                child: Text(
                  dia,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: PlaybookTheme.carbon,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildHourRow({
    required String hora,
    required PlaybookHeatmapTheme heatmapTheme,
  }) {
    return SizedBox(
      height: 50,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: <Widget>[
          SizedBox(
            width: 68,
            child: Center(
              child: Text(
                hora,
                style: const TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                  color: PlaybookTheme.carbon,
                ),
              ),
            ),
          ),
          for (var d = 0; d < matriz.dias.length; d++)
            Expanded(
              child: _buildCell(
                diaSemana: d + 1,
                hora: hora,
                heatmapTheme: heatmapTheme,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildCell({
    required int diaSemana,
    required String hora,
    required PlaybookHeatmapTheme heatmapTheme,
  }) {
    final block = matriz.bloqueAt(diaSemana, hora);
    if (block == null) {
      return const ColoredBox(color: PlaybookColors.surfaceMuted);
    }

    final tone = heatmapTheme.forAvailability(
      block.disponiblesCount,
      total: block.totalJugadores,
    );
    final isSelected = selectedBlock?.id == block.id;
    final scheduled = isSlotScheduled?.call(block) ?? false;

    return Material(
      color: tone.background,
      child: InkWell(
        key: Key('heatmap_cell_${block.diaSemana}_${block.hora}'),
        onTap: () => onSelectBlock(block),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 140),
          decoration: BoxDecoration(
            border: isSelected
                ? Border.all(color: const Color(0xFF2563EB), width: 2.5)
                : Border.all(color: Colors.white.withValues(alpha: 0.35)),
            boxShadow: isSelected
                ? const <BoxShadow>[
                    BoxShadow(
                      color: Color(0x332563EB),
                      blurRadius: 6,
                      offset: Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Stack(
            children: <Widget>[
              Center(
                child: Text(
                  block.ratioLabel,
                  style: TextStyle(
                    color: tone.foreground,
                    fontSize: 13.5,
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
                        color: PlaybookTheme.dustyGrape,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: const Icon(
                        Icons.event_available_rounded,
                        size: 11,
                        color: Colors.white,
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
