# Playbook Coach (`playbook_coach`)

Aplicación Flutter para el **Coach** de Playbook (gestión de **Disponibilidad / Heatmap**, **Prácticas**, **Nóminas y Citaciones**, y **Plantel**).

---

## 1. Conexión con el Backend Laravel

La aplicación se comunica con la API REST de **Laravel + Sanctum** mediante [`ApiClient`](lib/core/network/api_client.dart) y [`Env`](lib/core/config/env.dart).

### Variables de compilación (`--dart-define`)

| Variable | Valor por defecto | Descripción |
| --- | --- | --- |
| `API_BASE_URL` | `http://10.0.2.2:8000/api` (Android)<br>`http://127.0.0.1:8000/api` (Linux/Desktop/Web) | URL base de la API Laravel (sin `/` final). |
| `USE_MOCK_DATA` | `true` | Si es `true`, usa repositorios mock en memoria; pasar `false` para conectar con Laravel real. |
| `API_TIMEOUT_SECONDS` | `15` | Timeout en segundos por petición HTTP. |
| `ENABLE_HTTP_LOGS` | `kDebugMode` | Habilita trazas HTTP en consola. |

Ejemplo para ejecutar contra Laravel local en modo real:

```bash
# En emulador Android (con Laravel corriendo en el host)
flutter run --dart-define=USE_MOCK_DATA=false

# Con URL personalizada (ej. dispositivo físico en red local o servidor staging)
flutter run \
  --dart-define=USE_MOCK_DATA=false \
  --dart-define=API_BASE_URL=http://192.168.1.50:8000/api
```

> **Tip para Laravel local + emulador Android:** levanta Laravel escuchando en todas las interfaces para que `10.0.2.2:8000` sea accesible desde el emulador:
> ```bash
> php artisan serve --host=0.0.0.0 --port=8000
> ```

---

## 2. Cabeceras, Autenticación y Códigos HTTP

Todas las peticiones envían:
- `Accept: application/json`
- `Content-Type: application/json`
- `Authorization: Bearer <token>` (en todas las rutas autenticadas)

El backend debe responder con códigos estándar manejados por [`ApiException`](lib/core/network/api_exception.dart):
- **`401 Unauthorized`**: token inválido o expirado → limpia sesión local y vuelve a `LoginScreen`.
- **`403 Forbidden`**: cuenta sin rol `coach` (ej. un jugador intentando ingresar a la app del coach).
- **`422 Unprocessable Entity`**: errores de validación con estructura estándar Laravel `{ "message": "...", "errors": { "campo": ["..."] } }`.
- **`429 Too Many Requests`**: rate limiting (idealmente con cabecera `Retry-After` en segundos).

---

## 3. Contrato de Endpoints Esperados en Laravel

Las respuestas pueden venir envueltas en `{ "data": ... }` o directamente en la raíz del JSON.

### 3.1 Autenticación (`/auth/*`)

#### `POST /api/auth/login` *(pública)*
- **Body enviado por Flutter:**
  ```json
  {
    "email": "coach@udd.cl",
    "password": "********",
    "device_name": "playbook_coach_flutter"
  }
  ```
- **Respuesta esperada (`200 OK`):**
  ```json
  {
    "token": "1|sanctum_plain_text_token...",
    "user": {
      "id": 1,
      "name": "Coach Martínez",
      "email": "coach@udd.cl",
      "role": "coach",
      "avatar_url": null,
      "equipos_dirigidos": [
        {
          "id": 1,
          "nombre": "UDD Titans",
          "disciplina": "LoL",
          "club": "UDD Esports",
          "quorum_minimo": 4
        }
      ]
    }
  }
  ```
  > Si `role` es `jugador` / `player`, Laravel debe responder **`403 Forbidden`** (y Flutter también valida el rol en cliente).

#### `GET /api/auth/me` *(autenticada)*
- **Respuesta esperada (`200 OK`):** objeto `user` (o `{ "user": { ... } }` / `{ "data": { ... } }`) con la misma estructura de arriba, incluyendo `equipos_dirigidos`.

#### `POST /api/auth/logout` *(autenticada)*
- Revoca el token actual de Sanctum y retorna `200 OK` o `204 No Content`.

---

### 3.2 Heatmap de Disponibilidad (`/coach/equipos/{equipoId}/heatmap`)

#### `GET /api/coach/equipos/{equipoId}/heatmap?semana=2026-W46`
- **Query param:** `semana` (semana ISO, ej. `2026-W46`).
- **Respuesta esperada (`200 OK`):**
  ```json
  {
    "equipo_id": 1,
    "semana_iso": "2026-W46",
    "semana_label": "9 nov – 15 nov 2026",
    "fecha_inicio": "2026-11-09",
    "quorum_minimo": 4,
    "total_jugadores": 5,
    "horas": ["15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00"],
    "dias": ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"],
    "bloques": [
      {
        "dia_semana": 4,
        "dia_nombre": "Jueves",
        "hora": "19:00",
        "fecha": "2026-11-12",
        "disponibles_count": 3,
        "total_jugadores": 5,
        "quorum_minimo": 4,
        "disponibles": [
          { "id": 11, "nombre": "Sebastián Mora", "nickname": "Aegis", "rol": "Top" }
        ],
        "ausencias": [
          {
            "jugador_id": 13,
            "nombre_jugador": "Diego 'Kuro' Pérez",
            "motivo": "Cruce con partido oficial",
            "categoria_badge": "PARTIDO VALORANT 20:00",
            "es_cruce_partido": true
          }
        ]
      }
    ]
  }
  ```

---

### 3.3 Prácticas (`/coach/equipos/{equipoId}/practicas`, `/coach/practicas`)

#### `GET /api/coach/equipos/{equipoId}/practicas?semana=2026-W46`
- **Query param opcional:** `semana` (ISO `YYYY-Www`).
- **Respuesta esperada (`200 OK`):** lista de prácticas (en raíz `[]` o `{ "data": [] }`).

#### `POST /api/coach/practicas`
- **Body enviado por Flutter:**
  ```json
  {
    "equipo_id": 1,
    "semana_iso": "2026-W46",
    "dia_semana": 4,
    "fecha": "2026-11-12T00:00:00.000",
    "hora_inicio": "19:00",
    "hora_fin": "21:00",
    "serie": "bo3",
    "rival": "PUCV Esports",
    "modalidad": "Scrim Bo3",
    "servidor": "LAS",
    "notas": "Revisar draft fase 1",
    "citar_disponibles": true,
    "jugadores_citados": ["Sebastián 'Aegis' Mora", "Vicente 'Kairo' Silva"]
  }
  ```
- **Valores posibles de `serie`:** `bo1`, `bo3`, `bo5`.
- **Respuesta esperada (`201 Created` / `200 OK`):** objeto de la práctica creada.

#### `PATCH /api/coach/practicas/{practicaId}/cancelar`
- **Body opcional:** `{ "motivo": "Rival reprogramó" }`
- **Respuesta esperada (`200 OK`):** objeto actualizado de la práctica con estado cancelado.

---

### 3.4 Nómina y Citaciones (`/coach/equipos/{equipoId}/nomina`, `/coach/nomina/*`)

#### `GET /api/coach/equipos/{equipoId}/nomina`
- **Respuesta esperada (`200 OK`):** lista de eventos (`partido` / `practica`) con sus citaciones:
  ```json
  {
    "data": [
      {
        "id": 101,
        "equipo_id": 1,
        "tipo": "partido",
        "titulo": "UDD Titans vs UNAB",
        "liga_o_competencia": "Copa Universitaria LoL · Jornada 4",
        "fecha": "2026-11-16",
        "hora_inicio": "20:00",
        "hora_fin": "21:30",
        "lugar_o_servidor": "Online · Servidor LAS",
        "mensaje_coach": "Presentarse 20 minutos antes en Discord.",
        "citaciones": [
          {
            "jugador_id": 13,
            "nombre": "Diego Pérez",
            "nickname": "Kuro",
            "posicion": "Mid",
            "rol_membresia": "Titular",
            "es_titular": true,
            "estado_respuesta": "rechazado",
            "motivo_respuesta": "Cruce horario con UDD Nova",
            "conflicto_horario": "PARTIDO VALORANT 20:00"
          }
        ]
      }
    ]
  }
  ```
- **Valores esperados:**
  - `tipo`: `partido` | `practica`
  - `estado_respuesta`: `confirmado` | `pendiente` | `rechazado`

#### `POST /api/coach/nomina/{eventoId}/intercambiar`
- **Body enviado por Flutter:**
  ```json
  {
    "titular_id": 13,
    "suplente_id": 16
  }
  ```
- **Respuesta esperada (`200 OK`):** el objeto `EventoNomina` actualizado con las citaciones reflejando el intercambio.

#### `PATCH /api/coach/nomina/{eventoId}/citaciones/{jugadorId}`
- **Body enviado por Flutter:**
  ```json
  {
    "estado_respuesta": "confirmado"
  }
  ```
- **Respuesta esperada (`200 OK`):** el objeto `EventoNomina` actualizado.

---

### 3.5 Plantel (`/coach/equipos/{equipoId}/plantel`)

#### `GET /api/coach/equipos/{equipoId}/plantel`
- **Respuesta esperada (`200 OK`):**
  ```json
  {
    "equipo_id": 1,
    "posiciones_requeridas": ["Top", "Jungla", "Mid", "ADC", "Support"],
    "miembros": [
      {
        "id": 11,
        "nombre": "Sebastián Mora",
        "nickname": "Aegis",
        "rol_membresia": "titular",
        "posicion_principal": "Top",
        "posicion_secundaria": "Support",
        "rango": "Diamante II",
        "es_capitan": true
      }
    ]
  }
  ```
- **Valores esperados para `rol_membresia`:** `titular` | `suplente` | `en_prueba`.

---

## 4. Verificación y Pruebas

```bash
cd "Playbook_TecnologiasAPPs/flutter/playbook-coach"
flutter analyze
flutter test -r expanded
```
