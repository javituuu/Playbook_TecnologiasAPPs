import 'package:flutter/foundation.dart';

/// Equipo dirigido por un coach dentro de Playbook Coach.
@immutable
class EquipoDirigido {
  const EquipoDirigido({
    required this.id,
    required this.nombre,
    required this.disciplina,
    this.club,
    this.quorumMinimo = 4,
  });

  /// Construye un [EquipoDirigido] desde un payload JSON de la API.
  factory EquipoDirigido.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int ? rawId : int.tryParse(rawId?.toString() ?? '') ?? 0;

    final nombre = (json['nombre'] ?? json['name'] ?? '').toString().trim();
    final disciplina =
        (json['disciplina'] ?? json['discipline'] ?? json['juego'] ?? '')
            .toString()
            .trim();
    final rawClub = json['club'] ?? json['club_nombre'];
    final club = rawClub == null || rawClub.toString().trim().isEmpty
        ? null
        : rawClub.toString().trim();

    final rawQuorum = json['quorum_minimo'] ?? json['quorumMinimo'];
    final quorumMinimo = rawQuorum is int
        ? rawQuorum
        : int.tryParse(rawQuorum?.toString() ?? '') ?? 4;

    return EquipoDirigido(
      id: id,
      nombre: nombre,
      disciplina: disciplina,
      club: club,
      quorumMinimo: quorumMinimo > 0 ? quorumMinimo : 4,
    );
  }

  final int id;
  final String nombre;
  final String disciplina;
  final String? club;
  final int quorumMinimo;

  /// Etiqueta visible en el selector de equipo (ej. `UDD Titans — LoL`).
  String get displayName =>
      disciplina.isEmpty ? nombre : '$nombre — $disciplina';

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'nombre': nombre,
      'disciplina': disciplina,
      if (club != null) 'club': club,
      'quorum_minimo': quorumMinimo,
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is EquipoDirigido &&
        other.id == id &&
        other.nombre == nombre &&
        other.disciplina == disciplina &&
        other.club == club &&
        other.quorumMinimo == quorumMinimo;
  }

  @override
  int get hashCode => Object.hash(id, nombre, disciplina, club, quorumMinimo);

  @override
  String toString() => 'EquipoDirigido(id: $id, displayName: $displayName)';
}
