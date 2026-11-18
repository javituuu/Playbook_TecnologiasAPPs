import 'estado_citacion.dart';
import 'evento_nomina.dart';

/// Contrato para gestión de partidos/prácticas, citaciones e intercambio de nómina.
abstract class NominaRepository {
  Future<List<EventoNomina>> fetchEventos({required int equipoId});

  Future<EventoNomina> intercambiarTitularSuplente({
    required int eventoId,
    required int titularId,
    required int suplenteId,
  });

  Future<EventoNomina> actualizarEstadoCitacion({
    required int eventoId,
    required int jugadorId,
    required EstadoCitacion estado,
  });
}
