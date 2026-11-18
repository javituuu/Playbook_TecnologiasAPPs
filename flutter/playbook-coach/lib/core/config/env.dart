import 'package:flutter/foundation.dart';

/// Configuración de entorno para Playbook Coach.
///
/// Permite sobreescribir valores en tiempo de compilación mediante `--dart-define`:
/// - `API_BASE_URL`
/// - `API_TIMEOUT_SECONDS`
/// - `ENABLE_HTTP_LOGS`
class Env {
  const Env._();

  static const String _definedBaseUrl = String.fromEnvironment('API_BASE_URL');

  /// URL base de la API REST de Laravel.
  static String get apiBaseUrl {
    if (_definedBaseUrl.trim().isNotEmpty) {
      return _stripTrailingSlashes(_definedBaseUrl.trim());
    }
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://127.0.0.1:8000/api';
  }

  /// Timeout estándar para peticiones HTTP.
  static const Duration requestTimeout = Duration(
    seconds: int.fromEnvironment('API_TIMEOUT_SECONDS', defaultValue: 15),
  );

  /// Habilita trazas de red en modo depuración o mediante `--dart-define`.
  static const bool enableHttpLogs = bool.fromEnvironment(
    'ENABLE_HTTP_LOGS',
    defaultValue: kDebugMode,
  );

  /// Habilita el modo mock de arranque mientras se completa el backend del coach.
  static const bool useMockData = bool.fromEnvironment(
    'USE_MOCK_DATA',
    defaultValue: true,
  );

  /// Construye un [Uri] combinando [baseUrl] (o [apiBaseUrl]), [path] y [queryParameters].
  static Uri buildUri(
    String path, {
    String? baseUrl,
    Map<String, dynamic>? queryParameters,
  }) {
    final normalizedBase = _stripTrailingSlashes((baseUrl ?? apiBaseUrl).trim());
    final trimmedPath = path.trim();
    final normalizedPath =
        trimmedPath.startsWith('/') ? trimmedPath : '/$trimmedPath';
    final uri = Uri.parse('$normalizedBase$normalizedPath');

    if (queryParameters == null || queryParameters.isEmpty) {
      return uri;
    }

    final filteredParams = <String, String>{};
    for (final entry in queryParameters.entries) {
      if (entry.value != null) {
        filteredParams[entry.key] = entry.value.toString();
      }
    }

    if (filteredParams.isEmpty) {
      return uri;
    }

    return uri.replace(
      queryParameters: <String, String>{
        ...uri.queryParameters,
        ...filteredParams,
      },
    );
  }

  static String _stripTrailingSlashes(String value) {
    var result = value;
    while (result.endsWith('/')) {
      result = result.substring(0, result.length - 1);
    }
    return result;
  }
}
