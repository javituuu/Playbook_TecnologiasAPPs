/// Formato de serie y duración estimada para agendar una práctica.
enum SerieDuracion {
  bo1(
    label: 'Bo1',
    shortDetail: '1h',
    description: 'Best of 1 · 1 hora',
    durationHours: 1,
    apiValue: 'bo1',
  ),
  bo3(
    label: 'Bo3',
    shortDetail: '2h',
    description: 'Best of 3 · 2 horas',
    durationHours: 2,
    apiValue: 'bo3',
  ),
  bo5(
    label: 'Bo5',
    shortDetail: '3h',
    description: 'Best of 5 · 3 horas',
    durationHours: 3,
    apiValue: 'bo5',
  );

  const SerieDuracion({
    required this.label,
    required this.shortDetail,
    required this.description,
    required this.durationHours,
    required this.apiValue,
  });

  final String label;
  final String shortDetail;
  final String description;
  final int durationHours;
  final String apiValue;

  /// Parsea un valor proveniente de la API (`bo1`, `bo3`, `bo5`).
  static SerieDuracion fromApiValue(String? raw) {
    final normalized = raw?.trim().toLowerCase();
    for (final value in SerieDuracion.values) {
      if (value.apiValue == normalized ||
          value.label.toLowerCase() == normalized) {
        return value;
      }
    }
    return SerieDuracion.bo1;
  }

  /// Calcula la hora de término (`HH:00`) a partir de [horaInicio].
  String computeEndTime(String horaInicio) {
    final parts = horaInicio.split(':');
    final startHour = int.tryParse(parts.first) ?? 15;
    final endHour = (startHour + durationHours).clamp(0, 23);
    final minute = parts.length > 1 ? parts[1] : '00';
    return '${endHour.toString().padLeft(2, '0')}:$minute';
  }
}
