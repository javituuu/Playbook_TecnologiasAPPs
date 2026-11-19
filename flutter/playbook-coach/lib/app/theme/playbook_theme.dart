import 'package:material_ui/material_ui.dart';

/// Paleta de marca "Aqua y uva" y colores base de Playbook Coach.
class PlaybookColors {
  const PlaybookColors._();

  /// Carbon — color principal oscuro y texto primario.
  static const Color carbon = Color(0xFF222222);

  /// Dusty Grape — color primario de marca (botones, énfasis, encabezados).
  static const Color dustyGrape = Color(0xFF4B4E6D);

  /// Pearl Aqua — acento secundario de marca (acciones destacadas, estados positivos).
  static const Color pearlAqua = Color(0xFF84DCC6);

  /// Cool Steel — color neutro frío para textos secundarios, íconos y bordes.
  static const Color coolSteel = Color(0xFF95A3B3);

  // Superficies y neutros complementarios (modo claro)
  static const Color canvas = Color(0xFFF8FAFC);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceMuted = Color(0xFFF1F5F9);
  static const Color border = Color(0xFFE2E8F0);

  // Superficies complementarias (modo oscuro)
  static const Color darkCanvas = Color(0xFF181920);
  static const Color darkSurface = carbon;
  static const Color darkSurfaceElevated = Color(0xFF2C2E3B);
  static const Color darkBorder = Color(0xFF3A3D4D);

  // Semántica general
  static const Color error = Color(0xFFDC2626);
  static const Color errorContainer = Color(0xFFFEE2E2);
}

/// Par de colores para representar una celda/badge de disponibilidad del heatmap.
@immutable
class HeatmapTone {
  const HeatmapTone({
    required this.background,
    required this.foreground,
    required this.border,
  });

  final Color background;
  final Color foreground;
  final Color border;

  /// Interpola linealmente entre dos [HeatmapTone] para animaciones de tema.
  static HeatmapTone lerp(HeatmapTone a, HeatmapTone b, double t) {
    return HeatmapTone(
      background: Color.lerp(a.background, b.background, t) ?? a.background,
      foreground: Color.lerp(a.foreground, b.foreground, t) ?? a.foreground,
      border: Color.lerp(a.border, b.border, t) ?? a.border,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is HeatmapTone &&
        other.background == background &&
        other.foreground == foreground &&
        other.border == border;
  }

  @override
  int get hashCode => Object.hash(background, foreground, border);
}

/// Extensión de tema con los seis niveles de disponibilidad del heatmap (`5/5` a `0/5`).
@immutable
class PlaybookHeatmapTheme extends ThemeExtension<PlaybookHeatmapTheme> {
  const PlaybookHeatmapTheme({
    required this.level5,
    required this.level4,
    required this.level3,
    required this.level2,
    required this.level1,
    required this.level0,
  });

  /// Paleta estándar del heatmap alineada al diseño de Playbook Coach.
  static const PlaybookHeatmapTheme standard = PlaybookHeatmapTheme(
    level5: HeatmapTone(
      background: Color(0xFFDCFCE7),
      foreground: Color(0xFF166534),
      border: Color(0xFFBBF7D0),
    ),
    level4: HeatmapTone(
      background: Color(0xFFF0FDF4),
      foreground: Color(0xFF15803D),
      border: Color(0xFFDCFCE7),
    ),
    level3: HeatmapTone(
      background: Color(0xFFFEF9C3),
      foreground: Color(0xFF854D0E),
      border: Color(0xFFFEF08A),
    ),
    level2: HeatmapTone(
      background: Color(0xFFFFEDD5),
      foreground: Color(0xFF9A3412),
      border: Color(0xFFFED7AA),
    ),
    level1: HeatmapTone(
      background: Color(0xFFFFE4E6),
      foreground: Color(0xFF9F1239),
      border: Color(0xFFFECDD3),
    ),
    level0: HeatmapTone(
      background: Color(0xFFFEE2E2),
      foreground: Color(0xFF991B1B),
      border: Color(0xFFFECACA),
    ),
  );

  final HeatmapTone level5;
  final HeatmapTone level4;
  final HeatmapTone level3;
  final HeatmapTone level2;
  final HeatmapTone level1;
  final HeatmapTone level0;

  /// Retorna el [HeatmapTone] correspondiente a [available] jugadores sobre [total].
  HeatmapTone forAvailability(int available, {int total = 5}) {
    if (total <= 0 || available <= 0) {
      return level0;
    }
    if (available >= total) {
      return level5;
    }

    final ratio = available / total;
    if (ratio >= 0.8) return level4;
    if (ratio >= 0.6) return level3;
    if (ratio >= 0.4) return level2;
    return level1;
  }

  /// Lista ordenada de niveles desde `5/5` hasta `0/5` para leyendas del heatmap.
  List<HeatmapTone> get orderedLevels => <HeatmapTone>[
        level5,
        level4,
        level3,
        level2,
        level1,
        level0,
      ];

  @override
  PlaybookHeatmapTheme copyWith({
    HeatmapTone? level5,
    HeatmapTone? level4,
    HeatmapTone? level3,
    HeatmapTone? level2,
    HeatmapTone? level1,
    HeatmapTone? level0,
  }) {
    return PlaybookHeatmapTheme(
      level5: level5 ?? this.level5,
      level4: level4 ?? this.level4,
      level3: level3 ?? this.level3,
      level2: level2 ?? this.level2,
      level1: level1 ?? this.level1,
      level0: level0 ?? this.level0,
    );
  }

  @override
  PlaybookHeatmapTheme lerp(
    covariant ThemeExtension<PlaybookHeatmapTheme>? other,
    double t,
  ) {
    if (other is! PlaybookHeatmapTheme) {
      return this;
    }
    return PlaybookHeatmapTheme(
      level5: HeatmapTone.lerp(level5, other.level5, t),
      level4: HeatmapTone.lerp(level4, other.level4, t),
      level3: HeatmapTone.lerp(level3, other.level3, t),
      level2: HeatmapTone.lerp(level2, other.level2, t),
      level1: HeatmapTone.lerp(level1, other.level1, t),
      level0: HeatmapTone.lerp(level0, other.level0, t),
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is PlaybookHeatmapTheme &&
        other.level5 == level5 &&
        other.level4 == level4 &&
        other.level3 == level3 &&
        other.level2 == level2 &&
        other.level1 == level1 &&
        other.level0 == level0;
  }

  @override
  int get hashCode =>
      Object.hash(level5, level4, level3, level2, level1, level0);
}

/// Configuración central de temas Material 3 para Playbook Coach ("Aqua y uva").
class PlaybookTheme {
  const PlaybookTheme._();

  // Acceso directo a los colores de marca
  static const Color carbon = PlaybookColors.carbon;
  static const Color dustyGrape = PlaybookColors.dustyGrape;
  static const Color pearlAqua = PlaybookColors.pearlAqua;
  static const Color coolSteel = PlaybookColors.coolSteel;

  // Acceso directo a los tonos del heatmap
  static HeatmapTone get heatmap5 => PlaybookHeatmapTheme.standard.level5;
  static HeatmapTone get heatmap4 => PlaybookHeatmapTheme.standard.level4;
  static HeatmapTone get heatmap3 => PlaybookHeatmapTheme.standard.level3;
  static HeatmapTone get heatmap2 => PlaybookHeatmapTheme.standard.level2;
  static HeatmapTone get heatmap1 => PlaybookHeatmapTheme.standard.level1;
  static HeatmapTone get heatmap0 => PlaybookHeatmapTheme.standard.level0;

  /// Obtiene el tono del heatmap para [available] disponibles sobre [total].
  static HeatmapTone heatmapToneFor(int available, {int total = 5}) =>
      PlaybookHeatmapTheme.standard.forAvailability(available, total: total);

  /// Tema claro principal de Playbook Coach.
  static ThemeData light() {
    const colorScheme = ColorScheme(
      brightness: Brightness.light,
      primary: PlaybookColors.dustyGrape,
      onPrimary: Colors.white,
      primaryContainer: Color(0xFFE3E4EE),
      onPrimaryContainer: PlaybookColors.carbon,
      secondary: PlaybookColors.pearlAqua,
      onSecondary: PlaybookColors.carbon,
      secondaryContainer: Color(0xFFD9F7EE),
      onSecondaryContainer: PlaybookColors.carbon,
      tertiary: PlaybookColors.coolSteel,
      onTertiary: PlaybookColors.carbon,
      tertiaryContainer: PlaybookColors.surfaceMuted,
      onTertiaryContainer: PlaybookColors.carbon,
      error: PlaybookColors.error,
      onError: Colors.white,
      errorContainer: PlaybookColors.errorContainer,
      onErrorContainer: Color(0xFF991B1B),
      surface: PlaybookColors.surface,
      onSurface: PlaybookColors.carbon,
      surfaceContainerLowest: Colors.white,
      surfaceContainerLow: PlaybookColors.canvas,
      surfaceContainer: PlaybookColors.surfaceMuted,
      surfaceContainerHigh: Color(0xFFE8EEF5),
      surfaceContainerHighest: PlaybookColors.border,
      onSurfaceVariant: PlaybookColors.coolSteel,
      outline: PlaybookColors.coolSteel,
      outlineVariant: PlaybookColors.border,
      shadow: Color(0x14222222),
      scrim: Color(0x66222222),
      inverseSurface: PlaybookColors.carbon,
      onInverseSurface: Colors.white,
      inversePrimary: PlaybookColors.pearlAqua,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: PlaybookColors.canvas,
      dividerColor: PlaybookColors.border,
      appBarTheme: const AppBarTheme(
        backgroundColor: PlaybookColors.surface,
        foregroundColor: PlaybookColors.carbon,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
        surfaceTintColor: Colors.transparent,
      ),
      cardTheme: CardThemeData(
        color: PlaybookColors.surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: const BorderSide(color: PlaybookColors.border),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: PlaybookColors.dustyGrape,
          foregroundColor: Colors.white,
          minimumSize: const Size(0, 46),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: PlaybookColors.dustyGrape,
          foregroundColor: Colors.white,
          elevation: 0,
          minimumSize: const Size(0, 46),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: PlaybookColors.dustyGrape,
          side: const BorderSide(color: PlaybookColors.coolSteel),
          minimumSize: const Size(0, 46),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: PlaybookColors.surface,
        hintStyle: const TextStyle(color: PlaybookColors.coolSteel),
        labelStyle: const TextStyle(color: PlaybookColors.dustyGrape),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: PlaybookColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: PlaybookColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(
            color: PlaybookColors.dustyGrape,
            width: 1.5,
          ),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: PlaybookColors.error),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: PlaybookColors.surface,
        indicatorColor: PlaybookColors.pearlAqua.withValues(alpha: 0.35),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final isSelected = states.contains(WidgetState.selected);
          return TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
            color: isSelected
                ? PlaybookColors.carbon
                : PlaybookColors.coolSteel,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final isSelected = states.contains(WidgetState.selected);
          return IconThemeData(
            color: isSelected
                ? PlaybookColors.dustyGrape
                : PlaybookColors.coolSteel,
          );
        }),
      ),
      dividerTheme: const DividerThemeData(
        color: PlaybookColors.border,
        thickness: 1,
        space: 1,
      ),
      extensions: const <ThemeExtension<dynamic>>[
        PlaybookHeatmapTheme.standard,
      ],
    );
  }

  /// Tema oscuro alternativo apoyado en Carbon (`#222222`) y Pearl Aqua (`#84DCC6`).
  static ThemeData dark() {
    const colorScheme = ColorScheme(
      brightness: Brightness.dark,
      primary: PlaybookColors.pearlAqua,
      onPrimary: PlaybookColors.carbon,
      primaryContainer: PlaybookColors.dustyGrape,
      onPrimaryContainer: Colors.white,
      secondary: PlaybookColors.dustyGrape,
      onSecondary: Colors.white,
      secondaryContainer: PlaybookColors.darkSurfaceElevated,
      onSecondaryContainer: PlaybookColors.pearlAqua,
      tertiary: PlaybookColors.coolSteel,
      onTertiary: PlaybookColors.carbon,
      tertiaryContainer: PlaybookColors.darkSurfaceElevated,
      onTertiaryContainer: Colors.white,
      error: Color(0xFFF87171),
      onError: PlaybookColors.carbon,
      errorContainer: Color(0xFF7F1D1D),
      onErrorContainer: Color(0xFFFEE2E2),
      surface: PlaybookColors.darkSurface,
      onSurface: Colors.white,
      surfaceContainerLowest: PlaybookColors.darkCanvas,
      surfaceContainerLow: PlaybookColors.darkSurface,
      surfaceContainer: PlaybookColors.darkSurfaceElevated,
      surfaceContainerHigh: Color(0xFF343746),
      surfaceContainerHighest: PlaybookColors.darkBorder,
      onSurfaceVariant: PlaybookColors.coolSteel,
      outline: PlaybookColors.coolSteel,
      outlineVariant: PlaybookColors.darkBorder,
      shadow: Colors.black,
      scrim: Colors.black87,
      inverseSurface: PlaybookColors.canvas,
      onInverseSurface: PlaybookColors.carbon,
      inversePrimary: PlaybookColors.dustyGrape,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: PlaybookColors.darkCanvas,
      dividerColor: PlaybookColors.darkBorder,
      appBarTheme: const AppBarTheme(
        backgroundColor: PlaybookColors.darkSurface,
        foregroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
        surfaceTintColor: Colors.transparent,
      ),
      cardTheme: CardThemeData(
        color: PlaybookColors.darkSurface,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: const BorderSide(color: PlaybookColors.darkBorder),
        ),
      ),
      extensions: const <ThemeExtension<dynamic>>[
        PlaybookHeatmapTheme.standard,
      ],
    );
  }
}
