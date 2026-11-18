import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/core/network/api_exception.dart';

void main() {
  group('ApiException', () {
    test('mapea 401 a unauthorized con mensaje estándar en español', () {
      final exception = ApiException.fromResponse(
        statusCode: 401,
        body: <String, dynamic>{'message': 'Unauthenticated.'},
      );

      expect(exception.type, ApiErrorType.unauthorized);
      expect(exception.isUnauthorized, isTrue);
      expect(
        exception.message,
        'Sesión expirada o no autenticado. Inicia sesión nuevamente.',
      );
    });

    test('mapea 403 a forbidden preservando mensaje personalizado si existe', () {
      final defaultForbidden = ApiException.fromResponse(statusCode: 403);
      expect(defaultForbidden.type, ApiErrorType.forbidden);
      expect(defaultForbidden.isForbidden, isTrue);
      expect(
        defaultForbidden.message,
        'No tienes permisos para realizar esta acción.',
      );

      final customForbidden = ApiException.fromResponse(
        statusCode: 403,
        body: <String, dynamic>{'message': 'Solo el coach asignado puede citar.'},
      );
      expect(customForbidden.message, 'Solo el coach asignado puede citar.');
    });

    test('mapea 422 extrayendo errores por campo de Laravel', () {
      final exception = ApiException.fromResponse(
        statusCode: 422,
        body: <String, dynamic>{
          'message': 'The given data was invalid.',
          'errors': <String, dynamic>{
            'email': <String>['El correo es obligatorio.'],
            'password': <String>['Debe tener al menos 8 caracteres.'],
          },
        },
      );

      expect(exception.type, ApiErrorType.validation);
      expect(exception.isValidationError, isTrue);
      expect(exception.fieldError('email'), 'El correo es obligatorio.');
      expect(
        exception.fieldError('password'),
        'Debe tener al menos 8 caracteres.',
      );
      expect(exception.allValidationMessages, hasLength(2));
      expect(exception.message, 'El correo es obligatorio.');
    });

    test('mapea 429 extrayendo header Retry-After', () {
      final exception = ApiException.fromResponse(
        statusCode: 429,
        body: <String, dynamic>{'message': 'Too Many Attempts.'},
        headers: const <String, String>{'Retry-After': '45'},
      );

      expect(exception.type, ApiErrorType.rateLimited);
      expect(exception.isRateLimited, isTrue);
      expect(exception.retryAfterSeconds, 45);
      expect(exception.message, contains('45 segundos'));
    });
  });
}
