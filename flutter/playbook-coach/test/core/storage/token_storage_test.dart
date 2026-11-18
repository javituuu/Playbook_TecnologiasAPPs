import 'package:flutter_test/flutter_test.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  group('TokenStorage', () {
    late TokenStorage storage;

    setUp(() {
      SharedPreferences.setMockInitialValues(<String, Object>{});
      storage = TokenStorage();
    });

    test('retorna null cuando no existe token guardado', () async {
      expect(await storage.readToken(), isNull);
      expect(await storage.hasToken(), isFalse);
    });

    test('guarda, lee y limpia el token recortando espacios', () async {
      await storage.saveToken('  sanctum-token-123  ');

      expect(await storage.readToken(), 'sanctum-token-123');
      expect(await storage.hasToken(), isTrue);

      await storage.clearToken();

      expect(await storage.readToken(), isNull);
      expect(await storage.hasToken(), isFalse);
    });

    test('lanza ArgumentError si se intenta guardar un token vacío', () async {
      expect(() => storage.saveToken('   '), throwsArgumentError);
    });
  });
}
