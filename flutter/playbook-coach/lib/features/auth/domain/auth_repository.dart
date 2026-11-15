import 'auth_user.dart';

/// Contrato de autenticación y gestión de sesión del Coach.
abstract class AuthRepository {
  /// Indica si el repositorio está operando actualmente contra datos mock.
  bool get isMockMode;

  /// Cambia entre origen de datos mock y remoto.
  void setMockMode(bool enabled);

  /// Inicia sesión con credenciales y valida que el usuario sea Coach.
  Future<AuthUser> login({
    required String email,
    required String password,
  });

  /// Acceso rápido con el coach de prueba del entorno mock.
  Future<AuthUser> loginAsDemoCoach();

  /// Restaura una sesión previamente guardada, o retorna `null` si no existe.
  Future<AuthUser?> restoreSession();

  /// Cierra la sesión actual y limpia el almacenamiento local del token.
  Future<void> logout();

  /// Limpia únicamente la sesión local sin invocar el endpoint remoto.
  Future<void> clearSession();
}
