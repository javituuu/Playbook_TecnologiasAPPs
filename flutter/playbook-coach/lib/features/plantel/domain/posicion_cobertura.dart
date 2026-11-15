import 'package:flutter/foundation.dart';

/// Resumen de cobertura para una posición requerida dentro del equipo.
@immutable
class PosicionCobertura {
  const PosicionCobertura({
    required this.posicion,
    required this.titularesPrincipales,
    required this.respaldoSecundarioOSuplente,
    required this.enPruebaCount,
  });

  final String posicion;
  final int titularesPrincipales;
  final int respaldoSecundarioOSuplente;
  final int enPruebaCount;

  bool get tieneTitular => titularesPrincipales > 0;

  bool get tieneRespaldo => respaldoSecundarioOSuplente > 0;

  bool get estaCubierta => tieneTitular;
}
