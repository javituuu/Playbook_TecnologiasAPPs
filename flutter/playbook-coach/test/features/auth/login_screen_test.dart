import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:playbook_coach/app/theme/playbook_theme.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/data/auth_repository_impl.dart';
import 'package:playbook_coach/features/auth/data/mock_auth_data_source.dart';
import 'package:playbook_coach/features/auth/data/remote_auth_data_source.dart';
import 'package:playbook_coach/features/auth/presentation/auth_controller.dart';
import 'package:playbook_coach/features/auth/presentation/login_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  late AuthController controller;

  setUp(() {
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
  });

  tearDown(() {
    controller.dispose();
  });

  Widget buildSubject() {
    return MaterialApp(
      theme: PlaybookTheme.light(),
      home: LoginScreen(controller: controller),
    );
  }

  testWidgets('valida campos obligatorios, formato de correo y largo de clave', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(buildSubject());

    await tester.tap(find.byKey(const Key('login_submit_button')));
    await tester.pump();

    expect(find.text('Ingresa tu correo electrónico.'), findsOneWidget);
    expect(find.text('Ingresa tu contraseña.'), findsOneWidget);

    await tester.enterText(
      find.byKey(const Key('login_email_field')),
      'correo-invalido',
    );
    await tester.enterText(
      find.byKey(const Key('login_password_field')),
      '123',
    );
    await tester.tap(find.byKey(const Key('login_submit_button')));
    await tester.pump();

    expect(find.text('Ingresa un correo electrónico válido.'), findsOneWidget);
    expect(
      find.text('La contraseña debe tener al menos 6 caracteres.'),
      findsOneWidget,
    );
  });

  testWidgets('muestra errores 401, 403 (jugador) y 429 en el banner de error', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(buildSubject());

    // 401: credenciales inválidas
    await tester.enterText(
      find.byKey(const Key('login_email_field')),
      MockAuthDataSource.demoCoachEmail,
    );
    await tester.enterText(
      find.byKey(const Key('login_password_field')),
      'clave-erronea',
    );
    await tester.tap(find.byKey(const Key('login_submit_button')));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('login_error_banner')), findsOneWidget);
    expect(
      find.descendant(
        of: find.byKey(const Key('login_error_banner')),
        matching: find.text('Credenciales o sesión inválida (401)'),
      ),
      findsOneWidget,
    );

    // 403: jugador intentando acceder
    await tester.enterText(
      find.byKey(const Key('login_email_field')),
      MockAuthDataSource.demoPlayerEmail,
    );
    await tester.enterText(
      find.byKey(const Key('login_password_field')),
      MockAuthDataSource.demoCoachPassword,
    );
    await tester.tap(find.byKey(const Key('login_submit_button')));
    await tester.pumpAndSettle();

    expect(
      find.descendant(
        of: find.byKey(const Key('login_error_banner')),
        matching: find.text('Acceso restringido (403)'),
      ),
      findsOneWidget,
    );
    expect(find.textContaining('Playbook Jugador'), findsWidgets);

    // 429: demasiados intentos
    await tester.enterText(
      find.byKey(const Key('login_email_field')),
      MockAuthDataSource.rateLimitedEmail,
    );
    await tester.tap(find.byKey(const Key('login_submit_button')));
    await tester.pumpAndSettle();

    expect(
      find.descendant(
        of: find.byKey(const Key('login_error_banner')),
        matching: find.text('Demasiados intentos (429)'),
      ),
      findsOneWidget,
    );
    expect(find.text('Espera sugerida: 30 s'), findsOneWidget);
  });

  testWidgets('acceso rápido mock autentica al coach sin llenar formulario', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(buildSubject());

    await tester.tap(find.byKey(const Key('login_mock_demo_button')));
    await tester.pumpAndSettle();

    expect(controller.isAuthenticated, isTrue);
    expect(controller.user?.name, 'Coach Martínez');
  });
}
