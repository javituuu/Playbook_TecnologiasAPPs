import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/features/nomina/data/mock_nomina_data_source.dart';
import 'package:playbook_coach/features/nomina/data/nomina_repository_impl.dart';
import 'package:playbook_coach/features/nomina/data/remote_nomina_data_source.dart';
import 'package:playbook_coach/features/nomina/domain/estado_citacion.dart';
import 'package:playbook_coach/features/nomina/domain/evento_nomina.dart';
import 'package:playbook_coach/features/nomina/presentation/nomina_controller.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';

void main() {
  NominaController createController() {
    final mock = MockNominaDataSource();
    final remote = RemoteNominaDataSource(
      apiClient: ApiClient(tokenStorage: TokenStorage()),
    );
    final repo = NominaRepositoryImpl(
      mockDataSource: mock,
      remoteDataSource: remote,
      isMockMode: () => true,
    );
    return NominaController(repository: repo);
  }

  group('NominaController', () {
    test('loadEventos carga partidos y prácticas y selecciona el primero',
        () async {
      final controller = createController();
      addTearDown(controller.dispose);

      await controller.loadEventos(equipoId: 1);

      expect(controller.isLoading, isFalse);
      expect(controller.errorMessage, isNull);
      expect(controller.eventos, hasLength(3));
      expect(controller.selectedEvento?.titulo, 'UDD Titans vs UNAB');
      expect(controller.selectedEvento?.tieneConflictosEnTitulares, isTrue);
    });

    test('setFiltroTipo filtra por partido y práctica', () async {
      final controller = createController();
      addTearDown(controller.dispose);

      await controller.loadEventos(equipoId: 1);

      controller.setFiltroTipo(TipoEventoNomina.practica);
      expect(controller.filteredEventos, hasLength(1));
      expect(controller.selectedEvento?.tipo, TipoEventoNomina.practica);

      controller.setFiltroTipo(TipoEventoNomina.partido);
      expect(controller.filteredEventos, hasLength(2));
      expect(controller.selectedEvento?.tipo, TipoEventoNomina.partido);

      controller.setFiltroTipo(null);
      expect(controller.filteredEventos, hasLength(3));
    });

    test('intercambiarTitularSuplente resuelve conflicto en titulares',
        () async {
      final controller = createController();
      addTearDown(controller.dispose);

      await controller.loadEventos(equipoId: 1);
      final evento = controller.selectedEvento!;
      expect(evento.tieneConflictosEnTitulares, isTrue);

      final updated = await controller.intercambiarTitularSuplente(
        eventoId: evento.id,
        titularId: 13,
        suplenteId: 16,
      );

      expect(updated, isNotNull);
      expect(updated!.tieneConflictosEnTitulares, isFalse);
      expect(
        updated.titulares.any((j) => j.jugadorId == 16),
        isTrue,
      );
      expect(
        updated.suplentes.any((j) => j.jugadorId == 13),
        isTrue,
      );
    });

    test('actualizarEstadoCitacion actualiza estado del jugador', () async {
      final controller = createController();
      addTearDown(controller.dispose);

      await controller.loadEventos(equipoId: 1);
      final updated = await controller.actualizarEstadoCitacion(
        eventoId: 101,
        jugadorId: 15,
        estado: EstadoCitacion.confirmado,
      );

      expect(updated, isNotNull);
      final benjamin = updated!.citaciones.firstWhere((c) => c.jugadorId == 15);
      expect(benjamin.estadoRespuesta, EstadoCitacion.confirmado);
    });
  });
}
