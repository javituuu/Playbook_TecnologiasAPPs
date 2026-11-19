import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../domain/matriz_disponibilidad.dart';
import 'heatmap_data_source.dart';

/// Fuente de datos remota contra la API Laravel para la matriz semanal.
class RemoteHeatmapDataSource implements HeatmapDataSource {
  RemoteHeatmapDataSource({
    required this._apiClient,
  });

  final ApiClient _apiClient;

  @override
  Future<MatrizDisponibilidad> fetchHeatmap({
    required int equipoId,
    required String semanaIso,
  }) async {
    final response = await _apiClient.get(
      '/coach/equipos/$equipoId/heatmap',
      queryParameters: <String, dynamic>{'semana': semanaIso},
    );

    final payload = _unwrapMap(response);
    if (payload == null) {
      throw ApiException.invalidResponse(
        'La respuesta de disponibilidad semanal es inválida.',
      );
    }

    return MatrizDisponibilidad.fromJson(payload);
  }

  Map<String, dynamic>? _unwrapMap(dynamic response) {
    if (response is! Map) {
      return null;
    }
    final root = Map<String, dynamic>.from(response);
    final nested = root['data'];
    if (nested is Map) {
      return Map<String, dynamic>.from(nested);
    }
    return root;
  }
}
