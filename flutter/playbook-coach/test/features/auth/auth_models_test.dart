import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/features/auth/domain/auth_user.dart';
import 'package:playbook_coach/features/auth/domain/equipo_dirigido.dart';

void main() {
  group('EquipoDirigido', () {
    test('parsea JSON y formatea displayName correctamente', () {
      final equipo = EquipoDirigido.fromJson(<String, dynamic>{
        'id': 2,
        'nombre': 'UDD Nova',
        'disciplina': 'Valorant',
        'club': 'Universidad De Dioses (UDD)',
        'quorum_minimo': 4,
      });

      expect(equipo.id, 2);
      expect(equipo.nombre, 'UDD Nova');
      expect(equipo.disciplina, 'Valorant');
      expect(equipo.club, 'Universidad De Dioses (UDD)');
      expect(equipo.quorumMinimo, 4);
      expect(equipo.displayName, 'UDD Nova — Valorant');
      expect(EquipoDirigido.fromJson(equipo.toJson()), equipo);
    });
  });

  group('AuthUser', () {
    test('identifica rol coach, rol jugador, iniciales y equipos dirigidos', () {
      final coach = AuthUser.fromJson(<String, dynamic>{
        'id': 10,
        'name': 'Coach Martínez',
        'email': 'coach@udd.cl',
        'role': 'coach',
        'equipos_dirigidos': <Map<String, dynamic>>[
          <String, dynamic>{
            'id': 1,
            'nombre': 'UDD Titans',
            'disciplina': 'LoL',
          },
          <String, dynamic>{
            'id': 2,
            'nombre': 'UDD Nova',
            'disciplina': 'Valorant',
          },
        ],
      });

      expect(coach.isCoach, isTrue);
      expect(coach.isJugador, isFalse);
      expect(coach.initials, 'M');
      expect(coach.equiposDirigidos, hasLength(2));
      expect(coach.equiposDirigidos.first.displayName, 'UDD Titans — LoL');
      expect(AuthUser.fromJson(coach.toJson()), coach);

      final player = AuthUser.fromJson(<String, dynamic>{
        'id': 20,
        'nombre_completo': 'Javier Benavides',
        'email': 'javier@email.com',
        'rol': 'jugador',
      });

      expect(player.isCoach, isFalse);
      expect(player.isJugador, isTrue);
      expect(player.initials, 'JB');
    });
  });
}
