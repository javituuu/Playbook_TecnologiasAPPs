import 'package:flutter/foundation.dart';

import 'citacion_jugador.dart';
import 'estado_citacion.dart';

/// Tipo de evento competitivo o entrenamiento con citación asociada.
enum TipoEventoNomina {
  partido('Partido oficial', 'partido'),
  practica('Práctica', 'practica');

  const TipoEventoNomina(this.label, this.apiValue);

  final String label;
  final String apiValue;

  static TipoEventoNomina fromApiValue(String? raw) {
    final normalized = raw?.trim().toLowerCase();
    for (final value in TipoEventoNomina.values) {
      if (value.apiValue == normalized ||
          value.label.toLowerCase() == normalized) {
        return value;
      }
    }
    return TipoEventoNomina.partido;
  }
}

/// Partido o práctica del equipo junto con sus citaciones y nómina titular/suplente.
@immutable
class EventoNomina {
  const EventoNomina({
    required this.id,
    required this.equipoId,
    required this.tipo,
    required this.titulo,
    required this.ligaOCompetencia,
    required this.fecha,
    required this.horaInicio,
    required this.horaFin,
    required this.lugarOServidor,
    this.mensajeCoach,
    required this.citaciones,
  });

  factory EventoNomina.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int ? rawId : int.tryParse(rawId?.toString() ?? '') ?? 0;

    final rawEquipoId = json['equipo_id'] ?? json['equipoId'] ?? 0;
    final equipoId = rawEquipoId is int
        ? rawEquipoId
        : int.tryParse(rawEquipoId.toString()) ?? 0;

    final tipo = TipoEventoNomina.fromApiValue(json['tipo']?.toString());
    final titulo = (json['titulo'] ?? '').toString().trim();
    final ligaOCompetencia =
        (json['liga_o_competencia'] ?? json['liga'] ?? 'Competencia')
            .toString()
            .trim();

    final rawFecha = json['fecha']?.toString();
    final fecha = rawFecha != null && rawFecha.isNotEmpty
        ? DateTime.tryParse(rawFecha) ?? DateTime.now()
        : DateTime.now();

    final horaInicio =
        (json['hora_inicio'] ?? json['horaInicio'] ?? '20:00')
            .toString()
            .trim();
    final horaFin =
        (json['hora_fin'] ?? json['horaFin'] ?? '21:30').toString().trim();
    final lugarOServidor =
        (json['lugar_o_servidor'] ?? json['lugar'] ?? 'Online')
            .toString()
            .trim();

    final rawMensaje =
        (json['mensaje_coach'] ?? json['mensajeCoach'])?.toString();
    final mensajeCoach = rawMensaje == null || rawMensaje.trim().isEmpty
        ? null
        : rawMensaje.trim();

    final citaciones = <CitacionJugador>[];
    final rawCitaciones = json['citaciones'];
    if (rawCitaciones is List) {
      for (final item in rawCitaciones) {
        if (item is Map) {
          citaciones.add(
            CitacionJugador.fromJson(Map<String, dynamic>.from(item)),
          );
        }
      }
    }

    return EventoNomina(
      id: id,
      equipoId: equipoId,
      tipo: tipo,
      titulo: titulo,
      ligaOCompetencia: ligaOCompetencia,
      fecha: fecha,
      horaInicio: horaInicio,
      horaFin: horaFin,
      lugarOServidor: lugarOServidor,
      mensajeCoach: mensajeCoach,
      citaciones: List<CitacionJugador>.unmodifiable(citaciones),
    );
  }

  final int id;
  final int equipoId;
  final TipoEventoNomina tipo;
  final String titulo;
  final String ligaOCompetencia;
  final DateTime fecha;
  final String horaInicio;
  final String horaFin;
  final String lugarOServidor;
  final String? mensajeCoach;
  final List<CitacionJugador> citaciones;

  List<CitacionJugador> get titulares =>
      citaciones.where((c) => c.esTitular).toList(growable: false);

  List<CitacionJugador> get suplentes =>
      citaciones.where((c) => !c.esTitular).toList(growable: false);

  int get confirmadosCount => citaciones
      .where((c) => c.estadoRespuesta == EstadoCitacion.confirmado)
      .length;

  int get pendientesCount => citaciones
      .where((c) => c.estadoRespuesta == EstadoCitacion.pendiente)
      .length;

  int get rechazadosCount => citaciones
      .where((c) => c.estadoRespuesta == EstadoCitacion.rechazado)
      .length;

  int get titularesConflictosCount =>
      titulares.where((c) => c.tieneConflicto).length;

  bool get tieneConflictosEnTitulares => titularesConflictosCount > 0;

  EventoNomina copyWith({
    List<CitacionJugador>? citaciones,
  }) {
    return EventoNomina(
      id: id,
      equipoId: equipoId,
      tipo: tipo,
      titulo: titulo,
      ligaOCompetencia: ligaOCompetencia,
      fecha: fecha,
      horaInicio: horaInicio,
      horaFin: horaFin,
      lugarOServidor: lugarOServidor,
      mensajeCoach: mensajeCoach,
      citaciones: citaciones ?? this.citaciones,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'equipo_id': equipoId,
      'tipo': tipo.apiValue,
      'titulo': titulo,
      'liga_o_competencia': ligaOCompetencia,
      'fecha': fecha.toIso8601String(),
      'hora_inicio': horaInicio,
      'hora_fin': horaFin,
      'lugar_o_servidor': lugarOServidor,
      if (mensajeCoach != null) 'mensaje_coach': mensajeCoach,
      'citaciones': citaciones.map((e) => e.toJson()).toList(),
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is EventoNomina &&
        other.id == id &&
        other.equipoId == equipoId &&
        other.tipo == tipo &&
        other.titulo == titulo &&
        other.horaInicio == horaInicio &&
        listEquals(other.citaciones, citaciones);
  }

  @override
  int get hashCode => Object.hash(
        id,
        equipoId,
        tipo,
        titulo,
        horaInicio,
        Object.hashAll(citaciones),
      );
}
