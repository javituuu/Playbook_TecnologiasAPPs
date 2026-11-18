import '../domain/estado_citacion.dart';
import '../domain/evento_nomina.dart';

/// Fuente de datos abstracta para el módulo de nóminas y citaciones.
abstract class NominaDataSource {
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
