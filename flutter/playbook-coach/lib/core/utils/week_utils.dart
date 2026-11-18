import 'package:intl/intl.dart';

/// Utilidades de semanas ISO (`YYYY-Www`) y formateo de rangos en español.
class WeekUtils {
  const WeekUtils._();

  static const List<String> _shortMonths = <String>[
    'ene',
    'feb',
    'mar',
    'abr',
    'may',
    'jun',
    'jul',
    'ago',
    'sep',
    'oct',
    'nov',
    'dic',
  ];

  /// Retorna el lunes (00:00) correspondiente a la semana de [date].
  static DateTime mondayOfWeek(DateTime date) {
    final normalized = DateTime(date.year, date.month, date.day);
    return normalized.subtract(Duration(days: normalized.weekday - 1));
  }

  /// Calcula el identificador ISO (`YYYY-Www`) para la semana que contiene a [date].
  static String toIsoWeek(DateTime date) {
    final monday = mondayOfWeek(date);
    final thursdayUtc = DateTime.utc(monday.year, monday.month, monday.day)
        .add(const Duration(days: 3));
    final jan1Utc = DateTime.utc(thursdayUtc.year, 1, 1);
    final weekNumber = 1 + (thursdayUtc.difference(jan1Utc).inDays ~/ 7);
    return '${thursdayUtc.year}-W${weekNumber.toString().padLeft(2, '0')}';
  }

  /// Convierte un identificador ISO (`YYYY-Www`) al lunes correspondiente.
  static DateTime parseIsoWeekMonday(String semanaIso) {
    final match = RegExp(r'^(\d{4})-W(\d{2})$').firstMatch(semanaIso.trim());
    if (match == null) {
      return mondayOfWeek(DateTime.now());
    }
    final year = int.parse(match.group(1)!);
    final week = int.parse(match.group(2)!);
    final jan4Utc = DateTime.utc(year, 1, 4);
    final week1MondayUtc =
        jan4Utc.subtract(Duration(days: jan4Utc.weekday - 1));
    final targetUtc = week1MondayUtc.add(Duration(days: (week - 1) * 7));
    return DateTime(targetUtc.year, targetUtc.month, targetUtc.day);
  }

  /// Formatea el rango semanal (ej. `9 nov – 15 nov 2026`).
  static String formatWeekRange(DateTime monday) {
    final start = mondayOfWeek(monday);
    final end = start.add(const Duration(days: 6));
    final startDay = DateFormat('d').format(start);
    final endDay = DateFormat('d').format(end);
    final startMonth = _shortMonths[start.month - 1];
    final endMonth = _shortMonths[end.month - 1];

    if (start.year != end.year) {
      return '$startDay $startMonth ${start.year} – $endDay $endMonth ${end.year}';
    }
    return '$startDay $startMonth – $endDay $endMonth ${end.year}';
  }

  /// Formatea una fecha puntual (ej. `12 nov`).
  static String formatShortDate(DateTime date) {
    final day = DateFormat('d').format(date);
    final month = _shortMonths[date.month - 1];
    return '$day $month';
  }
}
