import '../domain/crear_practica_request.dart';
import '../domain/practica.dart';

/// Fuente de datos abstracta para el módulo de prácticas.
abstract class PracticasDataSource {
  Future<List<Practica>> fetchPracticas({
    required int equipoId,
    String? semanaIso,
  });

  Future<Practica> agendarPractica(CrearPracticaRequest request);

  Future<Practica> cancelarPractica({
    required int practicaId,
    String? motivo,
  });
}
