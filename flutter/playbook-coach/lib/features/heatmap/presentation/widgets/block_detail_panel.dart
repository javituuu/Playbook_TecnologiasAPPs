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
                  const Text(
                    'DETALLE DEL BLOQUE',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.7,
                      color: PlaybookTheme.coolSteel,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    bloque.tituloDetalle,
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                      color: PlaybookTheme.carbon,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${WeekUtils.formatShortDate(bloque.fecha)} · ${bloque.hora} – $endTime (${serie.label})',
                    style: const TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w500,
                      color: PlaybookTheme.dustyGrape,
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
          style: const TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.6,
            color: PlaybookTheme.carbon,
          ),
        ),
        const SizedBox(height: 8),
        if (bloque.ausencias.isEmpty)
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: PlaybookColors.surfaceMuted,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: PlaybookColors.border),
            ),
            child: const Text(
              'Sin ausencias registradas en este bloque.',
              style: TextStyle(
                fontSize: 12.5,
                color: PlaybookTheme.coolSteel,
              ),
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
          style: const TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.6,
            color: PlaybookTheme.carbon,
          ),
        ),
        const SizedBox(height: 8),
        if (bloque.disponibles.isEmpty)
          const Text(
            'Ningún jugador disponible en este horario.',
            style: TextStyle(
              fontSize: 12.5,
              color: PlaybookTheme.coolSteel,
            ),
          )
        else
          for (final jugador in bloque.disponibles)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 5),
              child: Row(
                children: <Widget>[
                  const Icon(
                    Icons.check_rounded,
                    size: 17,
                    color: Color(0xFF15803D),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      jugador.displayName,
                      style: const TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w500,
                        color: PlaybookTheme.carbon,
                      ),
                    ),
                  ),
                  if (jugador.rol != null)
                    Text(
                      jugador.rol!,
                      style: const TextStyle(
                        fontSize: 11.5,
                        color: PlaybookTheme.coolSteel,
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
              color: PlaybookColors.surfaceMuted,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: PlaybookColors.border),
            ),
            child: Row(
              children: <Widget>[
                const Icon(
                  Icons.lock_outline_rounded,
                  size: 16,
                  color: PlaybookTheme.coolSteel,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Bloqueado: No cumple quórum mínimo (${bloque.quorumMinimo}/${bloque.totalJugadores})',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: PlaybookTheme.coolSteel,
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
    final background =
        cumple ? const Color(0xFFF0FDF4) : const Color(0xFFFEF2F2);
    final border = cumple ? const Color(0xFFBBF7D0) : const Color(0xFFFECACA);
    final foreground =
        cumple ? const Color(0xFF166534) : const Color(0xFFB91C1C);

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
    final badgeStyle = _resolveBadgeStyle(ausencia);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: PlaybookColors.surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: PlaybookColors.border),
      ),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  ausencia.nombreJugador,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                    color: PlaybookTheme.carbon,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  ausencia.motivo,
                  style: const TextStyle(
                    fontSize: 12,
                    color: PlaybookTheme.coolSteel,
                  ),
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

  _AbsenceBadgeStyle _resolveBadgeStyle(AusenciaRegistrada item) {
    if (item.esCrucePartido || item.categoriaBadge.contains('PARTIDO')) {
      return const _AbsenceBadgeStyle(
        background: Color(0xFFFCE7F3),
        foreground: Color(0xFF9D174D),
      );
    }
    if (item.categoriaBadge.contains('ACAD')) {
      return const _AbsenceBadgeStyle(
        background: Color(0xFFFEF3C7),
        foreground: Color(0xFF92400E),
      );
    }
    return const _AbsenceBadgeStyle(
      background: PlaybookColors.surfaceMuted,
      foreground: PlaybookTheme.dustyGrape,
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
              const Icon(
                Icons.event_available_rounded,
                size: 16,
                color: PlaybookTheme.dustyGrape,
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  'Práctica agendada · ${practica.serie.label}',
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: PlaybookTheme.carbon,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'vs ${practica.rival} (${practica.horaInicio} – ${practica.horaFin})',
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: PlaybookTheme.dustyGrape,
            ),
          ),
          if (onCancelar != null) ...<Widget>[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: onCancelar,
                style: TextButton.styleFrom(
                  foregroundColor: PlaybookColors.error,
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
