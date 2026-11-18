import 'crear_practica_request.dart';
import 'practica.dart';

/// Contrato de acceso para consulta, agendamiento y cancelación de prácticas.
abstract class PracticasRepository {
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
