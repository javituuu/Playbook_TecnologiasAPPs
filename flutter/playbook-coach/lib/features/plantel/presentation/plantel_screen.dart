import 'package:material_ui/material_ui.dart';

import '../../../app/theme/playbook_theme.dart';
import '../../auth/domain/equipo_dirigido.dart';
import '../domain/miembro_plantel.dart';
import '../domain/plantel_equipo.dart';
import '../domain/posicion_cobertura.dart';
import '../domain/rol_membresia.dart';
import 'plantel_controller.dart';

/// Pantalla de gestión del plantel agrupado por rol y cobertura de posiciones.
class PlantelScreen extends StatelessWidget {
  const PlantelScreen({
    super.key,
    required this.controller,
    required this.equipo,
  });

  final PlantelController controller;
  final EquipoDirigido? equipo;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: controller,
      builder: (context, _) {
        final selectedEquipo = equipo;
        if (selectedEquipo == null) {
          return const Center(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Text(
                'Selecciona un equipo para revisar su plantel y cobertura.',
              ),
            ),
          );
        }

        final plantel = controller.plantel;

        return LayoutBuilder(
          builder: (context, constraints) {
            final isWide = constraints.maxWidth >= 900;

            return RefreshIndicator(
              onRefresh: controller.refresh,
              child: ListView(
                padding: EdgeInsets.symmetric(
                  horizontal: isWide ? 24 : 16,
                  vertical: 16,
                ),
                children: <Widget>[
                  _PlantelHeader(
                    equipo: selectedEquipo,
                    filtroRol: controller.filtroRol,
                    isLoading: controller.isLoading,
                    onSelectRol: controller.setFiltroRol,
                    onRefresh: controller.refresh,
                  ),
                  if (controller.errorMessage != null) ...<Widget>[
                    const SizedBox(height: 12),
                    MaterialBanner(
                      backgroundColor:
                          Theme.of(context).colorScheme.error.withValues(
                                alpha: 0.10,
                              ),
                      content: Text(controller.errorMessage!),
                      actions: <Widget>[
                        TextButton(
                          onPressed: controller.clearError,
                          child: const Text('Cerrar'),
                        ),
                      ],
                    ),
                  ],
                  const SizedBox(height: 16),
                  if (controller.isLoading && plantel == null)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 48),
                      child: Center(child: CircularProgressIndicator()),
                    )
                  else if (plantel == null || plantel.miembros.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 48),
                      child: Center(
                        child: Text(
                          'No hay integrantes registrados en este plantel.',
                        ),
                      ),
                    )
                  else ...<Widget>[
                    _CoberturaCard(plantel: plantel),
                    const SizedBox(height: 20),
                    ...controller.visibleRoles.map(
                      (rol) => Padding(
                        padding: const EdgeInsets.only(bottom: 20),
                        child: _RolGroupSection(
                          rol: rol,
                          miembros: controller.miembrosPorRol(rol),
                          isWide: isWide,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            );
          },
        );
      },
    );
  }
}

class _PlantelHeader extends StatelessWidget {
  const _PlantelHeader({
    required this.equipo,
    required this.filtroRol,
    required this.isLoading,
    required this.onSelectRol,
    required this.onRefresh,
  });

  final EquipoDirigido equipo;
  final RolMembresia? filtroRol;
  final bool isLoading;
  final ValueChanged<RolMembresia?> onSelectRol;
  final VoidCallback onRefresh;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Wrap(
      spacing: 12,
      runSpacing: 12,
      alignment: WrapAlignment.spaceBetween,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: <Widget>[
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Plantel · ${equipo.nombre}',
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              '${equipo.disciplina} · Titulares, suplentes, jugadores en prueba y cobertura de roles.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: <Widget>[
            FilterChip(
              label: const Text('Todos'),
              selected: filtroRol == null,
              onSelected: (_) => onSelectRol(null),
            ),
            ...RolMembresia.values.map(
              (rol) => FilterChip(
                label: Text(rol.pluralLabel),
                selected: filtroRol == rol,
                onSelected: (_) => onSelectRol(rol),
              ),
            ),
            IconButton(
              tooltip: 'Actualizar plantel',
              onPressed: isLoading ? null : onRefresh,
              icon: const Icon(Icons.refresh_rounded),
            ),
          ],
        ),
      ],
    );
  }
}

class _CoberturaCard extends StatelessWidget {
  const _CoberturaCard({required this.plantel});

  final PlantelEquipo plantel;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final coberturas = plantel.coberturas;
    final sinTitular = plantel.posicionesSinTitular;

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: BorderSide(color: theme.colorScheme.outlineVariant),
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Wrap(
              spacing: 12,
              runSpacing: 8,
              alignment: WrapAlignment.spaceBetween,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: <Widget>[
                Text(
                  'Cobertura de posiciones',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: sinTitular.isEmpty
                        ? PlaybookColors.pearlAqua.withValues(alpha: 0.25)
                        : theme.colorScheme.error.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    '${plantel.posicionesCubiertasCount}/${plantel.posicionesRequeridas.length} posiciones cubiertas',
                    style: theme.textTheme.labelMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: sinTitular.isEmpty
                          ? Theme.of(context).colorScheme.onSurface
                          : theme.colorScheme.error,
                    ),
                  ),
                ),
              ],
            ),
            if (sinTitular.isNotEmpty) ...<Widget>[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: theme.colorScheme.error.withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: <Widget>[
                    Icon(
                      Icons.warning_amber_rounded,
                      size: 18,
                      color: theme.colorScheme.error,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Falta titular asignado en: ${sinTitular.join(', ')}.',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.error,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 14),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: coberturas
                  .map((cobertura) => _PosicionCoberturaChip(
                        cobertura: cobertura,
                      ))
                  .toList(growable: false),
            ),
          ],
        ),
      ),
    );
  }
}

class _PosicionCoberturaChip extends StatelessWidget {
  const _PosicionCoberturaChip({required this.cobertura});

  final PosicionCobertura cobertura;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isCovered = cobertura.estaCubierta;
    final bg = isCovered
        ? PlaybookColors.pearlAqua.withValues(alpha: 0.16)
        : theme.colorScheme.error.withValues(alpha: 0.10);
    final border = isCovered
        ? PlaybookColors.pearlAqua
        : theme.colorScheme.error.withValues(alpha: 0.45);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Row(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              Icon(
                isCovered
                    ? Icons.check_circle_outline_rounded
                    : Icons.error_outline_rounded,
                size: 16,
                color: isCovered
                    ? Theme.of(context).colorScheme.onSurface
                    : theme.colorScheme.error,
              ),
              const SizedBox(width: 6),
              Text(
                cobertura.posicion,
                style: theme.textTheme.labelLarge?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            '${cobertura.titularesPrincipales} titular · ${cobertura.respaldoSecundarioOSuplente} respaldo',
            style: theme.textTheme.labelSmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }
}

class _RolGroupSection extends StatelessWidget {
  const _RolGroupSection({
    required this.rol,
    required this.miembros,
    required this.isWide,
  });

  final RolMembresia rol;
  final List<MiembroPlantel> miembros;
  final bool isWide;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Row(
          children: <Widget>[
            Text(
              '${rol.pluralLabel} (${miembros.length})',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        if (miembros.isEmpty)
          Card(
            elevation: 0,
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Text(
                'Sin integrantes en categoría ${rol.pluralLabel}.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ),
          )
        else
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: miembros
                .map(
                  (miembro) => SizedBox(
                    width: isWide ? 360 : double.infinity,
                    child: _MiembroPlantelCard(miembro: miembro),
                  ),
                )
                .toList(growable: false),
          ),
      ],
    );
  }
}

class _MiembroPlantelCard extends StatelessWidget {
  const _MiembroPlantelCard({required this.miembro});

  final MiembroPlantel miembro;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: theme.colorScheme.outlineVariant),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Wrap(
              spacing: 8,
              runSpacing: 6,
              alignment: WrapAlignment.spaceBetween,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: <Widget>[
                Text(
                  miembro.displayName,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (miembro.esCapitan)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: PlaybookColors.pearlAqua.withValues(alpha: 0.28),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      'Capitán',
                      style: theme.textTheme.labelSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: theme.colorScheme.onSurface,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: <Widget>[
                _InfoTag(
                  icon: Icons.sports_esports_outlined,
                  label: 'Principal: ${miembro.posicionPrincipal}',
                  backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.12),
                  foregroundColor: theme.colorScheme.primary,
                ),
                if (miembro.posicionSecundaria != null)
                  _InfoTag(
                    icon: Icons.swap_calls_rounded,
                    label: 'Secundaria: ${miembro.posicionSecundaria}',
                    backgroundColor: PlaybookColors.coolSteel.withValues(
                      alpha: 0.20,
                    ),
                    foregroundColor: theme.colorScheme.onSurface,
                  ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: <Widget>[
                Icon(
                  Icons.military_tech_outlined,
                  size: 16,
                  color: theme.colorScheme.onSurfaceVariant,
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'Rango: ${miembro.rango}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                Text(
                  miembro.rolMembresia.singularLabel,
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoTag extends StatelessWidget {
  const _InfoTag({
    required this.icon,
    required this.label,
    required this.backgroundColor,
    required this.foregroundColor,
  });

  final IconData icon;
  final String label;
  final Color backgroundColor;
  final Color foregroundColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Icon(icon, size: 14, color: foregroundColor),
          const SizedBox(width: 4),
          Text(
            label,
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: foregroundColor,
                  fontWeight: FontWeight.w700,
                ),
          ),
        ],
      ),
    );
  }
}
