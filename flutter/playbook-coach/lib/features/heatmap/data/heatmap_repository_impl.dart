import '../domain/heatmap_repository.dart';
import '../domain/matriz_disponibilidad.dart';
import 'heatmap_data_source.dart';

/// Repositorio de disponibilidad semanal con conmutación dinámica mock/remoto.
class HeatmapRepositoryImpl implements HeatmapRepository {
  HeatmapRepositoryImpl({
    required this._mockDataSource,
    required this._remoteDataSource,
    required this._isMockMode,
  });

  final HeatmapDataSource _mockDataSource;
  final HeatmapDataSource _remoteDataSource;
  final bool Function() _isMockMode;

  @override
  Future<MatrizDisponibilidad> fetchHeatmap({
    required int equipoId,
    required String semanaIso,
  }) {
    final source = _isMockMode() ? _mockDataSource : _remoteDataSource;
    return source.fetchHeatmap(
      equipoId: equipoId,
      semanaIso: semanaIso,
    );
  }
}
