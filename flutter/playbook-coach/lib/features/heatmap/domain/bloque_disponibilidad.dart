import 'package:flutter/foundation.dart';

/// Jugador disponible en un bloque horario del heatmap.
@immutable
class JugadorDisponible {
  const JugadorDisponible({
    required this.id,
    required this.nombre,
    this.nickname,
    this.rol,
  });

  factory JugadorDisponible.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int ? rawId : int.tryParse(rawId?.toString() ?? '') ?? 0;
    final nombre =
        (json['nombre'] ?? json['name'] ?? json['nombre_completo'] ?? '')
            .toString()
            .trim();
    final rawNick = json['nickname'] ?? json['nick'];
    final nickname =
        rawNick == null || rawNick.toString().trim().isEmpty
            ? null
            : rawNick.toString().trim();
    final rawRol = json['rol'] ?? json['role'];
    final rol = rawRol == null || rawRol.toString().trim().isEmpty
        ? null
        : rawRol.toString().trim();

    return JugadorDisponible(
      id: id,
      nombre: nombre,
      nickname: nickname,
      rol: rol,
    );
  }

  final int id;
  final String nombre;
  final String? nickname;
  final String? rol;

  /// Formato legible en el detalle del bloque (ej. `Martín 'Slayer' Gómez`).
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

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'nombre': nombre,
      if (nickname != null) 'nickname': nickname,
      if (rol != null) 'rol': rol,
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is JugadorDisponible &&
        other.id == id &&
        other.nombre == nombre &&
        other.nickname == nickname &&
        other.rol == rol;
  }

  @override
  int get hashCode => Object.hash(id, nombre, nickname, rol);
}

/// Ausencia registrada para un jugador en un bloque horario específico.
@immutable
class AusenciaRegistrada {
  const AusenciaRegistrada({
    required this.jugadorId,
    required this.nombreJugador,
    required this.motivo,
    required this.categoriaBadge,
    this.esCrucePartido = false,
  });

  factory AusenciaRegistrada.fromJson(Map<String, dynamic> json) {
    final rawId = json['jugador_id'] ?? json['user_id'] ?? json['id'];
    final jugadorId =
        rawId is int ? rawId : int.tryParse(rawId?.toString() ?? '') ?? 0;

    final nombreJugador =
        (json['nombre_jugador'] ?? json['nombre'] ?? json['jugador'] ?? '')
            .toString()
            .trim();
    final motivo =
        (json['motivo'] ?? json['descripcion'] ?? 'Falta de disponibilidad')
            .toString()
            .trim();
    final categoriaBadge = (json['categoria_badge'] ??
            json['badge'] ??
            json['tipo'] ??
            'BLOQUEO')
        .toString()
        .trim()
        .toUpperCase();
    final esCrucePartido = json['es_cruce_partido'] == true ||
        categoriaBadge.contains('PARTIDO');

    return AusenciaRegistrada(
      jugadorId: jugadorId,
      nombreJugador: nombreJugador,
      motivo: motivo,
      categoriaBadge: categoriaBadge,
      esCrucePartido: esCrucePartido,
    );
  }

  final int jugadorId;
  final String nombreJugador;
  final String motivo;
  final String categoriaBadge;
  final bool esCrucePartido;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'jugador_id': jugadorId,
      'nombre_jugador': nombreJugador,
      'motivo': motivo,
      'categoria_badge': categoriaBadge,
      'es_cruce_partido': esCrucePartido,
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is AusenciaRegistrada &&
        other.jugadorId == jugadorId &&
        other.nombreJugador == nombreJugador &&
        other.motivo == motivo &&
        other.categoriaBadge == categoriaBadge &&
        other.esCrucePartido == esCrucePartido;
  }

  @override
  int get hashCode => Object.hash(
        jugadorId,
        nombreJugador,
        motivo,
        categoriaBadge,
        esCrucePartido,
      );
}

/// Bloque horario individual dentro de la matriz semanal de disponibilidad.
@immutable
class BloqueDisponibilidad {
  const BloqueDisponibilidad({
    required this.id,
    required this.diaSemana,
    required this.diaNombre,
    required this.fecha,
    required this.hora,
    required this.disponiblesCount,
    this.totalJugadores = 5,
    this.quorumMinimo = 4,
    this.disponibles = const <JugadorDisponible>[],
    this.ausencias = const <AusenciaRegistrada>[],
  });

  factory BloqueDisponibilidad.fromJson(Map<String, dynamic> json) {
    final rawDia = json['dia_semana'] ?? json['diaSemana'] ?? 1;
    final diaSemana =
        rawDia is int ? rawDia : int.tryParse(rawDia.toString()) ?? 1;

    final diaNombre = (json['dia_nombre'] ??
            json['diaNombre'] ??
            _defaultDayName(diaSemana))
        .toString()
        .trim();

    final rawFecha = json['fecha']?.toString();
    final fecha = rawFecha != null && rawFecha.isNotEmpty
        ? DateTime.tryParse(rawFecha) ?? DateTime.now()
        : DateTime.now();

    final hora = (json['hora'] ?? '15:00').toString().trim();

    final rawDisponiblesCount =
        json['disponibles_count'] ?? json['disponiblesCount'] ?? 0;
    final disponiblesCount = rawDisponiblesCount is int
        ? rawDisponiblesCount
        : int.tryParse(rawDisponiblesCount.toString()) ?? 0;

    final rawTotal = json['total_jugadores'] ?? json['totalJugadores'] ?? 5;
    final totalJugadores =
        rawTotal is int ? rawTotal : int.tryParse(rawTotal.toString()) ?? 5;

    final rawQuorum = json['quorum_minimo'] ?? json['quorumMinimo'] ?? 4;
    final quorumMinimo =
        rawQuorum is int ? rawQuorum : int.tryParse(rawQuorum.toString()) ?? 4;

    final disponiblesList = <JugadorDisponible>[];
    final rawDisponibles = json['disponibles'];
    if (rawDisponibles is List) {
      for (final item in rawDisponibles) {
        if (item is Map) {
          disponiblesList.add(
            JugadorDisponible.fromJson(Map<String, dynamic>.from(item)),
          );
        }
      }
    }

    final ausenciasList = <AusenciaRegistrada>[];
    final rawAusencias = json['ausencias'];
    if (rawAusencias is List) {
      for (final item in rawAusencias) {
        if (item is Map) {
          ausenciasList.add(
            AusenciaRegistrada.fromJson(Map<String, dynamic>.from(item)),
          );
        }
      }
    }

    final id = (json['id'] ?? '$diaSemana-$hora').toString();

    return BloqueDisponibilidad(
      id: id,
      diaSemana: diaSemana,
      diaNombre: diaNombre,
      fecha: fecha,
      hora: hora,
      disponiblesCount: disponiblesCount,
      totalJugadores: totalJugadores > 0 ? totalJugadores : 5,
      quorumMinimo: quorumMinimo > 0 ? quorumMinimo : 4,
      disponibles: List<JugadorDisponible>.unmodifiable(disponiblesList),
      ausencias: List<AusenciaRegistrada>.unmodifiable(ausenciasList),
    );
  }

  final String id;
  final int diaSemana;
  final String diaNombre;
  final DateTime fecha;
  final String hora;
  final int disponiblesCount;
  final int totalJugadores;
  final int quorumMinimo;
  final List<JugadorDisponible> disponibles;
  final List<AusenciaRegistrada> ausencias;

  bool get cumpleQuorum => disponiblesCount >= quorumMinimo;

  String get ratioLabel => '$disponiblesCount/$totalJugadores';

  String get tituloDetalle => '$diaNombre @ $hora';

  static String _defaultDayName(int diaSemana) {
    const names = <int, String>{
      1: 'Lunes',
      2: 'Martes',
      3: 'Miércoles',
      4: 'Jueves',
      5: 'Viernes',
      6: 'Sábado',
      7: 'Domingo',
    };
    return names[diaSemana] ?? 'Lunes';
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'dia_semana': diaSemana,
      'dia_nombre': diaNombre,
      'fecha': fecha.toIso8601String(),
      'hora': hora,
      'disponibles_count': disponiblesCount,
      'total_jugadores': totalJugadores,
      'quorum_minimo': quorumMinimo,
      'disponibles': disponibles.map((e) => e.toJson()).toList(),
      'ausencias': ausencias.map((e) => e.toJson()).toList(),
    };
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is BloqueDisponibilidad &&
        other.id == id &&
        other.diaSemana == diaSemana &&
        other.diaNombre == diaNombre &&
        other.fecha == fecha &&
        other.hora == hora &&
        other.disponiblesCount == disponiblesCount &&
        other.totalJugadores == totalJugadores &&
        other.quorumMinimo == quorumMinimo &&
        listEquals(other.disponibles, disponibles) &&
        listEquals(other.ausencias, ausencias);
  }

  @override
  int get hashCode => Object.hash(
        id,
        diaSemana,
        diaNombre,
        fecha,
        hora,
        disponiblesCount,
        totalJugadores,
        quorumMinimo,
        Object.hashAll(disponibles),
        Object.hashAll(ausencias),
      );
}
