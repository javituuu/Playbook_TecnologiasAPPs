import 'package:flutter/foundation.dart';

import '../../heatmap/domain/serie_duracion.dart';

/// Carga útil para agendar una nueva sesión de práctica.
@immutable
class CrearPracticaRequest {
  const CrearPracticaRequest({
    required this.equipoId,
    required this.equipoNombre,
    required this.semanaIso,
    required this.diaSemana,
    required this.diaNombre,
    required this.fecha,
    required this.horaInicio,
    required this.serie,
    required this.rival,
    required this.modalidad,
    required this.servidor,
    this.notas,
    this.citarDisponibles = true,
    this.jugadoresCitados = const <String>[],
  });

  final int equipoId;
  final String equipoNombre;
  final String semanaIso;
  final int diaSemana;
  final String diaNombre;
  final DateTime fecha;
  final String horaInicio;
  final SerieDuracion serie;
  final String rival;
  final String modalidad;
  final String servidor;
  final String? notas;
  final bool citarDisponibles;
  final List<String> jugadoresCitados;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'equipo_id': equipoId,
      'semana_iso': semanaIso,
      'dia_semana': diaSemana,
      'fecha': fecha.toIso8601String(),
      'hora_inicio': horaInicio,
      'hora_fin': serie.computeEndTime(horaInicio),
      'serie': serie.apiValue,
      'rival': rival.trim(),
      'modalidad': modalidad.trim(),
      'servidor': servidor.trim(),
      if (notas != null && notas!.trim().isNotEmpty) 'notas': notas!.trim(),
      'citar_disponibles': citarDisponibles,
      'jugadores_citados':
          citarDisponibles ? jugadoresCitados : const <String>[],
    };
  }
}
