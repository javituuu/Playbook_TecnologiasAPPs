import 'dart:async';

import 'package:material_ui/material_ui.dart';

import '../../core/network/api_client.dart';
import '../../core/storage/token_storage.dart';
import '../../core/widgets/playbook_brand_mark.dart';
import '../../features/auth/domain/auth_user.dart';
import '../../features/auth/domain/equipo_dirigido.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/heatmap/data/heatmap_repository_impl.dart';
import '../../features/heatmap/data/mock_heatmap_data_source.dart';
import '../../features/heatmap/data/remote_heatmap_data_source.dart';
import '../../features/heatmap/presentation/heatmap_controller.dart';
import '../../features/heatmap/presentation/heatmap_screen.dart';
import '../../features/nomina/data/mock_nomina_data_source.dart';
import '../../features/nomina/data/nomina_repository_impl.dart';
import '../../features/nomina/data/remote_nomina_data_source.dart';
import '../../features/nomina/presentation/nomina_controller.dart';
import '../../features/nomina/presentation/nomina_screen.dart';
import '../../features/plantel/data/mock_plantel_data_source.dart';
import '../../features/plantel/data/plantel_repository_impl.dart';
import '../../features/plantel/data/remote_plantel_data_source.dart';
import '../../features/plantel/presentation/plantel_controller.dart';
import '../../features/plantel/presentation/plantel_screen.dart';
import '../../features/practicas/data/mock_practicas_data_source.dart';
import '../../features/practicas/data/practicas_repository_impl.dart';
import '../../features/practicas/data/remote_practicas_data_source.dart';
import '../../features/practicas/presentation/practicas_controller.dart';
import '../../features/practicas/presentation/practicas_screen.dart';
import '../theme/playbook_theme.dart';

/// Destinos principales de la aplicación del Coach.
enum CoachDestination {
  disponibilidad(
    label: 'Disponibilidad',
    icon: Icons.grid_on_outlined,
    selectedIcon: Icons.grid_on_rounded,
    subtitle: 'Matriz semanal de disponibilidad y quórum del plantel.',
  ),
  practicas(
    label: 'Prácticas',
    icon: Icons.event_note_outlined,
    selectedIcon: Icons.event_note_rounded,
    subtitle: 'Gestión de sesiones agendadas, horarios y cancelaciones.',
  ),
  nomina(
    label: 'Nómina',
    icon: Icons.assignment_ind_outlined,
    selectedIcon: Icons.assignment_ind_rounded,
    subtitle: 'Convocatorias oficiales y estado de respuesta del equipo.',
  ),
  plantel(
    label: 'Plantel',
    icon: Icons.groups_outlined,
    selectedIcon: Icons.groups_rounded,
    subtitle: 'Titulares, suplentes, roles y disponibilidad base.',
  );

  const CoachDestination({
    required this.label,
    required this.icon,
    required this.selectedIcon,
    required this.subtitle,
  });

  final String label;
  final IconData icon;
  final IconData selectedIcon;
  final String subtitle;
}

/// Shell principal responsivo del Coach con selector de equipo y navegación adaptativa.
///
/// Usa [NavigationRail] en tablet/desktop/web (`>= 600px`) y [NavigationBar] en móvil.
class CoachShell extends StatefulWidget {
  const CoachShell({
    super.key,
    required this.controller,
    this.apiClient,
    this.heatmapController,
    this.practicasController,
    this.nominaController,
    this.plantelController,
  });

  static const double tabletBreakpoint = 600;
  static const double desktopExtendedBreakpoint = 1024;

  final AuthController controller;
  final ApiClient? apiClient;
  final HeatmapController? heatmapController;
  final PracticasController? practicasController;
  final NominaController? nominaController;
  final PlantelController? plantelController;

  @override
  State<CoachShell> createState() => _CoachShellState();
}

class _CoachShellState extends State<CoachShell> {
  int _selectedIndex = 0;

  late final HeatmapController _heatmapController;
  late final PracticasController _practicasController;
  late final NominaController _nominaController;
  late final PlantelController _plantelController;
  late final bool _ownsHeatmapController;
  late final bool _ownsPracticasController;
  late final bool _ownsNominaController;
  late final bool _ownsPlantelController;
  ApiClient? _fallbackApiClient;
  int? _lastLoadedTeamId;

  @override
  void initState() {
    super.initState();
    final sharedApiClient = widget.apiClient ?? _createFallbackApiClient();

    if (widget.heatmapController != null) {
      _heatmapController = widget.heatmapController!;
      _ownsHeatmapController = false;
    } else {
      _ownsHeatmapController = true;
      _heatmapController = HeatmapController(
        repository: HeatmapRepositoryImpl(
          mockDataSource: MockHeatmapDataSource(),
          remoteDataSource: RemoteHeatmapDataSource(apiClient: sharedApiClient),
          isMockMode: () => widget.controller.isMockMode,
        ),
      );
    }

    if (widget.practicasController != null) {
      _practicasController = widget.practicasController!;
      _ownsPracticasController = false;
    } else {
      _ownsPracticasController = true;
      _practicasController = PracticasController(
        repository: PracticasRepositoryImpl(
          mockDataSource: MockPracticasDataSource(),
          remoteDataSource:
              RemotePracticasDataSource(apiClient: sharedApiClient),
          isMockMode: () => widget.controller.isMockMode,
        ),
      );
    }

    if (widget.nominaController != null) {
      _nominaController = widget.nominaController!;
      _ownsNominaController = false;
    } else {
      _ownsNominaController = true;
      _nominaController = NominaController(
        repository: NominaRepositoryImpl(
          mockDataSource: MockNominaDataSource(),
          remoteDataSource: RemoteNominaDataSource(apiClient: sharedApiClient),
          isMockMode: () => widget.controller.isMockMode,
        ),
      );
    }

    if (widget.plantelController != null) {
      _plantelController = widget.plantelController!;
      _ownsPlantelController = false;
    } else {
      _ownsPlantelController = true;
      _plantelController = PlantelController(
        repository: PlantelRepositoryImpl(
          mockDataSource: MockPlantelDataSource(),
          remoteDataSource: RemotePlantelDataSource(apiClient: sharedApiClient),
          isMockMode: () => widget.controller.isMockMode,
        ),
      );
    }

    widget.controller.addListener(_onAuthChanged);
    _syncTeamData();
  }

  ApiClient _createFallbackApiClient() {
    final client = ApiClient(
      tokenStorage: TokenStorage(),
      onUnauthorized: widget.controller.handleUnauthorized,
    );
    _fallbackApiClient = client;
    return client;
  }

  void _onAuthChanged() {
    _syncTeamData();
  }

  void _syncTeamData() {
    final teamId = widget.controller.selectedTeam?.id;
    if (teamId == null || teamId == _lastLoadedTeamId) {
      return;
    }
    _lastLoadedTeamId = teamId;
    unawaited(_heatmapController.loadHeatmap(equipoId: teamId));
    unawaited(_practicasController.loadPracticas(equipoId: teamId));
    unawaited(_nominaController.loadEventos(equipoId: teamId));
    unawaited(_plantelController.loadPlantel(equipoId: teamId));
  }

  @override
  void dispose() {
    widget.controller.removeListener(_onAuthChanged);
    if (_ownsHeatmapController) {
      _heatmapController.dispose();
    }
    if (_ownsPracticasController) {
      _practicasController.dispose();
    }
    if (_ownsNominaController) {
      _nominaController.dispose();
    }
    if (_ownsPlantelController) {
      _plantelController.dispose();
    }
    _fallbackApiClient?.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.controller,
      builder: (context, _) {
        final user = widget.controller.user;
        if (user == null) {
          return const SizedBox.shrink();
        }

        final equipos = widget.controller.equipos;
        final selectedTeam = widget.controller.selectedTeam;

        return LayoutBuilder(
          builder: (context, constraints) {
            final isWide =
                constraints.maxWidth >= CoachShell.tabletBreakpoint ||
                (constraints.maxWidth >= 560 && constraints.maxHeight < 440);
            final isExtended =
                constraints.maxWidth >= CoachShell.desktopExtendedBreakpoint;

            final useShortLabels =
                constraints.maxWidth < MediaQuery.textScalerOf(context).scale(12) * 30;
            final bodyContent = IndexedStack(
              index: _selectedIndex,
              children: <Widget>[
                HeatmapScreen(
                  heatmapController: _heatmapController,
                  practicasController: _practicasController,
                  equipo: selectedTeam,
                  onOpenPracticas: () {
                    setState(() {
                      _selectedIndex = 1;
                    });
                  },
                ),
                PracticasScreen(
                  controller: _practicasController,
                  equipo: selectedTeam,
                  onOpenHeatmap: () {
                    setState(() {
                      _selectedIndex = 0;
                    });
                  },
                ),
                NominaScreen(
                  controller: _nominaController,
                  equipo: selectedTeam,
                ),
                PlantelScreen(
                  controller: _plantelController,
                  equipo: selectedTeam,
                ),
              ],
            );

            if (isWide) {
              return Scaffold(
                body: SafeArea(
                  child: Column(
                    children: <Widget>[
                      _WideTopHeader(
                        availableWidth: constraints.maxWidth,
                        user: user,
                        equipos: equipos,
                        selectedTeam: selectedTeam,
                        onTeamSelected: widget.controller.selectTeam,
                        onLogout: widget.controller.logout,
                      ),
                      const Divider(height: 1),
                      Expanded(
                        child: Row(
                          children: <Widget>[
                            NavigationRail(
                              selectedIndex: _selectedIndex,
                              extended: isExtended,
                              minWidth: 76,
                              minExtendedWidth: 210,
                              scrollable: true,
                              onDestinationSelected: (index) {
                                setState(() {
                                  _selectedIndex = index;
                                });
                              },
                              destinations: <NavigationRailDestination>[
                                for (final item in CoachDestination.values)
                                  NavigationRailDestination(
                                    icon: Icon(item.icon),
                                    selectedIcon: Icon(item.selectedIcon),
                                    label: Text(item.label),
                                  ),
                              ],
                            ),
                            const VerticalDivider(width: 1),
                            Expanded(child: bodyContent),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }

            return Scaffold(
              appBar: AppBar(
                titleSpacing: 16,
                title: const Row(
                  children: <Widget>[
                    PlaybookBrandMark(size: 32),
                    SizedBox(width: 10),
                    Expanded(child: PlaybookCoachWordmark(fontSize: 22)),
                  ],
                ),
                actions: <Widget>[
                  _CoachAvatarBadge(user: user, radius: 15),
                  IconButton(
                    key: const Key('logout_button'),
                    tooltip: 'Cerrar sesión',
                    onPressed: widget.controller.logout,
                    icon: const Icon(Icons.logout_rounded),
                  ),
                  const SizedBox(width: 4),
                ],
                bottom: PreferredSize(
                  preferredSize: Size.fromHeight(
                    76 + MediaQuery.textScalerOf(context).scale(14) - 14,
                  ),
                  child: _MobileSessionAndTeamBar(
                    user: user,
                    equipos: equipos,
                    selectedTeam: selectedTeam,
                    onTeamSelected: widget.controller.selectTeam,
                  ),
                ),
              ),
              body: bodyContent,
              bottomNavigationBar: NavigationBar(
                selectedIndex: _selectedIndex,
                onDestinationSelected: (index) {
                  setState(() {
                    _selectedIndex = index;
                  });
                },
                destinations: <NavigationDestination>[
                  for (final item in CoachDestination.values)
                    NavigationDestination(
                      icon: Icon(item.icon),
                      selectedIcon: Icon(item.selectedIcon),
                      label: useShortLabels && item == CoachDestination.disponibilidad
                          ? 'Mapa'
                          : item.label,
                      tooltip: item.label,
                    ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}

class _WideTopHeader extends StatelessWidget {
  const _WideTopHeader({
    required this.availableWidth,
    required this.user,
    required this.equipos,
    required this.selectedTeam,
    required this.onTeamSelected,
    required this.onLogout,
  });

  final double availableWidth;
  final AuthUser user;
  final List<EquipoDirigido> equipos;
  final EquipoDirigido? selectedTeam;
  final ValueChanged<EquipoDirigido> onTeamSelected;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    final showFullTitleAndLogoutLabel = availableWidth >= 840;

    return Material(
      color: Theme.of(context).colorScheme.surface,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        child: Row(
          children: <Widget>[
            const PlaybookBrandMark(size: 36),
            if (showFullTitleAndLogoutLabel) ...<Widget>[
              const SizedBox(width: 12),
              const PlaybookCoachWordmark(fontSize: 24),
            ],
            const SizedBox(width: 14),
            ConstrainedBox(
              constraints: BoxConstraints(
                maxWidth: showFullTitleAndLogoutLabel ? 250 : 210,
              ),
              child: _TeamSelectorDropdown(
                equipos: equipos,
                selectedTeam: selectedTeam,
                onSelected: onTeamSelected,
                isExpanded: true,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Align(
                alignment: Alignment.centerRight,
                child: _ActiveSessionBadge(user: user),
              ),
            ),
            const SizedBox(width: 10),
            _CoachAvatarBadge(user: user, radius: 17),
            const SizedBox(width: 8),
            if (showFullTitleAndLogoutLabel)
              OutlinedButton.icon(
                key: const Key('logout_button'),
                onPressed: onLogout,
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size(0, 48),
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                ),
                icon: const Icon(Icons.logout_rounded, size: 18),
                label: const Text('Cerrar sesión'),
              )
            else
              IconButton(
                key: const Key('logout_button'),
                tooltip: 'Cerrar sesión',
                onPressed: onLogout,
                icon: const Icon(Icons.logout_rounded),
              ),
          ],
        ),
      ),
    );
  }
}

class _MobileSessionAndTeamBar extends StatelessWidget {
  const _MobileSessionAndTeamBar({
    required this.user,
    required this.equipos,
    required this.selectedTeam,
    required this.onTeamSelected,
  });

  final AuthUser user;
  final List<EquipoDirigido> equipos;
  final EquipoDirigido? selectedTeam;
  final ValueChanged<EquipoDirigido> onTeamSelected;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
      decoration: BoxDecoration(
        color: colors.surface,
        border: Border(bottom: BorderSide(color: colors.outlineVariant)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: <Widget>[
          _ActiveSessionBadge(user: user),
          const SizedBox(height: 8),
          _TeamSelectorDropdown(
            equipos: equipos,
            selectedTeam: selectedTeam,
            onSelected: onTeamSelected,
            isExpanded: true,
          ),
        ],
      ),
    );
  }
}

class _TeamSelectorDropdown extends StatelessWidget {
  const _TeamSelectorDropdown({
    required this.equipos,
    required this.selectedTeam,
    required this.onSelected,
    this.isExpanded = false,
  });

  final List<EquipoDirigido> equipos;
  final EquipoDirigido? selectedTeam;
  final ValueChanged<EquipoDirigido> onSelected;
  final bool isExpanded;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    if (equipos.isEmpty) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: colors.surfaceContainer,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: colors.outlineVariant),
        ),
        child: Text(
          'Sin equipos asignados',
          style: TextStyle(color: colors.onSurfaceVariant, fontSize: 13),
        ),
      );
    }

    final current = selectedTeam ?? equipos.first;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      constraints: const BoxConstraints(minHeight: 48),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: colors.outlineVariant),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<int>(
          key: const Key('team_selector_dropdown'),
          value: current.id,
          isExpanded: isExpanded,
          isDense: true,
          borderRadius: BorderRadius.circular(12),
          icon: Icon(Icons.keyboard_arrow_down_rounded, color: colors.primary),
          style: TextStyle(
            color: colors.onSurface,
            fontWeight: FontWeight.w600,
            fontSize: 13.5,
          ),
          onChanged: (selectedId) {
            if (selectedId == null) return;
            for (final team in equipos) {
              if (team.id == selectedId) {
                onSelected(team);
                break;
              }
            }
          },
          items: <DropdownMenuItem<int>>[
            for (final team in equipos)
              DropdownMenuItem<int>(
                value: team.id,
                child: Text(
                  team.displayName,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _ActiveSessionBadge extends StatelessWidget {
  const _ActiveSessionBadge({required this.user});

  final AuthUser user;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        Container(
          width: 8,
          height: 8,
          decoration: const BoxDecoration(
            color: PlaybookTheme.pearlAqua,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 8),
        Flexible(
          child: Text(
            'Sesión activa: ${user.name}',
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Theme.of(context).colorScheme.primary,
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }
}

class _CoachAvatarBadge extends StatelessWidget {
  const _CoachAvatarBadge({
    required this.user,
    required this.radius,
  });

  final AuthUser user;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return CircleAvatar(
      radius: radius,
      backgroundColor: colors.primary,
      foregroundColor: colors.onPrimary,
      child: Text(
        user.initials,
        style: TextStyle(
          fontSize: radius * 0.72,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
