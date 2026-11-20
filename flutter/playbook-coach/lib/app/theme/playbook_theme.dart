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
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceMuted = Color(0xFFECEEF1);
  static const Color border = Color(0xFFD5D9E0);

  // Superficies complementarias (modo oscuro)
  static const Color darkSurface = carbon;
  static const Color darkSurfaceElevated = Color(0xFF343644);
  static const Color darkBorder = Color(0xFF4B4E6D);

  // Semántica general
  static const Color error = Color(0xFFB3261E);
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
      background: Color(0xFFD9F7EE),
      foreground: Color(0xFF0E705B),
      border: Color(0xFF84DCC6),
    ),
    level4: HeatmapTone(
      background: Color(0xFFF0FDF4),
      foreground: Color(0xFF0E705B),
      border: Color(0xFFD9F7EE),
    ),
    level3: HeatmapTone(
      background: Color(0xFFFEF9C3),
      foreground: Color(0xFF8A5A00),
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
      foreground: Color(0xFFB3261E),
      border: Color(0xFFFECACA),
    ),
  );

  static const PlaybookHeatmapTheme dark = PlaybookHeatmapTheme(
    level5: HeatmapTone(
      background: Color(0xFF245449),
      foreground: PlaybookColors.pearlAqua,
      border: Color(0xFF35675B),
    ),
    level4: HeatmapTone(
      background: Color(0xFF293E3A),
      foreground: PlaybookColors.pearlAqua,
      border: Color(0xFF34524C),
    ),
    level3: HeatmapTone(
      background: Color(0xFF4A3C26),
      foreground: Color(0xFFF2C879),
      border: Color(0xFF645436),
    ),
    level2: HeatmapTone(
      background: Color(0xFF512F25),
      foreground: Color(0xFFFFB28A),
      border: Color(0xFF673E32),
    ),
    level1: HeatmapTone(
      background: Color(0xFF4B2B3D),
      foreground: Color(0xFFF5A9CC),
      border: Color(0xFF63394F),
    ),
    level0: HeatmapTone(
      background: Color(0xFF542826),
      foreground: Color(0xFFF28B82),
      border: Color(0xFF6C3532),
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
      onErrorContainer: Color(0xFFB3261E),
      surface: PlaybookColors.surface,
      onSurface: PlaybookColors.carbon,
      surfaceContainerLowest: Colors.white,
      surfaceContainerLow: PlaybookColors.surface,
      surfaceContainer: PlaybookColors.surfaceMuted,
      surfaceContainerHigh: Color(0xFFE3E4EE),
      surfaceContainerHighest: PlaybookColors.border,
      onSurfaceVariant: PlaybookColors.dustyGrape,
      outline: Color(0xFF747A8D),
      outlineVariant: PlaybookColors.border,
      shadow: Color(0x14222222),
      scrim: Color(0x66222222),
      inverseSurface: PlaybookColors.carbon,
      onInverseSurface: Colors.white,
      inversePrimary: PlaybookColors.pearlAqua,
    );

    return _build(colorScheme, PlaybookHeatmapTheme.standard);
  }

  /// Tema oscuro alternativo apoyado en Carbon (`#222222`) y Pearl Aqua (`#84DCC6`).
  static ThemeData dark() {
    const colorScheme = ColorScheme(
      brightness: Brightness.dark,
      primary: PlaybookColors.pearlAqua,
      onPrimary: PlaybookColors.carbon,
      primaryContainer: PlaybookColors.dustyGrape,
      onPrimaryContainer: Colors.white,
      secondary: PlaybookColors.coolSteel,
      onSecondary: PlaybookColors.carbon,
      secondaryContainer: PlaybookColors.darkSurfaceElevated,
      onSecondaryContainer: PlaybookColors.pearlAqua,
      tertiary: PlaybookColors.coolSteel,
      onTertiary: PlaybookColors.carbon,
      tertiaryContainer: PlaybookColors.darkSurfaceElevated,
      onTertiaryContainer: Colors.white,
      error: Color(0xFFFFAFA7),
      onError: PlaybookColors.carbon,
      errorContainer: Color(0xFF7F1D1D),
      onErrorContainer: Color(0xFFFEE2E2),
      surface: PlaybookColors.darkSurface,
      onSurface: Colors.white,
      surfaceContainerLowest: PlaybookColors.darkSurface,
      surfaceContainerLow: PlaybookColors.darkSurface,
      surfaceContainer: PlaybookColors.darkSurfaceElevated,
      surfaceContainerHigh: Color(0xFF3D4052),
      surfaceContainerHighest: PlaybookColors.darkBorder,
      onSurfaceVariant: Color(0xFFA5B2C0),
      outline: PlaybookColors.coolSteel,
      outlineVariant: PlaybookColors.darkBorder,
      shadow: Colors.black,
      scrim: Colors.black87,
      inverseSurface: PlaybookColors.surface,
      onInverseSurface: PlaybookColors.carbon,
      inversePrimary: PlaybookColors.dustyGrape,
    );

    return _build(colorScheme, PlaybookHeatmapTheme.dark);
  }

  static Color success(ColorScheme colors) => colors.brightness == Brightness.dark
      ? PlaybookColors.pearlAqua
      : const Color(0xFF0E705B);

  static Color warning(ColorScheme colors) => colors.brightness == Brightness.dark
      ? const Color(0xFFF2C879)
      : const Color(0xFF8A5A00);

  static ThemeData _build(ColorScheme colors, PlaybookHeatmapTheme heatmap) {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: colors,
      fontFamily: 'Inter',
      scaffoldBackgroundColor: colors.surface,
      dividerColor: colors.outlineVariant,
    );
    TextStyle? heading(TextStyle? style) =>
        style?.copyWith(fontFamily: 'League Spartan', fontWeight: FontWeight.w800);

    return base.copyWith(
      textTheme: base.textTheme.copyWith(
        headlineSmall: heading(base.textTheme.headlineSmall),
        titleLarge: heading(base.textTheme.titleLarge),
        titleMedium: heading(base.textTheme.titleMedium),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: colors.surface,
        foregroundColor: colors.onSurface,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        surfaceTintColor: Colors.transparent,
      ),
      cardTheme: CardThemeData(
        color: colors.surfaceContainerLow,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: colors.outlineVariant),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(0, 48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: colors.primary,
          foregroundColor: colors.onPrimary,
          elevation: 0,
          minimumSize: const Size(0, 48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: colors.primary,
          side: BorderSide(color: colors.outline),
          minimumSize: const Size(0, 48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      inputDecorationTheme: InputDecorationThemeData(
        filled: true,
        fillColor: colors.surface,
        hintStyle: TextStyle(color: colors.onSurfaceVariant),
        labelStyle: TextStyle(color: colors.onSurfaceVariant),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: colors.outline),
        ),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: colors.primary, width: 2),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: colors.surfaceContainer,
        indicatorColor: colors.secondaryContainer,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return TextStyle(
            fontSize: 12,
            fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            color: selected ? colors.onSurface : colors.onSurfaceVariant,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          return IconThemeData(
            color: states.contains(WidgetState.selected)
                ? colors.onSecondaryContainer
                : colors.onSurfaceVariant,
          );
        }),
      ),
      navigationRailTheme: NavigationRailThemeData(
        backgroundColor: colors.surfaceContainer,
        indicatorColor: colors.secondaryContainer,
        selectedIconTheme: IconThemeData(color: colors.onSecondaryContainer),
        unselectedIconTheme: IconThemeData(color: colors.onSurfaceVariant),
        selectedLabelTextStyle: TextStyle(
          color: colors.onSurface,
          fontWeight: FontWeight.w700,
        ),
        unselectedLabelTextStyle: TextStyle(color: colors.onSurfaceVariant),
      ),
      dividerTheme: DividerThemeData(
        color: colors.outlineVariant,
        thickness: 1,
        space: 1,
      ),
      extensions: [heatmap],
    );
  }
}
