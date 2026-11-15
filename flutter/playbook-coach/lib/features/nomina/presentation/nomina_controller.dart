import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../domain/estado_citacion.dart';
import '../domain/evento_nomina.dart';
import '../domain/nomina_repository.dart';

/// Controlador para gestionar partidos/prácticas, citaciones y cambios de nómina.
class NominaController extends ChangeNotifier {
  NominaController({
    required this._repository,
  });

  final NominaRepository _repository;

  int? _activeEquipoId;
  int _requestSequence = 0;

  bool _isLoading = false;
  bool _isSubmitting = false;
  String? _errorMessage;
  List<EventoNomina> _eventos = const <EventoNomina>[];
  int? _selectedEventoId;
  TipoEventoNomina? _filtroTipo;

  bool get isLoading => _isLoading;
  bool get isSubmitting => _isSubmitting;
  String? get errorMessage => _errorMessage;
  List<EventoNomina> get eventos => _eventos;
  TipoEventoNomina? get filtroTipo => _filtroTipo;

  List<EventoNomina> get filteredEventos {
    if (_filtroTipo == null) {
      return _eventos;
    }
    return _eventos
        .where((evento) => evento.tipo == _filtroTipo)
        .toList(growable: false);
  }

  EventoNomina? get selectedEvento {
    final visible = filteredEventos;
    if (visible.isEmpty) {
      return null;
    }
    if (_selectedEventoId != null) {
      for (final evento in visible) {
        if (evento.id == _selectedEventoId) {
          return evento;
        }
      }
    }
    return visible.first;
  }

  void selectEvento(int eventoId) {
    if (_selectedEventoId == eventoId) {
      return;
    }
    _selectedEventoId = eventoId;
    notifyListeners();
  }

  void setFiltroTipo(TipoEventoNomina? tipo) {
    if (_filtroTipo == tipo) {
      return;
    }
    _filtroTipo = tipo;
    final visible = filteredEventos;
    if (visible.isNotEmpty &&
        !visible.any((evento) => evento.id == _selectedEventoId)) {
      _selectedEventoId = visible.first.id;
    }
    notifyListeners();
  }

  void clearError() {
    if (_errorMessage != null) {
      _errorMessage = null;
      notifyListeners();
    }
  }

  Future<void> loadEventos({required int equipoId}) async {
    _activeEquipoId = equipoId;
    final requestId = ++_requestSequence;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _repository.fetchEventos(equipoId: equipoId);
      if (requestId != _requestSequence) {
        return;
      }
      _eventos = _sortEventos(result);
      final visible = filteredEventos;
      if (visible.isEmpty) {
        _selectedEventoId = null;
      } else if (!visible.any((e) => e.id == _selectedEventoId)) {
        _selectedEventoId = visible.first.id;
      }
    } on ApiException catch (error) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = error.message;
    } catch (_) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = 'No se pudieron cargar las nóminas del equipo.';
    } finally {
      if (requestId == _requestSequence) {
        _isLoading = false;
        notifyListeners();
      }
    }
  }

  Future<void> refresh() async {
    final equipoId = _activeEquipoId;
    if (equipoId == null) {
      return;
    }
    await loadEventos(equipoId: equipoId);
  }

  Future<EventoNomina?> intercambiarTitularSuplente({
    required int eventoId,
    required int titularId,
    required int suplenteId,
  }) async {
    if (_isSubmitting) {
      return null;
    }

    _isSubmitting = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final updated = await _repository.intercambiarTitularSuplente(
        eventoId: eventoId,
        titularId: titularId,
        suplenteId: suplenteId,
      );
      _replaceEvento(updated);
      return updated;
    } on ApiException catch (error) {
      _errorMessage = error.message;
      return null;
    } catch (_) {
      _errorMessage = 'No se pudo actualizar el intercambio de nómina.';
      return null;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<EventoNomina?> actualizarEstadoCitacion({
    required int eventoId,
    required int jugadorId,
    required EstadoCitacion estado,
  }) async {
    if (_isSubmitting) {
      return null;
    }

    _isSubmitting = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final updated = await _repository.actualizarEstadoCitacion(
        eventoId: eventoId,
        jugadorId: jugadorId,
        estado: estado,
      );
      _replaceEvento(updated);
      return updated;
    } on ApiException catch (error) {
      _errorMessage = error.message;
      return null;
    } catch (_) {
      _errorMessage = 'No se pudo actualizar el estado de citación.';
      return null;
    }
  }

  void _replaceEvento(EventoNomina updated) {
    final next = _eventos
        .map((item) => item.id == updated.id ? updated : item)
        .toList(growable: false);
    _eventos = _sortEventos(next);
    _selectedEventoId = updated.id;
  }

  List<EventoNomina> _sortEventos(List<EventoNomina> items) {
    final copy = List<EventoNomina>.from(items);
    copy.sort((a, b) {
      final dateCompare = a.fecha.compareTo(b.fecha);
      if (dateCompare != 0) {
        return dateCompare;
      }
      return a.horaInicio.compareTo(b.horaInicio);
    });
    return List<EventoNomina>.unmodifiable(copy);
  }
}
