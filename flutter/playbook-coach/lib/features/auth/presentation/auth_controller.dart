import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../domain/auth_repository.dart';
import '../domain/auth_user.dart';
import '../domain/equipo_dirigido.dart';

/// Estados del ciclo de autenticación del Coach.
enum AuthStatus {
  initial,
  restoring,
  unauthenticated,
  authenticating,
  authenticated,
}

/// Controlador de sesión, equipo activo y errores de autenticación del Coach.
class AuthController extends ChangeNotifier {
  AuthController({
    required this._repository,
  });

  final AuthRepository _repository;

  AuthStatus _status = AuthStatus.initial;
  AuthUser? _user;
  EquipoDirigido? _selectedTeam;
  String? _errorMessage;
  ApiErrorType? _errorType;
  int? _errorStatusCode;
  Map<String, List<String>> _fieldErrors = const <String, List<String>>{};
  int? _retryAfterSeconds;

  AuthStatus get status => _status;
  AuthUser? get user => _user;
  EquipoDirigido? get selectedTeam => _selectedTeam;
  String? get errorMessage => _errorMessage;
  ApiErrorType? get errorType => _errorType;
  int? get errorStatusCode => _errorStatusCode;
  Map<String, List<String>> get fieldErrors => _fieldErrors;
  int? get retryAfterSeconds => _retryAfterSeconds;

  bool get isMockMode => _repository.isMockMode;
  bool get isAuthenticated =>
      _status == AuthStatus.authenticated && _user != null;
  bool get isBusy =>
      _status == AuthStatus.restoring || _status == AuthStatus.authenticating;
  List<EquipoDirigido> get equipos =>
      _user?.equiposDirigidos ?? const <EquipoDirigido>[];

  /// Retorna el primer error de validación de servidor para [field].
  String? fieldError(String field) {
    final list = _fieldErrors[field];
    if (list == null || list.isEmpty) {
      return null;
    }
    return list.first;
  }

  /// Activa o desactiva el modo mock para pruebas de acceso.
  void setMockMode(bool enabled) {
    if (_repository.isMockMode == enabled) {
      return;
    }
    _repository.setMockMode(enabled);
    clearError();
    notifyListeners();
  }

  /// Intenta restaurar la sesión guardada en [TokenStorage].
  Future<void> restoreSession() async {
    if (_status == AuthStatus.restoring) {
      return;
    }
    _status = AuthStatus.restoring;
    _clearErrorFields();
    notifyListeners();

    try {
      final restoredUser = await _repository.restoreSession();
      if (restoredUser != null) {
        _applyAuthenticatedUser(restoredUser);
      } else {
        _user = null;
        _selectedTeam = null;
        _status = AuthStatus.unauthenticated;
      }
    } on ApiException catch (error) {
      _user = null;
      _selectedTeam = null;
      _status = AuthStatus.unauthenticated;
      _captureApiError(error);
    } catch (_) {
      _user = null;
      _selectedTeam = null;
      _status = AuthStatus.unauthenticated;
      _errorType = ApiErrorType.unknown;
      _errorMessage = 'No se pudo restaurar la sesión guardada.';
    }

    notifyListeners();
  }

  /// Inicia sesión validando credenciales y rol Coach.
  Future<bool> login({
    required String email,
    required String password,
  }) async {
    if (_status == AuthStatus.authenticating) {
      return false;
    }

    _status = AuthStatus.authenticating;
    _clearErrorFields();
    notifyListeners();

    try {
      final loggedUser = await _repository.login(
        email: email,
        password: password,
      );
      _applyAuthenticatedUser(loggedUser);
      notifyListeners();
      return true;
    } on ApiException catch (error) {
      _user = null;
      _selectedTeam = null;
      _status = AuthStatus.unauthenticated;
      _captureApiError(error);
      notifyListeners();
      return false;
    } catch (_) {
      _user = null;
      _selectedTeam = null;
      _status = AuthStatus.unauthenticated;
      _errorType = ApiErrorType.unknown;
      _errorMessage = 'Ocurrió un error inesperado al iniciar sesión.';
      notifyListeners();
      return false;
    }
  }

  /// Acceso rápido con la cuenta demo del Coach en modo mock.
  Future<bool> loginMockDemo() async {
    if (_status == AuthStatus.authenticating) {
      return false;
    }

    _status = AuthStatus.authenticating;
    _clearErrorFields();
    notifyListeners();

    try {
      final demoUser = await _repository.loginAsDemoCoach();
      _applyAuthenticatedUser(demoUser);
      notifyListeners();
      return true;
    } on ApiException catch (error) {
      _user = null;
      _selectedTeam = null;
      _status = AuthStatus.unauthenticated;
      _captureApiError(error);
      notifyListeners();
      return false;
    } catch (_) {
      _user = null;
      _selectedTeam = null;
      _status = AuthStatus.unauthenticated;
      _errorType = ApiErrorType.unknown;
      _errorMessage = 'No se pudo iniciar la sesión demo del coach.';
      notifyListeners();
      return false;
    }
  }

  /// Cambia el equipo actualmente seleccionado en el shell del Coach.
  void selectTeam(EquipoDirigido team) {
    if (_selectedTeam?.id == team.id && _selectedTeam == team) {
      return;
    }
    _selectedTeam = team;
    notifyListeners();
  }

  /// Cierra la sesión activa del Coach.
  Future<void> logout() async {
    _clearErrorFields();
    try {
      await _repository.logout();
    } finally {
      _user = null;
      _selectedTeam = null;
      _status = AuthStatus.unauthenticated;
      notifyListeners();
    }
  }

  /// Maneja una respuesta HTTP 401 recibida durante una sesión activa.
  void handleUnauthorized() {
    if (_status == AuthStatus.unauthenticated && _user == null) {
      return;
    }
    _user = null;
    _selectedTeam = null;
    _status = AuthStatus.unauthenticated;
    _errorType = ApiErrorType.unauthorized;
    _errorStatusCode = 401;
    _errorMessage = 'Tu sesión expiró. Inicia sesión nuevamente.';
    _fieldErrors = const <String, List<String>>{};
    _retryAfterSeconds = null;
    unawaited(_repository.clearSession());
    notifyListeners();
  }

  /// Limpia mensajes y errores visibles del formulario.
  void clearError() {
    if (_errorMessage == null &&
        _errorType == null &&
        _fieldErrors.isEmpty &&
        _retryAfterSeconds == null) {
      return;
    }
    _clearErrorFields();
    notifyListeners();
  }

  void _applyAuthenticatedUser(AuthUser loggedUser) {
    _user = loggedUser;
    _selectedTeam = _resolveSelectedTeam(loggedUser.equiposDirigidos);
    _status = AuthStatus.authenticated;
    _clearErrorFields();
  }

  EquipoDirigido? _resolveSelectedTeam(List<EquipoDirigido> available) {
    if (available.isEmpty) {
      return null;
    }
    final previousId = _selectedTeam?.id;
    if (previousId != null) {
      for (final team in available) {
        if (team.id == previousId) {
          return team;
        }
      }
    }
    return available.first;
  }

  void _captureApiError(ApiException error) {
    _errorType = error.type;
    _errorStatusCode = error.statusCode;
    _errorMessage = error.message;
    _fieldErrors = error.errors;
    _retryAfterSeconds = error.retryAfterSeconds;
  }

  void _clearErrorFields() {
    _errorMessage = null;
    _errorType = null;
    _errorStatusCode = null;
    _fieldErrors = const <String, List<String>>{};
    _retryAfterSeconds = null;
  }
}
