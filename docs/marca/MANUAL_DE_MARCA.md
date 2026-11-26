# Manual de marca de Playbook

Playbook organiza la agenda de jugadores universitarios de esports, la disponibilidad que revisan sus coaches y los fixtures y planteles que gestionan los administradores. Las tres interfaces comparten identidad visual y adaptan la navegación al trabajo de cada rol.

## Nombre y logotipo

Escribe **Playbook**. Usa **Playbook Coach** para identificar la app del coach y acompaña Playbook de «Jugador» o «Panel» cuando el contexto necesite distinguir la interfaz.

La marca gráfica es una **P cortada por la línea de agenda**. Usa la versión uva en claro y aqua en oscuro. La versión bicolor, con línea aqua, se reserva para fondos claros. Mantén el encuadre cuadrado del original y su espacio interno. En encabezados, usa el símbolo a 28–36 px o dp. Cuando el texto contiguo ya dice Playbook, trata el símbolo como decorativo. En el nombre compuesto, usa League Spartan ExtraBold para «Play» y Light para «book».

![Símbolo Playbook en uva con línea aqua](ionic/playbook-jugador/src/assets/logo/playbook-mark-duo.svg)

## Paleta Aqua y uva

| Color | Valor | Función |
| --- | --- | --- |
| Carbon Black | `#222222` | Texto en claro, fondo en oscuro y texto sobre aqua |
| Dusty Grape | `#4B4E6D` | Acciones principales y texto secundario en claro |
| Pearl Aqua | `#84DCC6` | Acentos, selección y acciones principales en oscuro |
| Cool Steel | `#95A3B3` | Texto secundario en oscuro y detalles neutros |
| White | `#FFFFFF` | Fondo claro y texto sobre uva o Carbon |

En claro, usa superficies neutras `#ECEEF1` y bordes `#D5D9E0`. En oscuro, usa superficies `#343644`, superficies elevadas `#3D4052` y divisores `#4B4E6D`. Usa un contorno más visible en controles interactivos cuando el borde sea necesario para reconocerlos.

| Estado | Claro | Oscuro |
| --- | --- | --- |
| Confirmado o disponible | `#0E705B` | `#84DCC6` |
| Pendiente o advertencia | `#8A5A00` | `#F2C879` |
| Conflicto o error | `#B3261E` | `#F28B82` |

Sobre superficies elevadas oscuras, usa `#A5B2C0` para texto secundario y `#FFAFA7` para texto de error cuando se necesite más contraste. Acompaña los estados con texto o icono significativo. En el mapa de disponibilidad, cada celda incluye la proporción de jugadores disponibles. Conserva el orden visual desde disponibilidad alta hasta bloqueo y distingue la selección con un contorno.

## Tipografía y componentes

- **League Spartan ExtraBold** para títulos de pantalla y encabezados de sección. **Inter** para acciones, datos y texto de lectura. Usa numerales tabulares en horas, ratios y tablas. Space Grotesk puede acompañar datos compactos del panel.
- Ordena la información con superficies planas, bordes de 1 px y espacios en múltiplos de 4. Usa radios de 12–16 px en tarjetas, 10–12 px en controles y forma de píldora para estados breves.
- Escribe acciones en español directo, como «Aceptar», «Guardar» y «Cerrar sesión». Permite que nombres, recintos y mensajes largos ocupen más de una línea.
- Contraste mínimo de 4,5:1 para texto normal y 3:1 para texto grande, iconos funcionales y contornos necesarios. Aqua y Cool Steel funcionan como rellenos sobre blanco. Usa uva o Carbon para el texto en ese contexto.
- Conserva el foco al navegar con teclado, respeta la preferencia de movimiento reducido y procura áreas táctiles de al menos 44 px en web o 48 dp en Flutter.

## Ilustración

Las fichas de Blender evocan los bloques de agenda, disponibilidad y fixture. Usa el WebP transparente como figura de fondo en accesos amplios, con baja opacidad y fuera de los controles. En estados vacíos, acompaña el mensaje con una ilustración geométrica en SVG.

[Escena editable de Blender](design/playbook/playbook-calendar.blend) · [Script de render](design/playbook/render_calendar.py)

## Adaptación por interfaz

| Interfaz | Espacio compacto | Espacio amplio |
| --- | --- | --- |
| Jugador · Ionic | Navegación inferior para Agenda, Citaciones y Perfil. Contenido en una columna y controles dentro de las áreas seguras. | Navegación inferior. El próximo partido y el resto de la agenda comparten la vista. El perfil y los bloqueos se leen en paralelo. |
| Coach · Flutter | Navegación inferior y detalle de bloque en hoja desplazable. La matriz conserva el tamaño de las celdas y permite desplazamiento horizontal. | Barra lateral de destinos y, con espacio suficiente, matriz y detalle en paralelo. |
| Panel · Laravel | Barra superior con sesión accesible. Tablas operativas en tarjetas con sus etiquetas. La matriz semanal conserva sus filas y columnas con desplazamiento. | Barra lateral, controles de gestión agrupados y tablas completas para comparar filas. |

La interfaz sigue el modo de color del dispositivo. Revisa las pantallas con texto ampliado, teclado, móvil estrecho, orientación horizontal, tablet y escritorio. Comprueba los estados de carga, vacío, conflicto y confirmación con datos representativos.

## Mantenimiento

Usa los temas de cada aplicación para aplicar estas reglas. El panel carga `laravel/public/css/panel.css`, Jugador usa `src/theme/variables.scss` y Coach usa `lib/app/theme/playbook_theme.dart`. Para regenerar la versión web de Jugador que sirve Laravel, ejecuta `npm run build:laravel` desde `ionic/playbook-jugador` y actualiza los archivos generados de `laravel/public/jugador`.

[Referencia de marca aplicada en Ionic](https://github.com/javituuu/Playbook_TecnologiasAPPs/pull/13)
