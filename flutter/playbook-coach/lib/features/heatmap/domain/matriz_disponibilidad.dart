import 'package:flutter/foundation.dart';

import 'bloque_disponibilidad.dart';

/// Matriz semanal completa de disponibilidad (`15:00`–`22:00` × `Lunes`–`Domingo`).
@immutable
class MatrizDisponibilidad {
  const MatrizDisponibilidad({
    required this.equipoId,
    required this.semanaIso,
    required this.semanaLabel,
    required this.fechaInicio,
    this.quorumMinimo = 4,
    this.totalJugadores = 5,
    this.horas = defaultHoras,
    this.dias = defaultDias,
    required this.bloques,
  });

  factory MatrizDisponibilidad.fromJson(Map<String, dynamic> json) {
    final rawEquipoId = json['equipo_id'] ?? json['equipoId'] ?? 0;
    final equipoId = rawEquipoId is int
        ? rawEquipoId
        : int.tryParse(rawEquipoId.toString()) ?? 0;

    final semanaIso =
        (json['semana_iso'] ?? json['semanaIso'] ?? json['semana'] ?? '')
            .toString()
            .trim();
    final semanaLabel =
        (json['semana_label'] ?? json['semanaLabel'] ?? semanaIso)
            .toString()
            .trim();

    final rawFechaInicio =
        (json['fecha_inicio'] ?? json['fechaInicio'])?.toString();
    final fechaInicio = rawFechaInicio != null && rawFechaInicio.isNotEmpty
        ? DateTime.tryParse(rawFechaInicio) ?? DateTime.now()
        : DateTime.now();

    final rawQuorum = json['quorum_minimo'] ?? json['quorumMinimo'] ?? 4;
    final quorumMinimo =
        rawQuorum is int ? rawQuorum : int.tryParse(rawQuorum.toString()) ?? 4;

    final rawTotal = json['total_jugadores'] ?? json['totalJugadores'] ?? 5;
    final totalJugadores =
        rawTotal is int ? rawTotal : int.tryParse(rawTotal.toString()) ?? 5;

    final horas = json['horas'] is List
        ? (json['horas'] as List).map((e) => e.toString()).toList()
        : defaultHoras;
    final dias = json['dias'] is List
        ? (json['dias'] as List).map((e) => e.toString()).toList()
        : defaultDias;

    final bloques = <BloqueDisponibilidad>[];
    final rawBloques = json['bloques'];
    if (rawBloques is List) {
      for (final item in rawBloques) {
        if (item is Map) {
          bloques.add(
            BloqueDisponibilidad.fromJson(Map<String, dynamic>.from(item)),
          );
        }
      }
    }

    return MatrizDisponibilidad(
      equipoId: equipoId,
      semanaIso: semanaIso,
      semanaLabel: semanaLabel,
      fechaInicio: fechaInicio,
      quorumMinimo: quorumMinimo > 0 ? quorumMinimo : 4,
      totalJugadores: totalJugadores > 0 ? totalJugadores : 5,
      horas: List<String>.unmodifiable(horas),
      dias: List<String>.unmodifiable(dias),
      bloques: List<BloqueDisponibilidad>.unmodifiable(bloques),
    );
  }

  static const List<String> defaultHoras = <String>[
    '15:00',
    '16:00',
    '17:00',
    '18:00',
    '19:00',
    '20:00',
    '21:00',
    '22:00',
  ];

  static const List<String> defaultDias = <String>[
    'Lunes',
    'Martes',
    'Miércoles',
    'Jueves',
    'Viernes',
    'Sábado',
    'Domingo',
  ];

  final int equipoId;
  final String semanaIso;
  final String semanaLabel;
  final DateTime fechaInicio;
  final int quorumMinimo;
  final int totalJugadores;
  final List<String> horas;
  final List<String> dias;
  final List<BloqueDisponibilidad> bloques;

  /// Busca el bloque para el día [diaSemana] (`1..7`) y [hora] (`HH:00`).
  BloqueDisponibilidad? bloqueAt(int diaSemana, String hora) {
    for (final bloque in bloques) {
      if (bloque.diaSemana == diaSemana && bloque.hora == hora) {
        return bloque;
      }
    }
    return null;
  }

  /// Busca un bloque por su identificador.
  BloqueDisponibilidad? bloqueById(String id) {
    for (final bloque in bloques) {
      if (bloque.id == id) {
        return bloque;
      }
    }
    return null;
  }
}
