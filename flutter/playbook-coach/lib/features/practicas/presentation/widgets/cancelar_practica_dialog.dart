import 'package:material_ui/material_ui.dart';

import '../../domain/practica.dart';
import '../practicas_controller.dart';

/// Diálogo compartido para confirmar la cancelación de una práctica programada.
Future<bool> showCancelarPracticaDialog({
  required BuildContext context,
  required Practica practica,
  required PracticasController controller,
}) async {
  final result = await showDialog<_CancelDialogResult>(
    context: context,
    builder: (dialogContext) => _CancelarPracticaDialog(practica: practica),
  );

  if (result == null || !result.confirmed) {
    return false;
  }

  return controller.cancelarPractica(
    practicaId: practica.id,
    motivo: result.reason,
  );
}

class _CancelDialogResult {
  const _CancelDialogResult({
    required this.confirmed,
    this.reason,
  });

  final bool confirmed;
  final String? reason;
}

class _CancelarPracticaDialog extends StatefulWidget {
  const _CancelarPracticaDialog({required this.practica});

  final Practica practica;

  @override
  State<_CancelarPracticaDialog> createState() =>
      _CancelarPracticaDialogState();
}

class _CancelarPracticaDialogState extends State<_CancelarPracticaDialog> {
  final _motivoController = TextEditingController();

  @override
  void dispose() {
    _motivoController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return AlertDialog(
      title: const Text('Cancelar práctica'),
      content: SizedBox(
        width: 360,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                '¿Deseas cancelar la práctica vs ${widget.practica.rival} (${widget.practica.horarioResumen})?',
                style: TextStyle(fontSize: 13.5, color: colors.onSurface),
              ),
              const SizedBox(height: 12),
              TextField(
                key: const Key('cancel_reason_field'),
                controller: _motivoController,
                decoration: const InputDecoration(
                  labelText: 'Motivo de cancelación (opcional)',
                  hintText: 'Ej. Rival reprogramó horario',
                ),
              ),
            ],
          ),
        ),
      ),
      actions: <Widget>[
        TextButton(
          onPressed: () => Navigator.of(context).pop(
            const _CancelDialogResult(confirmed: false),
          ),
          child: const Text('Volver'),
        ),
        FilledButton(
          key: const Key('confirm_cancel_practice_button'),
          style: FilledButton.styleFrom(
            backgroundColor: colors.error,
            foregroundColor: colors.onError,
          ),
          onPressed: () {
            final reason = _motivoController.text.trim();
            Navigator.of(context).pop(
              _CancelDialogResult(
                confirmed: true,
                reason: reason.isEmpty ? null : reason,
              ),
            );
          },
          child: const Text('Confirmar cancelación'),
        ),
      ],
    );
  }
}
