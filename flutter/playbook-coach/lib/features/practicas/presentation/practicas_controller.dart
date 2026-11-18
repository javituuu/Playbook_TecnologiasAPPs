import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../domain/crear_practica_request.dart';
import '../domain/practica.dart';
import '../domain/practicas_repository.dart';

/// Controlador para consultar, agendar y cancelar prácticas programadas.
class PracticasController extends ChangeNotifier {
  PracticasController({
    required this._repository,
  });

  final PracticasRepository _repository;

  int? _activeEquipoId;
  int _requestSequence = 0;

  bool _isLoading = false;
  bool _isSubmitting = false;
  String? _errorMessage;
  List<Practica> _practicas = const <Practica>[];

  bool get isLoading => _isLoading;
  bool get isSubmitting => _isSubmitting;
  String? get errorMessage => _errorMessage;
  List<Practica> get practicas => _practicas;

  List<Practica> get programadas => _practicas
      .where((item) => item.estaProgramada)
      .toList(growable: false);

  List<Practica> get canceladas => _practicas
      .where((item) => item.estaCancelada)
      .toList(growable: false);

  /// Carga las prácticas del equipo [equipoId].
  Future<void> loadPracticas({
    required int equipoId,
    String? semanaIso,
  }) async {
    _activeEquipoId = equipoId;
    final requestId = ++_requestSequence;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _repository.fetchPracticas(
        equipoId: equipoId,
        semanaIso: semanaIso,
      );
      if (requestId != _requestSequence) {
        return;
      }
      _practicas = _sortPracticas(result);
    } on ApiException catch (error) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = error.message;
    } catch (_) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = 'No se pudieron cargar las prácticas programadas.';
    } finally {
      if (requestId == _requestSequence) {
        _isLoading = false;
        notifyListeners();
      }
    }
  }

  /// Agenda una nueva práctica y la incorpora inmediatamente en el estado local.
  Future<Practica?> agendarPractica(CrearPracticaRequest request) async {
    if (_isSubmitting) {
      return null;
    }

    _isSubmitting = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final created = await _repository.agendarPractica(request);
      if (_activeEquipoId == null || _activeEquipoId == created.equipoId) {
        _practicas = _sortPracticas(<Practica>[..._practicas, created]);
      }
      return created;
    } on ApiException catch (error) {
      _errorMessage = error.message;
      return null;
    } catch (_) {
      _errorMessage = 'No se pudo agendar la práctica.';
      return null;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  /// Cancela una práctica programada y actualiza su estado en la lista.
  Future<bool> cancelarPractica({
    required int practicaId,
    String? motivo,
  }) async {
    if (_isSubmitting) {
      return false;
    }

    _isSubmitting = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final updated = await _repository.cancelarPractica(
        practicaId: practicaId,
        motivo: motivo,
      );
      final nextList = _practicas
          .map((item) => item.id == practicaId ? updated : item)
          .toList(growable: false);
      _practicas = _sortPracticas(nextList);
      return true;
    } on ApiException catch (error) {
      _errorMessage = error.message;
      return false;
    } catch (_) {
      _errorMessage = 'No se pudo cancelar la práctica.';
      return false;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  /// Busca si existe una práctica programada que cubra el bloque indicado.
  Practica? findScheduledForBlock({
    required int equipoId,
    required String semanaIso,
    required int diaSemana,
    required String hora,
  }) {
    final targetHour = _parseHour(hora);
    for (final practica in _practicas) {
      if (!practica.estaProgramada) continue;
      if (practica.equipoId != equipoId) continue;
      if (practica.semanaIso != semanaIso) continue;
      if (practica.diaSemana != diaSemana) continue;

      final startHour = _parseHour(practica.horaInicio);
      final endHour = _parseHour(practica.horaFin);
      if (targetHour != null && startHour != null && endHour != null) {
        if (targetHour >= startHour && targetHour < endHour) {
          return practica;
        }
      } else if (practica.horaInicio == hora) {
        return practica;
      }
    }
    return null;
  }

  void clearError() {
    if (_errorMessage == null) return;
    _errorMessage = null;
    notifyListeners();
  }

  int? _parseHour(String hhmm) {
    final parts = hhmm.split(':');
    return int.tryParse(parts.first);
  }

  List<Practica> _sortPracticas(List<Practica> source) {
    final sorted = List<Practica>.from(source);
    sorted.sort((a, b) {
      if (a.estaProgramada != b.estaProgramada) {
        return a.estaProgramada ? -1 : 1;
      }
      final weekCompare = a.semanaIso.compareTo(b.semanaIso);
      if (weekCompare != 0) return weekCompare;
      final dayCompare = a.diaSemana.compareTo(b.diaSemana);
      if (dayCompare != 0) return dayCompare;
      return a.horaInicio.compareTo(b.horaInicio);
    });
    return List<Practica>.unmodifiable(sorted);
  }
}
