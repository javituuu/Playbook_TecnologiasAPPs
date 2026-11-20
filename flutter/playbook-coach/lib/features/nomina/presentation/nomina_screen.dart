import 'package:material_ui/material_ui.dart';

import '../../../app/theme/playbook_theme.dart';
import '../../auth/domain/equipo_dirigido.dart';
import '../domain/citacion_jugador.dart';
import '../domain/estado_citacion.dart';
import '../domain/evento_nomina.dart';
import 'nomina_controller.dart';
import 'widgets/intercambiar_nomina_dialog.dart';

/// Pantalla de gestión de partidos, prácticas, citaciones e intercambio de nómina.
class NominaScreen extends StatelessWidget {
  const NominaScreen({
    super.key,
    required this.controller,
    required this.equipo,
  });

  static const double masterDetailBreakpoint = 920;

  final NominaController controller;
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
                'Selecciona un equipo para gestionar sus nóminas y citaciones.',
              ),
            ),
          );
        }

        final eventos = controller.filteredEventos;
        final selectedEvento = controller.selectedEvento;

        return LayoutBuilder(
          builder: (context, constraints) {
            final isWide = constraints.maxWidth >= masterDetailBreakpoint;

            return RefreshIndicator(
              onRefresh: controller.refresh,
              child: Padding(
                padding: EdgeInsets.symmetric(
                  horizontal: isWide ? 24 : 16,
                  vertical: 16,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: <Widget>[
                    _NominaHeader(
                      equipo: selectedEquipo,
                      filtroTipo: controller.filtroTipo,
                      isLoading: controller.isLoading,
                      onSelectFiltro: controller.setFiltroTipo,
                      onRefresh: controller.refresh,
                    ),
                    if (controller.errorMessage != null) ...<Widget>[
                      const SizedBox(height: 12),
                      _ErrorBanner(
                        message: controller.errorMessage!,
                        onDismiss: controller.clearError,
                      ),
                    ],
                    const SizedBox(height: 16),
                    Expanded(
                      child: controller.isLoading && controller.eventos.isEmpty
                          ? const Center(child: CircularProgressIndicator())
                          : eventos.isEmpty
                              ? _EmptyNominaState(
                                  filtroTipo: controller.filtroTipo,
                                  onResetFiltro: () =>
                                      controller.setFiltroTipo(null),
                                )
                              : isWide
                                  ? Row(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: <Widget>[
                                        SizedBox(
                                          width: 370,
                                          child: _EventosList(
                                            eventos: eventos,
                                            selectedEventoId:
                                                selectedEvento?.id,
                                            onSelectEvento: (evento) =>
                                                controller.selectEvento(
                                              evento.id,
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 20),
                                        Expanded(
                                          child: selectedEvento == null
                                              ? const SizedBox.shrink()
                                              : _NominaDetailPanel(
                                                  evento: selectedEvento,
                                                  isSubmitting:
                                                      controller.isSubmitting,
                                                  onIntercambiar: (
                                                    evento, {
                                                    titularId,
                                                    suplenteId,
                                                  }) =>
                                                      _openSwapDialog(
                                                    context,
                                                    evento,
                                                    initialTitularId: titularId,
                                                    initialSuplenteId:
                                                        suplenteId,
                                                  ),
                                          onCambiarEstado:
                                              (eventoId, jugadorId, estado) =>
                                                  controller
                                                      .actualizarEstadoCitacion(
                                                        eventoId: eventoId,
                                                        jugadorId: jugadorId,
                                                        estado: estado,
                                                      ),
                                        ),
                                ),
                              ],
                            )
                          : _EventosList(
                              eventos: eventos,
                              selectedEventoId: selectedEvento?.id,
                              onSelectEvento: (evento) {
                                controller.selectEvento(evento.id);
                                _openDetailBottomSheet(context, evento);
                              },
                            ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _openDetailBottomSheet(
    BuildContext context,
    EventoNomina evento,
  ) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (sheetContext) {
        return ListenableBuilder(
          listenable: controller,
          builder: (context, _) {
            final current = controller.eventos.firstWhere(
              (item) => item.id == evento.id,
              orElse: () => evento,
            );
            return DraggableScrollableSheet(
              expand: false,
              initialChildSize: 0.88,
              minChildSize: 0.55,
              maxChildSize: 0.96,
              builder: (context, scrollController) {
                return Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
                  child: _NominaDetailPanel(
                    evento: current,
                    scrollController: scrollController,
                    isSubmitting: controller.isSubmitting,
                    onIntercambiar: (
                      targetEvento, {
                      titularId,
                      suplenteId,
                    }) =>
                        _openSwapDialog(
                      context,
                      targetEvento,
                      initialTitularId: titularId,
                      initialSuplenteId: suplenteId,
                    ),
                    onCambiarEstado: (eventoId, jugadorId, estado) =>
                        controller.actualizarEstadoCitacion(
                      eventoId: eventoId,
                      jugadorId: jugadorId,
                      estado: estado,
                    ),
                  ),
                );
              },
            );
          },
        );
      },
    );
  }

  Future<void> _openSwapDialog(
    BuildContext context,
    EventoNomina evento, {
    int? initialTitularId,
    int? initialSuplenteId,
  }) async {
    final result =
        await showDialog<({int titularId, int suplenteId})>(
      context: context,
      builder: (_) => IntercambiarNominaDialog(
        evento: evento,
        initialTitularId: initialTitularId,
        initialSuplenteId: initialSuplenteId,
      ),
    );

    if (result == null || !context.mounted) {
      return;
    }

    final updated = await controller.intercambiarTitularSuplente(
      eventoId: evento.id,
      titularId: result.titularId,
      suplenteId: result.suplenteId,
    );

    if (updated != null && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Nómina actualizada: intercambio aplicado.'),
        ),
      );
    }
  }
}

class _NominaHeader extends StatelessWidget {
  const _NominaHeader({
    required this.equipo,
    required this.filtroTipo,
    required this.isLoading,
    required this.onSelectFiltro,
    required this.onRefresh,
  });

  final EquipoDirigido equipo;
  final TipoEventoNomina? filtroTipo;
  final bool isLoading;
  final ValueChanged<TipoEventoNomina?> onSelectFiltro;
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
              'Nómina y Citaciones · ${equipo.nombre}',
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Revisa confirmaciones, detecta cruces horarios e intercambia titulares con suplentes.',
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
              selected: filtroTipo == null,
              onSelected: (_) => onSelectFiltro(null),
            ),
            FilterChip(
              label: const Text('Partidos'),
              selected: filtroTipo == TipoEventoNomina.partido,
              onSelected: (_) => onSelectFiltro(TipoEventoNomina.partido),
            ),
            FilterChip(
              label: const Text('Prácticas'),
              selected: filtroTipo == TipoEventoNomina.practica,
              onSelected: (_) => onSelectFiltro(TipoEventoNomina.practica),
            ),
            IconButton(
              tooltip: 'Actualizar nóminas',
              onPressed: isLoading ? null : onRefresh,
              icon: const Icon(Icons.refresh_rounded),
            ),
          ],
        ),
      ],
    );
  }
}

class _EventosList extends StatelessWidget {
  const _EventosList({
    required this.eventos,
    required this.selectedEventoId,
    required this.onSelectEvento,
  });

  final List<EventoNomina> eventos;
  final int? selectedEventoId;
  final ValueChanged<EventoNomina> onSelectEvento;

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      itemCount: eventos.length,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final evento = eventos[index];
        final isSelected = evento.id == selectedEventoId;
        return _EventoCard(
          evento: evento,
          selected: isSelected,
          onTap: () => onSelectEvento(evento),
        );
      },
    );
  }
}

class _EventoCard extends StatelessWidget {
  const _EventoCard({
    required this.evento,
    required this.selected,
    required this.onTap,
  });

  final EventoNomina evento;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isPartido = evento.tipo == TipoEventoNomina.partido;

    return Card(
      elevation: 0,
      color: selected
          ? PlaybookColors.pearlAqua.withValues(alpha: 0.14)
          : theme.colorScheme.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: selected
              ? theme.colorScheme.primary
              : theme.colorScheme.outlineVariant,
          width: selected ? 1.6 : 1,
        ),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
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
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: isPartido
                          ? theme.colorScheme.primary.withValues(alpha: 0.14)
                          : PlaybookColors.pearlAqua.withValues(alpha: 0.25),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      evento.tipo.label,
                      style: theme.textTheme.labelSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: isPartido
                            ? theme.colorScheme.primary
                            : theme.colorScheme.onSurface,
                      ),
                    ),
                  ),
                  if (evento.tieneConflictosEnTitulares)
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.error.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: <Widget>[
                          Icon(
                            Icons.warning_amber_rounded,
                            size: 14,
                            color: theme.colorScheme.error,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '${evento.titularesConflictosCount} conflicto',
                            style: theme.textTheme.labelSmall?.copyWith(
                              color: theme.colorScheme.error,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                evento.titulo,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                evento.ligaOCompetencia,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                '${_formatFecha(evento.fecha)} · ${evento.horaInicio}–${evento.horaFin}',
                style: theme.textTheme.bodySmall?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: <Widget>[
                  _CountPill(
                    label: '${evento.confirmadosCount} confirmados',
                    color: PlaybookTheme.success(theme.colorScheme)
                        .withValues(alpha: 0.12),
                    textColor: PlaybookTheme.success(theme.colorScheme),
                  ),
                  _CountPill(
                    label: '${evento.pendientesCount} pendientes',
                    color: PlaybookTheme.warning(theme.colorScheme)
                        .withValues(alpha: 0.10),
                    textColor: PlaybookTheme.warning(theme.colorScheme),
                  ),
                  if (evento.rechazadosCount > 0)
                    _CountPill(
                      label: '${evento.rechazadosCount} rechazados',
                      color: theme.colorScheme.error.withValues(alpha: 0.14),
                      textColor: theme.colorScheme.error,
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NominaDetailPanel extends StatelessWidget {
  const _NominaDetailPanel({
    required this.evento,
    required this.isSubmitting,
    required this.onIntercambiar,
    required this.onCambiarEstado,
    this.scrollController,
  });

  final EventoNomina evento;
  final bool isSubmitting;
  final Future<void> Function(
    EventoNomina evento, {
    int? titularId,
    int? suplenteId,
  }) onIntercambiar;
  final Future<void> Function(
    int eventoId,
    int jugadorId,
    EstadoCitacion estado,
  ) onCambiarEstado;
  final ScrollController? scrollController;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final titulares = evento.titulares;
    final suplentes = evento.suplentes;
    final canSwap = titulares.isNotEmpty && suplentes.isNotEmpty && !isSubmitting;

    CitacionJugador? firstConflictTitular;
    for (final titular in titulares) {
      if (titular.tieneConflicto) {
        firstConflictTitular = titular;
        break;
      }
    }

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: BorderSide(color: theme.colorScheme.outlineVariant),
      ),
      child: ListView(
        controller: scrollController,
        padding: const EdgeInsets.all(20),
        children: <Widget>[
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      evento.titulo,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${evento.ligaOCompetencia} · ${evento.lugarOServidor}',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      '${_formatFecha(evento.fecha)} · ${evento.horaInicio}–${evento.horaFin}',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              FilledButton.icon(
                onPressed: canSwap
                    ? () => onIntercambiar(
                          evento,
                          titularId: firstConflictTitular?.jugadorId,
                        )
                    : null,
                icon: const Icon(Icons.swap_horiz_rounded),
                label: const Text('Editar nómina'),
              ),
            ],
          ),
          if (evento.mensajeCoach != null) ...<Widget>[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: PlaybookColors.coolSteel.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: <Widget>[
                  const Icon(Icons.campaign_outlined, size: 18),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      evento.mensajeCoach!,
                      style: theme.textTheme.bodySmall,
                    ),
                  ),
                ],
              ),
            ),
          ],
          if (firstConflictTitular != null) ...<Widget>[
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: theme.colorScheme.error.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: theme.colorScheme.error.withValues(alpha: 0.35),
                ),
              ),
              child: Row(
                children: <Widget>[
                  Icon(
                    Icons.warning_amber_rounded,
                    color: theme.colorScheme.error,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Conflicto en titulares: ${firstConflictTitular.displayName} (${firstConflictTitular.conflictoHorario ?? firstConflictTitular.motivoRespuesta ?? 'No disponible'}).',
                      style: theme.textTheme.bodySmall?.copyWith(
                        fontWeight: FontWeight.w600,
                        color: theme.colorScheme.error,
                      ),
                    ),
                  ),
                  if (suplentes.isNotEmpty) ...<Widget>[
                    const SizedBox(width: 8),
                    TextButton(
                      onPressed: isSubmitting
                          ? null
                          : () => onIntercambiar(
                                evento,
                                titularId: firstConflictTitular!.jugadorId,
                              ),
                      child: const Text('Resolver'),
                    ),
                  ],
                ],
              ),
            ),
          ],
          const SizedBox(height: 18),
          Text(
            'Titulares (${titulares.length})',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 10),
          ...titulares.map(
            (jugador) => _CitacionRow(
              jugador: jugador,
              canSwap: canSwap,
              isSubmitting: isSubmitting,
              swapLabel: 'Intercambiar',
              onSwapPressed: () => onIntercambiar(
                evento,
                titularId: jugador.jugadorId,
              ),
              onEstadoSelected: (estado) => onCambiarEstado(
                evento.id,
                jugador.jugadorId,
                estado,
              ),
            ),
          ),
          const SizedBox(height: 18),
          Text(
            'Suplentes (${suplentes.length})',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 10),
          if (suplentes.isEmpty)
            Text(
              'No hay suplentes convocados para este evento.',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            )
          else
            ...suplentes.map(
              (jugador) => _CitacionRow(
                jugador: jugador,
                canSwap: canSwap,
                isSubmitting: isSubmitting,
                swapLabel: 'Promover',
                onSwapPressed: () => onIntercambiar(
                  evento,
                  titularId: firstConflictTitular?.jugadorId,
                  suplenteId: jugador.jugadorId,
                ),
                onEstadoSelected: (estado) => onCambiarEstado(
                  evento.id,
                  jugador.jugadorId,
                  estado,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _CitacionRow extends StatelessWidget {
  const _CitacionRow({
    required this.jugador,
    required this.canSwap,
    required this.isSubmitting,
    required this.swapLabel,
    required this.onSwapPressed,
    required this.onEstadoSelected,
  });

  final CitacionJugador jugador;
  final bool canSwap;
  final bool isSubmitting;
  final String swapLabel;
  final VoidCallback onSwapPressed;
  final ValueChanged<EstadoCitacion> onEstadoSelected;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: jugador.tieneConflicto
              ? theme.colorScheme.error.withValues(alpha: 0.45)
              : theme.colorScheme.outlineVariant,
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: theme.colorScheme.primary.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              jugador.posicion,
              style: theme.textTheme.labelMedium?.copyWith(
                fontWeight: FontWeight.w700,
                color: theme.colorScheme.primary,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  jugador.displayName,
                  style: theme.textTheme.bodyLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  jugador.rolMembresia,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                if (jugador.conflictoHorario != null) ...<Widget>[
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.error.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      jugador.conflictoHorario!,
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: theme.colorScheme.error,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
                if (jugador.motivoRespuesta != null) ...<Widget>[
                  const SizedBox(height: 4),
                  Text(
                    jugador.motivoRespuesta!,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: <Widget>[
              PopupMenuButton<EstadoCitacion>(
                enabled: !isSubmitting,
                tooltip: 'Actualizar respuesta de citación',
                onSelected: onEstadoSelected,
                itemBuilder: (_) => EstadoCitacion.values
                    .map(
                      (estado) => PopupMenuItem<EstadoCitacion>(
                        value: estado,
                        child: Text(estado.label),
                      ),
                    )
                    .toList(growable: false),
                child: _EstadoCitacionChip(estado: jugador.estadoRespuesta),
              ),
              if (canSwap) ...<Widget>[
                const SizedBox(height: 6),
                TextButton.icon(
                  onPressed: onSwapPressed,
                  icon: const Icon(Icons.swap_horiz_rounded, size: 16),
                  label: Text(swapLabel),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _EstadoCitacionChip extends StatelessWidget {
  const _EstadoCitacionChip({required this.estado});

  final EstadoCitacion estado;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final (bg, fg, icon) = switch (estado) {
      EstadoCitacion.confirmado => (
        PlaybookTheme.success(theme.colorScheme).withValues(alpha: 0.12),
        PlaybookTheme.success(theme.colorScheme),
        Icons.check_circle_outline_rounded,
      ),
      EstadoCitacion.pendiente => (
        PlaybookTheme.warning(theme.colorScheme).withValues(alpha: 0.10),
        PlaybookTheme.warning(theme.colorScheme),
        Icons.schedule_rounded,
      ),
      EstadoCitacion.rechazado => (
          theme.colorScheme.error.withValues(alpha: 0.14),
          theme.colorScheme.error,
          Icons.cancel_outlined,
        ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Icon(icon, size: 14, color: fg),
          const SizedBox(width: 4),
          Text(
            estado.label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: fg,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _CountPill extends StatelessWidget {
  const _CountPill({
    required this.label,
    required this.color,
    required this.textColor,
  });

  final String label;
  final Color color;
  final Color textColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              color: textColor,
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({
    required this.message,
    required this.onDismiss,
  });

  final String message;
  final VoidCallback onDismiss;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return MaterialBanner(
      backgroundColor: theme.colorScheme.error.withValues(alpha: 0.10),
      content: Text(message),
      actions: <Widget>[
        TextButton(
          onPressed: onDismiss,
          child: const Text('Cerrar'),
        ),
      ],
    );
  }
}

class _EmptyNominaState extends StatelessWidget {
  const _EmptyNominaState({
    required this.filtroTipo,
    required this.onResetFiltro,
  });

  final TipoEventoNomina? filtroTipo;
  final VoidCallback onResetFiltro;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          const Icon(Icons.assignment_ind_outlined, size: 42),
          const SizedBox(height: 12),
          Text(
            filtroTipo == null
                ? 'No hay partidos ni prácticas registradas para este equipo.'
                : 'No hay eventos del tipo "${filtroTipo!.label}".',
          ),
          if (filtroTipo != null) ...<Widget>[
            const SizedBox(height: 10),
            TextButton(
              onPressed: onResetFiltro,
              child: const Text('Ver todos'),
            ),
          ],
        ],
      ),
    );
  }
}

String _formatFecha(DateTime fecha) {
  const weekdays = <String>[
    'Lun',
    'Mar',
    'Mié',
    'Jue',
    'Vie',
    'Sáb',
    'Dom',
  ];
  const months = <String>[
    'ene',
    'feb',
    'mar',
    'abr',
    'may',
    'jun',
    'jul',
    'ago',
    'sep',
    'oct',
    'nov',
    'dic',
  ];
  final weekday = weekdays[(fecha.weekday - 1).clamp(0, 6)];
  final month = months[(fecha.month - 1).clamp(0, 11)];
  return '$weekday ${fecha.day} $month';
}
