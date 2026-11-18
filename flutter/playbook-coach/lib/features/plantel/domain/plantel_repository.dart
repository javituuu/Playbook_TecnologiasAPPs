import 'plantel_equipo.dart';

/// Contrato para consultar el plantel y cobertura de posiciones del equipo.
abstract class PlantelRepository {
  Future<PlantelEquipo> fetchPlantel({required int equipoId});
}
