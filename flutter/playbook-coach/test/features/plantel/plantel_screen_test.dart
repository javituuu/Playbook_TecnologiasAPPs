import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:playbook_coach/app/theme/playbook_theme.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/domain/equipo_dirigido.dart';
import 'package:playbook_coach/features/plantel/data/mock_plantel_data_source.dart';
import 'package:playbook_coach/features/plantel/data/plantel_repository_impl.dart';
import 'package:playbook_coach/features/plantel/data/remote_plantel_data_source.dart';
import 'package:playbook_coach/features/plantel/domain/rol_membresia.dart';
import 'package:playbook_coach/features/plantel/presentation/plantel_controller.dart';
import 'package:playbook_coach/features/plantel/presentation/plantel_screen.dart';

void main() {
  const team = EquipoDirigido(
    id: 1,
    nombre: 'UDD Titans',
    disciplina: 'LoL',
    quorumMinimo: 4,
  );

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

  testWidgets(
      'PlantelScreen muestra cobertura, grupos por rol y filtra por categoría',
      (tester) async {
    tester.view.physicalSize = const Size(1280, 900);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.reset);

    final controller = createController();
    addTearDown(controller.dispose);
    await tester.runAsync(() => controller.loadPlantel(equipoId: 1));

    await tester.pumpWidget(
      MaterialApp(
        theme: PlaybookTheme.light(),
        home: Scaffold(
          body: PlantelScreen(
            controller: controller,
            equipo: team,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Plantel · UDD Titans'), findsOneWidget);
    expect(find.text('Cobertura de posiciones'), findsOneWidget);
    expect(find.text('5/5 posiciones cubiertas'), findsOneWidget);
    expect(find.text('Titulares (5)'), findsOneWidget);
    expect(find.text('Suplentes (2)'), findsOneWidget);

    await tester.tap(find.text(RolMembresia.enPrueba.pluralLabel));
    await tester.pumpAndSettle();

    expect(find.text('Titulares (5)'), findsNothing);
    expect(find.text('En prueba (2)'), findsOneWidget);
  });
}
