import 'package:flutter/foundation.dart';

import 'estado_citacion.dart';

/// Detalle de citación y estado de respuesta de un jugador dentro de una nómina.
@immutable
class CitacionJugador {
  const CitacionJugador({
    required this.jugadorId,
    required this.nombre,
    this.nickname,
    required this.posicion,
    required this.rolMembresia,
    required this.esTitular,
    required this.estadoRespuesta,
    this.motivoRespuesta,
    this.conflictoHorario,
  });

  factory CitacionJugador.fromJson(Map<String, dynamic> json) {
    final rawId = json['jugador_id'] ?? json['user_id'] ?? json['id'] ?? 0;
    final jugadorId =
        rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;

    final nombre =
        (json['nombre'] ?? json['nombre_completo'] ?? json['name'] ?? '')
            .toString()
            .trim();
    final rawNick = json['nickname'] ?? json['nick'];
    final nickname =
        rawNick == null || rawNick.toString().trim().isEmpty
            ? null
            : rawNick.toString().trim();
    final posicion = (json['posicion'] ?? json['rol'] ?? 'Flex').toString().trim();
    final rolMembresia =
        (json['rol_membresia'] ?? json['rolMembresia'] ?? 'Titular')
            .toString()
            .trim();
    final esTitular = json['es_titular'] ??
        json['esTitular'] ??
        rolMembresia.toLowerCase() == 'titular';

    final estadoRespuesta = EstadoCitacion.fromApiValue(
      (json['estado_respuesta'] ?? json['estadoRespuesta'] ?? json['estado'])
          ?.toString(),
    );
    final rawMotivo =
        (json['motivo_respuesta'] ?? json['motivoRespuesta'] ?? json['motivo'])
            ?.toString();
    final motivoRespuesta =
        rawMotivo == null || rawMotivo.trim().isEmpty ? null : rawMotivo.trim();

    final rawConflicto =
        (json['conflicto_horario'] ?? json['conflictoHorario'])?.toString();
    final conflictoHorario = rawConflicto == null || rawConflicto.trim().isEmpty
        ? null
        : rawConflicto.trim();

    return CitacionJugador(
      jugadorId: jugadorId,
      nombre: nombre,
      nickname: nickname,
      posicion: posicion,
      rolMembresia: rolMembresia,
      esTitular: esTitular == true,
      estadoRespuesta: estadoRespuesta,
      motivoRespuesta: motivoRespuesta,
      conflictoHorario: conflictoHorario,
    );
  }

  final int jugadorId;
  final String nombre;
  final String? nickname;
  final String posicion;
  final String rolMembresia;
  final bool esTitular;
  final EstadoCitacion estadoRespuesta;
  final String? motivoRespuesta;
  final String? conflictoHorario;

  /// Indica si el jugador presenta cruce horario o rechazó la citación.
  bool get tieneConflicto =>
      conflictoHorario != null || estadoRespuesta == EstadoCitacion.rechazado;

  String get displayName {
    if (nickname == null || nickname!.isEmpty) {
      return nombre;
    }
    final parts = nombre.split(' ');
    if (parts.length >= 2) {
      return "${parts.first} '$nickname' ${parts.sublist(1).join(' ')}";
    }
    return "$nombre '$nickname'";
  }

  CitacionJugador copyWith({
    String? posicion,
    String? rolMembresia,
    bool? esTitular,
    EstadoCitacion? estadoRespuesta,
    String? motivoRespuesta,
    String? conflictoHorario,
  }) {
    return CitacionJugador(
      jugadorId: jugadorId,
      nombre: nombre,
      nickname: nickname,
      posicion: posicion ?? this.posicion,
      rolMembresia: rolMembresia ?? this.rolMembresia,
      esTitular: esTitular ?? this.esTitular,
      estadoRespuesta: estadoRespuesta ?? this.estadoRespuesta,
      motivoRespuesta: motivoRespuesta ?? this.motivoRespuesta,
      conflictoHorario: conflictoHorario ?? this.conflictoHorario,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'jugador_id': jugadorId,
      'nombre': nombre,
      if (nickname != null) 'nickname': nickname,
      'posicion': posicion,
      'rol_membresia': rolMembresia,
      'es_titular': esTitular,
      'estado_respuesta': estadoRespuesta.apiValue,
      if (motivoRespuesta != null) 'motivo_respuesta': motivoRespuesta,
      if (conflictoHorario != null) 'conflicto_horario': conflictoHorario,
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is CitacionJugador &&
        other.jugadorId == jugadorId &&
        other.nombre == nombre &&
        other.nickname == nickname &&
        other.posicion == posicion &&
        other.rolMembresia == rolMembresia &&
        other.esTitular == esTitular &&
        other.estadoRespuesta == estadoRespuesta &&
        other.motivoRespuesta == motivoRespuesta &&
        other.conflictoHorario == conflictoHorario;
  }

  @override
  int get hashCode => Object.hash(
        jugadorId,
        nombre,
        nickname,
        posicion,
        rolMembresia,
        esTitular,
        estadoRespuesta,
        motivoRespuesta,
        conflictoHorario,
      );
}
