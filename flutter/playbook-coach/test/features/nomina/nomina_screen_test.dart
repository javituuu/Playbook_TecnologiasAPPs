import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:playbook_coach/app/theme/playbook_theme.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/domain/equipo_dirigido.dart';
import 'package:playbook_coach/features/nomina/data/mock_nomina_data_source.dart';
import 'package:playbook_coach/features/nomina/data/nomina_repository_impl.dart';
import 'package:playbook_coach/features/nomina/data/remote_nomina_data_source.dart';
import 'package:playbook_coach/features/nomina/presentation/nomina_controller.dart';
import 'package:playbook_coach/features/nomina/presentation/nomina_screen.dart';

void main() {
  const team = EquipoDirigido(
    id: 1,
    nombre: 'UDD Titans',
    disciplina: 'LoL',
    quorumMinimo: 4,
  );

  NominaController createController() {
    final mock = MockNominaDataSource();
    final remote = RemoteNominaDataSource(
      apiClient: ApiClient(tokenStorage: TokenStorage()),
    );
    return NominaController(
      repository: NominaRepositoryImpl(
        mockDataSource: mock,
        remoteDataSource: remote,
        isMockMode: () => true,
      ),
    );
  }

  testWidgets(
      'NominaScreen muestra citaciones, alerta conflicto y permite intercambiar titular por suplente',
      (tester) async {
    tester.view.physicalSize = const Size(1280, 820);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.reset);

    final controller = createController();
    addTearDown(controller.dispose);
    await tester.runAsync(() => controller.loadEventos(equipoId: 1));

    await tester.pumpWidget(
      MaterialApp(
        theme: PlaybookTheme.light(),
        home: Scaffold(
          body: NominaScreen(
            controller: controller,
            equipo: team,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Nómina y Citaciones · UDD Titans'), findsOneWidget);
    expect(find.text('UDD Titans vs UNAB'), findsWidgets);
    expect(find.text('Titulares (5)'), findsOneWidget);
    expect(find.text('Resolver'), findsOneWidget);

    await tester.tap(find.text('Resolver'));
    await tester.pumpAndSettle();

    expect(find.text('Intercambiar titular ↔ suplente'), findsOneWidget);
    await tester.tap(find.text('Confirmar intercambio'));
    await tester.pump(const Duration(milliseconds: 250));
    await tester.pumpAndSettle();

    expect(find.text('Nómina actualizada: intercambio aplicado.'), findsOneWidget);
    expect(find.text('Resolver'), findsNothing);

    await tester.scrollUntilVisible(
      find.text('Suplentes (2)'),
      200,
      scrollable: find.byType(Scrollable).last,
    );
    expect(find.text('Suplentes (2)'), findsOneWidget);
  });
}
