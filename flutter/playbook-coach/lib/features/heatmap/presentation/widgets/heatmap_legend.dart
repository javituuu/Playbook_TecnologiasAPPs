import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';

/// Leyenda visual de los 6 niveles de disponibilidad del heatmap (`5/5` a `0/5`).
class HeatmapLegend extends StatelessWidget {
  const HeatmapLegend({
    super.key,
    this.totalJugadores = 5,
  });

  final int totalJugadores;

  @override
  Widget build(BuildContext context) {
    final heatmapTheme = Theme.of(context).extension<PlaybookHeatmapTheme>() ??
        PlaybookHeatmapTheme.standard;

    return Wrap(
      crossAxisAlignment: WrapCrossAlignment.center,
      spacing: 6,
      runSpacing: 6,
      children: <Widget>[
        const Padding(
          padding: EdgeInsets.only(right: 4),
          child: Text(
            'Disponibilidad:',
            style: TextStyle(
              fontSize: 12.5,
              fontWeight: FontWeight.w500,
              color: PlaybookTheme.coolSteel,
            ),
          ),
        ),
        for (var available = totalJugadores; available >= 0; available--)
          _LegendChip(
            label: '$available/$totalJugadores',
            tone: heatmapTheme.forAvailability(
              available,
              total: totalJugadores,
            ),
          ),
      ],
    );
  }
}

class _LegendChip extends StatelessWidget {
  const _LegendChip({
    required this.label,
    required this.tone,
  });

  final String label;
  final HeatmapTone tone;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: tone.background,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: tone.border, width: 0.8),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: tone.foreground,
          fontSize: 11.5,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
