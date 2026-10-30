# Plan de Implementación: Frontend Web en Laravel (Admin de Liga y Admin de Club)

Este documento deja especificada la hoja de ruta exacta para conectar el **Motor de Conflictos (`MotorConflictosService`)** con las vistas Blade del **Panel Web de Laravel** en la siguiente sesión de desarrollo.

---

## 1. Estado Actual de las Vistas Web (`resources/views/panel/`)

Actualmente existen 3 vistas en Blade (solo lectura) en [`routes/web.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/routes/web.php):
* [`resources/views/panel/fixture.blade.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/resources/views/panel/fixture.blade.php) (`GET /`): Muestra la tabla de partidos agrupados por liga.
* [`resources/views/panel/equipos.blade.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/resources/views/panel/equipos.blade.php) (`GET /equipos`): Muestra el listado de equipos y cuántos titulares tienen (`3/5`).
* [`resources/views/panel/roster.blade.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/resources/views/panel/roster.blade.php) (`GET /equipos/{equipo}`): Muestra las 5 tarjetas de posición (`TOP`, `JUNGLA`, `MID`, `ADC`, `SUPPORT`) y la tabla de suplentes/invitaciones.

El motor de negocio ya está **100% terminado** en [`MotorConflictosService.php`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Services/Disponibilidad/MotorConflictosService.php), por lo que el trabajo de frontend consistirá únicamente en inyectar este servicio en los controladores web y agregar los formularios/controles en Blade.

---

## 2. Pantallas y Funcionalidades a Construir en el Frontend Web

### Paso 1: Panel de Validación Automática en el Fixture (`fixture.blade.php`)
Basado en los mockups `referencia/figma/laravel-admin-fixture.png` y `admin-liga-fixture-lol-2026.png`:
1. **Inyectar `MotorConflictosService::validarFixtureLiga($liga, sincronizarEstados: true)`** en el controlador de Fixture.
2. **Tarjetas de KPIs superiores por Liga**:
   * **Equilibrio de Localía**: `metricas.equilibrio_localia_pct` (%).
   * **Descansos Mínimos ($\ge 24\text{h}$)**: `metricas.descansos_minimos_pct` (%).
   * **Penalización Total**: `penalizacion_total` pts vs `tolerancia_penalizacion_max` (25 pts).
3. **Bloque de Alertas Reglamentarias ("Panel de Validación Automática")**:
   * Renderizar las tarjetas rojas (`aviso--error`) para cada `VIOLACION_DURA` (ej. *Diego Pérez tiene partido publicado en Liga Valorant el Mar 03 Nov 20:30*) con botón **"Ver conflicto / Reprogramar"**.
   * Renderizar las tarjetas amarillas (`aviso--aviso`) para cada `ADVERTENCIA_BLANDA` (ej. *UDD Titans: descanso insuficiente entre Jornada 3 y Jornada 4 — solo 23h*).
4. **Botón "Publicar Fixture"**:
   * Deshabilitado (`disabled`) mientras `puede_publicarse === false` (`violaciones_duras_count > 0`).
   * Al hacer clic cuando no hay violaciones duras (`POST /ligas/{liga}/publicar`), pasa todos los partidos `tentativo` a `publicado`.

---

### Paso 2: Programador de Partidos con Grilla de Horarios Disponibles (Admin de Liga + Coach)
1. **Selector de Enfrentamiento y Formato de Serie**:
   * Seleccionar `Equipo Local`, `Equipo Visitante`, `Semana`, `Formato de Serie` (`Bo1`, `Bo3`, `Bo5`) y `Minutos por partida` (por defecto `50` min $\rightarrow 50\text{m}, 150\text{m}, 250\text{m}$).
2. **Grilla Interactiva de Ventanas Horarias (`sugerirVentanasParaEnfrentamiento`)**:
   * Llamar a `$motor->sugerirVentanasParaEnfrentamiento(...)` para mostrar una grilla visual con:
     * **Celdas Verdes (Disponibles Óptimas)**: Ambos equipos tienen sus 5 posiciones cubiertas (con titulares o suplentes habilitados) y sin choques. Botón **"Agendar aquí"**.
     * **Celdas Amarillas (Disponibles con Advertencia)**: Se puede jugar usando un suplente o con advertencia de descanso $<24\text{h}$.
     * **Celdas Rojas (Bloqueadas / Ocultables con filtro "Solo disponibles")**: Muestra en tooltip qué jugador tiene bloqueo recurrente/unitario o choque con otra liga.
3. **Acción de Guardar / Reprogramar Partido**:
   * Endpoint `POST /partidos` y `PATCH /partidos/{partido}` validando con [`StorePartidoRequest`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Http/Requests/Torneos/StorePartidoRequest.php).
   * Al reprogramar el partido en conflicto (ej. mover `UDD Titans vs PUC Paladins` del martes 20:00 al lunes 20:00), desaparece la violación dura y se habilita el botón **Publicar Fixture**.

---

### Paso 3: Gestión Interactiva del Roster del Equipo (`roster.blade.php`)
Basado en el mockup `referencia/figma/laravel-admin-club-roster.png`:
1. **Actualizar Rol y Posiciones de un Miembro (`PATCH /equipos/{equipo}/roster/{user}`)**:
   * Usar [`UpdateRosterMemberRequest`](file:///home/javierpc/UNI/Tecnologias_APP/Playbook_TecnologiasAPPs/laravel/app/Http/Requests/Clubes/UpdateRosterMemberRequest.php) para cambiar un jugador entre `Titular`, `Suplente` y `En prueba`, o ajustar su `posicion_principal` y `posicion_secundaria`.
   * Permitir confirmar la invitación pendiente de **Felipe Araya (`SUPPORT`)** y pasar a **Valentina Soto (`MID`)** a `Titular` para ver cómo el estado del roster de *UDD Titans* cambia en vivo de **NO VÁLIDO (3/5)** a **ROSTER COMPLETO (5/5)**.
2. **Vista de Matriz de Disponibilidad Semanal del Equipo en la página del Equipo**:
   * Mostrar la grilla devuelta por `$motor->generarGrillaDisponibilidadSemanalEquipo($equipo, ...)` para visualizar qué bloques de la semana tienen quórum `5/5`, `4/5` o están bloqueados por ramos recurrentes o certámenes unitarios.

---

## 3. Contratos de Datos Listos para Usar en Blade

En tus controladores web puedes invocar directamente:

```php
use App\Enums\FormatoSerieEnum;
use App\Services\Disponibilidad\MotorConflictosService;
use Carbon\CarbonImmutable;

// 1. Validar el fixture completo de una liga:
$reporteLiga = $motor->validarFixtureLiga($liga, sincronizarEstados: true);

// 2. Obtener la grilla semanal de un equipo (ej. en Bo3 de 50 min = 150 min):
$grillaEquipo = $motor->generarGrillaDisponibilidadSemanalEquipo(
    equipo: $equipo,
    fechaEnSemana: CarbonImmutable::parse('2026-11-03'),
    formatoSerie: FormatoSerieEnum::BO3,
    minutosPorPartida: 50,
);

// 3. Obtener las ventanas disponibles entre dos equipos (Local vs Visitante):
$ventanasSugeridas = $motor->sugerirVentanasParaEnfrentamiento(
    equipoLocal: $titans,
    equipoVisitante: $paladins,
    fechaEnSemana: CarbonImmutable::parse('2026-11-03'),
    formatoSerie: FormatoSerieEnum::BO3,
    minutosPorPartida: 50,
    liga: $liga,
    soloDisponibles: true, // true = muestra solo horarios donde pueden jugar
);
```
