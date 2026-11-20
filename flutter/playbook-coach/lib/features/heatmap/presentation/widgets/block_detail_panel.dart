import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';
import '../../../../core/utils/week_utils.dart';
import '../../../practicas/domain/practica.dart';
import '../../domain/bloque_disponibilidad.dart';
import '../../domain/serie_duracion.dart';

/// Panel de detalle para el bloque seleccionado del heatmap.
///
/// Muestra ratio y alerta de quórum, ausencias registradas con su badge,
/// lista de jugadores disponibles y el botón `Agendar aquí`.
class BlockDetailPanel extends StatelessWidget {
  const BlockDetailPanel({
    super.key,
    required this.bloque,
    required this.serie,
    required this.onAgendar,
    this.scheduledPractica,
    this.onCancelarPractica,
    this.showCloseButton = false,
  });

  final BloqueDisponibilidad bloque;
  final SerieDuracion serie;
  final VoidCallback onAgendar;
  final Practica? scheduledPractica;
  final ValueChanged<Practica>? onCancelarPractica;
  final bool showCloseButton;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final cumpleQuorum = bloque.cumpleQuorum;
    final endTime = serie.computeEndTime(bloque.hora);

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: <Widget>[
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    'DETALLE DEL BLOQUE',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.7,
                      color: colors.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    bloque.tituloDetalle,
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                      color: colors.onSurface,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${WeekUtils.formatShortDate(bloque.fecha)} · ${bloque.hora} – $endTime (${serie.label})',
                    style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w500,
                      color: colors.primary,
                    ),
                  ),
                ],
              ),
            ),
            if (showCloseButton)
              IconButton(
                tooltip: 'Cerrar detalle',
                onPressed: () => Navigator.of(context).pop(),
                icon: const Icon(Icons.close_rounded),
              ),
          ],
        ),
        const SizedBox(height: 16),
        _QuorumStatusBanner(bloque: bloque),
        if (scheduledPractica != null) ...<Widget>[
          const SizedBox(height: 14),
          _ScheduledPracticeCard(
            practica: scheduledPractica!,
            onCancelar: onCancelarPractica == null
                ? null
                : () => onCancelarPractica!(scheduledPractica!),
          ),
        ],
        const SizedBox(height: 18),
        Text(
          'AUSENCIAS REGISTRADAS (${bloque.ausencias.length})',
          style: TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.6,
            color: colors.onSurface,
          ),
        ),
        const SizedBox(height: 8),
        if (bloque.ausencias.isEmpty)
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: colors.surfaceContainer,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: colors.outlineVariant),
            ),
            child: Text(
              'Sin ausencias registradas en este bloque.',
              style: TextStyle(fontSize: 12.5, color: colors.onSurfaceVariant),
            ),
          )
        else
          for (var i = 0; i < bloque.ausencias.length; i++) ...<Widget>[
            _AbsenceCard(ausencia: bloque.ausencias[i]),
            if (i < bloque.ausencias.length - 1) const SizedBox(height: 8),
          ],
        const SizedBox(height: 18),
        Text(
          'DISPONIBLES PARA PRÁCTICA (${bloque.disponibles.length})',
          style: TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.6,
            color: colors.onSurface,
          ),
        ),
        const SizedBox(height: 8),
        if (bloque.disponibles.isEmpty)
          Text(
            'Ningún jugador disponible en este horario.',
            style: TextStyle(fontSize: 12.5, color: colors.onSurfaceVariant),
          )
        else
          for (final jugador in bloque.disponibles)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 5),
              child: Row(
                children: <Widget>[
                  Icon(
                    Icons.check_rounded,
                    size: 17,
                    color: PlaybookTheme.success(colors),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      jugador.displayName,
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w500,
                        color: colors.onSurface,
                      ),
                    ),
                  ),
                  if (jugador.rol != null)
                    Text(
                      jugador.rol!,
                      style: TextStyle(
                        fontSize: 11.5,
                        color: colors.onSurfaceVariant,
                      ),
                    ),
                ],
              ),
            ),
        const SizedBox(height: 20),
        if (!cumpleQuorum) ...<Widget>[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: colors.surfaceContainer,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: colors.outlineVariant),
            ),
            child: Row(
              children: <Widget>[
                Icon(
                  Icons.lock_outline_rounded,
                  size: 16,
                  color: colors.onSurfaceVariant,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Bloqueado: No cumple quórum mínimo (${bloque.quorumMinimo}/${bloque.totalJugadores})',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: colors.onSurfaceVariant,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),
        ],
        FilledButton(
          key: const Key('agendar_aqui_button'),
          onPressed: cumpleQuorum ? onAgendar : null,
          child: const Text('Agendar aquí'),
        ),
      ],
    );
  }
}

class _QuorumStatusBanner extends StatelessWidget {
  const _QuorumStatusBanner({required this.bloque});

  final BloqueDisponibilidad bloque;

  @override
  Widget build(BuildContext context) {
    final cumple = bloque.cumpleQuorum;
    final colors = Theme.of(context).colorScheme;
    final foreground = cumple ? PlaybookTheme.success(colors) : colors.error;
    final background = foreground.withValues(alpha: 0.10);
    final border = foreground.withValues(alpha: 0.32);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            '${bloque.ratioLabel} disponibles',
            style: TextStyle(
              color: foreground,
              fontSize: 15,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            cumple
                ? 'Cumple con el quórum mínimo de ${bloque.quorumMinimo} integrantes para agendar práctica.'
                : 'No se cumple con el quórum mínimo de ${bloque.quorumMinimo} integrantes para programar oficial.',
            style: TextStyle(
              color: foreground,
              fontSize: 12.5,
              height: 1.35,
            ),
          ),
        ],
      ),
    );
  }
}

class _AbsenceCard extends StatelessWidget {
  const _AbsenceCard({required this.ausencia});

  final AusenciaRegistrada ausencia;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final badgeStyle = _resolveBadgeStyle(context, ausencia);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: colors.outlineVariant),
      ),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  ausencia.nombreJugador,
                  style: TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                    color: colors.onSurface,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  ausencia.motivo,
                  style: TextStyle(fontSize: 12, color: colors.onSurfaceVariant),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: badgeStyle.background,
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              ausencia.categoriaBadge,
              style: TextStyle(
                color: badgeStyle.foreground,
                fontSize: 10.5,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.3,
              ),
            ),
          ),
        ],
      ),
    );
  }

  _AbsenceBadgeStyle _resolveBadgeStyle(
    BuildContext context,
    AusenciaRegistrada item,
  ) {
    if (item.esCrucePartido || item.categoriaBadge.contains('PARTIDO')) {
      final error = Theme.of(context).colorScheme.error;
      return _AbsenceBadgeStyle(
        background: error.withValues(alpha: 0.12),
        foreground: error,
      );
    }
    if (item.categoriaBadge.contains('ACAD')) {
      final warning = PlaybookTheme.warning(Theme.of(context).colorScheme);
      return _AbsenceBadgeStyle(
        background: warning.withValues(alpha: 0.12),
        foreground: warning,
      );
    }
    return _AbsenceBadgeStyle(
      background: Theme.of(context).colorScheme.surfaceContainer,
      foreground: Theme.of(context).colorScheme.primary,
    );
  }
}

class _AbsenceBadgeStyle {
  const _AbsenceBadgeStyle({
    required this.background,
    required this.foreground,
  });

  final Color background;
  final Color foreground;
}

class _ScheduledPracticeCard extends StatelessWidget {
  const _ScheduledPracticeCard({
    required this.practica,
    this.onCancelar,
  });

  final Practica practica;
  final VoidCallback? onCancelar;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: PlaybookTheme.pearlAqua.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: PlaybookTheme.pearlAqua.withValues(alpha: 0.7),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Icon(Icons.event_available_rounded, size: 16, color: colors.primary),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  'Práctica agendada · ${practica.serie.label}',
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: colors.onSurface,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'vs ${practica.rival} (${practica.horaInicio} – ${practica.horaFin})',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: colors.primary,
            ),
          ),
          if (onCancelar != null) ...<Widget>[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: onCancelar,
                style: TextButton.styleFrom(
                  foregroundColor: colors.error,
                  visualDensity: VisualDensity.compact,
                ),
                icon: const Icon(Icons.cancel_outlined, size: 16),
                label: const Text('Cancelar práctica'),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
