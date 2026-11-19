import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:playbook_coach/app/theme/playbook_theme.dart';
import 'package:playbook_coach/core/network/api_client.dart';
import 'package:playbook_coach/core/storage/token_storage.dart';
import 'package:playbook_coach/features/auth/domain/equipo_dirigido.dart';
import 'package:playbook_coach/features/heatmap/data/heatmap_repository_impl.dart';
import 'package:playbook_coach/features/heatmap/data/mock_heatmap_data_source.dart';
import 'package:playbook_coach/features/heatmap/data/remote_heatmap_data_source.dart';
import 'package:playbook_coach/features/heatmap/domain/serie_duracion.dart';
import 'package:playbook_coach/features/heatmap/presentation/heatmap_controller.dart';
import 'package:playbook_coach/features/heatmap/presentation/heatmap_screen.dart';
import 'package:playbook_coach/features/practicas/data/mock_practicas_data_source.dart';
import 'package:playbook_coach/features/practicas/data/practicas_repository_impl.dart';
import 'package:playbook_coach/features/practicas/data/remote_practicas_data_source.dart';
import 'package:playbook_coach/features/practicas/presentation/practicas_controller.dart';

void main() {
  const equipoLol = EquipoDirigido(
    id: 1,
    nombre: 'UDD Titans',
    disciplina: 'LoL',
  );

  late HeatmapController heatmapController;
  late PracticasController practicasController;

  setUp(() async {
    final tokenStorage = TokenStorage();
    final apiClient = ApiClient(tokenStorage: tokenStorage);

    heatmapController = HeatmapController(
      repository: HeatmapRepositoryImpl(
        mockDataSource: MockHeatmapDataSource(simulatedDelay: Duration.zero),
        remoteDataSource: RemoteHeatmapDataSource(apiClient: apiClient),
        isMockMode: () => true,
      ),
    );
    practicasController = PracticasController(
      repository: PracticasRepositoryImpl(
        mockDataSource: MockPracticasDataSource(simulatedDelay: Duration.zero),
        remoteDataSource: RemotePracticasDataSource(apiClient: apiClient),
        isMockMode: () => true,
      ),
    );

    await heatmapController.loadHeatmap(equipoId: equipoLol.id);
    await practicasController.loadPracticas(equipoId: equipoLol.id);
  });

  tearDown(() {
    heatmapController.dispose();
    practicasController.dispose();
  });

  Future<void> pumpAtSize(WidgetTester tester, Size size) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });

    await tester.pumpWidget(
      MaterialApp(
        theme: PlaybookTheme.light(),
        home: Scaffold(
          body: HeatmapScreen(
            heatmapController: heatmapController,
            practicasController: practicasController,
            equipo: equipoLol,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
  }

  testWidgets(
    'pantalla ancha renderiza matriz 8x7, quórum bloqueado en 3/5 y agenda en 5/5',
    (WidgetTester tester) async {
      await pumpAtSize(tester, const Size(1280, 800));

      expect(find.text('Matriz de Disponibilidad Semanal'), findsOneWidget);
      expect(find.text('UMBRAL DE QUÓRUM (4/5)'), findsOneWidget);
      expect(find.text('DETALLE DEL BLOQUE'), findsOneWidget);

      // Bloque inicial: Jueves @ 19:00 (3/5) -> Agendar bloqueado
      expect(find.text('Jueves @ 19:00'), findsOneWidget);
      expect(find.text('3/5 disponibles'), findsOneWidget);
      expect(find.text('Diego Pérez'), findsOneWidget);
      expect(find.text('PARTIDO VALORANT 20:00'), findsOneWidget);
      expect(find.text('Cata Ríos'), findsOneWidget);
      expect(find.text('ACADÉMICO'), findsOneWidget);
      expect(find.text("Martín 'Slayer' Gómez"), findsOneWidget);

      final lockedButton = tester.widget<FilledButton>(
        find.byKey(const Key('agendar_aqui_button')),
      );
      expect(lockedButton.onPressed, isNull);

      // Cambia duración a Bo3 y selecciona bloque 5/5 (Jueves @ 20:00)
      await tester.tap(find.byKey(const Key('serie_chip_bo3')));
      await tester.pumpAndSettle();
      expect(heatmapController.selectedSerie, SerieDuracion.bo3);

      await tester.tap(find.byKey(const Key('heatmap_cell_4_20:00')));
      await tester.pumpAndSettle();

      expect(find.text('Jueves @ 20:00'), findsOneWidget);
      expect(find.text('5/5 disponibles'), findsOneWidget);

      final enabledButton = tester.widget<FilledButton>(
        find.byKey(const Key('agendar_aqui_button')),
      );
      expect(enabledButton.onPressed, isNotNull);

      // Abre AgendarPracticaSheet y agenda práctica
      await tester.tap(find.byKey(const Key('agendar_aqui_button')));
      await tester.pumpAndSettle();

      expect(find.text('Agendar práctica'), findsOneWidget);
      await tester.enterText(
        find.byKey(const Key('practica_rival_field')),
        'Fénix Esports',
      );
      await tester.tap(find.byKey(const Key('confirmar_practica_button')));
      await tester.pumpAndSettle();

      expect(
        practicasController.programadas.any((p) => p.rival == 'Fénix Esports'),
        isTrue,
      );
      expect(find.textContaining('Práctica agendada · Bo3'), findsOneWidget);
    },
  );

  testWidgets('cambia de semana con controles anterior y siguiente', (
    WidgetTester tester,
  ) async {
    await pumpAtSize(tester, const Size(1280, 800));
    final initialWeek = heatmapController.semanaIso;

    await tester.tap(find.byKey(const Key('week_next_button')));
    await tester.pumpAndSettle();
    expect(heatmapController.semanaIso, isNot(initialWeek));

    await tester.tap(find.byKey(const Key('week_prev_button')));
    await tester.pumpAndSettle();
    expect(heatmapController.semanaIso, initialWeek);
  });

  testWidgets('móvil abre Detalle del Bloque en hoja modal inferior', (
    WidgetTester tester,
  ) async {
    await pumpAtSize(tester, const Size(390, 844));

    expect(find.byKey(const Key('mobile_block_summary_tap')), findsOneWidget);

    // Toca Jueves @ 20:00 (5/5) para abrir hoja inferior con botón habilitado
    await tester.tap(find.byKey(const Key('heatmap_cell_4_20:00')));
    await tester.pumpAndSettle();

    expect(find.text('DETALLE DEL BLOQUE'), findsOneWidget);
    expect(find.text('5/5 disponibles'), findsOneWidget);

    final sheetButton = tester.widget<FilledButton>(
      find.byKey(const Key('agendar_aqui_button')),
    );
    expect(sheetButton.onPressed, isNotNull);
  });
}
