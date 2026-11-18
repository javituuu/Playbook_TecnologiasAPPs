/// Estado de respuesta de un jugador ante una citación a partido o práctica.
enum EstadoCitacion {
  confirmado('Confirmado', 'confirmado'),
  pendiente('Pendiente', 'pendiente'),
  rechazado('Rechazado', 'rechazado');

  const EstadoCitacion(this.label, this.apiValue);

  final String label;
  final String apiValue;

  static EstadoCitacion fromApiValue(String? raw) {
    final normalized = raw?.trim().toLowerCase();
    for (final value in EstadoCitacion.values) {
      if (value.apiValue == normalized ||
          value.label.toLowerCase() == normalized) {
        return value;
      }
    }
    return EstadoCitacion.pendiente;
  }
}
