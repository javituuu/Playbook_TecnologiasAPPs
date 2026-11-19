import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/core/utils/week_utils.dart';

void main() {
  group('WeekUtils', () {
    test('calcula lunes, semana ISO y parseo inverso sin desfase', () {
      final sunday = DateTime(2026, 11, 15);
      final monday = WeekUtils.mondayOfWeek(sunday);
      expect(monday, DateTime(2026, 11, 9));

      final iso = WeekUtils.toIsoWeek(sunday);
      expect(iso, '2026-W46');

      final parsedMonday = WeekUtils.parseIsoWeekMonday(iso);
      expect(parsedMonday, monday);
    });

    test('formatea rango semanal y fecha corta en español', () {
      final monday = DateTime(2026, 11, 9);
      expect(WeekUtils.formatWeekRange(monday), '9 nov – 15 nov 2026');
      expect(WeekUtils.formatShortDate(DateTime(2026, 11, 12)), '12 nov');
    });
  });
}
