import '../domain/matriz_disponibilidad.dart';

/// Fuente de datos abstracta para la matriz de disponibilidad semanal.
abstract class HeatmapDataSource {
  Future<MatrizDisponibilidad> fetchHeatmap({
    required int equipoId,
    required String semanaIso,
  });
}
