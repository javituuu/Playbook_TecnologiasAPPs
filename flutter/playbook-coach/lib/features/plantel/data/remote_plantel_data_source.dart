import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../domain/plantel_equipo.dart';
import 'plantel_data_source.dart';

/// Fuente de datos remota contra la API Laravel para el plantel del equipo.
class RemotePlantelDataSource implements PlantelDataSource {
  RemotePlantelDataSource({
    required this._apiClient,
  });

  final ApiClient _apiClient;

  @override
  Future<PlantelEquipo> fetchPlantel({required int equipoId}) async {
    final response = await _apiClient.get(
      '/coach/equipos/$equipoId/plantel',
    );

    if (response is List) {
      return PlantelEquipo.fromJson(
        <String, dynamic>{'equipo_id': equipoId, 'miembros': response},
        fallbackEquipoId: equipoId,
      );
    }

    if (response is Map) {
      final rawMap = Map<String, dynamic>.from(response);
      final data = rawMap['data'];
      if (data is Map) {
        return PlantelEquipo.fromJson(
          Map<String, dynamic>.from(data),
          fallbackEquipoId: equipoId,
        );
      }
      if (data is List) {
        return PlantelEquipo.fromJson(
          <String, dynamic>{...rawMap, 'miembros': data},
          fallbackEquipoId: equipoId,
        );
      }
      return PlantelEquipo.fromJson(
        rawMap,
        fallbackEquipoId: equipoId,
      );
    }

    throw ApiException.invalidResponse(
      'La respuesta del plantel del equipo es inválida.',
    );
  }
}
