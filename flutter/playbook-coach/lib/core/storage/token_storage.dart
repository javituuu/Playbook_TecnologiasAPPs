import 'package:shared_preferences/shared_preferences.dart';

/// Persistencia local del token Bearer de Laravel Sanctum.
class TokenStorage {
  TokenStorage({
    Future<SharedPreferences> Function()? preferencesProvider,
    this.tokenKey = defaultTokenKey,
  }) : _preferencesProvider =
            preferencesProvider ?? SharedPreferences.getInstance;

  /// Clave por defecto usada en [SharedPreferences].
  static const String defaultTokenKey = 'playbook_auth_token';

  final Future<SharedPreferences> Function() _preferencesProvider;

  /// Clave de almacenamiento del token.
  final String tokenKey;

  /// Guarda el [token] de autenticación tras eliminar espacios laterales.
  Future<void> saveToken(String token) async {
    final normalized = token.trim();
    if (normalized.isEmpty) {
      throw ArgumentError.value(
        token,
        'token',
        'El token de autenticación no puede estar vacío.',
      );
    }
    final prefs = await _preferencesProvider();
    await prefs.setString(tokenKey, normalized);
  }

  /// Lee el token almacenado o retorna `null` si no existe o está vacío.
  Future<String?> readToken() async {
    final prefs = await _preferencesProvider();
    final value = prefs.getString(tokenKey)?.trim();
    if (value == null || value.isEmpty) {
      return null;
    }
    return value;
  }

  /// Elimina el token almacenado.
  Future<void> clearToken() async {
    final prefs = await _preferencesProvider();
    await prefs.remove(tokenKey);
  }

  /// Indica si existe un token válido almacenado.
  Future<bool> hasToken() async {
    final token = await readToken();
    return token != null;
  }
}
