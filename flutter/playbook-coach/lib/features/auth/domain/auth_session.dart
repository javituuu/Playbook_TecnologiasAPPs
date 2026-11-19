import 'auth_user.dart';

/// Resultado de autenticación que agrupa el token Sanctum y el usuario.
class AuthSession {
  const AuthSession({
    required this.token,
    required this.user,
  });

  final String token;
  final AuthUser user;
}
