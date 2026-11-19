import '../domain/crear_practica_request.dart';
import '../domain/practica.dart';
import '../domain/practicas_repository.dart';
import 'practicas_data_source.dart';

/// Repositorio de prácticas que conmuta entre modo mock en memoria y API remota.
class PracticasRepositoryImpl implements PracticasRepository {
  PracticasRepositoryImpl({
    required this._mockDataSource,
    required this._remoteDataSource,
    required this._isMockMode,
  });

  final PracticasDataSource _mockDataSource;
  final PracticasDataSource _remoteDataSource;
  final bool Function() _isMockMode;

  PracticasDataSource get _activeSource =>
      _isMockMode() ? _mockDataSource : _remoteDataSource;

  @override
  Future<List<Practica>> fetchPracticas({
    required int equipoId,
    String? semanaIso,
  }) {
    return _activeSource.fetchPracticas(
      equipoId: equipoId,
      semanaIso: semanaIso,
    );
  }

  @override
  Future<Practica> agendarPractica(CrearPracticaRequest request) {
    return _activeSource.agendarPractica(request);
  }

  @override
  Future<Practica> cancelarPractica({
    required int practicaId,
    String? motivo,
  }) {
    return _activeSource.cancelarPractica(
      practicaId: practicaId,
      motivo: motivo,
    );
  }
}
