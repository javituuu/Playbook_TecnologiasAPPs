import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';

/// Banner inferior con borde discontinuo que explica la regla de quórum mínimo.
class QuorumThresholdBanner extends StatelessWidget {
  const QuorumThresholdBanner({
    super.key,
    this.quorumMinimo = 4,
    this.totalJugadores = 5,
  });

  final int quorumMinimo;
  final int totalJugadores;

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: const _DashedRoundedBorderPainter(
        color: Color(0xFFF59E0B),
        radius: 10,
      ),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: const Color(0xFFFFFBEB),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Wrap(
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 10,
          runSpacing: 6,
          children: <Widget>[
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                'UMBRAL DE QUÓRUM ($quorumMinimo/$totalJugadores)',
                style: const TextStyle(
                  color: Color(0xFFB45309),
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.4,
                ),
              ),
            ),
            Text(
              'Los bloques amarillos, naranjas y rojos no alcanzan el quórum mínimo sugerido de $quorumMinimo jugadores disponibles para reservar práctica.',
              style: const TextStyle(
                color: PlaybookTheme.dustyGrape,
                fontSize: 12.5,
                height: 1.35,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DashedRoundedBorderPainter extends CustomPainter {
  const _DashedRoundedBorderPainter({
    required this.color,
    required this.radius,
  });

  final Color color;
  final double radius;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1.2
      ..style = PaintingStyle.stroke;

    final rrect = RRect.fromRectAndRadius(
      Offset.zero & size,
      Radius.circular(radius),
    );
    final path = Path()..addRRect(rrect);

    const dashLength = 6.0;
    const gapLength = 4.0;

    for (final metric in path.computeMetrics()) {
      var distance = 0.0;
      while (distance < metric.length) {
        final next = (distance + dashLength).clamp(0.0, metric.length);
        canvas.drawPath(metric.extractPath(distance, next), paint);
        distance += dashLength + gapLength;
      }
    }
  }

  @override
  bool shouldRepaint(covariant _DashedRoundedBorderPainter oldDelegate) {
    return oldDelegate.color != color || oldDelegate.radius != radius;
  }
}
