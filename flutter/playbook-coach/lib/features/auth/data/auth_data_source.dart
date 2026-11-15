import '../domain/auth_session.dart';
import '../domain/auth_user.dart';

/// Fuente de datos abstracta para operaciones de autenticación.
abstract class AuthDataSource {
  Future<AuthSession> login({
    required String email,
    required String password,
  });

  Future<AuthUser> fetchProfile();

  Future<void> logout();
}
