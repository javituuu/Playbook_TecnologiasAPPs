import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../config/env.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

/// Cliente HTTP base para consumir la API REST de Laravel + Sanctum.
///
/// Incluye automáticamente headers JSON, token Bearer desde [TokenStorage]
/// y traducción estándar de códigos 401, 403, 422, 429 y errores de red.
class ApiClient {
  ApiClient({
    required this._tokenStorage,
    http.Client? httpClient,
    String? baseUrl,
    Duration? timeout,
    this.onUnauthorized,
  })  : _httpClient = httpClient ?? http.Client(),
        _baseUrl = baseUrl ?? Env.apiBaseUrl,
        _timeout = timeout ?? Env.requestTimeout;

  final TokenStorage _tokenStorage;
  final http.Client _httpClient;
  final String _baseUrl;
  final Duration _timeout;

  /// Callback opcional invocado cuando una petición autenticada responde 401.
  final VoidCallback? onUnauthorized;

  /// Ejecuta una petición HTTP `GET`.
  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
    bool requiresAuth = true,
  }) {
    return _send(
      method: 'GET',
      path: path,
      queryParameters: queryParameters,
      headers: headers,
      requiresAuth: requiresAuth,
    );
  }

  /// Ejecuta una petición HTTP `POST` serializando [body] como JSON.
  Future<dynamic> post(
    String path, {
    Object? body,
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
    bool requiresAuth = true,
  }) {
    return _send(
      method: 'POST',
      path: path,
      body: body,
      queryParameters: queryParameters,
      headers: headers,
      requiresAuth: requiresAuth,
    );
  }

  /// Ejecuta una petición HTTP `PUT` serializando [body] como JSON.
  Future<dynamic> put(
    String path, {
    Object? body,
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
    bool requiresAuth = true,
  }) {
    return _send(
      method: 'PUT',
      path: path,
      body: body,
      queryParameters: queryParameters,
      headers: headers,
      requiresAuth: requiresAuth,
    );
  }

  /// Ejecuta una petición HTTP `PATCH` serializando [body] como JSON.
  Future<dynamic> patch(
    String path, {
    Object? body,
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
    bool requiresAuth = true,
  }) {
    return _send(
      method: 'PATCH',
      path: path,
      body: body,
      queryParameters: queryParameters,
      headers: headers,
      requiresAuth: requiresAuth,
    );
  }

  /// Ejecuta una petición HTTP `DELETE`.
  Future<dynamic> delete(
    String path, {
    Object? body,
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
    bool requiresAuth = true,
  }) {
    return _send(
      method: 'DELETE',
      path: path,
      body: body,
      queryParameters: queryParameters,
      headers: headers,
      requiresAuth: requiresAuth,
    );
  }

  /// Libera los recursos del cliente HTTP subyacente.
  void close() {
    _httpClient.close();
  }

  Future<dynamic> _send({
    required String method,
    required String path,
    Object? body,
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
    required bool requiresAuth,
  }) async {
    final uri = Env.buildUri(
      path,
      baseUrl: _baseUrl,
      queryParameters: queryParameters,
    );
    final requestHeaders = await _buildHeaders(
      requiresAuth: requiresAuth,
      extraHeaders: headers,
    );

    final encodedBody = _encodeBody(body);

    try {
      final response = await _executeRequest(
        method: method,
        uri: uri,
        headers: requestHeaders,
        encodedBody: encodedBody,
      ).timeout(_timeout);

      return _handleResponse(
        response,
        requiresAuth: requiresAuth,
      );
    } on TimeoutException {
      throw ApiException.timeout();
    } on ApiException {
      rethrow;
    } on http.ClientException catch (error) {
      throw ApiException.network(error.message);
    } on FormatException {
      throw ApiException.invalidResponse();
    } catch (_) {
      throw ApiException.network();
    }
  }

  Future<Map<String, String>> _buildHeaders({
    required bool requiresAuth,
    Map<String, String>? extraHeaders,
  }) async {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    if (requiresAuth) {
      final token = await _tokenStorage.readToken();
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    if (extraHeaders != null && extraHeaders.isNotEmpty) {
      headers.addAll(extraHeaders);
    }

    return headers;
  }

  String? _encodeBody(Object? body) {
    if (body == null) {
      return null;
    }
    if (body is String) {
      return body;
    }
    return jsonEncode(body);
  }

  Future<http.Response> _executeRequest({
    required String method,
    required Uri uri,
    required Map<String, String> headers,
    required String? encodedBody,
  }) {
    switch (method) {
      case 'GET':
        return _httpClient.get(uri, headers: headers);
      case 'POST':
        return _httpClient.post(uri, headers: headers, body: encodedBody);
      case 'PUT':
        return _httpClient.put(uri, headers: headers, body: encodedBody);
      case 'PATCH':
        return _httpClient.patch(uri, headers: headers, body: encodedBody);
      case 'DELETE':
        return _httpClient.delete(uri, headers: headers, body: encodedBody);
      default:
        throw ArgumentError.value(method, 'method', 'Método HTTP no soportado.');
    }
  }

  dynamic _handleResponse(
    http.Response response, {
    required bool requiresAuth,
  }) {
    final statusCode = response.statusCode;
    final decodedBody = _tryDecodeBody(response);

    if (statusCode >= 200 && statusCode < 300) {
      return decodedBody;
    }

    if (statusCode == 401 && requiresAuth) {
      onUnauthorized?.call();
    }

    throw ApiException.fromResponse(
      statusCode: statusCode,
      body: decodedBody ?? _decodeRawText(response),
      headers: response.headers,
    );
  }

  dynamic _tryDecodeBody(http.Response response) {
    if (response.statusCode == 204 || response.bodyBytes.isEmpty) {
      return null;
    }
    final text = _decodeResponseText(response).trim();
    if (text.isEmpty) {
      return null;
    }
    try {
      return jsonDecode(text);
    } on FormatException {
      if (response.statusCode >= 200 && response.statusCode < 300) {
        throw ApiException.invalidResponse();
      }
      return null;
    }
  }

  String? _decodeRawText(http.Response response) {
    if (response.bodyBytes.isEmpty) {
      return null;
    }
    final text = _decodeResponseText(response).trim();
    return text.isEmpty ? null : text;
  }

  String _decodeResponseText(http.Response response) {
    final contentType = response.headers['content-type']?.toLowerCase() ?? '';
    if (contentType.contains('iso-8859-1') || contentType.contains('latin1')) {
      return latin1.decode(response.bodyBytes);
    }
    try {
      return utf8.decode(response.bodyBytes);
    } on FormatException {
      return latin1.decode(response.bodyBytes);
    }
  }
}
