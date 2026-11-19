import '../../../core/utils/week_utils.dart';
import '../domain/bloque_disponibilidad.dart';
import '../domain/matriz_disponibilidad.dart';
import 'heatmap_data_source.dart';

/// Fuente mock con matrices de disponibilidad fieles al diseño de Playbook Coach.
class MockHeatmapDataSource implements HeatmapDataSource {
  MockHeatmapDataSource({
    this.simulatedDelay = const Duration(milliseconds: 180),
  });

  final Duration simulatedDelay;

  static const List<JugadorDisponible> _rosterLol = <JugadorDisponible>[
    JugadorDisponible(
      id: 101,
      nombre: 'Martín Gómez',
      nickname: 'Slayer',
      rol: 'Top',
    ),
    JugadorDisponible(
      id: 102,
      nombre: 'Sofía Paz',
      nickname: 'Lumina',
      rol: 'Mid',
    ),
    JugadorDisponible(
      id: 103,
      nombre: 'Lucas Ruiz',
      nickname: 'Frost',
      rol: 'ADC',
    ),
    JugadorDisponible(
      id: 104,
      nombre: 'Diego Pérez',
      nickname: 'Vortex',
      rol: 'Jungla',
    ),
    JugadorDisponible(
      id: 105,
      nombre: 'Cata Ríos',
      nickname: 'Nyx',
      rol: 'Support',
    ),
  ];

  static const List<JugadorDisponible> _rosterValorant = <JugadorDisponible>[
    JugadorDisponible(
      id: 201,
      nombre: 'Diego Pérez',
      nickname: 'Vortex',
      rol: 'Duelista',
    ),
    JugadorDisponible(
      id: 202,
      nombre: 'Valentina Soto',
      nickname: 'Valkyrie',
      rol: 'Controlador',
    ),
    JugadorDisponible(
      id: 203,
      nombre: 'Tomás Herrera',
      nickname: 'Krono',
      rol: 'Iniciador',
    ),
    JugadorDisponible(
      id: 204,
      nombre: 'Andrés Molina',
      nickname: 'Atlas',
      rol: 'Centinela',
    ),
    JugadorDisponible(
      id: 205,
      nombre: 'Felipe Araya',
      nickname: 'Echo',
      rol: 'Flex',
    ),
  ];

  /// Matriz base para `UDD Titans — LoL` (idéntica a `flutter-coach-heatmap.png`).
  static const List<List<int>> _lolCurrentCounts = <List<int>>[
    // Lun, Mar, Mié, Jue, Vie, Sáb, Dom
    <int>[5, 4, 3, 5, 2, 5, 1], // 15:00
    <int>[4, 4, 4, 4, 1, 4, 2], // 16:00
    <int>[5, 3, 5, 5, 0, 5, 3], // 17:00
    <int>[3, 5, 2, 4, 4, 3, 4], // 18:00
    <int>[5, 4, 5, 3, 5, 4, 5], // 19:00
    <int>[2, 1, 4, 5, 5, 5, 5], // 20:00
    <int>[5, 5, 5, 4, 3, 2, 1], // 21:00
    <int>[4, 4, 3, 3, 1, 1, 0], // 22:00
  ];

  /// Matriz base para `UDD Nova — Valorant`.
  static const List<List<int>> _valorantCurrentCounts = <List<int>>[
    <int>[3, 4, 4, 2, 5, 4, 2], // 15:00
    <int>[4, 5, 4, 3, 4, 5, 3], // 16:00
    <int>[4, 4, 5, 4, 3, 4, 4], // 17:00
    <int>[5, 5, 4, 4, 2, 5, 4], // 18:00
    <int>[5, 4, 5, 5, 3, 5, 5], // 19:00
    <int>[4, 5, 5, 5, 4, 4, 5], // 20:00
    <int>[3, 4, 4, 5, 5, 3, 4], // 21:00
    <int>[2, 3, 3, 4, 4, 2, 1], // 22:00
  ];

  @override
  Future<MatrizDisponibilidad> fetchHeatmap({
    required int equipoId,
    required String semanaIso,
  }) async {
    if (simulatedDelay > Duration.zero) {
      await Future<void>.delayed(simulatedDelay);
    }

    final monday = WeekUtils.parseIsoWeekMonday(semanaIso);
    final currentWeekIso = WeekUtils.toIsoWeek(DateTime.now());
    final currentMonday = WeekUtils.parseIsoWeekMonday(currentWeekIso);
    final weekOffset = monday.difference(currentMonday).inDays ~/ 7;

    final roster = equipoId == 2 ? _rosterValorant : _rosterLol;
    final baseMatrix =
        equipoId == 2 ? _valorantCurrentCounts : _lolCurrentCounts;

    final bloques = <BloqueDisponibilidad>[];
    final horas = MatrizDisponibilidad.defaultHoras;
    final dias = MatrizDisponibilidad.defaultDias;

    for (var rowIndex = 0; rowIndex < horas.length; rowIndex++) {
      final hora = horas[rowIndex];
      for (var dayIndex = 0; dayIndex < dias.length; dayIndex++) {
        final diaSemana = dayIndex + 1;
        final diaNombre = dias[dayIndex];
        final fecha = monday.add(Duration(days: dayIndex));
        final count = _resolveCount(
          baseCount: baseMatrix[rowIndex][dayIndex],
          weekOffset: weekOffset,
          rowIndex: rowIndex,
          dayIndex: dayIndex,
        );

        final blockData = _buildParticipants(
          equipoId: equipoId,
          roster: roster,
          count: count,
          diaSemana: diaSemana,
          hora: hora,
          rowIndex: rowIndex,
          dayIndex: dayIndex,
        );

        bloques.add(
          BloqueDisponibilidad(
            id: '$semanaIso-$diaSemana-$hora',
            diaSemana: diaSemana,
            diaNombre: diaNombre,
            fecha: fecha,
            hora: hora,
            disponiblesCount: count,
            totalJugadores: roster.length,
            quorumMinimo: 4,
            disponibles: blockData.disponibles,
            ausencias: blockData.ausencias,
          ),
        );
      }
    }

    return MatrizDisponibilidad(
      equipoId: equipoId,
      semanaIso: semanaIso,
      semanaLabel: WeekUtils.formatWeekRange(monday),
      fechaInicio: monday,
      quorumMinimo: 4,
      totalJugadores: roster.length,
      bloques: List<BloqueDisponibilidad>.unmodifiable(bloques),
    );
  }

  int _resolveCount({
    required int baseCount,
    required int weekOffset,
    required int rowIndex,
    required int dayIndex,
  }) {
    if (weekOffset == 0) {
      return baseCount;
    }
    // En otras semanas varía suavemente manteniendo patrones realistas de 0..5.
    final shift = (weekOffset + rowIndex + dayIndex) % 3 - 1;
    return (baseCount + shift).clamp(0, 5);
  }

  _ParticipantsSplit _buildParticipants({
    required int equipoId,
    required List<JugadorDisponible> roster,
    required int count,
    required int diaSemana,
    required String hora,
    required int rowIndex,
    required int dayIndex,
  }) {
    // Caso destacado fiel a `flutter-coach-heatmap.png`: Jueves @ 19:00 en LoL (3/5)
    if (equipoId == 1 && diaSemana == 4 && hora == '19:00' && count == 3) {
      return _ParticipantsSplit(
        disponibles: roster.sublist(0, 3),
        ausencias: const <AusenciaRegistrada>[
          AusenciaRegistrada(
            jugadorId: 104,
            nombreJugador: 'Diego Pérez',
            motivo: 'Cruce de horario activo',
            categoriaBadge: 'PARTIDO VALORANT 20:00',
            esCrucePartido: true,
          ),
          AusenciaRegistrada(
            jugadorId: 105,
            nombreJugador: 'Cata Ríos',
            motivo: 'Falta de disponibilidad',
            categoriaBadge: 'ACADÉMICO',
          ),
        ],
      );
    }

    final safeCount = count.clamp(0, roster.length);
    final disponibles = roster.sublist(0, safeCount);
    final absentPlayers = roster.sublist(safeCount);
    final ausencias = <AusenciaRegistrada>[];

    for (var i = 0; i < absentPlayers.length; i++) {
      final player = absentPlayers[i];
      final selector = (rowIndex + dayIndex + i) % 4;
      final ausencia = switch (selector) {
        0 => AusenciaRegistrada(
            jugadorId: player.id,
            nombreJugador: player.nombre,
            motivo: 'Falta de disponibilidad',
            categoriaBadge: 'ACADÉMICO',
          ),
        1 => AusenciaRegistrada(
            jugadorId: player.id,
            nombreJugador: player.nombre,
            motivo: 'Cruce de horario activo',
            categoriaBadge: equipoId == 1
                ? 'PARTIDO VALORANT 20:00'
                : 'TORNEO UNIVERSITARIO',
            esCrucePartido: true,
          ),
        2 => AusenciaRegistrada(
            jugadorId: player.id,
            nombreJugador: player.nombre,
            motivo: 'Bloqueo laboral recurrente',
            categoriaBadge: 'LABORAL',
          ),
        _ => AusenciaRegistrada(
            jugadorId: player.id,
            nombreJugador: player.nombre,
            motivo: 'Laboratorio vespertino UDD',
            categoriaBadge: 'ACADÉMICO',
          ),
      };
      ausencias.add(ausencia);
    }

    return _ParticipantsSplit(
      disponibles: disponibles,
      ausencias: ausencias,
    );
  }
}

class _ParticipantsSplit {
  const _ParticipantsSplit({
    required this.disponibles,
    required this.ausencias,
  });

  final List<JugadorDisponible> disponibles;
  final List<AusenciaRegistrada> ausencias;
}
