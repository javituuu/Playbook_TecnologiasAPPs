import '../domain/plantel_equipo.dart';
import '../domain/plantel_repository.dart';
import 'plantel_data_source.dart';

/// Repositorio de plantel que conmuta entre modo mock en memoria y API remota.
class PlantelRepositoryImpl implements PlantelRepository {
  PlantelRepositoryImpl({
    required this._mockDataSource,
    required this._remoteDataSource,
    required this._isMockMode,
  });

  final PlantelDataSource _mockDataSource;
  final PlantelDataSource _remoteDataSource;
  final bool Function() _isMockMode;

  PlantelDataSource get _activeSource =>
      _isMockMode() ? _mockDataSource : _remoteDataSource;

  @override
  Future<PlantelEquipo> fetchPlantel({required int equipoId}) {
    return _activeSource.fetchPlantel(equipoId: equipoId);
  }
}
