# Guía de Integración: API Gateway de Autenticación (Laravel Sanctum)

Este documento describe cómo funciona el **API Gateway de Autenticación en Laravel** y cómo integrarlo en la **App del Jugador (Ionic / Angular)** y en la **App del Coach (Flutter / Dart)**.

---

## 1. Arquitectura del API Gateway

Laravel actúa como punto central de autenticación y autorización para todo el ecosistema **Playbook** mediante **Laravel Sanctum (Bearer Tokens)** y **RBAC (Control de Acceso Basado en Roles)**:

1. **Emisión de Token (`POST /api/auth/login`)**:
   * El cliente móvil envía `email`, `password`, `device_name` (opcional) y `rol_esperado` (opcional: `"jugador"` en Ionic, `"coach"` en Flutter).
   * Si las credenciales son válidas y el usuario tiene el rol requerido, Laravel emite un `access_token` de tipo `Bearer` con permisos (*abilities*) específicos para ese rol y devuelve inmediatamente el perfil y contexto deportivo del usuario (equipo principal, club, disciplina o equipos dirigidos).
2. **Consumo de Endpoints Protegidos**:
   * La app móvil guarda el `access_token` localmente y lo envía en cada petición HTTP mediante el encabezado:
     ```http
     Authorization: Bearer <access_token>
     Accept: application/json
     ```
3. **Resolución Dinámica de Usuario y Retrocompatibilidad**:
   * Los endpoints `/api/perfil`, `/api/agenda`, `/api/citaciones` y `/api/bloqueos` están protegidos por `ResolveApiUserMiddleware` (`api.user`).
   * **Con token Bearer**: Devuelve y modifica **únicamente** la información del jugador autenticado en el token (por ejemplo, si inicia sesión `diego.perez@udd.cl`, verá su perfil de Jungla y sus propios bloqueos/citaciones; además, se impide con `403 Forbidden` modificar bloqueos o citaciones de otro jugador).
   * **Sin token Bearer (Modo Demo Local)**: Si aún no se envía el encabezado `Authorization` y `APP_ENV=local`, el middleware hace *fallback* automático a `javier@email.com` para no romper la app de Ionic del Certamen 1 mientras se integra la pantalla de Login.

---

## 2. Cuentas de Prueba (Sembradas en MySQL)

Todas las cuentas creadas por `php artisan migrate:fresh --seed` tienen la contraseña: **`password`**

| Rol (`role`) | Nombre | Correo (`email`) | Contraseña | Uso principal |
| :--- | :--- | :--- | :--- | :--- |
| `jugador` | Javier Benavides | `javier@email.com` | `password` | App Ionic (Top Laner · UDD Titans) |
| `jugador` | Diego Pérez | `diego.perez@udd.cl` | `password` | App Ionic (Jungla LoL + Duelista Valorant · Caso choque multi-liga) |
| `jugador` | Valentina Soto | `valentina.soto@udd.cl` | `password` | App Ionic (Mid Laner en prueba · UDD Titans) |
| `jugador` | Cata Ríos | `cata.rios@udd.cl` | `password` | App Ionic (Suplente Mid/Support · Bloqueo académico jueves) |
| `coach` | Coach Martínez | `coach.martinez@udd.cl` | `password` | App Flutter (Coach de UDD Titans — LoL) |
| `coach` | Coach Rojas | `coach.rojas@udd.cl` | `password` | App Flutter (Coach de UDD Nova — Valorant) |
| `admin_club` | Directiva Club UDD | `directiva.club@udd.cl` | `password` | Panel Web (Administra Universidad del Desarrollo) |
| `admin_liga` | Directiva Liga | `directiva.liga@playbook.cl` | `password` | Panel Web (Organiza ligas y fixture) |

---

## 3. Especificación de Endpoints de Autenticación

### 3.1 Iniciar Sesión — `POST /api/auth/login`

* **Autenticación requerida**: No (Público).
* **Headers**:
  * `Content-Type: application/json`
  * `Accept: application/json`
* **Body (JSON)**:
  ```json
  {
    "email": "diego.perez@udd.cl",
    "password": "password",
    "device_name": "ionic-android",
    "rol_esperado": "jugador"
  }
  ```
  *(Nota: `rol_esperado` es opcional. En Ionic pueden enviar `"jugador"` y en Flutter `"coach"` para que el propio API Gateway rechace con HTTP `403` si un usuario de otro rol intenta entrar en esa app).*

* **Respuesta Exitosa (`200 OK`) — Ejemplo Jugador**:
  ```json
  {
    "status": "success",
    "message": "Autenticación exitosa.",
    "data": {
      "token_type": "Bearer",
      "access_token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ1234567890",
      "abilities": [
        "role:jugador",
        "perfil:read",
        "agenda:read",
        "citaciones:read",
        "citaciones:respond",
        "bloqueos:manage"
      ],
      "user": {
        "id": 6,
        "nombre_completo": "Diego Pérez",
        "email": "diego.perez@udd.cl",
        "role": "jugador",
        "role_label": "Jugador",
        "avatar_url": null,
        "telefono": null,
        "activo": true,
        "contexto": {
          "equipo_principal_id": 1,
          "equipo_principal": "UDD Titans",
          "club_actual": "Universidad del Desarrollo (UDD)",
          "disciplina": "League of Legends",
          "posicion": "JUNGLA",
          "rango": "Platino 2"
        }
      }
    }
  }
  ```

* **Respuesta Exitosa (`200 OK`) — Ejemplo Coach (Flutter)**:
  ```json
  {
    "status": "success",
    "message": "Autenticación exitosa.",
    "data": {
      "token_type": "Bearer",
      "access_token": "2|zYxWvUtSrQpOnMlKjIhGfEdCbA0987654321",
      "abilities": [
        "role:coach",
        "perfil:read",
        "plantel:read",
        "disponibilidad:read",
        "nomina:manage",
        "practicas:manage"
      ],
      "user": {
        "id": 3,
        "nombre_completo": "Coach Martínez",
        "email": "coach.martinez@udd.cl",
        "role": "coach",
        "role_label": "Coach",
        "avatar_url": null,
        "telefono": null,
        "activo": true,
        "contexto": {
          "equipos_dirigidos": [
            {
              "id": 1,
              "nombre": "UDD Titans",
              "club": "Universidad del Desarrollo (UDD)",
              "disciplina": "League of Legends"
            }
          ]
        }
      }
    }
  }
  ```

* **Códigos de Error en Login**:
  * `401 Unauthorized`: Correo o contraseña incorrectos (`{"status": "error", "message": "Las credenciales proporcionadas son incorrectas."}`).
  * `403 Forbidden`: Usuario desactivado o no cumple con `rol_esperado`.
  * `422 Unprocessable Entity`: Campos faltantes o formato de email inválido.

---

### 3.2 Verificar Token / Obtener Usuario Actual — `GET /api/auth/me` (o `GET /api/auth/verify`)

Ideal para ejecutar al abrir la app móvil (Splash Screen o Route Guard) y comprobar si el token guardado sigue vigente.

* **Headers**:
  * `Authorization: Bearer <access_token>`
  * `Accept: application/json`
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "status": "success",
    "data": {
      "authenticated": true,
      "abilities": ["role:jugador", "perfil:read", "..."],
      "user": {
        "id": 6,
        "nombre_completo": "Diego Pérez",
        "email": "diego.perez@udd.cl",
        "role": "jugador",
        "role_label": "Jugador",
        "avatar_url": null,
        "telefono": null,
        "activo": true,
        "contexto": { "..." : "..." }
      }
    }
  }
  ```

---

### 3.3 Cerrar Sesión — `POST /api/auth/logout` y `POST /api/auth/logout-all`

* **`POST /api/auth/logout`**: Revoca únicamente el token del dispositivo actual.
* **`POST /api/auth/logout-all`**: Revoca todos los tokens del usuario en todos sus dispositivos.
* **Headers**: `Authorization: Bearer <access_token>`
* **Respuesta (`200 OK`)**:
  ```json
  {
    "status": "success",
    "message": "Sesión cerrada correctamente en este dispositivo."
  }
  ```

---

## 4. Guía de Integración para Ionic / Angular (Diego)

En `ionic/playbook-jugador`, pueden agregar autenticación sin tocar la lógica de las vistas actuales siguiendo estos 2 pasos:

### Paso 1: Agregar métodos de Auth en `jugador.service.ts` (o `auth.service.ts`)

```typescript
// En src/app/services/jugador.service.ts
private readonly TOKEN_KEY = 'playbook_auth_token';

login(email: string, password: string): Observable<any> {
  return this.http.post<any>(`${this.apiUrl}/auth/login`, {
    email,
    password,
    device_name: 'ionic-jugador-app',
    rol_esperado: 'jugador'
  }).pipe(
    tap((res) => {
      if (res?.data?.access_token) {
        localStorage.setItem(this.TOKEN_KEY, res.data.access_token);
      }
    }),
    catchError(this.handleError)
  );
}

verificarSesion(): Observable<any> {
  return this.http.get<any>(`${this.apiUrl}/auth/verify`).pipe(
    catchError(this.handleError)
  );
}

logout(): Observable<any> {
  return this.http.post<any>(`${this.apiUrl}/auth/logout`, {}).pipe(
    tap(() => localStorage.removeItem(this.TOKEN_KEY)),
    catchError(this.handleError)
  );
}
```

### Paso 2: Interceptor HTTP en `src/main.ts` para inyectar el Token automáticamente

Con un `HttpInterceptorFn` funcional de Angular 18, todas las llamadas actuales (`getPerfil()`, `getAgenda()`, `getCitaciones()`, `crearBloqueo()`) enviarán el header `Authorization: Bearer <token>` automáticamente:

```typescript
// En src/main.ts
import { provideHttpClient, withInterceptors, HttpInterceptorFn } from '@angular/common/http';

const authInterceptor: HttpInterceptorFn = (req, next) => {
  const token = localStorage.getItem('playbook_auth_token');
  if (token) {
    req = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    });
  }
  return next(req);
};

// Dentro de bootstrapApplication(AppComponent, { providers: [ ... ] }):
provideHttpClient(withInterceptors([authInterceptor]))
```

---

## 5. Guía de Integración para Flutter / Dart (Cristóbal)

Para la App del Coach en Flutter, se recomienda crear un `AuthService` usando el paquete `http` (o `dio`) y guardar el token en memoria o `shared_preferences`:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class PlaybookAuthService {
  // En Android Emulator usar 10.0.2.2:8000; en iOS Simulator / Web usar localhost:8000
  static const String baseUrl = 'http://localhost:8000/api';
  String? _accessToken;

  String? get accessToken => _accessToken;

  Map<String, String> get authHeaders => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (_accessToken != null) 'Authorization': 'Bearer $_accessToken',
      };

  /// Inicia sesión verificando que el usuario tenga rol 'coach'
  Future<Map<String, dynamic>> loginCoach({
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': 'flutter-coach-app',
        'rol_esperado': 'coach',
      }),
    );

    final body = jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode == 200 && body['status'] == 'success') {
      _accessToken = body['data']['access_token'] as String;
      // body['data']['user']['contexto']['equipos_dirigidos'] contiene la lista de equipos del coach
      return body['data'] as Map<String, dynamic>;
    }

    throw Exception(body['message'] ?? 'Error al iniciar sesión (${response.statusCode})');
  }

  /// Verifica si el token actual sigue siendo válido
  Future<Map<String, dynamic>> verificarToken() async {
    final response = await http.get(
      Uri.parse('$baseUrl/auth/verify'),
      headers: authHeaders,
    );

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200) {
      return body['data'] as Map<String, dynamic>;
    }
    throw Exception(body['message'] ?? 'Sesión inválida');
  }

  /// Cierra la sesión actual en el backend
  Future<void> logout() async {
    await http.post(Uri.parse('$baseUrl/auth/logout'), headers: authHeaders);
    _accessToken = null;
  }
}
```
