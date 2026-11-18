import 'package:material_ui/material_ui.dart';

import '../../app/theme/playbook_theme.dart';

/// Insignia visual con la paleta "Aqua y uva" para encabezados y acceso.
class PlaybookBrandMark extends StatelessWidget {
  const PlaybookBrandMark({
    super.key,
    this.size = 38,
  });

  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(size * 0.28),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: <Color>[
            PlaybookTheme.dustyGrape,
            PlaybookTheme.pearlAqua,
          ],
        ),
        boxShadow: const <BoxShadow>[
          BoxShadow(
            color: Color(0x1A222222),
            blurRadius: 8,
            offset: Offset(0, 3),
          ),
        ],
      ),
      child: Icon(
        Icons.sports_esports_rounded,
        color: Colors.white,
        size: size * 0.54,
      ),
    );
  }
}
