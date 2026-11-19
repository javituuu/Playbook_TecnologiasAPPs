import '../../../core/network/api_exception.dart';
import '../domain/citacion_jugador.dart';
import '../domain/estado_citacion.dart';
import '../domain/evento_nomina.dart';
import 'nomina_data_source.dart';

/// Fuente de datos mock en memoria para partidos, prácticas, citaciones y swaps.
class MockNominaDataSource implements NominaDataSource {
  MockNominaDataSource() {
    _seed();
  }

  final Map<int, List<EventoNomina>> _eventosPorEquipo =
      <int, List<EventoNomina>>{};

  @override
  Future<List<EventoNomina>> fetchEventos({required int equipoId}) async {
    await Future<void>.delayed(const Duration(milliseconds: 180));
    final list = _eventosPorEquipo[equipoId] ?? const <EventoNomina>[];
    return List<EventoNomina>.unmodifiable(list);
  }

  @override
  Future<EventoNomina> intercambiarTitularSuplente({
    required int eventoId,
    required int titularId,
    required int suplenteId,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 140));
    for (final entry in _eventosPorEquipo.entries) {
      final index = entry.value.indexWhere((evento) => evento.id == eventoId);
      if (index == -1) {
        continue;
      }

      final evento = entry.value[index];
      final titularIndex = evento.citaciones.indexWhere(
        (c) => c.jugadorId == titularId && c.esTitular,
      );
      final suplenteIndex = evento.citaciones.indexWhere(
        (c) => c.jugadorId == suplenteId && !c.esTitular,
      );

      if (titularIndex == -1 || suplenteIndex == -1) {
        throw const ApiException(
          type: ApiErrorType.validation,
          message:
              'Selecciona un titular y un suplente válidos para intercambiar.',
          statusCode: 422,
        );
      }

      final updatedCitaciones = List<CitacionJugador>.from(evento.citaciones);
      final titular = updatedCitaciones[titularIndex];
      final suplente = updatedCitaciones[suplenteIndex];

      updatedCitaciones[titularIndex] = titular.copyWith(
        esTitular: false,
      );
      updatedCitaciones[suplenteIndex] = suplente.copyWith(
        esTitular: true,
      );

      final updatedEvento = evento.copyWith(
        citaciones: List<CitacionJugador>.unmodifiable(updatedCitaciones),
      );
      entry.value[index] = updatedEvento;
      return updatedEvento;
    }

    throw const ApiException(
      type: ApiErrorType.notFound,
      message: 'No se encontró el evento solicitado.',
      statusCode: 404,
    );
  }

  @override
  Future<EventoNomina> actualizarEstadoCitacion({
    required int eventoId,
    required int jugadorId,
    required EstadoCitacion estado,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
    for (final entry in _eventosPorEquipo.entries) {
      final index = entry.value.indexWhere((evento) => evento.id == eventoId);
      if (index == -1) {
        continue;
      }

      final evento = entry.value[index];
      final playerIndex = evento.citaciones.indexWhere(
        (c) => c.jugadorId == jugadorId,
      );
      if (playerIndex == -1) {
        throw const ApiException(
          type: ApiErrorType.notFound,
          message: 'Jugador no encontrado en la citación.',
          statusCode: 404,
        );
      }

      final updatedCitaciones = List<CitacionJugador>.from(evento.citaciones);
      updatedCitaciones[playerIndex] = updatedCitaciones[playerIndex].copyWith(
        estadoRespuesta: estado,
      );

      final updatedEvento = evento.copyWith(
        citaciones: List<CitacionJugador>.unmodifiable(updatedCitaciones),
      );
      entry.value[index] = updatedEvento;
      return updatedEvento;
    }

    throw const ApiException(
      type: ApiErrorType.notFound,
      message: 'No se encontró el evento solicitado.',
      statusCode: 404,
    );
  }

  void _seed() {
    final now = DateTime.now();
    final baseDate = DateTime(now.year, now.month, now.day);

    _eventosPorEquipo[1] = <EventoNomina>[
      EventoNomina(
        id: 101,
        equipoId: 1,
        tipo: TipoEventoNomina.partido,
        titulo: 'UDD Titans vs UNAB',
        ligaOCompetencia: 'Copa Universitaria LoL · Jornada 4',
        fecha: baseDate.add(const Duration(days: 1)),
        horaInicio: '20:00',
        horaFin: '21:30',
        lugarOServidor: 'Online · Servidor LAS',
        mensajeCoach:
            'Presentarse 20 minutos antes en Discord para draft y warm-up.',
        citaciones: const <CitacionJugador>[
          CitacionJugador(
            jugadorId: 11,
            nombre: 'Sebastián Mora',
            nickname: 'Aegis',
            posicion: 'Top',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 12,
            nombre: 'Vicente Silva',
            nickname: 'Kairo',
            posicion: 'Jungla',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 13,
            nombre: 'Diego Pérez',
            nickname: 'Kuro',
            posicion: 'Mid',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.rechazado,
            motivoRespuesta: 'Cruce horario con UDD Nova en Liga Valorant',
            conflictoHorario: 'PARTIDO VALORANT 20:00',
          ),
          CitacionJugador(
            jugadorId: 14,
            nombre: 'Lucas Soto',
            nickname: 'DravenCL',
            posicion: 'ADC',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 15,
            nombre: 'Benjamín Paredes',
            nickname: 'Nox',
            posicion: 'Support',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.pendiente,
          ),
          CitacionJugador(
            jugadorId: 16,
            nombre: 'Matías Rojas',
            nickname: 'Flux',
            posicion: 'Mid',
            rolMembresia: 'Suplente',
            esTitular: false,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 17,
            nombre: 'Tomás Herrera',
            nickname: 'Vex',
            posicion: 'Support',
            rolMembresia: 'Suplente',
            esTitular: false,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
        ],
      ),
      EventoNomina(
        id: 102,
        equipoId: 1,
        tipo: TipoEventoNomina.practica,
        titulo: 'Scrim vs PUCV Esports',
        ligaOCompetencia: 'Práctica Bo3 · Control de objetivos',
        fecha: baseDate.add(const Duration(days: 2)),
        horaInicio: '19:00',
        horaFin: '21:00',
        lugarOServidor: 'Online · Custom Lobby LAS',
        mensajeCoach: 'Revisar rotaciones de heraldo y setup de dragón.',
        citaciones: const <CitacionJugador>[
          CitacionJugador(
            jugadorId: 11,
            nombre: 'Sebastián Mora',
            nickname: 'Aegis',
            posicion: 'Top',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 12,
            nombre: 'Vicente Silva',
            nickname: 'Kairo',
            posicion: 'Jungla',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 13,
            nombre: 'Diego Pérez',
            nickname: 'Kuro',
            posicion: 'Mid',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 14,
            nombre: 'Lucas Soto',
            nickname: 'DravenCL',
            posicion: 'ADC',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.pendiente,
          ),
          CitacionJugador(
            jugadorId: 15,
            nombre: 'Benjamín Paredes',
            nickname: 'Nox',
            posicion: 'Support',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 16,
            nombre: 'Matías Rojas',
            nickname: 'Flux',
            posicion: 'Mid',
            rolMembresia: 'Suplente',
            esTitular: false,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
        ],
      ),
      EventoNomina(
        id: 103,
        equipoId: 1,
        tipo: TipoEventoNomina.partido,
        titulo: 'UDD Titans vs USM',
        ligaOCompetencia: 'Copa Universitaria LoL · Jornada 5',
        fecha: baseDate.add(const Duration(days: 4)),
        horaInicio: '18:00',
        horaFin: '20:00',
        lugarOServidor: 'Online · Servidor LAS',
        citaciones: const <CitacionJugador>[
          CitacionJugador(
            jugadorId: 11,
            nombre: 'Sebastián Mora',
            nickname: 'Aegis',
            posicion: 'Top',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 12,
            nombre: 'Vicente Silva',
            nickname: 'Kairo',
            posicion: 'Jungla',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 13,
            nombre: 'Diego Pérez',
            nickname: 'Kuro',
            posicion: 'Mid',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 14,
            nombre: 'Lucas Soto',
            nickname: 'DravenCL',
            posicion: 'ADC',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 15,
            nombre: 'Benjamín Paredes',
            nickname: 'Nox',
            posicion: 'Support',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
        ],
      ),
    ];

    _eventosPorEquipo[2] = <EventoNomina>[
      EventoNomina(
        id: 201,
        equipoId: 2,
        tipo: TipoEventoNomina.partido,
        titulo: 'UDD Nova vs INACAP',
        ligaOCompetencia: 'Liga Universitaria Valorant · Fecha 3',
        fecha: baseDate.add(const Duration(days: 1)),
        horaInicio: '20:00',
        horaFin: '21:30',
        lugarOServidor: 'Online · Servidor Santiago',
        mensajeCoach: 'Veto de mapas 19:30 en canal oficial de capitanes.',
        citaciones: const <CitacionJugador>[
          CitacionJugador(
            jugadorId: 13,
            nombre: 'Diego Pérez',
            nickname: 'Kuro',
            posicion: 'Duelist',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 22,
            nombre: 'Sofía Lagos',
            nickname: 'Nyx',
            posicion: 'Controller',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.rechazado,
            motivoRespuesta: 'Certamen vespertino hasta las 20:45',
            conflictoHorario: 'ACADÉMICO',
          ),
          CitacionJugador(
            jugadorId: 23,
            nombre: 'Martín Núñez',
            nickname: 'Spike',
            posicion: 'Initiator',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 24,
            nombre: 'Camila Vera',
            nickname: 'Valk',
            posicion: 'Sentinel',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 25,
            nombre: 'Joaquín León',
            nickname: 'Zen',
            posicion: 'Flex',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.pendiente,
          ),
          CitacionJugador(
            jugadorId: 26,
            nombre: 'Ignacio Vega',
            nickname: 'Pulse',
            posicion: 'Controller',
            rolMembresia: 'Suplente',
            esTitular: false,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
        ],
      ),
      EventoNomina(
        id: 202,
        equipoId: 2,
        tipo: TipoEventoNomina.practica,
        titulo: 'Scrim táctico Lotus / Ascent',
        ligaOCompetencia: 'Práctica Bo3 · Ejecuciones en ataque',
        fecha: baseDate.add(const Duration(days: 3)),
        horaInicio: '19:00',
        horaFin: '21:00',
        lugarOServidor: 'Online · Servidor Santiago',
        citaciones: const <CitacionJugador>[
          CitacionJugador(
            jugadorId: 13,
            nombre: 'Diego Pérez',
            nickname: 'Kuro',
            posicion: 'Duelist',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 22,
            nombre: 'Sofía Lagos',
            nickname: 'Nyx',
            posicion: 'Controller',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 23,
            nombre: 'Martín Núñez',
            nickname: 'Spike',
            posicion: 'Initiator',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 24,
            nombre: 'Camila Vera',
            nickname: 'Valk',
            posicion: 'Sentinel',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
          CitacionJugador(
            jugadorId: 25,
            nombre: 'Joaquín León',
            nickname: 'Zen',
            posicion: 'Flex',
            rolMembresia: 'Titular',
            esTitular: true,
            estadoRespuesta: EstadoCitacion.pendiente,
          ),
          CitacionJugador(
            jugadorId: 26,
            nombre: 'Ignacio Vega',
            nickname: 'Pulse',
            posicion: 'Controller',
            rolMembresia: 'Suplente',
            esTitular: false,
            estadoRespuesta: EstadoCitacion.confirmado,
          ),
        ],
      ),
    ];
  }
}
