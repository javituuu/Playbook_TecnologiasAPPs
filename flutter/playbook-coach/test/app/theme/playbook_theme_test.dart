import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:playbook_coach/app/theme/playbook_theme.dart';

void main() {
  group('PlaybookTheme', () {
    test('define los colores exactos de marca Aqua y uva', () {
      expect(PlaybookTheme.carbon, const Color(0xFF222222));
      expect(PlaybookTheme.dustyGrape, const Color(0xFF4B4E6D));
      expect(PlaybookTheme.pearlAqua, const Color(0xFF84DCC6));
      expect(PlaybookTheme.coolSteel, const Color(0xFF95A3B3));
    });

    test('registra los 6 tonos de disponibilidad del heatmap (5/5 a 0/5)', () {
      final heatmap = PlaybookHeatmapTheme.standard;

      expect(heatmap.forAvailability(5, total: 5), PlaybookTheme.heatmap5);
      expect(heatmap.forAvailability(4, total: 5), PlaybookTheme.heatmap4);
      expect(heatmap.forAvailability(3, total: 5), PlaybookTheme.heatmap3);
      expect(heatmap.forAvailability(2, total: 5), PlaybookTheme.heatmap2);
      expect(heatmap.forAvailability(1, total: 5), PlaybookTheme.heatmap1);
      expect(heatmap.forAvailability(0, total: 5), PlaybookTheme.heatmap0);

      // Todos los niveles tienen contraste distinto entre fondo y texto
      for (final tone in heatmap.orderedLevels) {
        expect(tone.background, isNot(equals(tone.foreground)));
      }
    });

    test('light y dark configuran ColorScheme y extensión PlaybookHeatmapTheme', () {
      final light = PlaybookTheme.light();
      expect(light.colorScheme.primary, PlaybookTheme.dustyGrape);
      expect(light.colorScheme.secondary, PlaybookTheme.pearlAqua);
      expect(light.colorScheme.onSurface, PlaybookTheme.carbon);
      expect(light.extension<PlaybookHeatmapTheme>(), isNotNull);

      final dark = PlaybookTheme.dark();
      expect(dark.colorScheme.surface, PlaybookTheme.carbon);
      expect(dark.colorScheme.primary, PlaybookTheme.pearlAqua);
      expect(dark.extension<PlaybookHeatmapTheme>(), isNotNull);
    });
  });
}
