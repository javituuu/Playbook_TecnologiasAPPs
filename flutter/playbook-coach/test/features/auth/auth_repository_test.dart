import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/network/api_exception.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/data/auth_data_source.dart';
import 'package:playbook_coach/features/auth/data/auth_repository_impl.dart';
import 'package:playbook_coach/features/auth/data/mock_auth_data_source.dart';
import 'package:playbook_coach/features/auth/data/remote_auth_data_source.dart';
import 'package:playbook_coach/features/auth/domain/auth_session.dart';
import 'package:playbook_coach/features/auth/domain/auth_user.dart';
import 'package:shared_preferences/shared_preferences.dart';

class _FakePlayerRemoteDataSource implements AuthDataSource {
  @override
  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    return const AuthSession(
      token: 'player-token',
      user: AuthUser(
        id: 99,
        name: 'Javier Benavides',
        email: 'javier@email.com',
        role: 'jugador',
      ),
    );
  }

  @override
  Future<AuthUser> fetchProfile() async {
    return const AuthUser(
      id: 99,
      name: 'Javier Benavides',
      email: 'javier@email.com',
      role: 'jugador',
    );
  }

  @override
  Future<void> logout() async {}
}

void main() {
  late TokenStorage tokenStorage;
  late MockAuthDataSource mockDataSource;

  setUp(() {
    SharedPreferences.setMockInitialValues(<String, Object>{});
    tokenStorage = TokenStorage();
    mockDataSource = MockAuthDataSource(simulatedDelay: Duration.zero);
  });

  group('AuthRepositoryImpl (Mock)', () {
    test('login exitoso como coach persiste token y permite restoreSession', () async {
      final repository = AuthRepositoryImpl(
        tokenStorage: tokenStorage,
        mockDataSource: mockDataSource,
        remoteDataSource: RemoteAuthDataSource(
          apiClient: ApiClient(tokenStorage: tokenStorage),
        ),
        initialMockMode: true,
      );

      final user = await repository.login(
        email: MockAuthDataSource.demoCoachEmail,
        password: MockAuthDataSource.demoCoachPassword,
      );

      expect(user.isCoach, isTrue);
      expect(user.name, 'Coach Martínez');
      expect(await tokenStorage.hasToken(), isTrue);

      final restored = await repository.restoreSession();
      expect(restored, user);

      await repository.logout();
      expect(await tokenStorage.hasToken(), isFalse);
    });

    test('rechaza con 403 cuando intenta ingresar un jugador en mock', () async {
      final repository = AuthRepositoryImpl(
        tokenStorage: tokenStorage,
        mockDataSource: mockDataSource,
        remoteDataSource: RemoteAuthDataSource(
          apiClient: ApiClient(tokenStorage: tokenStorage),
        ),
        initialMockMode: true,
      );

      await expectLater(
        () => repository.login(
          email: MockAuthDataSource.demoPlayerEmail,
          password: MockAuthDataSource.demoCoachPassword,
        ),
        throwsA(isA<ApiException>().having((e) => e.isForbidden, '403', isTrue)),
      );
      expect(await tokenStorage.hasToken(), isFalse);
    });

    test('lanza 401 con credenciales inválidas y 429 en rate limit', () async {
      final repository = AuthRepositoryImpl(
        tokenStorage: tokenStorage,
        mockDataSource: mockDataSource,
        remoteDataSource: RemoteAuthDataSource(
          apiClient: ApiClient(tokenStorage: tokenStorage),
        ),
        initialMockMode: true,
      );

      await expectLater(
        () => repository.login(email: 'coach@udd.cl', password: 'incorrecta'),
        throwsA(
          isA<ApiException>().having((e) => e.isUnauthorized, '401', isTrue),
        ),
      );

      await expectLater(
        () => repository.login(
          email: MockAuthDataSource.rateLimitedEmail,
          password: MockAuthDataSource.demoCoachPassword,
        ),
        throwsA(
          isA<ApiException>()
              .having((e) => e.isRateLimited, '429', isTrue)
              .having((e) => e.retryAfterSeconds, 'retryAfter', 30),
        ),
      );
    });
  });

  group('AuthRepositoryImpl (Remote)', () {
    test('login, restoreSession y logout contra RemoteAuthDataSource', () async {
      final mockHttp = MockClient((request) async {
        if (request.url.path.endsWith('/auth/login')) {
          expect(request.headers.containsKey('Authorization'), isFalse);
          return http.Response(
            jsonEncode(<String, dynamic>{
              'token': 'sanctum-live-token',
              'user': <String, dynamic>{
                'id': 7,
                'name': 'Coach Martínez',
                'email': 'coach@udd.cl',
                'role': 'coach',
                'equipos_dirigidos': <Map<String, dynamic>>[
                  <String, dynamic>{
                    'id': 1,
                    'nombre': 'UDD Titans',
                    'disciplina': 'LoL',
                  },
                ],
              },
            }),
            200,
            headers: const <String, String>{
              'content-type': 'application/json; charset=utf-8',
            },
          );
        }

        if (request.url.path.endsWith('/auth/me')) {
          expect(
            request.headers['Authorization'],
            'Bearer sanctum-live-token',
          );
          return http.Response(
            jsonEncode(<String, dynamic>{
              'data': <String, dynamic>{
                'id': 7,
                'name': 'Coach Martínez',
                'email': 'coach@udd.cl',
                'role': 'coach',
                'equipos_dirigidos': <Map<String, dynamic>>[
                  <String, dynamic>{
                    'id': 1,
                    'nombre': 'UDD Titans',
                    'disciplina': 'LoL',
                  },
                ],
              },
            }),
            200,
            headers: const <String, String>{
              'content-type': 'application/json; charset=utf-8',
            },
          );
        }

        if (request.url.path.endsWith('/auth/logout')) {
          return http.Response('', 204);
        }

        return http.Response('{}', 404);
      });

      final apiClient = ApiClient(
        tokenStorage: tokenStorage,
        httpClient: mockHttp,
        baseUrl: 'https://playbook.test/api',
      );
      final repository = AuthRepositoryImpl(
        tokenStorage: tokenStorage,
        mockDataSource: mockDataSource,
        remoteDataSource: RemoteAuthDataSource(apiClient: apiClient),
        initialMockMode: false,
      );

      final user = await repository.login(
        email: 'coach@udd.cl',
        password: 'password',
      );
      expect(user.id, 7);
      expect(await tokenStorage.readToken(), 'sanctum-live-token');

      final restored = await repository.restoreSession();
      expect(restored?.name, 'Coach Martínez');

      await repository.logout();
      expect(await tokenStorage.hasToken(), isFalse);
    });

    test('bloquea con 403 y limpia token si remoto responde con rol jugador', () async {
      final repository = AuthRepositoryImpl(
        tokenStorage: tokenStorage,
        mockDataSource: mockDataSource,
        remoteDataSource: _FakePlayerRemoteDataSource(),
        initialMockMode: false,
      );

      await expectLater(
        () => repository.login(email: 'javier@email.com', password: 'password'),
        throwsA(isA<ApiException>().having((e) => e.isForbidden, '403', isTrue)),
      );
      expect(await tokenStorage.hasToken(), isFalse);
    });
  });
}
