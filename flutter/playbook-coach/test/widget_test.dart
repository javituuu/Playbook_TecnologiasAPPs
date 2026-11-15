import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/app/app.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/data/auth_repository_impl.dart';
import 'package:playbook_coach/features/auth/data/mock_auth_data_source.dart';
import 'package:playbook_coach/features/auth/data/remote_auth_data_source.dart';
import 'package:playbook_coach/features/auth/presentation/auth_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  testWidgets('Flujo completo Login Mock -> CoachShell -> Cerrar sesión', (
    WidgetTester tester,
  ) async {
    SharedPreferences.setMockInitialValues(<String, Object>{});
    final tokenStorage = TokenStorage();
    final repository = AuthRepositoryImpl(
      tokenStorage: tokenStorage,
      mockDataSource: MockAuthDataSource(simulatedDelay: Duration.zero),
      remoteDataSource: RemoteAuthDataSource(
        apiClient: ApiClient(tokenStorage: tokenStorage),
      ),
      initialMockMode: true,
    );
    final controller = AuthController(repository: repository);
    addTearDown(controller.dispose);

    await tester.pumpWidget(PlaybookCoachApp(authController: controller));
    await tester.pumpAndSettle();

    expect(find.text('Iniciar sesión'), findsOneWidget);

    await tester.tap(find.text('Acceso rápido Coach (Mock)'));
    await tester.pumpAndSettle();

    expect(find.text('Sesión activa: Coach Martínez'), findsOneWidget);
    expect(find.text('UDD Titans — LoL'), findsOneWidget);

    await tester.tap(find.byTooltip('Cerrar sesión'));
    await tester.pumpAndSettle();

    expect(find.text('Iniciar sesión'), findsOneWidget);
  });
}
