import '../domain/miembro_plantel.dart';
import '../domain/plantel_equipo.dart';
import '../domain/rol_membresia.dart';
import 'plantel_data_source.dart';

/// Fuente de datos mock en memoria para el plantel del equipo.
class MockPlantelDataSource implements PlantelDataSource {
  MockPlantelDataSource() {
    _seed();
  }

  final Map<int, PlantelEquipo> _planteles = <int, PlantelEquipo>{};

  @override
  Future<PlantelEquipo> fetchPlantel({required int equipoId}) async {
    await Future<void>.delayed(const Duration(milliseconds: 160));
    return _planteles[equipoId] ??
        PlantelEquipo(
          equipoId: equipoId,
          posicionesRequeridas: const <String>[],
          miembros: const <MiembroPlantel>[],
        );
  }

  void _seed() {
    _planteles[1] = const PlantelEquipo(
      equipoId: 1,
      posicionesRequeridas: <String>[
        'Top',
        'Jungla',
        'Mid',
        'ADC',
        'Support',
      ],
      miembros: <MiembroPlantel>[
        MiembroPlantel(
          id: 11,
          nombre: 'Sebastián Mora',
          nickname: 'Aegis',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Top',
          posicionSecundaria: 'Support',
          rango: 'Diamante II',
          esCapitan: true,
        ),
        MiembroPlantel(
          id: 12,
          nombre: 'Vicente Silva',
          nickname: 'Kairo',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Jungla',
          posicionSecundaria: 'Top',
          rango: 'Maestro',
        ),
        MiembroPlantel(
          id: 13,
          nombre: 'Diego Pérez',
          nickname: 'Kuro',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Mid',
          posicionSecundaria: 'ADC',
          rango: 'Diamante I',
        ),
        MiembroPlantel(
          id: 14,
          nombre: 'Lucas Soto',
          nickname: 'DravenCL',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'ADC',
          posicionSecundaria: 'Mid',
          rango: 'Esmeralda I',
        ),
        MiembroPlantel(
          id: 15,
          nombre: 'Benjamín Paredes',
          nickname: 'Nox',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Support',
          posicionSecundaria: 'Jungla',
          rango: 'Diamante III',
        ),
        MiembroPlantel(
          id: 16,
          nombre: 'Matías Rojas',
          nickname: 'Flux',
          rolMembresia: RolMembresia.suplente,
          posicionPrincipal: 'Mid',
          posicionSecundaria: 'Top',
          rango: 'Esmeralda II',
        ),
        MiembroPlantel(
          id: 17,
          nombre: 'Tomás Herrera',
          nickname: 'Vex',
          rolMembresia: RolMembresia.suplente,
          posicionPrincipal: 'Support',
          posicionSecundaria: 'Jungla',
          rango: 'Platino I',
        ),
        MiembroPlantel(
          id: 18,
          nombre: 'Renato Campos',
          nickname: 'RivenX',
          rolMembresia: RolMembresia.enPrueba,
          posicionPrincipal: 'Top',
          posicionSecundaria: 'Jungla',
          rango: 'Esmeralda III',
        ),
        MiembroPlantel(
          id: 19,
          nombre: 'Alonso Vidal',
          nickname: 'Zedrik',
          rolMembresia: RolMembresia.enPrueba,
          posicionPrincipal: 'ADC',
          posicionSecundaria: 'Support',
          rango: 'Platino II',
        ),
      ],
    );

    _planteles[2] = const PlantelEquipo(
      equipoId: 2,
      posicionesRequeridas: <String>[
        'Duelist',
        'Controller',
        'Initiator',
        'Sentinel',
        'Flex',
      ],
      miembros: <MiembroPlantel>[
        MiembroPlantel(
          id: 13,
          nombre: 'Diego Pérez',
          nickname: 'Kuro',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Duelist',
          posicionSecundaria: 'Flex',
          rango: 'Ascendente II',
          esCapitan: true,
        ),
        MiembroPlantel(
          id: 22,
          nombre: 'Sofía Lagos',
          nickname: 'Nyx',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Controller',
          posicionSecundaria: 'Sentinel',
          rango: 'Ascendente I',
        ),
        MiembroPlantel(
          id: 23,
          nombre: 'Martín Núñez',
          nickname: 'Spike',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Initiator',
          posicionSecundaria: 'Flex',
          rango: 'Inmortal I',
        ),
        MiembroPlantel(
          id: 24,
          nombre: 'Camila Vera',
          nickname: 'Valk',
          rolMembresia: RolMembresia.titular,
          posicionPrincipal: 'Sentinel',
          posicionSecundaria: 'Controller',
          rango: 'Diamante III',
        ),
        MiembroPlantel(
          id: 26,
          nombre: 'Ignacio Vega',
          nickname: 'Pulse',
          rolMembresia: RolMembresia.suplente,
          posicionPrincipal: 'Controller',
          posicionSecundaria: 'Flex',
          rango: 'Diamante II',
        ),
        MiembroPlantel(
          id: 27,
          nombre: 'Gabriel Pinto',
          nickname: 'Kova',
          rolMembresia: RolMembresia.enPrueba,
          posicionPrincipal: 'Flex',
          posicionSecundaria: 'Initiator',
          rango: 'Diamante I',
        ),
      ],
    );
  }
}
