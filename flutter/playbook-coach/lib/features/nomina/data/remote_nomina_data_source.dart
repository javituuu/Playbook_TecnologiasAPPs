import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../domain/estado_citacion.dart';
import '../domain/evento_nomina.dart';
import 'nomina_data_source.dart';

/// Fuente de datos remota contra la API Laravel para nóminas y citaciones.
class RemoteNominaDataSource implements NominaDataSource {
  RemoteNominaDataSource({
    required this._apiClient,
  });

  final ApiClient _apiClient;

  @override
  Future<List<EventoNomina>> fetchEventos({required int equipoId}) async {
    final response = await _apiClient.get(
      '/coach/equipos/$equipoId/nomina',
    );

    final rawList = _unwrapList(response);
    if (rawList == null) {
      throw ApiException.invalidResponse(
        'La respuesta de nóminas y citaciones es inválida.',
      );
    }

    return rawList
        .whereType<Map>()
        .map((item) => EventoNomina.fromJson(Map<String, dynamic>.from(item)))
        .toList(growable: false);
  }

  @override
  Future<EventoNomina> intercambiarTitularSuplente({
    required int eventoId,
    required int titularId,
    required int suplenteId,
  }) async {
    final response = await _apiClient.post(
      '/coach/nomina/$eventoId/intercambiar',
      body: <String, dynamic>{
        'titular_id': titularId,
        'suplente_id': suplenteId,
      },
    );

    final payload = _unwrapMap(response);
    if (payload == null) {
      throw ApiException.invalidResponse(
        'La respuesta al intercambiar titular y suplente es inválida.',
      );
    }

    return EventoNomina.fromJson(payload);
  }

  @override
  Future<EventoNomina> actualizarEstadoCitacion({
    required int eventoId,
    required int jugadorId,
    required EstadoCitacion estado,
  }) async {
    final response = await _apiClient.patch(
      '/coach/nomina/$eventoId/citaciones/$jugadorId',
      body: <String, dynamic>{
        'estado_respuesta': estado.apiValue,
      },
    );

    final payload = _unwrapMap(response);
    if (payload == null) {
      throw ApiException.invalidResponse(
        'La respuesta al actualizar la citación es inválida.',
      );
    }

    return EventoNomina.fromJson(payload);
  }

  List<dynamic>? _unwrapList(dynamic response) {
    if (response is List) {
      return response;
    }
    if (response is Map && response['data'] is List) {
      return response['data'] as List<dynamic>;
    }
    return null;
  }

  Map<String, dynamic>? _unwrapMap(dynamic response) {
    if (response is Map<String, dynamic>) {
      final data = response['data'];
      if (data is Map) {
        return Map<String, dynamic>.from(data);
      }
      return response;
    }
    if (response is Map) {
      return Map<String, dynamic>.from(response);
    }
    return null;
  }
}
