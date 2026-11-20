import 'package:material_ui/material_ui.dart';

/// Símbolo de Playbook junto al nombre de la app.
class PlaybookBrandMark extends StatelessWidget {
  const PlaybookBrandMark({
    super.key,
    this.size = 38,
  });

  final double size;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'assets/brand/playbook-mark.png',
      width: size,
      height: size,
      color: Theme.of(context).colorScheme.primary,
      excludeFromSemantics: true,
    );
  }
}

class PlaybookCoachWordmark extends StatelessWidget {
  const PlaybookCoachWordmark({super.key, required this.fontSize});

  final double fontSize;

  @override
  Widget build(BuildContext context) {
    return Text.rich(
      const TextSpan(
        children: [
          TextSpan(text: 'Play'),
          TextSpan(
            text: 'book',
            style: TextStyle(fontWeight: FontWeight.w300),
          ),
          TextSpan(text: ' Coach'),
        ],
      ),
      style: TextStyle(
        fontFamily: 'League Spartan',
        fontSize: fontSize,
        fontWeight: FontWeight.w800,
        color: Theme.of(context).colorScheme.primary,
      ),
      overflow: TextOverflow.ellipsis,
      maxLines: 1,
    );
  }
}
