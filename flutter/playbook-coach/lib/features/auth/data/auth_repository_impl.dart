import '../../../core/config/env.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/storage/token_storage.dart';
import '../domain/auth_repository.dart';
import '../domain/auth_session.dart';
import '../domain/auth_user.dart';
import 'auth_data_source.dart';
import 'mock_auth_data_source.dart';

/// Implementación de [AuthRepository] que coordina origen mock/remoto y [TokenStorage].
class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl({
    required this._tokenStorage,
    required this._mockDataSource,
    required this._remoteDataSource,
    bool initialMockMode = Env.useMockData,
  }) : _isMockMode = initialMockMode;

  final TokenStorage _tokenStorage;
  final MockAuthDataSource _mockDataSource;
  final AuthDataSource _remoteDataSource;

  bool _isMockMode;

  @override
  bool get isMockMode => _isMockMode;

  @override
  void setMockMode(bool enabled) {
    _isMockMode = enabled;
  }

  AuthDataSource get _activeDataSource =>
      _isMockMode ? _mockDataSource : _remoteDataSource;

  @override
  Future<AuthUser> login({
    required String email,
    required String password,
  }) async {
    final session = await _activeDataSource.login(
      email: email,
      password: password,
    );
    return _persistVerifiedSession(session);
  }

  @override
  Future<AuthUser> loginAsDemoCoach() async {
    _isMockMode = true;
    final session = await _mockDataSource.loginAsDemoCoach();
    return _persistVerifiedSession(session);
  }

  @override
  Future<AuthUser?> restoreSession() async {
    final token = await _tokenStorage.readToken();
    if (token == null) {
      return null;
    }

    final isMockToken = token.startsWith(MockAuthDataSource.mockTokenPrefix);
    if (isMockToken) {
      _isMockMode = true;
    }

    final dataSource = isMockToken ? _mockDataSource : _remoteDataSource;

    try {
      final user = await dataSource.fetchProfile();
      await _ensureCoachRole(user);
      return user;
    } on ApiException catch (error) {
      if (error.isUnauthorized || error.isForbidden) {
        await _tokenStorage.clearToken();
        return null;
      }
      rethrow;
    }
  }

  @override
  Future<void> logout() async {
    final token = await _tokenStorage.readToken();
    final isMockToken =
        token != null && token.startsWith(MockAuthDataSource.mockTokenPrefix);
    final dataSource =
        (isMockToken || _isMockMode) ? _mockDataSource : _remoteDataSource;

    try {
      if (token != null) {
        await dataSource.logout();
      }
    } finally {
      await _tokenStorage.clearToken();
    }
  }

  @override
  Future<void> clearSession() => _tokenStorage.clearToken();

  Future<AuthUser> _persistVerifiedSession(AuthSession session) async {
    await _ensureCoachRole(session.user);
    await _tokenStorage.saveToken(session.token);
    return session.user;
  }

  Future<void> _ensureCoachRole(AuthUser user) async {
    if (user.isCoach) {
      return;
    }
    await _tokenStorage.clearToken();
    final detail = user.isJugador
        ? 'Acceso restringido: las cuentas de jugador deben ingresar desde Playbook Jugador.'
        : 'Acceso restringido: esta aplicación es exclusiva para cuentas con rol Coach.';
    throw ApiException(
      type: ApiErrorType.forbidden,
      statusCode: 403,
      message: detail,
    );
  }
}
