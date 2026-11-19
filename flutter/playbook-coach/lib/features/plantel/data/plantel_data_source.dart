import '../domain/plantel_equipo.dart';

/// Fuente de datos abstracta para el módulo de plantel.
abstract class PlantelDataSource {
  Future<PlantelEquipo> fetchPlantel({required int equipoId});
}
