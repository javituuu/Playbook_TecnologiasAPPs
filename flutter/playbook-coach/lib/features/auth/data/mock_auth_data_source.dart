import '../../../core/network/api_exception.dart';
import '../domain/auth_session.dart';
import '../domain/auth_user.dart';
import '../domain/equipo_dirigido.dart';
import 'auth_data_source.dart';

/// Fuente de datos mock para validar flujos de autenticación sin backend activo.
class MockAuthDataSource implements AuthDataSource {
  MockAuthDataSource({
    this.simulatedDelay = const Duration(milliseconds: 250),
  });

  /// Credenciales de demostración válidas para el coach.
  static const String demoCoachEmail = 'coach@udd.cl';
  static const String demoCoachPassword = 'password';

  /// Credenciales de prueba para validar rechazo HTTP 403 de un jugador.
  static const String demoPlayerEmail = 'jugador@udd.cl';

  /// Correo de prueba para simular límite de peticiones HTTP 429.
  static const String rateLimitedEmail = 'ratelimit@udd.cl';

  /// Prefijo utilizado para identificar tokens generados en modo mock.
  static const String mockTokenPrefix = 'mock-coach-token-';

  final Duration simulatedDelay;

  static const AuthUser demoCoachUser = AuthUser(
    id: 1,
    name: 'Coach Martínez',
    email: demoCoachEmail,
    role: 'coach',
    equiposDirigidos: <EquipoDirigido>[
      EquipoDirigido(
        id: 1,
        nombre: 'UDD Titans',
        disciplina: 'LoL',
        club: 'Universidad De Dioses (UDD)',
        quorumMinimo: 4,
      ),
      EquipoDirigido(
        id: 2,
        nombre: 'UDD Nova',
        disciplina: 'Valorant',
        club: 'Universidad De Dioses (UDD)',
        quorumMinimo: 4,
      ),
    ],
  );

  AuthUser? _currentUser;

  @override
  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    await _waitIfNeeded();

    final normalizedEmail = email.trim().toLowerCase();
    final trimmedPassword = password.trim();

    if (normalizedEmail == rateLimitedEmail) {
      throw ApiException.fromResponse(
        statusCode: 429,
        body: const <String, dynamic>{
          'message': 'Too Many Attempts.',
        },
        headers: const <String, String>{'Retry-After': '30'},
      );
    }

    if (normalizedEmail == demoPlayerEmail ||
        normalizedEmail == 'javier@email.com') {
      throw ApiException.fromResponse(
        statusCode: 403,
        body: const <String, dynamic>{
          'message':
              'Acceso restringido: las cuentas de jugador deben ingresar desde Playbook Jugador.',
        },
      );
    }

    final isValidCoachPassword =
        trimmedPassword == demoCoachPassword || trimmedPassword == 'coach1234';

    if (normalizedEmail == demoCoachEmail && isValidCoachPassword) {
      _currentUser = demoCoachUser;
      return const AuthSession(
        token: '${mockTokenPrefix}coach-martinez',
        user: demoCoachUser,
      );
    }

    throw ApiException.fromResponse(
      statusCode: 401,
      body: const <String, dynamic>{
        'message': 'Credenciales inválidas. Verifica tu correo y contraseña.',
      },
    );
  }

  /// Inicia sesión directamente con el usuario Coach de demostración.
  Future<AuthSession> loginAsDemoCoach() async {
    await _waitIfNeeded();
    _currentUser = demoCoachUser;
    return const AuthSession(
      token: '${mockTokenPrefix}coach-martinez',
      user: demoCoachUser,
    );
  }

  @override
  Future<AuthUser> fetchProfile() async {
    await _waitIfNeeded();
    return _currentUser ?? demoCoachUser;
  }

  @override
  Future<void> logout() async {
    await _waitIfNeeded();
    _currentUser = null;
  }

  Future<void> _waitIfNeeded() async {
    if (simulatedDelay > Duration.zero) {
      await Future<void>.delayed(simulatedDelay);
    }
  }
}
