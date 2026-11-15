import 'matriz_disponibilidad.dart';

/// Contrato de acceso a la matriz semanal de disponibilidad del equipo.
abstract class HeatmapRepository {
  Future<MatrizDisponibilidad> fetchHeatmap({
    required int equipoId,
    required String semanaIso,
  });
}
