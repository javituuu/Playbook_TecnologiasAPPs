import 'package:flutter/foundation.dart';

import 'equipo_dirigido.dart';

/// Usuario autenticado en la aplicación del Coach.
@immutable
class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.avatarUrl,
    this.equiposDirigidos = const <EquipoDirigido>[],
  });

  /// Construye un [AuthUser] a partir de una respuesta JSON de Laravel.
  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int ? rawId : int.tryParse(rawId?.toString() ?? '') ?? 0;

    final name =
        (json['name'] ?? json['nombre_completo'] ?? json['nombre'] ?? '')
            .toString()
            .trim();
    final email = (json['email'] ?? '').toString().trim();
    final role = (json['role'] ?? json['rol'] ?? '').toString().trim();

    final rawAvatar = json['avatar_url'] ?? json['avatarUrl'];
    final avatarUrl =
        rawAvatar == null || rawAvatar.toString().trim().isEmpty
            ? null
            : rawAvatar.toString().trim();

    final rawEquipos = json['equipos_dirigidos'] ??
        json['equiposDirigidos'] ??
        json['equipos'];
    final equipos = <EquipoDirigido>[];
    if (rawEquipos is List) {
      for (final item in rawEquipos) {
        if (item is Map<String, dynamic>) {
          equipos.add(EquipoDirigido.fromJson(item));
        } else if (item is Map) {
          equipos.add(
            EquipoDirigido.fromJson(Map<String, dynamic>.from(item)),
          );
        }
      }
    }

    return AuthUser(
      id: id,
      name: name,
      email: email,
      role: role,
      avatarUrl: avatarUrl,
      equiposDirigidos: List<EquipoDirigido>.unmodifiable(equipos),
    );
  }

  final int id;
  final String name;
  final String email;
  final String role;
  final String? avatarUrl;
  final List<EquipoDirigido> equiposDirigidos;

  String get _normalizedRole => role.trim().toLowerCase();

  /// Indica si la cuenta tiene permisos de Coach para acceder a esta app.
  bool get isCoach =>
      _normalizedRole == 'coach' || _normalizedRole == 'entrenador';

  /// Indica si la cuenta corresponde a un jugador (rechazado con HTTP 403).
  bool get isJugador =>
      _normalizedRole == 'jugador' || _normalizedRole == 'player';

  /// Iniciales breves para el avatar en el shell del coach.
  String get initials {
    final cleaned = name
        .replaceFirst(RegExp(r'^coach\s+', caseSensitive: false), '')
        .trim();
    if (cleaned.isEmpty) {
      return 'C';
    }
    final parts = cleaned.split(RegExp(r'\s+'));
    if (parts.length == 1) {
      return parts.first.substring(0, 1).toUpperCase();
    }
    return '${parts.first.substring(0, 1)}${parts[1].substring(0, 1)}'
        .toUpperCase();
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'name': name,
      'email': email,
      'role': role,
      if (avatarUrl != null) 'avatar_url': avatarUrl,
      'equipos_dirigidos': equiposDirigidos.map((e) => e.toJson()).toList(),
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is AuthUser &&
        other.id == id &&
        other.name == name &&
        other.email == email &&
        other.role == role &&
        other.avatarUrl == avatarUrl &&
        listEquals(other.equiposDirigidos, equiposDirigidos);
  }

  @override
  int get hashCode => Object.hash(
        id,
        name,
        email,
        role,
        avatarUrl,
        Object.hashAll(equiposDirigidos),
      );
}
