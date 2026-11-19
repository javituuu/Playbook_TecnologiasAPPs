import 'package:material_ui/material_ui.dart';

import '../../../../app/theme/playbook_theme.dart';
import '../../../../core/utils/week_utils.dart';
import '../../../auth/domain/equipo_dirigido.dart';
import '../../../heatmap/domain/bloque_disponibilidad.dart';
import '../../../heatmap/domain/serie_duracion.dart';
import '../../domain/crear_practica_request.dart';
import '../../domain/practica.dart';
import '../practicas_controller.dart';

/// Abre el sheet modal para agendar una práctica desde un bloque del heatmap.
Future<Practica?> showAgendarPracticaSheet({
  required BuildContext context,
  required EquipoDirigido equipo,
  required String semanaIso,
  required BloqueDisponibilidad bloque,
  required SerieDuracion initialSerie,
  required PracticasController practicasController,
}) {
  practicasController.clearError();
  return showModalBottomSheet<Practica?>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: Colors.transparent,
    builder: (context) {
      return Center(
        heightFactor: 1,
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 560),
          child: Material(
            color: PlaybookColors.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
            clipBehavior: Clip.antiAlias,
            child: AgendarPracticaSheet(
              equipo: equipo,
              semanaIso: semanaIso,
              bloque: bloque,
              initialSerie: initialSerie,
              practicasController: practicasController,
            ),
          ),
        ),
      );
    },
  );
}

/// Formulario modal para programar una nueva sesión de práctica.
class AgendarPracticaSheet extends StatefulWidget {
  const AgendarPracticaSheet({
    super.key,
    required this.equipo,
    required this.semanaIso,
    required this.bloque,
    required this.initialSerie,
    required this.practicasController,
  });

  final EquipoDirigido equipo;
  final String semanaIso;
  final BloqueDisponibilidad bloque;
  final SerieDuracion initialSerie;
  final PracticasController practicasController;

  @override
  State<AgendarPracticaSheet> createState() => _AgendarPracticaSheetState();
}

class _AgendarPracticaSheetState extends State<AgendarPracticaSheet> {
  static const List<String> _modalidades = <String>[
    'Scrim competitivo',
    'Entrenamiento táctico',
    'Revisión VOD',
    'Match preparatorio',
  ];

  final _formKey = GlobalKey<FormState>();
  final _rivalController = TextEditingController();
  final _servidorController =
      TextEditingController(text: 'Discord + Sala Privada');
  final _notasController = TextEditingController();

  late SerieDuracion _selectedSerie;
  String _modalidad = _modalidades.first;
  bool _citarDisponibles = true;

  @override
  void initState() {
    super.initState();
    _selectedSerie = widget.initialSerie;
  }

  @override
  void dispose() {
    _rivalController.dispose();
    _servidorController.dispose();
    _notasController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }

    final request = CrearPracticaRequest(
      equipoId: widget.equipo.id,
      equipoNombre: widget.equipo.displayName,
      semanaIso: widget.semanaIso,
      diaSemana: widget.bloque.diaSemana,
      diaNombre: widget.bloque.diaNombre,
      fecha: widget.bloque.fecha,
      horaInicio: widget.bloque.hora,
      serie: _selectedSerie,
      rival: _rivalController.text.trim(),
      modalidad: _modalidad,
      servidor: _servidorController.text.trim().isEmpty
          ? 'Discord'
          : _servidorController.text.trim(),
      notas: _notasController.text.trim(),
      citarDisponibles: _citarDisponibles,
      jugadoresCitados: widget.bloque.disponibles
          .map((player) => player.displayName)
          .toList(growable: false),
    );

    final created = await widget.practicasController.agendarPractica(request);
    if (created != null && mounted) {
      Navigator.of(context).pop(created);
    }
  }

  @override
  Widget build(BuildContext context) {
    final endTime = _selectedSerie.computeEndTime(widget.bloque.hora);
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;

    return Padding(
      padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + bottomInset),
      child: SingleChildScrollView(
        child: ListenableBuilder(
          listenable: widget.practicasController,
          builder: (context, _) {
            final isSubmitting = widget.practicasController.isSubmitting;
            final errorMessage = widget.practicasController.errorMessage;

            return Form(
              key: _formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: <Widget>[
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: PlaybookColors.border,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: <Widget>[
                            const Text(
                              'Agendar práctica',
                              style: TextStyle(
                                fontSize: 19,
                                fontWeight: FontWeight.w800,
                                color: PlaybookTheme.carbon,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '${widget.equipo.displayName} · ${widget.bloque.diaNombre} ${WeekUtils.formatShortDate(widget.bloque.fecha)} (${widget.bloque.hora} – $endTime)',
                              style: const TextStyle(
                                fontSize: 12.5,
                                color: PlaybookTheme.dustyGrape,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        tooltip: 'Cerrar',
                        onPressed: isSubmitting
                            ? null
                            : () => Navigator.of(context).pop(),
                        icon: const Icon(Icons.close_rounded),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Serie / Duración',
                    style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w700,
                      color: PlaybookTheme.carbon,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: <Widget>[
                      for (final serie in SerieDuracion.values)
                        ChoiceChip(
                          key: Key('sheet_serie_${serie.apiValue}'),
                          label: Text('${serie.label} (${serie.shortDetail})'),
                          selected: _selectedSerie == serie,
                          onSelected: isSubmitting
                              ? null
                              : (_) {
                                  setState(() {
                                    _selectedSerie = serie;
                                  });
                                },
                        ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    key: const Key('practica_rival_field'),
                    controller: _rivalController,
                    enabled: !isSubmitting,
                    textInputAction: TextInputAction.next,
                    decoration: const InputDecoration(
                      labelText: 'Rival / Scrim',
                      hintText: 'Ej. USM Gaming, UC Esports...',
                      prefixIcon: Icon(Icons.sports_esports_outlined),
                    ),
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Ingresa el rival o título de la práctica.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 14),
                  DropdownButtonFormField<String>(
                    key: const Key('practica_modalidad_dropdown'),
                    initialValue: _modalidad,
                    decoration: const InputDecoration(
                      labelText: 'Modalidad',
                      prefixIcon: Icon(Icons.category_outlined),
                    ),
                    items: <DropdownMenuItem<String>>[
                      for (final item in _modalidades)
                        DropdownMenuItem<String>(
                          value: item,
                          child: Text(item),
                        ),
                    ],
                    onChanged: isSubmitting
                        ? null
                        : (value) {
                            if (value != null) {
                              setState(() {
                                _modalidad = value;
                              });
                            }
                          },
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    key: const Key('practica_servidor_field'),
                    controller: _servidorController,
                    enabled: !isSubmitting,
                    textInputAction: TextInputAction.next,
                    decoration: const InputDecoration(
                      labelText: 'Canal / Servidor',
                      hintText: 'Discord + Sala Privada',
                      prefixIcon: Icon(Icons.headset_mic_outlined),
                    ),
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    key: const Key('practica_notas_field'),
                    controller: _notasController,
                    enabled: !isSubmitting,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Notas tácticas (opcional)',
                      hintText: 'Ej. Practicar rotaciones y objetivos tempranos',
                      prefixIcon: Icon(Icons.notes_rounded),
                    ),
                  ),
                  const SizedBox(height: 10),
                  SwitchListTile.adaptive(
                    contentPadding: EdgeInsets.zero,
                    value: _citarDisponibles,
                    onChanged: isSubmitting
                        ? null
                        : (value) {
                            setState(() {
                              _citarDisponibles = value;
                            });
                          },
                    title: Text(
                      'Citar disponibles (${widget.bloque.disponibles.length}/${widget.bloque.totalJugadores})',
                      style: const TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    subtitle: Text(
                      widget.bloque.disponibles
                          .map((p) => p.nickname ?? p.nombre)
                          .join(', '),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 12,
                        color: PlaybookTheme.coolSteel,
                      ),
                    ),
                  ),
                  if (errorMessage != null) ...<Widget>[
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: PlaybookTheme.heatmap0.background,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: PlaybookTheme.heatmap0.border,
                        ),
                      ),
                      child: Text(
                        errorMessage,
                        style: TextStyle(
                          color: PlaybookTheme.heatmap0.foreground,
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: OutlinedButton(
                          onPressed: isSubmitting
                              ? null
                              : () => Navigator.of(context).pop(),
                          child: const Text('Cancelar'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        flex: 2,
                        child: FilledButton(
                          key: const Key('confirmar_practica_button'),
                          onPressed: isSubmitting ? null : _submit,
                          child: isSubmitting
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Text('Confirmar práctica'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}
