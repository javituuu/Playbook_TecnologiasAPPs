import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:playbook_coach/app/theme/playbook_theme.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/domain/equipo_dirigido.dart';
import 'package:playbook_coach/features/practicas/data/mock_practicas_data_source.dart';
import 'package:playbook_coach/features/practicas/data/practicas_repository_impl.dart';
import 'package:playbook_coach/features/practicas/data/remote_practicas_data_source.dart';
import 'package:playbook_coach/features/practicas/presentation/practicas_controller.dart';
import 'package:playbook_coach/features/practicas/presentation/practicas_screen.dart';

void main() {
  const equipoLol = EquipoDirigido(
    id: 1,
    nombre: 'UDD Titans',
    disciplina: 'LoL',
  );

  testWidgets('lista prácticas y permite cancelar una sesión programada', (
    WidgetTester tester,
  ) async {
    final tokenStorage = TokenStorage();
    final controller = PracticasController(
      repository: PracticasRepositoryImpl(
        mockDataSource: MockPracticasDataSource(simulatedDelay: Duration.zero),
        remoteDataSource: RemotePracticasDataSource(
          apiClient: ApiClient(tokenStorage: tokenStorage),
        ),
        isMockMode: () => true,
      ),
    );
    addTearDown(controller.dispose);

    await controller.loadPracticas(equipoId: equipoLol.id);

    await tester.pumpWidget(
      MaterialApp(
        theme: PlaybookTheme.light(),
        home: Scaffold(
          body: PracticasScreen(
            controller: controller,
            equipo: equipoLol,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Prácticas Programadas'), findsOneWidget);
    expect(find.text('vs USM Gaming'), findsOneWidget);

    await tester.tap(find.byKey(const Key('cancelar_practica_1')));
    await tester.pumpAndSettle();

    expect(find.text('Confirmar cancelación'), findsOneWidget);
    await tester.enterText(
      find.byKey(const Key('cancel_reason_field')),
      'Rival sin roster',
    );
    await tester.tap(find.byKey(const Key('confirm_cancel_practice_button')));
    await tester.pumpAndSettle();

    expect(find.text('CANCELADA'), findsOneWidget);
    expect(find.text('Motivo: Rival sin roster'), findsOneWidget);
  });
}
