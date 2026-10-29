# Guía Técnica y Pruebas CLI: Motor de Conflictos y Disponibilidad

Este documento describe la arquitectura, reglas de negocio y comandos de demostración por consola (CLI) de lo implementado en el **Motor de Conflictos y Disponibilidad (`MotorConflictosService`)** del ecosistema **Playbook**.

---

## 1. ¿Qué se implementó hoy?

Se construyó el núcleo algorítmico del backend en Laravel para resolver el problema central de Playbook (cruzar disponibilidad por alumno entre múltiples equipos, disciplinas y ramos académicos):

1. **Formato de Serie (`Bo1`, `Bo3`, `Bo5`) y Duración Dinámica (`n * minutos_por_partida`)**:
   * Nuevo enum [`FormatoSerieEnum`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Enums/FormatoSerieEnum.php) (`bo1`, `bo3`, `bo5`) con estándar de **50 minutos por partida** (configurable a cualquier valor entre 15 y 120 min):
     * **Mejor de 1 (`Bo1`)**: $1 \times 50 = 50\text{ min}$.
     * **Mejor de 3 (`Bo3`)**: $3 \times 50 = 150\text{ min}$.
     * **Mejor de 5 (`Bo5`)**: $5 \times 50 = 250\text{ min}$.
   * Migración [`2026_10_29_000001_add_formato_serie_to_partidos_table.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/database/migrations/2026_10_29_000001_add_formato_serie_to_partidos_table.php) que añade `formato_serie` y `minutos_por_partida` a la tabla `partidos`.
   * Actualización de [`ProgramarPartidoDTO`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/DTOs/Torneos/ProgramarPartidoDTO.php) y [`StorePartidoRequest`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Http/Requests/Torneos/StorePartidoRequest.php) para calcular automáticamente `fecha_hora_fin` según `formato_serie` y `minutos_por_partida`.

2. **Cruce de Bloqueos Horarios: Recurrentes (Semanal) vs Unitarios (Solo esa semana)**:
   * El motor ([`MotorConflictosService`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Services/Disponibilidad/MotorConflictosService.php)) evalúa los bloqueos del jugador diferenciando:
     * **Bloqueo Recurrente (`es_recurrente = true`)**: Aplica todas las semanas en el `dia_semana_iso` indicado (ej. clases universitarias o horario laboral fijo).
     * **Bloqueo Unitario (`es_recurrente = false` + `fecha_especifica`)**: Aplica **únicamente** en la fecha calendario exacta (ej. Certamen el viernes `2026-11-06` de 15:00 a 18:00), dejando libres los viernes de las demás semanas.

3. **Evaluación de Quórum y Cobertura Automática entre Titulares (Mains) y Suplentes**:
   * En cada ventana horaria $[t_{\text{inicio}}, t_{\text{fin}})$, el motor:
     * **Pasada 1 (Mains / Titulares)**: Intenta cubrir las 5 posiciones oficiales de la disciplina (`TOP`, `JUNGLA`, `MID`, `ADC`, `SUPPORT` en LoL) con los titulares disponibles.
     * **Pasada 2 (Suplentes / En Prueba)**: Si un titular tiene un bloqueo horario en esa franja, el motor busca automáticamente si hay un **suplente o jugador en prueba disponible** cuya `posicion_principal` o `posicion_secundaria` cubra ese puesto (ej. Cata Ríos cubriendo `MID` o `SUPPORT`).
     * Si el suplente cubre la posición, el partido **sí cumple quórum oficial (5/5)** y el bloqueo del titular pasa de `VIOLACION_DURA` a `ADVERTENCIA_BLANDA` (`BLOQUEO_HORARIO_RECURRENTE_CUBIERTO_POR_SUPLENTE`).

4. **Grilla Semanal de Disponibilidad y Sugeridor de Horarios para Admin de Liga + Coach**:
   * `generarGrillaDisponibilidadSemanalEquipo()`: Genera la matriz de 7 días $\times$ bloques horarios clasificando cada celda en:
     * `OPTIMO_OFICIAL` (`5/5` posiciones cubiertas sin choques duros).
     * `APTO_PRACTICA` (alcanza el quórum mínimo de entrenamiento $\ge 4/5$).
     * `BLOQUEADO` (no alcanza quórum o presenta choques).
   * `sugerirVentanasParaEnfrentamiento()`: Cruza las agendas de **Equipo Local vs Equipo Visitante** (mains + suplentes de ambos) y devuelve las ventanas evaluadas (`VentanaSugeridaDTO`), con opción `soloDisponibles: true` para listar únicamente los horarios donde ambos equipos pueden jugar.

5. **Validador Automático de Fixture de Liga (`validarFixtureLiga`)**:
   * Evalúa todos los partidos de una liga detectando:
     * **Violación Dura (`CHOQUE_MULTI_LIGA`)**: Jugador citado a dos competencias al mismo tiempo (ej. Diego Pérez en LoL 20:00 y Valorant 20:30 el martes `2026-11-03`). Bloquea la publicación del fixture.
     * **Violación Dura (`BLOQUEO_HORARIO_*`)**: Jugador citado con tope académico/laboral en ese horario.
     * **Advertencia Blanda (`DESCANSO_INSUFICIENTE`)**: Equipo con menos de 24 horas de descanso entre dos jornadas consecutivas.
     * **Advertencia Blanda (`DESEQUILIBRIO_LOCALIA`)**: Equipo con 3 o más partidos seguidos en la misma condición (local o visitante).

6. **Comando Artisan CLI (`playbook:motor-conflictos`) y Suite de Pruebas (`MotorConflictosServiceTest`)**:
   * Comando interactivo en [`EvaluarMotorConflictosCommand.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Console/Commands/EvaluarMotorConflictosCommand.php) para demostrar todo el motor directamente en la terminal.

---

## 2. Comandos para Ejecutar y Demostrar las Pruebas por CLI

Todos los comandos se ejecutan dentro de la carpeta `laravel/`:

```bash
cd /home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel
```

### 2.1 Ejecutar la Suite de Pruebas Automatizadas (PHPUnit)

Para correr únicamente los tests del **Motor de Conflictos** (con formato detallado):

```bash
php artisan test --filter=MotorConflictosServiceTest
```

Para correr **toda** la suite del proyecto (32 tests / 239 aserciones incluyendo API Ionic, Auth Sanctum, Dominio Eloquent y Motor de Conflictos):

```bash
php artisan test
```

---

### 2.2 Demostración Visual en Consola del Motor de Conflictos (`playbook:motor-conflictos`)

> **Nota sobre la Base de Datos en CLI**: Si tienes el contenedor MySQL encendido (`docker compose up -d`), puedes usar los comandos directos. Si estás trabajando sin Docker encendido, antepón `DB_CONNECTION=sqlite` como se muestra abajo (la base `database/database.sqlite` ya está migrada y sembrada).

#### Preparar / Sembrar datos de prueba (si es primera vez):
```bash
php artisan migrate:fresh --seed --database=sqlite
```

#### Caso 1: Evaluar Grilla Semanal, Cruce de Equipos y Fixture en serie **Mejor de 3 (`Bo3` = 150 min)**
Evalúa una serie `Bo3` con estándar de `50` minutos por partida ($3 \times 50 = 150\text{ min}$):

```bash
DB_CONNECTION=sqlite php artisan playbook:motor-conflictos --formato=bo3 --minutos=50
```

#### Caso 2: Mostrar **SOLO los horarios disponibles** para que Admin de Liga y Coach aprueben el partido
Filtra los horarios bloqueados y muestra en la tabla únicamente las ventanas horarias donde tanto **UDD Titans** como **PUC Paladins** tienen disponibilidad (considerando titulares y suplentes):

```bash
DB_CONNECTION=sqlite php artisan playbook:motor-conflictos --formato=bo3 --minutos=50 --solo-disponibles
```

#### Caso 3: Evaluar una final al **Mejor de 5 (`Bo5` = 250 min)** o **Mejor de 1 (`Bo1` = 50 min)**
Puedes cambiar `--formato` (`bo1`, `bo3`, `bo5`) y `--minutos` para ver cómo se expande la ventana horaria y cómo el motor detecta nuevos topes si la serie se alarga:

```bash
# Serie al Mejor de 1 (1 x 50 = 50 min)
DB_CONNECTION=sqlite php artisan playbook:motor-conflictos --formato=bo1 --minutos=50

# Final al Mejor de 5 con partidas de 45 min (5 x 45 = 225 min)
DB_CONNECTION=sqlite php artisan playbook:motor-conflictos --formato=bo5 --minutos=45
```

---

## 3. Archivos Creados / Modificados

| Archivo | Rol en la Arquitectura |
| :--- | :--- |
| [`app/Enums/FormatoSerieEnum.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Enums/FormatoSerieEnum.php) | Enum para series `Bo1`, `Bo3`, `Bo5` y cálculo $n \times \text{minutos}$. |
| [`database/migrations/2026_10_29_000001_add_formato_serie_to_partidos_table.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/database/migrations/2026_10_29_000001_add_formato_serie_to_partidos_table.php) | Columnas `formato_serie` y `minutos_por_partida` en `partidos`. |
| [`app/Services/Disponibilidad/MotorConflictosService.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Services/Disponibilidad/MotorConflictosService.php) | Servicio principal del motor de disponibilidad, suplencias y validación de fixture. |
| [`app/Console/Commands/EvaluarMotorConflictosCommand.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Console/Commands/EvaluarMotorConflictosCommand.php) | Comando CLI `playbook:motor-conflictos` para demostración en terminal. |
| [`tests/Feature/MotorConflictosServiceTest.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/tests/Feature/MotorConflictosServiceTest.php) | Pruebas automatizadas de cada regla del motor. |
