import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/network/api_exception.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/data/auth_repository_impl.dart';
import 'package:playbook_coach/features/auth/data/mock_auth_data_source.dart';
import 'package:playbook_coach/features/auth/data/remote_auth_data_source.dart';
import 'package:playbook_coach/features/auth/presentation/auth_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  late TokenStorage tokenStorage;
  late AuthController controller;

  setUp(() {
    SharedPreferences.setMockInitialValues(<String, Object>{});
    tokenStorage = TokenStorage();
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

  group('AuthController', () {
    test('restoreSession pasa a unauthenticated cuando no hay token', () async {
      await controller.restoreSession();

      expect(controller.status, AuthStatus.unauthenticated);
      expect(controller.isAuthenticated, isFalse);
      expect(controller.selectedTeam, isNull);
    });

    test('loginMockDemo autentica coach, selecciona equipo y permite cambiarlo', () async {
      final ok = await controller.loginMockDemo();

      expect(ok, isTrue);
      expect(controller.status, AuthStatus.authenticated);
      expect(controller.isAuthenticated, isTrue);
      expect(controller.user?.name, 'Coach Martínez');
      expect(controller.selectedTeam?.displayName, 'UDD Titans — LoL');
      expect(controller.equipos, hasLength(2));

      controller.selectTeam(controller.equipos[1]);
      expect(controller.selectedTeam?.displayName, 'UDD Nova — Valorant');

      await controller.logout();
      expect(controller.status, AuthStatus.unauthenticated);
      expect(controller.user, isNull);
      expect(controller.selectedTeam, isNull);
    });

    test('login captura errores 401, 403 y 429', () async {
      final ok401 = await controller.login(
        email: 'coach@udd.cl',
        password: 'wrong-password',
      );
      expect(ok401, isFalse);
      expect(controller.errorType, ApiErrorType.unauthorized);
      expect(controller.errorStatusCode, 401);

      final ok403 = await controller.login(
        email: MockAuthDataSource.demoPlayerEmail,
        password: MockAuthDataSource.demoCoachPassword,
      );
      expect(ok403, isFalse);
      expect(controller.errorType, ApiErrorType.forbidden);
      expect(controller.errorStatusCode, 403);

      final ok429 = await controller.login(
        email: MockAuthDataSource.rateLimitedEmail,
        password: MockAuthDataSource.demoCoachPassword,
      );
      expect(ok429, isFalse);
      expect(controller.errorType, ApiErrorType.rateLimited);
      expect(controller.errorStatusCode, 429);
      expect(controller.retryAfterSeconds, 30);
    });

    test('handleUnauthorized limpia sesión y expone mensaje de expiración', () async {
      await controller.loginMockDemo();
      expect(controller.isAuthenticated, isTrue);

      controller.handleUnauthorized();

      expect(controller.status, AuthStatus.unauthenticated);
      expect(controller.isAuthenticated, isFalse);
      expect(controller.errorType, ApiErrorType.unauthorized);
      expect(controller.errorMessage, contains('expiró'));
    });
  });
}
