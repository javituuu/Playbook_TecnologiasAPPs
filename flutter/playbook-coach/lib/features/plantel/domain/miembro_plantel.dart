import 'package:flutter/foundation.dart';

import 'rol_membresia.dart';

/// Integrante del plantel competitivo con su rol de membresía, posiciones y rango.
@immutable
class MiembroPlantel {
  const MiembroPlantel({
    required this.id,
    required this.nombre,
    this.nickname,
    required this.rolMembresia,
    required this.posicionPrincipal,
    this.posicionSecundaria,
    required this.rango,
    this.esCapitan = false,
  });

  factory MiembroPlantel.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'] ?? json['jugador_id'] ?? 0;
    final id = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;

    final nombre =
        (json['nombre'] ?? json['nombre_completo'] ?? json['name'] ?? '')
            .toString()
            .trim();
    final rawNick = json['nickname'] ?? json['nick'];
    final nickname =
        rawNick == null || rawNick.toString().trim().isEmpty
            ? null
            : rawNick.toString().trim();

    final rolMembresia = RolMembresia.fromApiValue(
      (json['rol_membresia'] ?? json['rolMembresia'] ?? json['rol'])
          ?.toString(),
    );

    final posicionPrincipal = (json['posicion_principal'] ??
            json['posicionPrincipal'] ??
            json['posicion'] ??
            'Flex')
        .toString()
        .trim();

    final rawSecundaria =
        (json['posicion_secundaria'] ?? json['posicionSecundaria'])?.toString();
    final posicionSecundaria =
        rawSecundaria == null || rawSecundaria.trim().isEmpty
            ? null
            : rawSecundaria.trim();

    final rango =
        (json['rango'] ?? json['elo'] ?? 'Sin clasificar').toString().trim();
    final esCapitan = json['es_capitan'] == true || json['esCapitan'] == true;

    return MiembroPlantel(
      id: id,
      nombre: nombre,
      nickname: nickname,
      rolMembresia: rolMembresia,
      posicionPrincipal: posicionPrincipal,
      posicionSecundaria: posicionSecundaria,
      rango: rango,
      esCapitan: esCapitan,
    );
  }

  final int id;
  final String nombre;
  final String? nickname;
  final RolMembresia rolMembresia;
  final String posicionPrincipal;
  final String? posicionSecundaria;
  final String rango;
  final bool esCapitan;

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

  bool cubrePosicion(String posicion) {
    final target = posicion.trim().toLowerCase();
    if (posicionPrincipal.trim().toLowerCase() == target) {
      return true;
    }
    if (posicionSecundaria != null &&
        posicionSecundaria!.trim().toLowerCase() == target) {
      return true;
    }
    return false;
  }
}
