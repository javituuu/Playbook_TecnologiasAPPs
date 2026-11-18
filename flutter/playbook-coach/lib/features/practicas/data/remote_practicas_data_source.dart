import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../domain/crear_practica_request.dart';
import '../domain/practica.dart';
import 'practicas_data_source.dart';

/// Fuente de datos remota contra la API Laravel para prácticas programadas.
class RemotePracticasDataSource implements PracticasDataSource {
  RemotePracticasDataSource({
    required this._apiClient,
  });

  final ApiClient _apiClient;

  @override
  Future<List<Practica>> fetchPracticas({
    required int equipoId,
    String? semanaIso,
  }) async {
    final response = await _apiClient.get(
      '/coach/equipos/$equipoId/practicas',
      queryParameters: <String, dynamic>{
        if (semanaIso != null && semanaIso.trim().isNotEmpty)
          'semana': semanaIso.trim(),
      },
    );

    final rawList = _unwrapList(response);
    if (rawList == null) {
      throw ApiException.invalidResponse(
        'La respuesta del listado de prácticas es inválida.',
      );
    }

    return rawList
        .whereType<Map>()
        .map((item) => Practica.fromJson(Map<String, dynamic>.from(item)))
        .toList(growable: false);
  }

  @override
  Future<Practica> agendarPractica(CrearPracticaRequest request) async {
    final response = await _apiClient.post(
      '/coach/practicas',
      body: request.toJson(),
    );

    final payload = _unwrapMap(response);
    if (payload == null) {
      throw ApiException.invalidResponse(
        'La respuesta al agendar la práctica es inválida.',
      );
    }

    return Practica.fromJson(payload);
  }

  @override
  Future<Practica> cancelarPractica({
    required int practicaId,
    String? motivo,
  }) async {
    final response = await _apiClient.patch(
      '/coach/practicas/$practicaId/cancelar',
      body: <String, dynamic>{
        if (motivo != null && motivo.trim().isNotEmpty)
          'motivo': motivo.trim(),
      },
    );

    final payload = _unwrapMap(response);
    if (payload == null) {
      throw ApiException.invalidResponse(
        'La respuesta al cancelar la práctica es inválida.',
      );
    }

    return Practica.fromJson(payload);
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
    if (response is! Map) {
      return null;
    }
    final root = Map<String, dynamic>.from(response);
    if (root['data'] is Map) {
      return Map<String, dynamic>.from(root['data'] as Map);
    }
    return root;
  }
}
