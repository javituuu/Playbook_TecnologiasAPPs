/// Categorías estándar de error para respuestas de la API y fallos de red.
enum ApiErrorType {
  unauthorized,
  forbidden,
  validation,
  rateLimited,
  notFound,
  clientError,
  serverError,
  network,
  unknown,
}

/// Excepción estándar de red y API para Playbook Coach.
class ApiException implements Exception {
  const ApiException({
    required this.type,
    required this.message,
    this.statusCode,
    this.errors = const <String, List<String>>{},
    this.retryAfterSeconds,
    this.rawBody,
  });

  /// Construye un [ApiException] a partir de una respuesta HTTP no exitosa.
  factory ApiException.fromResponse({
    required int statusCode,
    Object? body,
    Map<String, String>? headers,
  }) {
    final type = _typeFromStatusCode(statusCode);
    final errors = _extractValidationErrors(body);
    final retryAfterSeconds = _parseRetryAfter(headers);
    final backendMessage = _extractMessage(body);
    final message = _resolveMessage(
      statusCode: statusCode,
      type: type,
      backendMessage: backendMessage,
      errors: errors,
      retryAfterSeconds: retryAfterSeconds,
    );

    return ApiException(
      type: type,
      message: message,
      statusCode: statusCode,
      errors: errors,
      retryAfterSeconds: retryAfterSeconds,
      rawBody: body,
    );
  }

  /// Error de conectividad o fallo del cliente HTTP.
  factory ApiException.network([String? message]) {
    return ApiException(
      type: ApiErrorType.network,
      message: message?.trim().isNotEmpty == true
          ? message!.trim()
          : 'No se pudo conectar con el servidor. Verifica tu conexión.',
    );
  }

  /// Error por tiempo de espera agotado.
  factory ApiException.timeout() {
    return const ApiException(
      type: ApiErrorType.network,
      message: 'La solicitud tardó demasiado en responder. Intenta nuevamente.',
    );
  }

  /// Error cuando el servidor retorna un cuerpo inesperado o JSON inválido.
  factory ApiException.invalidResponse([String? message]) {
    return ApiException(
      type: ApiErrorType.unknown,
      message: message?.trim().isNotEmpty == true
          ? message!.trim()
          : 'Respuesta inválida del servidor.',
    );
  }

  final ApiErrorType type;
  final String message;
  final int? statusCode;
  final Map<String, List<String>> errors;
  final int? retryAfterSeconds;
  final Object? rawBody;

  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isValidationError => statusCode == 422;
  bool get isRateLimited => statusCode == 429;
  bool get isNetworkError => type == ApiErrorType.network;

  /// Retorna el primer mensaje de validación asociado a [field], si existe.
  String? fieldError(String field) {
    final messages = errors[field];
    if (messages == null || messages.isEmpty) {
      return null;
    }
    return messages.first;
  }

  /// Lista plana con todos los mensajes de error de validación.
  List<String> get allValidationMessages =>
      errors.values.expand((messages) => messages).toList(growable: false);

  @override
  String toString() {
    final codeLabel = statusCode != null ? ' ($statusCode)' : '';
    return 'ApiException[$type$codeLabel]: $message';
  }

  static ApiErrorType _typeFromStatusCode(int statusCode) {
    return switch (statusCode) {
      401 => ApiErrorType.unauthorized,
      403 => ApiErrorType.forbidden,
      404 => ApiErrorType.notFound,
      422 => ApiErrorType.validation,
      429 => ApiErrorType.rateLimited,
      >= 400 && < 500 => ApiErrorType.clientError,
      >= 500 && < 600 => ApiErrorType.serverError,
      _ => ApiErrorType.unknown,
    };
  }

  static String? _extractMessage(Object? body) {
    if (body is Map) {
      final rawMessage = body['message'];
      if (rawMessage is String && rawMessage.trim().isNotEmpty) {
        return rawMessage.trim();
      }
      final rawError = body['error'];
      if (rawError is String && rawError.trim().isNotEmpty) {
        return rawError.trim();
      }
    }
    return null;
  }

  static Map<String, List<String>> _extractValidationErrors(Object? body) {
    if (body is! Map || body['errors'] is! Map) {
      return const <String, List<String>>{};
    }

    final rawErrors = body['errors'] as Map;
    final parsed = <String, List<String>>{};

    for (final entry in rawErrors.entries) {
      final key = entry.key.toString();
      final value = entry.value;
      if (value is List) {
        final messages = value
            .map((item) => item?.toString().trim() ?? '')
            .where((item) => item.isNotEmpty)
            .toList(growable: false);
        if (messages.isNotEmpty) {
          parsed[key] = messages;
        }
      } else if (value is String && value.trim().isNotEmpty) {
        parsed[key] = <String>[value.trim()];
      }
    }

    return Map<String, List<String>>.unmodifiable(parsed);
  }

  static int? _parseRetryAfter(Map<String, String>? headers) {
    if (headers == null || headers.isEmpty) {
      return null;
    }
    for (final entry in headers.entries) {
      if (entry.key.toLowerCase() == 'retry-after') {
        return int.tryParse(entry.value.trim());
      }
    }
    return null;
  }

  static String _resolveMessage({
    required int statusCode,
    required ApiErrorType type,
    required String? backendMessage,
    required Map<String, List<String>> errors,
    required int? retryAfterSeconds,
  }) {
    switch (type) {
      case ApiErrorType.unauthorized:
        if (backendMessage != null &&
            backendMessage.toLowerCase() != 'unauthenticated.') {
          return backendMessage;
        }
        return 'Sesión expirada o no autenticado. Inicia sesión nuevamente.';

      case ApiErrorType.forbidden:
        if (backendMessage != null &&
            backendMessage.toLowerCase() != 'forbidden' &&
            backendMessage.toLowerCase() != 'this action is unauthorized.') {
          return backendMessage;
        }
        return 'No tienes permisos para realizar esta acción.';

      case ApiErrorType.validation:
        if (errors.isNotEmpty) {
          return errors.values.first.first;
        }
        return backendMessage ?? 'Los datos enviados no son válidos.';

      case ApiErrorType.rateLimited:
        if (backendMessage != null &&
            backendMessage.toLowerCase() != 'too many attempts.') {
          return backendMessage;
        }
        if (retryAfterSeconds != null && retryAfterSeconds > 0) {
          return 'Demasiadas solicitudes. Intenta nuevamente en $retryAfterSeconds segundos.';
        }
        return 'Demasiadas solicitudes. Intenta nuevamente en unos momentos.';

      case ApiErrorType.notFound:
        return backendMessage ?? 'El recurso solicitado no fue encontrado.';

      case ApiErrorType.clientError:
        return backendMessage ?? 'Solicitud inválida (HTTP $statusCode).';

      case ApiErrorType.serverError:
        return backendMessage ??
            'Error interno del servidor. Intenta nuevamente más tarde.';

      case ApiErrorType.network:
        return backendMessage ??
            'No se pudo conectar con el servidor. Verifica tu conexión.';

      case ApiErrorType.unknown:
        return backendMessage ?? 'Ocurrió un error inesperado.';
    }
  }
}
