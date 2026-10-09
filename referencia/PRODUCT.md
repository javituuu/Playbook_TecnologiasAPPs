# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users
Jugadores universitarios de esports que compiten en una o más ligas y torneos (primario). Coaches y capitanes que arman nóminas. Administradores de club y de liga que inscriben planteles y programan partidos. Cliente académico: cátedra Tecnologías y Aplicaciones Web y Móviles (IIP323W), Universidad del Desarrollo, patrocinador Ignacio Villarroel.

## Product Purpose
Playbook centraliza los compromisos de cada jugador entre ligas, para evitar choques de horario y convocatorias sin quórum. Hoy la coordinación vive en WhatsApp, Discord y planillas. Éxito: detectar el choque antes de publicar la fecha. La métrica concreta de cada problema está en la presentación Ev1 (diapositiva "Cómo se mide el éxito").

## Positioning
Organiza los compromisos por alumno y no por equipo o liga, cruzando calendarios antes de confirmar cada fecha. Ninguna herramienta revisada cruza al mismo jugador entre ligas (verificado solo por categorías, sin comparar productos concretos).

## Operating Context
Proyecto de ramo en tres entregas: Ev1 Ionic (jugador, 02/10/2026), Ev2 Flutter (coach, 30/10/2026), Ev3 Laravel web y API (20/11/2026). Equipo: Diego Muñoz (Ionic), Cristóbal Montoya (Flutter), Javier Benavides (Laravel). Se evalúa con la pauta `referencia/documentos/Pauta-Ev1.xlsx`. La exposición de Ev1 la hace Diego, unos 10 minutos.

## Capabilities and Constraints
- App del jugador: Ionic 9, Angular 22, Capacitor; pestañas Agenda, Citaciones y Perfil; bloqueos de horario con crear y eliminar; confirmar o rechazar citaciones.
- API REST en Laravel con 5 rutas (closures, sin autenticación; usuario fijo de prueba). BD SQLite hoy, MySQL 8 como destino.
- Pendiente: login por rol, app del coach, panel web, detección de choques.
- Presupuesto $0 CLP; sin APIs de videojuegos comerciales.
- El acta (v1.0) dice Ionic 8, Angular 18 y MySQL; el código real difiere. La presentación sigue el código.

## Brand Commitments
- Nombre final: **Playbook**. El mockup de Figma dice "UniEsports" y queda desactualizado.
- Marca totalmente propia, sin relación con la identidad visual de la UDD (decisión del usuario, 2026-10-09).
- La paleta se define como diseño objetivo y se aplica después a la app Ionic (hoy usa el tema por defecto de Ionic).

## Evidence on Hand
- Acta de constitución: `referencia/documentos/Acta_Playbook_Final-1.docx`.
- Presentación previa: `referencia/documentos/Presentacion_Playbook_Final.pptx`.
- Mockups de Figma: `referencia/figma/*.png` y `WEB & MOBILE TECH - Piloto.pdf`.
- Presentación Ev1 en curso: `presentacion/Playbook_Ev1.pptx` (rev0).
- No hay pruebas de usuario, línea base ni medición de métricas. No inventarlas.

## Product Principles
1. El estado nunca depende solo del color: ícono o texto acompaña a cada aviso.
2. Cada afirmación del producto debe poder comprobarse en el código o en el acta.
3. Una acción principal por pantalla.
4. Lo pendiente se declara como pendiente.

## Accessibility & Inclusion
Contraste WCAG 2.x AA (4.5:1 texto normal, 3:1 texto grande y componentes). Modo oscuro especificado en el acta.
