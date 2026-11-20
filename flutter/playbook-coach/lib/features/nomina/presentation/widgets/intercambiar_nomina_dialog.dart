import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';
import '../../domain/citacion_jugador.dart';
import '../../domain/estado_citacion.dart';
import '../../domain/evento_nomina.dart';

/// Diálogo para intercambiar un titular (con o sin conflicto) por un suplente.
class IntercambiarNominaDialog extends StatefulWidget {
  const IntercambiarNominaDialog({
    super.key,
    required this.evento,
    this.initialTitularId,
    this.initialSuplenteId,
  });

  final EventoNomina evento;
  final int? initialTitularId;
  final int? initialSuplenteId;

  @override
  State<IntercambiarNominaDialog> createState() =>
      _IntercambiarNominaDialogState();
}

class _IntercambiarNominaDialogState extends State<IntercambiarNominaDialog> {
  late int? _selectedTitularId;
  late int? _selectedSuplenteId;

  @override
  void initState() {
    super.initState();
    final titulares = widget.evento.titulares;
    final suplentes = widget.evento.suplentes;

    _selectedTitularId = widget.initialTitularId ??
        _defaultTitularId(titulares);
    _selectedSuplenteId = widget.initialSuplenteId ??
        _defaultSuplenteId(suplentes);
  }

  int? _defaultTitularId(List<CitacionJugador> titulares) {
    for (final t in titulares) {
      if (t.tieneConflicto) {
        return t.jugadorId;
      }
    }
    return titulares.isNotEmpty ? titulares.first.jugadorId : null;
  }

  int? _defaultSuplenteId(List<CitacionJugador> suplentes) {
    for (final s in suplentes) {
      if (s.estadoRespuesta == EstadoCitacion.confirmado && !s.tieneConflicto) {
        return s.jugadorId;
      }
    }
    return suplentes.isNotEmpty ? suplentes.first.jugadorId : null;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final titulares = widget.evento.titulares;
    final suplentes = widget.evento.suplentes;

    final canConfirm = _selectedTitularId != null && _selectedSuplenteId != null;

    return AlertDialog(
      title: const Text('Intercambiar titular ↔ suplente'),
      content: SizedBox(
        width: 460,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                widget.evento.titulo,
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'Sale de titular',
                style: theme.textTheme.labelLarge?.copyWith(
                  color: theme.colorScheme.primary,
                ),
              ),
              const SizedBox(height: 8),
              ...titulares.map((titular) {
                final selected = titular.jugadorId == _selectedTitularId;
                return _SelectablePlayerTile(
                  jugador: titular,
                  selected: selected,
                  onTap: () {
                    setState(() {
                      _selectedTitularId = titular.jugadorId;
                    });
                  },
                );
              }),
              const SizedBox(height: 16),
              Text(
                'Ingresa como titular (suplente)',
                style: theme.textTheme.labelLarge?.copyWith(
                  color: theme.colorScheme.primary,
                ),
              ),
              const SizedBox(height: 8),
              if (suplentes.isEmpty)
                const Text('No hay suplentes disponibles en este evento.')
              else
                ...suplentes.map((suplente) {
                  final selected = suplente.jugadorId == _selectedSuplenteId;
                  return _SelectablePlayerTile(
                    jugador: suplente,
                    selected: selected,
                    onTap: () {
                      setState(() {
                        _selectedSuplenteId = suplente.jugadorId;
                      });
                    },
                  );
                }),
            ],
          ),
        ),
      ),
      actions: <Widget>[
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancelar'),
        ),
        FilledButton.icon(
          onPressed: canConfirm
              ? () {
                  Navigator.of(context).pop(
                    (
                      titularId: _selectedTitularId!,
                      suplenteId: _selectedSuplenteId!,
                    ),
                  );
                }
              : null,
          icon: const Icon(Icons.swap_horiz_rounded),
          label: const Text('Confirmar intercambio'),
        ),
      ],
    );
  }
}

class _SelectablePlayerTile extends StatelessWidget {
  const _SelectablePlayerTile({
    required this.jugador,
    required this.selected,
    required this.onTap,
  });

  final CitacionJugador jugador;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final borderColor = selected
        ? theme.colorScheme.primary
        : theme.colorScheme.outlineVariant;

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: borderColor,
              width: selected ? 1.6 : 1,
            ),
            color: selected
                ? PlaybookColors.pearlAqua.withValues(alpha: 0.14)
                : theme.colorScheme.surface,
          ),
          child: Row(
            children: <Widget>[
              Icon(
                selected
                    ? Icons.radio_button_checked_rounded
                    : Icons.radio_button_off_rounded,
                size: 20,
                color: selected
                    ? theme.colorScheme.primary
                    : theme.colorScheme.onSurfaceVariant,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      '${jugador.displayName} · ${jugador.posicion}',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    if (jugador.conflictoHorario != null ||
                        jugador.motivoRespuesta != null) ...<Widget>[
                      const SizedBox(height: 2),
                      Text(
                        jugador.conflictoHorario ?? jugador.motivoRespuesta!,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.error,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 8),
              _EstadoBadge(estado: jugador.estadoRespuesta),
            ],
          ),
        ),
      ),
    );
  }
}

class _EstadoBadge extends StatelessWidget {
  const _EstadoBadge({required this.estado});

  final EstadoCitacion estado;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final (bg, fg) = switch (estado) {
      EstadoCitacion.confirmado => (
        PlaybookTheme.success(colors).withValues(alpha: 0.12),
        PlaybookTheme.success(colors),
      ),
      EstadoCitacion.pendiente => (
        PlaybookTheme.warning(colors).withValues(alpha: 0.10),
        PlaybookTheme.warning(colors),
      ),
      EstadoCitacion.rechazado => (
        colors.error.withValues(alpha: 0.14),
        colors.error,
      ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        estado.label,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              color: fg,
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }
}
