import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/utils/week_utils.dart';
import '../domain/bloque_disponibilidad.dart';
import '../domain/heatmap_repository.dart';
import '../domain/matriz_disponibilidad.dart';
import '../domain/serie_duracion.dart';

/// Controlador de la Matriz de Disponibilidad Semanal y bloque seleccionado.
class HeatmapController extends ChangeNotifier {
  HeatmapController({
    required this._repository,
    DateTime? initialDate,
  }) : _currentWeekMonday =
            WeekUtils.mondayOfWeek(initialDate ?? DateTime.now());

  final HeatmapRepository _repository;

  DateTime _currentWeekMonday;
  int? _activeEquipoId;
  int _requestSequence = 0;

  bool _isLoading = false;
  String? _errorMessage;
  MatrizDisponibilidad? _matriz;
  SerieDuracion _selectedSerie = SerieDuracion.bo1;
  BloqueDisponibilidad? _selectedBlock;

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  MatrizDisponibilidad? get matriz => _matriz;
  SerieDuracion get selectedSerie => _selectedSerie;
  BloqueDisponibilidad? get selectedBlock => _selectedBlock;
  DateTime get currentWeekMonday => _currentWeekMonday;
  String get semanaIso => WeekUtils.toIsoWeek(_currentWeekMonday);
  String get semanaLabel =>
      _matriz?.semanaLabel ?? WeekUtils.formatWeekRange(_currentWeekMonday);

  bool get isCurrentWeek =>
      semanaIso == WeekUtils.toIsoWeek(DateTime.now());

  /// Carga la matriz semanal para [equipoId] en la semana activa.
  Future<void> loadHeatmap({required int equipoId}) async {
    _activeEquipoId = equipoId;
    final requestId = ++_requestSequence;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _repository.fetchHeatmap(
        equipoId: equipoId,
        semanaIso: semanaIso,
      );
      if (requestId != _requestSequence) {
        return;
      }
      _matriz = result;
      _selectedBlock = _resolveInitialSelection(result);
    } on ApiException catch (error) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = error.message;
    } catch (_) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = 'No se pudo cargar la matriz de disponibilidad semanal.';
    } finally {
      if (requestId == _requestSequence) {
        _isLoading = false;
        notifyListeners();
      }
    }
  }

  /// Cambia la semana activa desplazándose [deltaWeeks] semanas y recarga.
  Future<void> changeWeek(int deltaWeeks) async {
    _currentWeekMonday =
        _currentWeekMonday.add(Duration(days: deltaWeeks * 7));
    final equipoId = _activeEquipoId;
    if (equipoId != null) {
      await loadHeatmap(equipoId: equipoId);
    } else {
      notifyListeners();
    }
  }

  /// Vuelve a la semana actual y recarga la matriz.
  Future<void> goToCurrentWeek() async {
    final currentMonday = WeekUtils.mondayOfWeek(DateTime.now());
    if (_currentWeekMonday == currentMonday && _matriz != null) {
      return;
    }
    _currentWeekMonday = currentMonday;
    final equipoId = _activeEquipoId;
    if (equipoId != null) {
      await loadHeatmap(equipoId: equipoId);
    } else {
      notifyListeners();
    }
  }

  /// Selecciona un bloque específico para inspeccionar su detalle.
  void selectBlock(BloqueDisponibilidad block) {
    if (_selectedBlock?.id == block.id && _selectedBlock == block) {
      return;
    }
    _selectedBlock = block;
    notifyListeners();
  }

  /// Cambia la duración/serie de práctica (`Bo1`, `Bo3`, `Bo5`).
  void selectSerie(SerieDuracion serie) {
    if (_selectedSerie == serie) {
      return;
    }
    _selectedSerie = serie;
    notifyListeners();
  }

  BloqueDisponibilidad? _resolveInitialSelection(MatrizDisponibilidad matrix) {
    if (matrix.bloques.isEmpty) {
      return null;
    }
    final previous = _selectedBlock;
    if (previous != null) {
      final matched = matrix.bloqueAt(previous.diaSemana, previous.hora);
      if (matched != null) {
        return matched;
      }
    }
    // Selección inicial sugerida: Jueves @ 19:00 (referencia Figma)
    return matrix.bloqueAt(4, '19:00') ?? matrix.bloques.first;
  }
}
