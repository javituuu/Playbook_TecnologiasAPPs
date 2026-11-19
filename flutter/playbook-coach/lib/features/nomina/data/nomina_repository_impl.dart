import '../domain/estado_citacion.dart';
import '../domain/evento_nomina.dart';
import '../domain/nomina_repository.dart';
import 'nomina_data_source.dart';

/// Repositorio de nóminas que conmuta entre modo mock en memoria y API remota.
class NominaRepositoryImpl implements NominaRepository {
  NominaRepositoryImpl({
    required this._mockDataSource,
    required this._remoteDataSource,
    required this._isMockMode,
  });

  final NominaDataSource _mockDataSource;
  final NominaDataSource _remoteDataSource;
  final bool Function() _isMockMode;

  NominaDataSource get _activeSource =>
      _isMockMode() ? _mockDataSource : _remoteDataSource;

  @override
  Future<List<EventoNomina>> fetchEventos({required int equipoId}) {
    return _activeSource.fetchEventos(equipoId: equipoId);
  }

  @override
  Future<EventoNomina> intercambiarTitularSuplente({
    required int eventoId,
    required int titularId,
    required int suplenteId,
  }) {
    return _activeSource.intercambiarTitularSuplente(
      eventoId: eventoId,
      titularId: titularId,
      suplenteId: suplenteId,
    );
  }

  @override
  Future<EventoNomina> actualizarEstadoCitacion({
    required int eventoId,
    required int jugadorId,
    required EstadoCitacion estado,
  }) {
    return _activeSource.actualizarEstadoCitacion(
      eventoId: eventoId,
      jugadorId: jugadorId,
      estado: estado,
    );
  }
}
