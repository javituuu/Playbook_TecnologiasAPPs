# Playbook

Plataforma multiplataforma para la gestión de ligas, clubes, planteles, disponibilidad horaria, prácticas y citaciones de equipos competitivos de esports.

## Componentes

| Módulo | Ruta | Stack | Rol |
| --- | --- | --- | --- |
| Backend y Panel Web | `laravel/` | PHP 8.3, Laravel 13, Sanctum, Blade, MySQL 8 | API REST, motor de conflictos horarios y panel administrativo |
| App Coach | `flutter/playbook-coach/` | Flutter, Dart 3.14 | Heatmap de disponibilidad, prácticas, nóminas y plantel |
| App Jugador | `ionic/playbook-jugador/` | Ionic 9, Angular 22, Capacitor 8 | Agenda, respuesta a citaciones, perfil y bloqueos horarios |

## Estructura del repositorio

```text
playbook/
├── laravel/
│   ├── app/
│   │   ├── Domain/             # Enums y reglas de dominio
│   │   ├── Application/        # DTOs y servicios de aplicación (Auth, MotorConflictos)
│   │   ├── Http/               # Controladores API/Panel, Requests, Resources, Middleware
│   │   ├── Models/             # Modelos Eloquent (persistencia)
│   │   ├── Console/            # Comandos CLI
│   │   └── Providers/          # Contenedor e inyección de dependencias
│   ├── database/               # Migraciones, seeders y factories
│   ├── resources/              # Vistas Blade, estilos e iconos SVG
│   ├── routes/                 # Rutas web y API modularizadas
│   ├── tests/                  # Pruebas unitarias y de integración (PHPUnit)
│   └── docs/                   # Documentación técnica del backend
├── flutter/
│   └── playbook-coach/
│       └── lib/
│           ├── app/            # Configuración de app, shell y tema
│           ├── core/           # Red, configuración, almacenamiento y widgets comunes
│           └── features/       # auth, heatmap, nomina, plantel, practicas (data/domain/presentation)
├── ionic/
│   └── playbook-jugador/
│       └── src/app/
│           ├── core/           # Autenticación, guard, interceptor y utilidades HTTP
│           └── features/       # auth, home, agenda, citaciones, bloqueos, perfil (domain/data/presentation)
├── design/                     # Fuentes gráficas y escenas 3D
├── docs/
│   ├── marca/                  # Manual de marca
│   └── presentacion/           # Guion y material audiovisual
└── referencia/                 # SAD, figuras de arquitectura y prototipos Figma
```

## Requisitos

- PHP >= 8.3 y Composer
- Node.js >= 20 y npm
- Docker y Docker Compose (opcional, para MySQL 8 local)
- Flutter SDK (Dart ^3.14) para la app del Coach

## Puesta en marcha

### 1. Backend y Panel Web (Laravel)

```bash
cd laravel
cp .env.example .env
composer install
npm install && npm run build
docker compose up -d mysql
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8000
```

La API queda disponible en `http://127.0.0.1:8000/api` y el panel administrativo en `http://127.0.0.1:8000`.

### 2. App Jugador (Ionic / Angular)

```bash
cd ionic/playbook-jugador
npm install
npm start
```

Para compilar la versión integrada dentro de `laravel/public/jugador`:

```bash
npm run build:laravel
```

### 3. App Coach (Flutter)

```bash
cd flutter/playbook-coach
flutter pub get
flutter run --dart-define=USE_MOCK_DATA=false --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

Por defecto `USE_MOCK_DATA` es `true` para desarrollo offline; en emulador Android la URL por defecto es `http://10.0.2.2:8000/api`.

## Pruebas

```bash
# Backend Laravel (SQLite en memoria)
cd laravel && php artisan test

# App Jugador (Vitest)
cd ionic/playbook-jugador && npx ng test --watch=false

# App Coach (Flutter)
cd flutter/playbook-coach && flutter test
```

## Documentación

- [Manual de marca](docs/marca/MANUAL_DE_MARCA.md)
- [Autenticación y API Gateway](laravel/docs/AUTENTICACION_API_GATEWAY.md)
- [Integración Frontend](laravel/docs/FRONTEND_INTEGRATION.md)
- [Motor de Conflictos CLI](laravel/docs/MOTOR_CONFLICTOS_CLI.md)
- [Contrato API Flutter Coach](flutter/playbook-coach/README.md)
- [Documento de Arquitectura (SAD)](referencia/documentos/SAD_Playbook_v1.0.pdf)
