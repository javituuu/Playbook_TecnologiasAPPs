import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../domain/miembro_plantel.dart';
import '../domain/plantel_equipo.dart';
import '../domain/plantel_repository.dart';
import '../domain/rol_membresia.dart';

/// Controlador para consultar el plantel agrupado por rol y cobertura de posiciones.
class PlantelController extends ChangeNotifier {
  PlantelController({
    required this._repository,
  });

  final PlantelRepository _repository;

  int? _activeEquipoId;
  int _requestSequence = 0;

  bool _isLoading = false;
  String? _errorMessage;
  PlantelEquipo? _plantel;
  RolMembresia? _filtroRol;

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  PlantelEquipo? get plantel => _plantel;
  RolMembresia? get filtroRol => _filtroRol;

  List<MiembroPlantel> miembrosPorRol(RolMembresia rol) {
    final current = _plantel;
    if (current == null) {
      return const <MiembroPlantel>[];
    }
    return current.byRol(rol);
  }

  List<RolMembresia> get visibleRoles {
    if (_filtroRol != null) {
      return <RolMembresia>[_filtroRol!];
    }
    return RolMembresia.values;
  }

  void setFiltroRol(RolMembresia? rol) {
    if (_filtroRol == rol) {
      return;
    }
    _filtroRol = rol;
    notifyListeners();
  }

  void clearError() {
    if (_errorMessage != null) {
      _errorMessage = null;
      notifyListeners();
    }
  }

  Future<void> loadPlantel({required int equipoId}) async {
    _activeEquipoId = equipoId;
    final requestId = ++_requestSequence;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _repository.fetchPlantel(equipoId: equipoId);
      if (requestId != _requestSequence) {
        return;
      }
      _plantel = result;
    } on ApiException catch (error) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = error.message;
    } catch (_) {
      if (requestId != _requestSequence) {
        return;
      }
      _errorMessage = 'No se pudo cargar el plantel del equipo.';
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
    await loadPlantel(equipoId: equipoId);
  }
}
