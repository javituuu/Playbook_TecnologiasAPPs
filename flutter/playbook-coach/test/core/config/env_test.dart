import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/core/config/env.dart';

void main() {
  group('Env', () {
    test('apiBaseUrl apunta al endpoint /api sin barra final', () {
      expect(Env.apiBaseUrl, endsWith('/api'));
      expect(Env.apiBaseUrl.endsWith('/'), isFalse);
    });

    test('buildUri combina baseUrl, path y queryParameters filtrando nulos', () {
      final uri = Env.buildUri(
        'coach/heatmap',
        baseUrl: 'https://api.playbook.test/api/',
        queryParameters: <String, dynamic>{
          'equipo_id': 3,
          'semana': '2026-W47',
          'vacio': null,
        },
      );

      expect(
        uri.toString(),
        'https://api.playbook.test/api/coach/heatmap?equipo_id=3&semana=2026-W47',
      );
    });

    test('buildUri respeta path con barra inicial', () {
      final uri = Env.buildUri(
        '/auth/login',
        baseUrl: 'http://localhost:8000/api',
      );

      expect(uri.toString(), 'http://localhost:8000/api/auth/login');
    });
  });
}
