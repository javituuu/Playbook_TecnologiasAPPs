import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../domain/auth_session.dart';
import '../domain/auth_user.dart';
import 'auth_data_source.dart';

/// Fuente de datos remota contra la API REST de Laravel + Sanctum.
class RemoteAuthDataSource implements AuthDataSource {
  RemoteAuthDataSource({
    required this._apiClient,
    this.loginPath = '/auth/login',
    this.profilePath = '/auth/me',
    this.logoutPath = '/auth/logout',
  });

  final ApiClient _apiClient;
  final String loginPath;
  final String profilePath;
  final String logoutPath;

  @override
  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.post(
      loginPath,
      requiresAuth: false,
      body: <String, dynamic>{
        'email': email.trim(),
        'password': password,
        'device_name': 'playbook_coach_flutter',
        'rol_esperado': 'coach',
      },
    );

    final payload = _unwrapPayload(response);
    final token = _extractToken(payload);
    final userJson = _extractUserMap(payload);

    if (token == null || userJson == null) {
      throw ApiException.invalidResponse(
        'La respuesta de autenticación no incluyó token y usuario.',
      );
    }

    return AuthSession(
      token: token,
      user: AuthUser.fromJson(userJson),
    );
  }

  @override
  Future<AuthUser> fetchProfile() async {
    final response = await _apiClient.get(profilePath);
    final payload = _unwrapPayload(response);
    final userJson = _extractUserMap(payload) ?? payload;

    if (userJson == null) {
      throw ApiException.invalidResponse(
        'La respuesta de perfil del coach es inválida.',
      );
    }

    return AuthUser.fromJson(userJson);
  }

  @override
  Future<void> logout() async {
    try {
      await _apiClient.post(logoutPath);
    } on ApiException catch (error) {
      // Si el token ya estaba revocado/expirado en servidor, completamos el cierre local.
      if (!error.isUnauthorized) {
        rethrow;
      }
    }
  }

  Map<String, dynamic>? _unwrapPayload(dynamic response) {
    if (response is! Map) {
      return null;
    }
    final root = Map<String, dynamic>.from(response);
    final nested = root['data'];
    if (nested is Map) {
      return Map<String, dynamic>.from(nested);
    }
    return root;
  }

  String? _extractToken(Map<String, dynamic>? payload) {
    if (payload == null) {
      return null;
    }
    final rawToken =
        payload['token'] ?? payload['access_token'] ?? payload['plainTextToken'];
    if (rawToken is String && rawToken.trim().isNotEmpty) {
      return rawToken.trim();
    }
    return null;
  }

  Map<String, dynamic>? _extractUserMap(Map<String, dynamic>? payload) {
    if (payload == null) {
      return null;
    }
    final rawUser = payload['user'] ?? payload['coach'];
    if (rawUser is Map) {
      return Map<String, dynamic>.from(rawUser);
    }
    return null;
  }
}
