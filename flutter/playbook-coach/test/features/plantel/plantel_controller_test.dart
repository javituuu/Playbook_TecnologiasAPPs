import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/plantel/data/mock_plantel_data_source.dart';
import 'package:playbook_coach/features/plantel/data/plantel_repository_impl.dart';
import 'package:playbook_coach/features/plantel/data/remote_plantel_data_source.dart';
import 'package:playbook_coach/features/plantel/domain/rol_membresia.dart';
import 'package:playbook_coach/features/plantel/presentation/plantel_controller.dart';

void main() {
  PlantelController createController() {
    final mock = MockPlantelDataSource();
    final remote = RemotePlantelDataSource(
      apiClient: ApiClient(tokenStorage: TokenStorage()),
    );
    return PlantelController(
      repository: PlantelRepositoryImpl(
        mockDataSource: mock,
        remoteDataSource: remote,
        isMockMode: () => true,
      ),
    );
  }

  group('PlantelController', () {
    test('loadPlantel agrupa por rol y calcula cobertura de UDD Titans',
        () async {
      final controller = createController();
      addTearDown(controller.dispose);

      await controller.loadPlantel(equipoId: 1);

      expect(controller.isLoading, isFalse);
      expect(controller.errorMessage, isNull);
      expect(controller.plantel, isNotNull);
      expect(controller.miembrosPorRol(RolMembresia.titular), hasLength(5));
      expect(controller.miembrosPorRol(RolMembresia.suplente), hasLength(2));
      expect(controller.miembrosPorRol(RolMembresia.enPrueba), hasLength(2));
      expect(controller.plantel!.posicionesCubiertasCount, 5);
      expect(controller.plantel!.posicionesSinTitular, isEmpty);
    });

    test('detecta posición sin titular en UDD Nova y filtra por rol', () async {
      final controller = createController();
      addTearDown(controller.dispose);

      await controller.loadPlantel(equipoId: 2);

      expect(controller.plantel!.posicionesSinTitular, <String>['Flex']);

      controller.setFiltroRol(RolMembresia.enPrueba);
      expect(controller.visibleRoles, <RolMembresia>[RolMembresia.enPrueba]);

      controller.setFiltroRol(null);
      expect(controller.visibleRoles, RolMembresia.values);
    });
  });
}
