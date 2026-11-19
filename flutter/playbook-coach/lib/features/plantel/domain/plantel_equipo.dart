import 'package:flutter/foundation.dart';

import 'miembro_plantel.dart';
import 'posicion_cobertura.dart';
import 'rol_membresia.dart';

/// Plantel completo de un equipo junto con las posiciones requeridas de su disciplina.
@immutable
class PlantelEquipo {
  const PlantelEquipo({
    required this.equipoId,
    required this.posicionesRequeridas,
    required this.miembros,
  });

  factory PlantelEquipo.fromJson(
    Map<String, dynamic> json, {
    required int fallbackEquipoId,
  }) {
    final rawEquipoId = json['equipo_id'] ?? json['equipoId'];
    final equipoId = rawEquipoId is int
        ? rawEquipoId
        : int.tryParse(rawEquipoId?.toString() ?? '') ?? fallbackEquipoId;

    final rawPosiciones =
        json['posiciones_requeridas'] ?? json['posicionesRequeridas'];
    final posiciones = <String>[];
    if (rawPosiciones is List) {
      for (final item in rawPosiciones) {
        final value = item.toString().trim();
        if (value.isNotEmpty) {
          posiciones.add(value);
        }
      }
    }

    final rawMiembros = json['miembros'] ?? json['roster'] ?? json['data'];
    final miembros = <MiembroPlantel>[];
    if (rawMiembros is List) {
      for (final item in rawMiembros) {
        if (item is Map) {
          miembros.add(
            MiembroPlantel.fromJson(Map<String, dynamic>.from(item)),
          );
        }
      }
    }

    final resolvedPosiciones = posiciones.isNotEmpty
        ? posiciones
        : _inferPosiciones(miembros);

    return PlantelEquipo(
      equipoId: equipoId,
      posicionesRequeridas: List<String>.unmodifiable(resolvedPosiciones),
      miembros: List<MiembroPlantel>.unmodifiable(miembros),
    );
  }

  static List<String> _inferPosiciones(List<MiembroPlantel> miembros) {
    final seen = <String>{};
    for (final m in miembros) {
      seen.add(m.posicionPrincipal);
    }
    return seen.toList(growable: false);
  }

  final int equipoId;
  final List<String> posicionesRequeridas;
  final List<MiembroPlantel> miembros;

  List<MiembroPlantel> byRol(RolMembresia rol) => miembros
      .where((m) => m.rolMembresia == rol)
      .toList(growable: false);

  List<MiembroPlantel> get titulares => byRol(RolMembresia.titular);

  List<MiembroPlantel> get suplentes => byRol(RolMembresia.suplente);

  List<MiembroPlantel> get enPrueba => byRol(RolMembresia.enPrueba);

  List<PosicionCobertura> get coberturas {
    return posicionesRequeridas.map((posicion) {
      final target = posicion.trim().toLowerCase();
      final titularesPrincipales = titulares
          .where((m) => m.posicionPrincipal.trim().toLowerCase() == target)
          .length;
      final respaldo = miembros.where((m) {
        if (m.rolMembresia == RolMembresia.titular) {
          return m.posicionSecundaria?.trim().toLowerCase() == target;
        }
        return m.cubrePosicion(posicion);
      }).length;
      final enPruebaCount = enPrueba
          .where((m) => m.cubrePosicion(posicion))
          .length;

      return PosicionCobertura(
        posicion: posicion,
        titularesPrincipales: titularesPrincipales,
        respaldoSecundarioOSuplente: respaldo,
        enPruebaCount: enPruebaCount,
      );
    }).toList(growable: false);
  }

  int get posicionesCubiertasCount =>
      coberturas.where((c) => c.estaCubierta).length;

  List<String> get posicionesSinTitular => coberturas
      .where((c) => !c.tieneTitular)
      .map((c) => c.posicion)
      .toList(growable: false);
}
