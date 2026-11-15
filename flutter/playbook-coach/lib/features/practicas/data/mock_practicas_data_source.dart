import '../../../core/network/api_exception.dart';
import '../../../core/utils/week_utils.dart';
import '../../heatmap/domain/serie_duracion.dart';
import '../domain/crear_practica_request.dart';
import '../domain/practica.dart';
import 'practicas_data_source.dart';

/// Fuente de datos mock con estado en memoria para agendar y cancelar prácticas.
class MockPracticasDataSource implements PracticasDataSource {
  MockPracticasDataSource({
    this.simulatedDelay = const Duration(milliseconds: 200),
    DateTime? referenceDate,
  }) : _practicas = _seedInitialPracticas(referenceDate ?? DateTime.now()) {
    _nextId = _practicas.length + 1;
  }

  final Duration simulatedDelay;
  final List<Practica> _practicas;
  late int _nextId;

  static List<Practica> _seedInitialPracticas(DateTime referenceDate) {
    final monday = WeekUtils.mondayOfWeek(referenceDate);
    final semanaIso = WeekUtils.toIsoWeek(monday);

    return <Practica>[
      Practica(
        id: 1,
        equipoId: 1,
        equipoNombre: 'UDD Titans — LoL',
        semanaIso: semanaIso,
        diaSemana: 3,
        diaNombre: 'Miércoles',
        fecha: monday.add(const Duration(days: 2)),
        horaInicio: '21:00',
        horaFin: '23:00',
        serie: SerieDuracion.bo3,
        rival: 'USM Gaming',
        modalidad: 'Scrim competitivo',
        servidor: 'Discord + Server LAS',
        notas: 'Foco en draft de fase temprana y control de objetivos.',
        jugadoresCitados: const <String>[
          "Martín 'Slayer' Gómez",
          "Sofía 'Lumina' Paz",
          "Lucas 'Frost' Ruiz",
          "Diego 'Vortex' Pérez",
          "Cata 'Nyx' Ríos",
        ],
      ),
      Practica(
        id: 2,
        equipoId: 1,
        equipoNombre: 'UDD Titans — LoL',
        semanaIso: semanaIso,
        diaSemana: 6,
        diaNombre: 'Sábado',
        fecha: monday.add(const Duration(days: 5)),
        horaInicio: '17:00',
        horaFin: '18:00',
        serie: SerieDuracion.bo1,
        rival: 'UC Esports',
        modalidad: 'Revisión VOD + Scrim',
        servidor: 'Discord + Sala Privada',
        jugadoresCitados: const <String>[
          "Martín 'Slayer' Gómez",
          "Sofía 'Lumina' Paz",
          "Lucas 'Frost' Ruiz",
          "Diego 'Vortex' Pérez",
          "Cata 'Nyx' Ríos",
        ],
      ),
      Practica(
        id: 3,
        equipoId: 2,
        equipoNombre: 'UDD Nova — Valorant',
        semanaIso: semanaIso,
        diaSemana: 2,
        diaNombre: 'Martes',
        fecha: monday.add(const Duration(days: 1)),
        horaInicio: '20:00',
        horaFin: '22:00',
        serie: SerieDuracion.bo3,
        rival: 'UNAB Five',
        modalidad: 'Scrim competitivo',
        servidor: 'Server Santiago · Sala Privada',
        notas: 'Practicar retakes en Ascent y Lotus.',
        jugadoresCitados: const <String>[
          "Diego 'Vortex' Pérez",
          "Valentina 'Valkyrie' Soto",
          "Tomás 'Krono' Herrera",
          "Andrés 'Atlas' Molina",
          "Felipe 'Echo' Araya",
        ],
      ),
    ];
  }

  @override
  Future<List<Practica>> fetchPracticas({
    required int equipoId,
    String? semanaIso,
  }) async {
    await _waitIfNeeded();

    final filtered = _practicas.where((item) {
      if (item.equipoId != equipoId) return false;
      if (semanaIso != null &&
          semanaIso.trim().isNotEmpty &&
          item.semanaIso != semanaIso.trim()) {
        return false;
      }
      return true;
    }).toList();

    filtered.sort((a, b) {
      if (a.estaProgramada != b.estaProgramada) {
        return a.estaProgramada ? -1 : 1;
      }
      final dayCompare = a.diaSemana.compareTo(b.diaSemana);
      if (dayCompare != 0) return dayCompare;
      return a.horaInicio.compareTo(b.horaInicio);
    });

    return List<Practica>.unmodifiable(filtered);
  }

  @override
  Future<Practica> agendarPractica(CrearPracticaRequest request) async {
    await _waitIfNeeded();

    final created = Practica(
      id: _nextId++,
      equipoId: request.equipoId,
      equipoNombre: request.equipoNombre,
      semanaIso: request.semanaIso,
      diaSemana: request.diaSemana,
      diaNombre: request.diaNombre,
      fecha: request.fecha,
      horaInicio: request.horaInicio,
      horaFin: request.serie.computeEndTime(request.horaInicio),
      serie: request.serie,
      rival: request.rival.trim(),
      modalidad: request.modalidad.trim(),
      servidor: request.servidor.trim(),
      notas: request.notas?.trim().isEmpty == true
          ? null
          : request.notas?.trim(),
      jugadoresCitados: request.citarDisponibles
          ? List<String>.unmodifiable(request.jugadoresCitados)
          : const <String>[],
      estado: EstadoPractica.programada,
    );

    _practicas.add(created);
    return created;
  }

  @override
  Future<Practica> cancelarPractica({
    required int practicaId,
    String? motivo,
  }) async {
    await _waitIfNeeded();

    final index = _practicas.indexWhere((item) => item.id == practicaId);
    if (index == -1) {
      throw ApiException.fromResponse(
        statusCode: 404,
        body: const <String, dynamic>{
          'message': 'No se encontró la práctica solicitada.',
        },
      );
    }

    final normalizedReason = motivo?.trim();
    final updated = _practicas[index].copyWith(
      estado: EstadoPractica.cancelada,
      motivoCancelacion: normalizedReason != null && normalizedReason.isNotEmpty
          ? normalizedReason
          : 'Cancelada por el coach',
    );
    _practicas[index] = updated;
    return updated;
  }

  Future<void> _waitIfNeeded() async {
    if (simulatedDelay > Duration.zero) {
      await Future<void>.delayed(simulatedDelay);
    }
  }
}
