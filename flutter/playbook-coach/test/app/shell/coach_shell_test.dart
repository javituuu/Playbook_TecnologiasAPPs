import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:playbook_coach/app/shell/coach_shell.dart';
import 'package:playbook_coach/app/theme/playbook_theme.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/data/auth_repository_impl.dart';
import 'package:playbook_coach/features/auth/data/mock_auth_data_source.dart';
import 'package:playbook_coach/features/auth/data/remote_auth_data_source.dart';
import 'package:playbook_coach/features/auth/presentation/auth_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  late AuthController controller;

  setUp(() async {
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
    controller = AuthController(repository: repository);
    await controller.loginMockDemo();
  });

  tearDown(() {
    controller.dispose();
  });

  Future<void> pumpShellAtSize(WidgetTester tester, Size size) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });

    await tester.pumpWidget(
      MaterialApp(
        theme: PlaybookTheme.light(),
        darkTheme: PlaybookTheme.dark(),
        home: CoachShell(controller: controller),
      ),
    );
    await tester.pumpAndSettle();
  }

  testWidgets('usa NavigationBar en móvil y muestra sesión, equipo y logout', (
    WidgetTester tester,
  ) async {
    await pumpShellAtSize(tester, const Size(390, 844));

    expect(find.byType(NavigationBar), findsOneWidget);
    expect(find.byType(NavigationRail), findsNothing);
    expect(find.text('Sesión activa: Coach Martínez'), findsOneWidget);
    expect(find.text('UDD Titans — LoL'), findsOneWidget);

    // Cambia de equipo en el selector
    await tester.tap(find.byKey(const Key('team_selector_dropdown')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('UDD Nova — Valorant').last);
    await tester.pumpAndSettle();

    expect(controller.selectedTeam?.displayName, 'UDD Nova — Valorant');

    // Navega entre destinos en NavigationBar
    await tester.tap(find.text('Prácticas'));
    await tester.pumpAndSettle();
    expect(find.text('Prácticas Programadas'), findsOneWidget);
    expect(find.text('vs UNAB Five'), findsOneWidget);
  });

  testWidgets('usa NavigationRail en tablet/desktop y cierra sesión correctamente', (
    WidgetTester tester,
  ) async {
    await pumpShellAtSize(tester, const Size(1280, 800));

    expect(find.byType(NavigationRail), findsOneWidget);
    expect(find.byType(NavigationBar), findsNothing);
    expect(find.text('Sesión activa: Coach Martínez'), findsOneWidget);
    expect(find.text('Cerrar sesión'), findsOneWidget);
    expect(find.text('Matriz de Disponibilidad Semanal'), findsOneWidget);

    await tester.tap(find.text('Nómina'));
    await tester.pumpAndSettle();
    expect(
      find.text('Nómina y Citaciones · UDD Titans'),
      findsOneWidget,
    );

    await tester.tap(find.byKey(const Key('logout_button')));
    await tester.pumpAndSettle();
    expect(controller.isAuthenticated, isFalse);
  });

  testWidgets('no produce overflow en tablet compacto de 640px', (
    WidgetTester tester,
  ) async {
    await pumpShellAtSize(tester, const Size(640, 900));

    expect(find.byType(NavigationRail), findsOneWidget);
    expect(find.byType(NavigationBar), findsNothing);
    expect(find.text('Sesión activa: Coach Martínez'), findsOneWidget);
  });

  testWidgets('adapta la navegación al girar un móvil con texto ampliado', (
    WidgetTester tester,
  ) async {
    final dispatcher = tester.platformDispatcher;
    dispatcher.textScaleFactorTestValue = 1.5;
    dispatcher.platformBrightnessTestValue = Brightness.dark;
    addTearDown(dispatcher.clearTextScaleFactorTestValue);
    addTearDown(dispatcher.clearPlatformBrightnessTestValue);

    await pumpShellAtSize(tester, const Size(320, 640));
    expect(find.byType(NavigationBar), findsOneWidget);

    tester.view.physicalSize = const Size(568, 320);
    await tester.pumpAndSettle();
    expect(find.byType(NavigationRail), findsOneWidget);
    expect(find.byKey(const Key('team_selector_dropdown')), findsOneWidget);
  });
}
