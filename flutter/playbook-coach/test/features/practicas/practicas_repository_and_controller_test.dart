import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/core/utils/week_utils.dart';
import 'package:playbook_coach/features/heatmap/domain/serie_duracion.dart';
import 'package:playbook_coach/features/practicas/data/mock_practicas_data_source.dart';
import 'package:playbook_coach/features/practicas/data/practicas_repository_impl.dart';
import 'package:playbook_coach/features/practicas/data/remote_practicas_data_source.dart';
import 'package:playbook_coach/features/practicas/domain/crear_practica_request.dart';
import 'package:playbook_coach/features/practicas/domain/practica.dart';
import 'package:playbook_coach/features/practicas/presentation/practicas_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  group('PracticasController + MockPracticasDataSource', () {
    late PracticasController controller;
    late String currentWeekIso;

    setUp(() {
      final now = DateTime.now();
      currentWeekIso = WeekUtils.toIsoWeek(now);
      final repository = PracticasRepositoryImpl(
        mockDataSource: MockPracticasDataSource(
          simulatedDelay: Duration.zero,
          referenceDate: now,
        ),
        remoteDataSource: RemotePracticasDataSource(
          apiClient: ApiClient(tokenStorage: TokenStorage()),
        ),
        isMockMode: () => true,
      );
      controller = PracticasController(repository: repository);
    });

    tearDown(() {
      controller.dispose();
    });

    test('carga prácticas iniciales, agenda nueva práctica y la cancela en memoria', () async {
      await controller.loadPracticas(equipoId: 1);
      final initialCount = controller.programadas.length;
      expect(initialCount, greaterThanOrEqualTo(2));

      // Verifica cobertura Bo3 de la práctica inicial Miércoles 21:00 - 23:00
      final covered21 = controller.findScheduledForBlock(
        equipoId: 1,
        semanaIso: currentWeekIso,
        diaSemana: 3,
        hora: '21:00',
      );
      final covered22 = controller.findScheduledForBlock(
        equipoId: 1,
        semanaIso: currentWeekIso,
        diaSemana: 3,
        hora: '22:00',
      );
      expect(covered21?.rival, 'USM Gaming');
      expect(covered22?.rival, 'USM Gaming');

      final created = await controller.agendarPractica(
        CrearPracticaRequest(
          equipoId: 1,
          equipoNombre: 'UDD Titans — LoL',
          semanaIso: currentWeekIso,
          diaSemana: 4,
          diaNombre: 'Jueves',
          fecha: DateTime.now(),
          horaInicio: '20:00',
          serie: SerieDuracion.bo3,
          rival: 'Fénix Esports',
          modalidad: 'Scrim competitivo',
          servidor: 'Discord + Server LAS',
          jugadoresCitados: const <String>["Martín 'Slayer' Gómez"],
        ),
      );

      expect(created, isNotNull);
      expect(created!.horaFin, '22:00');
      expect(controller.programadas, hasLength(initialCount + 1));

      final canceled = await controller.cancelarPractica(
        practicaId: created.id,
        motivo: 'Cambio de horario del rival',
      );
      expect(canceled, isTrue);
      expect(controller.programadas, hasLength(initialCount));
      expect(
        controller.canceladas.any(
          (p) =>
              p.id == created.id &&
              p.estado == EstadoPractica.cancelada &&
              p.motivoCancelacion == 'Cambio de horario del rival',
        ),
        isTrue,
      );
    });
  });

  group('RemotePracticasDataSource', () {
    test('consume endpoints GET, POST y PATCH cancelar', () async {
      SharedPreferences.setMockInitialValues(<String, Object>{});
      final tokenStorage = TokenStorage();
      await tokenStorage.saveToken('coach-token');

      final apiClient = ApiClient(
        tokenStorage: tokenStorage,
        baseUrl: 'https://playbook.test/api',
        httpClient: MockClient((request) async {
          if (request.method == 'GET' &&
              request.url.path.endsWith('/coach/equipos/1/practicas')) {
            return http.Response(
              jsonEncode(<String, dynamic>{
                'data': <Map<String, dynamic>>[
                  <String, dynamic>{
                    'id': 50,
                    'equipo_id': 1,
                    'equipo_nombre': 'UDD Titans — LoL',
                    'semana_iso': '2026-W47',
                    'dia_semana': 4,
                    'dia_nombre': 'Jueves',
                    'fecha': '2026-11-19',
                    'hora_inicio': '20:00',
                    'hora_fin': '21:00',
                    'serie': 'bo1',
                    'rival': 'UC Esports',
                    'modalidad': 'Scrim',
                    'servidor': 'Discord',
                    'jugadores_citados': <String>['Slayer'],
                    'estado': 'programada',
                  },
                ],
              }),
              200,
              headers: const <String, String>{
                'content-type': 'application/json; charset=utf-8',
              },
            );
          }

          if (request.method == 'POST' &&
              request.url.path.endsWith('/coach/practicas')) {
            return http.Response(
              jsonEncode(<String, dynamic>{
                'data': <String, dynamic>{
                  'id': 51,
                  'equipo_id': 1,
                  'equipo_nombre': 'UDD Titans — LoL',
                  'semana_iso': '2026-W47',
                  'dia_semana': 5,
                  'dia_nombre': 'Viernes',
                  'fecha': '2026-11-20',
                  'hora_inicio': '19:00',
                  'hora_fin': '21:00',
                  'serie': 'bo3',
                  'rival': 'USM Gaming',
                  'modalidad': 'Scrim',
                  'servidor': 'Discord',
                  'jugadores_citados': <String>['Slayer', 'Lumina'],
                  'estado': 'programada',
                },
              }),
              201,
              headers: const <String, String>{
                'content-type': 'application/json; charset=utf-8',
              },
            );
          }

          if (request.method == 'PATCH' &&
              request.url.path.endsWith('/coach/practicas/51/cancelar')) {
            return http.Response(
              jsonEncode(<String, dynamic>{
                'data': <String, dynamic>{
                  'id': 51,
                  'equipo_id': 1,
                  'equipo_nombre': 'UDD Titans — LoL',
                  'semana_iso': '2026-W47',
                  'dia_semana': 5,
                  'dia_nombre': 'Viernes',
                  'fecha': '2026-11-20',
                  'hora_inicio': '19:00',
                  'hora_fin': '21:00',
                  'serie': 'bo3',
                  'rival': 'USM Gaming',
                  'modalidad': 'Scrim',
                  'servidor': 'Discord',
                  'jugadores_citados': <String>['Slayer', 'Lumina'],
                  'estado': 'cancelada',
                  'motivo_cancelacion': 'Sin quórum',
                },
              }),
              200,
              headers: const <String, String>{
                'content-type': 'application/json; charset=utf-8',
              },
            );
          }

          return http.Response('{}', 404);
        }),
      );

      final repository = PracticasRepositoryImpl(
        mockDataSource: MockPracticasDataSource(simulatedDelay: Duration.zero),
        remoteDataSource: RemotePracticasDataSource(apiClient: apiClient),
        isMockMode: () => false,
      );

      final list = await repository.fetchPracticas(equipoId: 1);
      expect(list, hasLength(1));
      expect(list.first.rival, 'UC Esports');

      final created = await repository.agendarPractica(
        CrearPracticaRequest(
          equipoId: 1,
          equipoNombre: 'UDD Titans — LoL',
          semanaIso: '2026-W47',
          diaSemana: 5,
          diaNombre: 'Viernes',
          fecha: DateTime(2026, 11, 20),
          horaInicio: '19:00',
          serie: SerieDuracion.bo3,
          rival: 'USM Gaming',
          modalidad: 'Scrim',
          servidor: 'Discord',
          jugadoresCitados: const <String>['Slayer', 'Lumina'],
        ),
      );
      expect(created.id, 51);

      final canceled = await repository.cancelarPractica(
        practicaId: 51,
        motivo: 'Sin quórum',
      );
      expect(canceled.estaCancelada, isTrue);
      expect(canceled.motivoCancelacion, 'Sin quórum');
    });
  });
}
