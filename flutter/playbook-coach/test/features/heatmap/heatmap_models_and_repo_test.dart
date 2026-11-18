import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/core/utils/week_utils.dart';
import 'package:playbook_coach/features/heatmap/data/heatmap_repository_impl.dart';
import 'package:playbook_coach/features/heatmap/data/mock_heatmap_data_source.dart';
import 'package:playbook_coach/features/heatmap/data/remote_heatmap_data_source.dart';
import 'package:playbook_coach/features/heatmap/domain/serie_duracion.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  group('SerieDuracion', () {
    test('calcula hora de término para Bo1, Bo3 y Bo5', () {
      expect(SerieDuracion.bo1.computeEndTime('19:00'), '20:00');
      expect(SerieDuracion.bo3.computeEndTime('19:00'), '21:00');
      expect(SerieDuracion.bo5.computeEndTime('19:00'), '22:00');
    });
  });

  group('MockHeatmapDataSource y HeatmapRepositoryImpl', () {
    test('genera matriz 8x7 con Jueves @ 19:00 fiel a referencia Figma', () async {
      final mockSource = MockHeatmapDataSource(simulatedDelay: Duration.zero);
      final weekIso = WeekUtils.toIsoWeek(DateTime.now());
      final matriz = await mockSource.fetchHeatmap(
        equipoId: 1,
        semanaIso: weekIso,
      );

      expect(matriz.horas, hasLength(8));
      expect(matriz.dias, hasLength(7));
      expect(matriz.bloques, hasLength(56));

      final jueves19 = matriz.bloqueAt(4, '19:00');
      expect(jueves19, isNotNull);
      expect(jueves19!.ratioLabel, '3/5');
      expect(jueves19.cumpleQuorum, isFalse);
      expect(jueves19.ausencias, hasLength(2));
      expect(jueves19.ausencias.first.nombreJugador, 'Diego Pérez');
      expect(
        jueves19.ausencias.first.categoriaBadge,
        'PARTIDO VALORANT 20:00',
      );
      expect(jueves19.ausencias[1].nombreJugador, 'Cata Ríos');
      expect(jueves19.ausencias[1].categoriaBadge, 'ACADÉMICO');
      expect(jueves19.disponibles, hasLength(3));
      expect(
        jueves19.disponibles.first.displayName,
        "Martín 'Slayer' Gómez",
      );

      final jueves20 = matriz.bloqueAt(4, '20:00');
      expect(jueves20, isNotNull);
      expect(jueves20!.ratioLabel, '5/5');
      expect(jueves20.cumpleQuorum, isTrue);
    });

    test('RemoteHeatmapDataSource consulta endpoint y parsea respuesta', () async {
      SharedPreferences.setMockInitialValues(<String, Object>{});
      final tokenStorage = TokenStorage();
      await tokenStorage.saveToken('coach-token');

      late Uri capturedUri;
      final apiClient = ApiClient(
        tokenStorage: tokenStorage,
        baseUrl: 'https://playbook.test/api',
        httpClient: MockClient((request) async {
          capturedUri = request.url;
          return http.Response(
            jsonEncode(<String, dynamic>{
              'data': <String, dynamic>{
                'equipo_id': 2,
                'semana_iso': '2026-W47',
                'semana_label': '16 nov – 22 nov 2026',
                'fecha_inicio': '2026-11-16',
                'quorum_minimo': 4,
                'total_jugadores': 5,
                'bloques': <Map<String, dynamic>>[
                  <String, dynamic>{
                    'id': '2026-W47-1-19:00',
                    'dia_semana': 1,
                    'dia_nombre': 'Lunes',
                    'fecha': '2026-11-16',
                    'hora': '19:00',
                    'disponibles_count': 4,
                    'total_jugadores': 5,
                    'quorum_minimo': 4,
                    'disponibles': <Map<String, dynamic>>[
                      <String, dynamic>{
                        'id': 1,
                        'nombre': 'Valentina Soto',
                        'nickname': 'Valkyrie',
                      },
                    ],
                    'ausencias': <Map<String, dynamic>>[],
                  },
                ],
              },
            }),
            200,
            headers: const <String, String>{
              'content-type': 'application/json; charset=utf-8',
            },
          );
        }),
      );

      final repository = HeatmapRepositoryImpl(
        mockDataSource: MockHeatmapDataSource(simulatedDelay: Duration.zero),
        remoteDataSource: RemoteHeatmapDataSource(apiClient: apiClient),
        isMockMode: () => false,
      );

      final result = await repository.fetchHeatmap(
        equipoId: 2,
        semanaIso: '2026-W47',
      );

      expect(
        capturedUri.toString(),
        'https://playbook.test/api/coach/equipos/2/heatmap?semana=2026-W47',
      );
      expect(result.equipoId, 2);
      expect(result.bloques, hasLength(1));
      expect(result.bloqueAt(1, '19:00')?.cumpleQuorum, isTrue);
    });
  });
}
