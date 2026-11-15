/// Rol de membresía de un jugador dentro del plantel del equipo.
enum RolMembresia {
  titular('Titular', 'Titulares', 'titular'),
  suplente('Suplente', 'Suplentes', 'suplente'),
  enPrueba('En prueba', 'En prueba', 'en_prueba');

  const RolMembresia(this.singularLabel, this.pluralLabel, this.apiValue);

  final String singularLabel;
  final String pluralLabel;
  final String apiValue;

  static RolMembresia fromApiValue(String? raw) {
    final normalized = raw?.trim().toLowerCase().replaceAll(' ', '_');
    for (final value in RolMembresia.values) {
      if (value.apiValue == normalized ||
          value.singularLabel.toLowerCase() == raw?.trim().toLowerCase() ||
          value.pluralLabel.toLowerCase() == raw?.trim().toLowerCase()) {
        return value;
      }
    }
    return RolMembresia.suplente;
  }
}
