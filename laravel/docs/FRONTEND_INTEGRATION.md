# Guía de Integración Técnica: API Gateway de Playbook

**Destinatarios:** Desarrolladores Frontend de la App del Jugador (Ionic) y App del Coach (Flutter).  
**Versión del API Gateway:** v1.2 (Securizada con Sanctum + RBAC + Rate Limiting).  
**Base URL:** `http://localhost:8000/api` (Local) o URL asignada en ambiente de staging/producción.

---

## 1. Reglas Globales de Comunicación

Para cualquier petición HTTP hacia el backend de Playbook se deben cumplir estrictamente dos condiciones:
1. **Header Obligatorio en Todas las Peticiones:**
   ```http
   Accept: application/json
   Content-Type: application/json
   ```
   *Nota: Sin el header `Accept: application/json`, Laravel podría responder redirecciones HTML en caso de error en lugar de payloads JSON.*
2. **Autenticación Bearer:** Todo endpoint fuera de `/api/auth/login` exige enviar el token emitido en el header `Authorization`:
   ```http
   Authorization: Bearer <access_token>
   ```

---

## 2. Flujo de Autenticación (Login)

### Endpoint: `POST /api/auth/login`
Autentica al usuario, verifica su estado activo, previene el cruce no autorizado de aplicaciones y emite un token Bearer con permisos específicos.

#### Headers Requeridos:
```http
Content-Type: application/json
Accept: application/json
```

#### Payload (Body JSON):
```json
{
  "email": "diego.perez@udd.cl",
  "password": "password",
  "device_name": "ionic-android",
  "rol_esperado": "jugador"
}
```

> ⚠️ **Importancia Crítica de `rol_esperado`**:
> - En la **App del Jugador (Ionic)** se **DEBE** enviar `"rol_esperado": "jugador"`.
> - En la **App del Coach (Flutter)** se **DEBE** enviar `"rol_esperado": "coach"`.
> 
> Si un coach intenta iniciar sesión en la app de jugadores (o viceversa), el API Gateway deniega el acceso automáticamente respondiendo **`403 Forbidden`** antes de emitir cualquier credencial, garantizando aislamiento estricto de roles.

#### Respuesta Exitosa (`200 OK`) — Ejemplo para Jugador:
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

#### Respuesta Exitosa (`200 OK`) — Ejemplo para Coach (Flutter):
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

---

## 3. Manejo de Sesión en el Cliente

1. **Almacenamiento Seguro:** Al recibir `data.access_token`, guardarlo en almacenamiento persistente del dispositivo (ej. `Preferences` de Capacitor en Ionic o `flutter_secure_storage` / `shared_preferences` en Flutter).
2. **Interceptores HTTP:** Toda petición a endpoints protegidos (`/api/perfil`, `/api/agenda`, `/api/citaciones`, `/api/bloqueos`) debe inyectar de forma transparente:
   ```http
   Authorization: Bearer <token_guardado>
   ```
3. **Vigencia del Token:** Los tokens tienen una validez de 7 días. Si expiran, el API Gateway responderá `401 Unauthorized`.

---

## 4. Matriz de Errores HTTP y Comportamiento Esperado de la UI

| Código HTTP | Causa en el Backend | Acción Obligatoria en la App Cliente |
| :--- | :--- | :--- |
| **`401 Unauthorized`** | - Credenciales erróneas en login.<br>- Token ausente, revocado, manipulado o expirado. | **En Login:** Notificar "Correo o contraseña incorrectos".<br>**En peticiones posteriores:** El interceptor HTTP debe purgar el token del storage local y redirigir inmediatamente a la pantalla de Login. |
| **`403 Forbidden`** | - Cuenta desactivada (`activo: false`).<br>- Conflicto de `rol_esperado` (ej. Coach intentando ingresar a la App de Jugadores).<br>- Violación de propiedad (un jugador intentando alterar citaciones o bloqueos de otro). | Mostrar alerta o banner informando: *"Tu cuenta no tiene permisos para acceder a esta aplicación o recurso."* No redirigir a login a menos que la cuenta esté inhabilitada. |
| **`422 Unprocessable Entity`** | - Validación de formulario fallida (ej. email inválido, campos obligatorios vacíos). | Resaltar los campos afectados en el formulario con los mensajes recibidos en `errors`. |
| **`429 Too Many Requests`** | - **Rate Limit activo:** Se superaron 5 intentos de login en menos de 1 minuto para esa combinación de IP y correo. | Bloquear temporalmente el botón de login durante 60 segundos y mostrar advertencia: *"Demasiados intentos fallidos. Espera 1 minuto antes de reintentar."* |

---

## 5. Verificación de Sesión y Cierre de Sesión

### 5.1 Verificación de Sesión al Iniciar la App
Al abrir la app o en los Guards/Middleware de rutas, validar si el token guardado sigue activo antes de cargar la pantalla principal:

* **Endpoint:** `GET /api/auth/verify` (o `GET /api/auth/me`)
* **Headers:** `Authorization: Bearer <token>`
* **Respuesta (`200 OK`):**
  ```json
  {
    "status": "success",
    "data": {
      "authenticated": true,
      "abilities": ["role:jugador", "..."],
      "user": { ... }
    }
  }
  ```
* **Si responde `401 Unauthorized`:** Limpiar el storage y enviar al usuario a la pantalla de Login.

### 5.2 Cerrar Sesión en el Dispositivo Actual
Revoca únicamente el token del dispositivo que realiza la petición:
* **Endpoint:** `POST /api/auth/logout`
* **Headers:** `Authorization: Bearer <token>`
* **Respuesta (`200 OK`):**
  ```json
  {
    "status": "success",
    "message": "Sesión cerrada correctamente en este dispositivo."
  }
  ```

### 5.3 Cerrar Sesión en Todos los Dispositivos (Cierre Global)
Útil para opciones de seguridad ("Cerrar sesión en todos los dispositivos"):
* **Endpoint:** `POST /api/auth/logout-all`
* **Headers:** `Authorization: Bearer <token>`
* **Respuesta (`200 OK`):**
  ```json
  {
    "status": "success",
    "message": "Se cerraron todas las sesiones activas del usuario.",
    "tokens_revocados": 3
  }
  ```

---

## 6. Cuentas Sembradas para Pruebas (Todas con contraseña: `password`)

| Rol | Correo | Contexto / Equipo |
| :--- | :--- | :--- |
| `jugador` | `javier@email.com` | UDD Titans (League of Legends - Top) |
| `jugador` | `diego.perez@udd.cl` | UDD Titans (Jungla LoL) + UDD Nova (Duelista Valorant) |
| `coach` | `coach.martinez@udd.cl` | Coach principal de UDD Titans (LoL) |
| `coach` | `coach.rojas@udd.cl` | Coach principal de UDD Nova (Valorant) |
