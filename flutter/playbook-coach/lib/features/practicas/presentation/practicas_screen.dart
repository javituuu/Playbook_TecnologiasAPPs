import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';
import '../../../../core/utils/week_utils.dart';
import '../../auth/domain/equipo_dirigido.dart';
import '../domain/practica.dart';
import 'practicas_controller.dart';
import 'widgets/cancelar_practica_dialog.dart';

enum _PracticasFilter { todas, programadas, canceladas }

/// Listado de prácticas programadas con filtros y flujo de cancelación.
class PracticasScreen extends StatefulWidget {
  const PracticasScreen({
    super.key,
    required this.controller,
    required this.equipo,
    this.onOpenHeatmap,
  });

  final PracticasController controller;
  final EquipoDirigido? equipo;
  final VoidCallback? onOpenHeatmap;

  @override
  State<PracticasScreen> createState() => _PracticasScreenState();
}

class _PracticasScreenState extends State<PracticasScreen> {
  _PracticasFilter _filter = _PracticasFilter.todas;

  Future<void> _handleCancelar(Practica practica) async {
    final ok = await showCancelarPracticaDialog(
      context: context,
      practica: practica,
      controller: widget.controller,
    );
    if (!mounted || !ok) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Práctica vs ${practica.rival} cancelada.'),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.controller,
      builder: (context, _) {
        final controller = widget.controller;
        final items = switch (_filter) {
          _PracticasFilter.todas => controller.practicas,
          _PracticasFilter.programadas => controller.programadas,
          _PracticasFilter.canceladas => controller.canceladas,
        };

        return RefreshIndicator(
          onRefresh: () async {
            final equipoId = widget.equipo?.id;
            if (equipoId != null) {
              await controller.loadPracticas(equipoId: equipoId);
            }
          },
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: <Widget>[
              Wrap(
                alignment: WrapAlignment.spaceBetween,
                crossAxisAlignment: WrapCrossAlignment.center,
                spacing: 12,
                runSpacing: 10,
                children: <Widget>[
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      const Text(
                        'Prácticas Programadas',
                        style: TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                          color: PlaybookTheme.carbon,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        widget.equipo != null
                            ? '${widget.equipo!.displayName} · ${controller.programadas.length} activas, ${controller.canceladas.length} canceladas'
                            : 'Selecciona un equipo para gestionar sus prácticas',
                        style: const TextStyle(
                          fontSize: 13.5,
                          color: PlaybookTheme.coolSteel,
                        ),
                      ),
                    ],
                  ),
                  if (widget.onOpenHeatmap != null)
                    OutlinedButton.icon(
                      onPressed: widget.onOpenHeatmap,
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(0, 40),
                      ),
                      icon: const Icon(Icons.grid_on_rounded, size: 18),
                      label: const Text('Agendar en Heatmap'),
                    ),
                ],
              ),
              const SizedBox(height: 16),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: <Widget>[
                  ChoiceChip(
                    label: Text('Todas (${controller.practicas.length})'),
                    selected: _filter == _PracticasFilter.todas,
                    onSelected: (_) {
                      setState(() => _filter = _PracticasFilter.todas);
                    },
                  ),
                  ChoiceChip(
                    label: Text(
                      'Programadas (${controller.programadas.length})',
                    ),
                    selected: _filter == _PracticasFilter.programadas,
                    onSelected: (_) {
                      setState(() => _filter = _PracticasFilter.programadas);
                    },
                  ),
                  ChoiceChip(
                    label: Text(
                      'Canceladas (${controller.canceladas.length})',
                    ),
                    selected: _filter == _PracticasFilter.canceladas,
                    onSelected: (_) {
                      setState(() => _filter = _PracticasFilter.canceladas);
                    },
                  ),
                ],
              ),
              const SizedBox(height: 16),
              if (controller.errorMessage != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: PlaybookTheme.heatmap0.background,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: PlaybookTheme.heatmap0.border),
                    ),
                    child: Text(
                      controller.errorMessage!,
                      style: TextStyle(
                        color: PlaybookTheme.heatmap0.foreground,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              if (controller.isLoading && controller.practicas.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 48),
                  child: Center(child: CircularProgressIndicator()),
                )
              else if (items.isEmpty)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      children: <Widget>[
                        const Icon(
                          Icons.event_busy_outlined,
                          size: 40,
                          color: PlaybookTheme.coolSteel,
                        ),
                        const SizedBox(height: 10),
                        const Text(
                          'No hay prácticas en esta vista.',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: PlaybookTheme.carbon,
                          ),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Selecciona un bloque con quórum (>= 4/5) en el heatmap para agendar una nueva práctica.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13,
                            color: PlaybookTheme.coolSteel,
                          ),
                        ),
                        if (widget.onOpenHeatmap != null) ...<Widget>[
                          const SizedBox(height: 14),
                          FilledButton.icon(
                            onPressed: widget.onOpenHeatmap,
                            icon: const Icon(Icons.calendar_view_week_rounded),
                            label: const Text('Ir a Disponibilidad'),
                          ),
                        ],
                      ],
                    ),
                  ),
                )
              else
                for (var i = 0; i < items.length; i++) ...<Widget>[
                  _PracticaCard(
                    practica: items[i],
                    isSubmitting: controller.isSubmitting,
                    onCancelar: items[i].estaProgramada
                        ? () => _handleCancelar(items[i])
                        : null,
                  ),
                  if (i < items.length - 1) const SizedBox(height: 12),
                ],
            ],
          ),
        );
      },
    );
  }
}

class _PracticaCard extends StatelessWidget {
  const _PracticaCard({
    required this.practica,
    required this.isSubmitting,
    this.onCancelar,
  });

  final Practica practica;
  final bool isSubmitting;
  final VoidCallback? onCancelar;

  @override
  Widget build(BuildContext context) {
    final isProgramada = practica.estaProgramada;
    final statusColor = isProgramada
        ? PlaybookTheme.heatmap5.foreground
        : PlaybookTheme.heatmap0.foreground;
    final statusBg = isProgramada
        ? PlaybookTheme.heatmap5.background
        : PlaybookTheme.heatmap0.background;

    return Card(
      key: Key('practica_card_${practica.id}'),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final isCompact = constraints.maxWidth < 460;

            final headerInfo = Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: <Widget>[
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: statusBg,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        practica.estado.label.toUpperCase(),
                        style: TextStyle(
                          color: statusColor,
                          fontSize: 10.5,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.4,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: PlaybookColors.surfaceMuted,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        practica.serie.label,
                        style: const TextStyle(
                          color: PlaybookTheme.dustyGrape,
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    Text(
                      practica.semanaIso,
                      style: const TextStyle(
                        fontSize: 11.5,
                        color: PlaybookTheme.coolSteel,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'vs ${practica.rival}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: PlaybookTheme.carbon,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  '${practica.horarioResumen} · ${WeekUtils.formatShortDate(practica.fecha)}',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: PlaybookTheme.dustyGrape,
                  ),
                ),
              ],
            );

            final cancelButton = onCancelar == null
                ? null
                : OutlinedButton.icon(
                    key: Key('cancelar_practica_${practica.id}'),
                    onPressed: isSubmitting ? null : onCancelar,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: PlaybookColors.error,
                      side: const BorderSide(color: Color(0xFFFECACA)),
                      minimumSize: const Size(0, 38),
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                    ),
                    icon: const Icon(Icons.cancel_outlined, size: 17),
                    label: const Text('Cancelar práctica'),
                  );

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                if (isCompact) ...<Widget>[
                  headerInfo,
                ] else
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Expanded(child: headerInfo),
                      if (cancelButton != null) ...<Widget>[
                        const SizedBox(width: 12),
                        cancelButton,
                      ],
                    ],
                  ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 16,
                  runSpacing: 6,
                  children: <Widget>[
                    _MetaItem(
                      icon: Icons.sports_esports_outlined,
                      text: practica.modalidad,
                    ),
                    _MetaItem(
                      icon: Icons.headset_mic_outlined,
                      text: practica.servidor,
                    ),
                    _MetaItem(
                      icon: Icons.groups_outlined,
                      text: '${practica.citadosCount} citados',
                    ),
                  ],
                ),
                if (practica.notas != null) ...<Widget>[
                  const SizedBox(height: 10),
                  Text(
                    practica.notas!,
                    style: const TextStyle(
                      fontSize: 12.5,
                      color: PlaybookTheme.coolSteel,
                    ),
                  ),
                ],
                if (practica.estaCancelada &&
                    practica.motivoCancelacion != null) ...<Widget>[
                  const SizedBox(height: 10),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: PlaybookTheme.heatmap0.background,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'Motivo: ${practica.motivoCancelacion}',
                      style: TextStyle(
                        fontSize: 12,
                        color: PlaybookTheme.heatmap0.foreground,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
                if (isCompact && cancelButton != null) ...<Widget>[
                  const SizedBox(height: 12),
                  Align(
                    alignment: Alignment.centerRight,
                    child: cancelButton,
                  ),
                ],
              ],
            );
          },
        ),
      ),
    );
  }
}

class _MetaItem extends StatelessWidget {
  const _MetaItem({
    required this.icon,
    required this.text,
  });

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        Icon(icon, size: 15, color: PlaybookTheme.coolSteel),
        const SizedBox(width: 5),
        Flexible(
          child: Text(
            text,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 12.5,
              color: PlaybookTheme.carbon,
            ),
          ),
        ),
      ],
    );
  }
}
