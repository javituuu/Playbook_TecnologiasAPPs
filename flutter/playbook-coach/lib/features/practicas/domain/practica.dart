import 'package:flutter/foundation.dart';

import '../../heatmap/domain/serie_duracion.dart';

/// Estado operativo de una sesión de práctica programada.
enum EstadoPractica {
  programada('Programada', 'programada'),
  completada('Completada', 'completada'),
  cancelada('Cancelada', 'cancelada');

  const EstadoPractica(this.label, this.apiValue);

  final String label;
  final String apiValue;

  static EstadoPractica fromApiValue(String? raw) {
    final normalized = raw?.trim().toLowerCase();
    for (final value in EstadoPractica.values) {
      if (value.apiValue == normalized ||
          value.label.toLowerCase() == normalized) {
        return value;
      }
    }
    return EstadoPractica.programada;
  }
}

/// Sesión de práctica agendada para un equipo desde la matriz de disponibilidad.
@immutable
class Practica {
  const Practica({
    required this.id,
    required this.equipoId,
    required this.equipoNombre,
    required this.semanaIso,
    required this.diaSemana,
    required this.diaNombre,
    required this.fecha,
    required this.horaInicio,
    required this.horaFin,
    required this.serie,
    required this.rival,
    required this.modalidad,
    required this.servidor,
    this.notas,
    required this.jugadoresCitados,
    this.estado = EstadoPractica.programada,
    this.motivoCancelacion,
  });

  factory Practica.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int ? rawId : int.tryParse(rawId?.toString() ?? '') ?? 0;

    final rawEquipoId = json['equipo_id'] ?? json['equipoId'] ?? 0;
    final equipoId = rawEquipoId is int
        ? rawEquipoId
        : int.tryParse(rawEquipoId.toString()) ?? 0;

    final equipoNombre =
        (json['equipo_nombre'] ?? json['equipoNombre'] ?? '').toString().trim();
    final semanaIso =
        (json['semana_iso'] ?? json['semanaIso'] ?? '').toString().trim();

    final rawDia = json['dia_semana'] ?? json['diaSemana'] ?? 1;
    final diaSemana =
        rawDia is int ? rawDia : int.tryParse(rawDia.toString()) ?? 1;
    final diaNombre =
        (json['dia_nombre'] ?? json['diaNombre'] ?? 'Lunes').toString().trim();

    final rawFecha = json['fecha']?.toString();
    final fecha = rawFecha != null && rawFecha.isNotEmpty
        ? DateTime.tryParse(rawFecha) ?? DateTime.now()
        : DateTime.now();

    final horaInicio =
        (json['hora_inicio'] ?? json['horaInicio'] ?? '19:00')
            .toString()
            .trim();
    final serie = SerieDuracion.fromApiValue(json['serie']?.toString());
    final horaFin = (json['hora_fin'] ??
            json['horaFin'] ??
            serie.computeEndTime(horaInicio))
        .toString()
        .trim();

    final rival = (json['rival'] ?? 'Scrim interno').toString().trim();
    final modalidad = (json['modalidad'] ?? 'Scrim').toString().trim();
    final servidor = (json['servidor'] ?? 'Discord').toString().trim();
    final rawNotas = json['notas']?.toString();
    final notas =
        rawNotas == null || rawNotas.trim().isEmpty ? null : rawNotas.trim();

    final citados = <String>[];
    final rawCitados = json['jugadores_citados'] ?? json['jugadoresCitados'];
    if (rawCitados is List) {
      for (final item in rawCitados) {
        if (item != null && item.toString().trim().isNotEmpty) {
          citados.add(item.toString().trim());
        }
      }
    }

    final estado = EstadoPractica.fromApiValue(json['estado']?.toString());
    final rawCancel =
        (json['motivo_cancelacion'] ?? json['motivoCancelacion'])?.toString();
    final motivoCancelacion =
        rawCancel == null || rawCancel.trim().isEmpty ? null : rawCancel.trim();

    return Practica(
      id: id,
      equipoId: equipoId,
      equipoNombre: equipoNombre,
      semanaIso: semanaIso,
      diaSemana: diaSemana,
      diaNombre: diaNombre,
      fecha: fecha,
      horaInicio: horaInicio,
      horaFin: horaFin,
      serie: serie,
      rival: rival,
      modalidad: modalidad,
      servidor: servidor,
      notas: notas,
      jugadoresCitados: List<String>.unmodifiable(citados),
      estado: estado,
      motivoCancelacion: motivoCancelacion,
    );
  }

  final int id;
  final int equipoId;
  final String equipoNombre;
  final String semanaIso;
  final int diaSemana;
  final String diaNombre;
  final DateTime fecha;
  final String horaInicio;
  final String horaFin;
  final SerieDuracion serie;
  final String rival;
  final String modalidad;
  final String servidor;
  final String? notas;
  final List<String> jugadoresCitados;
  final EstadoPractica estado;
  final String? motivoCancelacion;

  bool get estaProgramada => estado == EstadoPractica.programada;
  bool get estaCancelada => estado == EstadoPractica.cancelada;
  int get citadosCount => jugadoresCitados.length;
  String get horarioResumen => '$diaNombre · $horaInicio – $horaFin';

  Practica copyWith({
    EstadoPractica? estado,
    String? motivoCancelacion,
  }) {
    return Practica(
      id: id,
      equipoId: equipoId,
      equipoNombre: equipoNombre,
      semanaIso: semanaIso,
      diaSemana: diaSemana,
      diaNombre: diaNombre,
      fecha: fecha,
      horaInicio: horaInicio,
      horaFin: horaFin,
      serie: serie,
      rival: rival,
      modalidad: modalidad,
      servidor: servidor,
      notas: notas,
      jugadoresCitados: jugadoresCitados,
      estado: estado ?? this.estado,
      motivoCancelacion: motivoCancelacion ?? this.motivoCancelacion,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'equipo_id': equipoId,
      'equipo_nombre': equipoNombre,
      'semana_iso': semanaIso,
      'dia_semana': diaSemana,
      'dia_nombre': diaNombre,
      'fecha': fecha.toIso8601String(),
      'hora_inicio': horaInicio,
      'hora_fin': horaFin,
      'serie': serie.apiValue,
      'rival': rival,
      'modalidad': modalidad,
      'servidor': servidor,
      if (notas != null) 'notas': notas,
      'jugadores_citados': jugadoresCitados,
      'estado': estado.apiValue,
      if (motivoCancelacion != null) 'motivo_cancelacion': motivoCancelacion,
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is Practica &&
        other.id == id &&
        other.equipoId == equipoId &&
        other.semanaIso == semanaIso &&
        other.diaSemana == diaSemana &&
        other.horaInicio == horaInicio &&
        other.serie == serie &&
        other.rival == rival &&
        other.estado == estado &&
        other.motivoCancelacion == motivoCancelacion &&
        listEquals(other.jugadoresCitados, jugadoresCitados);
  }

  @override
  int get hashCode => Object.hash(
        id,
        equipoId,
        semanaIso,
        diaSemana,
        horaInicio,
        serie,
        rival,
        estado,
        motivoCancelacion,
        Object.hashAll(jugadoresCitados),
      );
}
