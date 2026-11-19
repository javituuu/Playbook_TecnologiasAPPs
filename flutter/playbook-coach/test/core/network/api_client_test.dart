import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/network/api_exception.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  late TokenStorage tokenStorage;

  setUp(() {
    SharedPreferences.setMockInitialValues(<String, Object>{});
    tokenStorage = TokenStorage();
  });

  group('ApiClient', () {
    test('envía headers JSON y Bearer token en peticiones autenticadas', () async {
      await tokenStorage.saveToken('token-coach-999');

      late Map<String, String> capturedHeaders;
      late Uri capturedUri;

      final mockClient = MockClient((request) async {
        capturedHeaders = request.headers;
        capturedUri = request.url;
        return http.Response(
          jsonEncode(<String, dynamic>{'ok': true}),
          200,
        );
      });

      final apiClient = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: mockClient,
        baseUrl: 'https://playbook.test/api',
      );

      final result = await apiClient.get(
        '/coach/equipos',
        queryParameters: <String, dynamic>{'activo': 1},
      );

      expect(result, <String, dynamic>{'ok': true});
      expect(
        capturedUri.toString(),
        'https://playbook.test/api/coach/equipos?activo=1',
      );
      expect(capturedHeaders['Accept'], 'application/json');
      expect(capturedHeaders['Content-Type'], 'application/json');
      expect(capturedHeaders['Authorization'], 'Bearer token-coach-999');
    });

    test('omite header Authorization cuando requiresAuth es false', () async {
      await tokenStorage.saveToken('token-existente');

      late Map<String, String> capturedHeaders;

      final mockClient = MockClient((request) async {
        capturedHeaders = request.headers;
        return http.Response(
          jsonEncode(<String, dynamic>{'token': 'nuevo-token'}),
          200,
        );
      });

      final apiClient = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: mockClient,
        baseUrl: 'https://playbook.test/api',
      );

      await apiClient.post(
        '/auth/login',
        body: <String, dynamic>{'email': 'coach@udd.cl', 'password': 'secret'},
        requiresAuth: false,
      );

      expect(capturedHeaders.containsKey('Authorization'), isFalse);
      expect(capturedHeaders['Accept'], 'application/json');
      expect(capturedHeaders['Content-Type'], 'application/json');
    });

    test('retorna null en respuesta 204 sin contenido', () async {
      final mockClient = MockClient((_) async => http.Response('', 204));
      final apiClient = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: mockClient,
        baseUrl: 'https://playbook.test/api',
      );

      final result = await apiClient.delete('/practicas/10');
      expect(result, isNull);
    });

    test('lanza ApiException 401 y ejecuta onUnauthorized', () async {
      await tokenStorage.saveToken('token-expirado');
      var unauthorizedCalled = false;

      final mockClient = MockClient((_) async {
        return http.Response(
          jsonEncode(<String, dynamic>{'message': 'Unauthenticated.'}),
          401,
        );
      });

      final apiClient = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: mockClient,
        baseUrl: 'https://playbook.test/api',
        onUnauthorized: () {
          unauthorizedCalled = true;
        },
      );

      await expectLater(
        () => apiClient.get('/coach/perfil'),
        throwsA(
          isA<ApiException>()
              .having((e) => e.statusCode, 'statusCode', 401)
              .having((e) => e.isUnauthorized, 'isUnauthorized', isTrue),
        ),
      );
      expect(unauthorizedCalled, isTrue);
    });

    test('maneja códigos 403, 422 y 429 lanzando ApiException estructurada', () async {
      final api403 = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: MockClient((_) async => http.Response('{}', 403)),
        baseUrl: 'https://playbook.test/api',
      );
      await expectLater(
        () => api403.post('/nominas'),
        throwsA(isA<ApiException>().having((e) => e.isForbidden, '403', isTrue)),
      );

      final api422 = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: MockClient(
          (_) async => http.Response(
            jsonEncode(<String, dynamic>{
              'message': 'Error de validación',
              'errors': <String, dynamic>{
                'hora_inicio': <String>['El bloque no cumple quórum.'],
              },
            }),
            422,
            headers: const <String, String>{
              'content-type': 'application/json; charset=utf-8',
            },
          ),
        ),
        baseUrl: 'https://playbook.test/api',
      );
      await expectLater(
        () => api422.post('/practicas'),
        throwsA(
          isA<ApiException>()
              .having((e) => e.isValidationError, '422', isTrue)
              .having(
                (e) => e.fieldError('hora_inicio'),
                'fieldError',
                'El bloque no cumple quórum.',
              ),
        ),
      );

      final api429 = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: MockClient(
          (_) async => http.Response(
            jsonEncode(<String, dynamic>{'message': 'Too Many Attempts.'}),
            429,
            headers: const <String, String>{'retry-after': '30'},
          ),
        ),
        baseUrl: 'https://playbook.test/api',
      );
      await expectLater(
        () => api429.get('/heatmap'),
        throwsA(
          isA<ApiException>()
              .having((e) => e.isRateLimited, '429', isTrue)
              .having((e) => e.retryAfterSeconds, 'retryAfterSeconds', 30),
        ),
      );
    });

    test('convierte TimeoutException y ClientException en ApiException de red', () async {
      final timeoutClient = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: MockClient((_) async {
          await Future<void>.delayed(const Duration(milliseconds: 50));
          return http.Response('{}', 200);
        }),
        baseUrl: 'https://playbook.test/api',
        timeout: const Duration(milliseconds: 5),
      );

      await expectLater(
        () => timeoutClient.get('/ping'),
        throwsA(
          isA<ApiException>().having((e) => e.isNetworkError, 'network', isTrue),
        ),
      );

      final networkFailClient = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: MockClient((_) async {
          throw http.ClientException('Connection refused');
        }),
        baseUrl: 'https://playbook.test/api',
      );

      await expectLater(
        () => networkFailClient.get('/ping'),
        throwsA(
          isA<ApiException>().having((e) => e.isNetworkError, 'network', isTrue),
        ),
      );
    });
  });
}
