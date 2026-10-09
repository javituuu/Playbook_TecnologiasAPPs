# Guion Playbook Ev1

Tres expositores: Cristóbal Montoya, Javier Benavides y Diego Muñoz. Minutos a 115 palabras por minuto. Total: 9.3 min (Cristóbal 3.0 min · Javier 3.1 min · Diego 3.2 min).

| N° | Diapositiva | Orador | Min |
|---|---|---|---|
| 1 | Portada | Cristóbal | 0.6 |
| 2 | El problema | Cristóbal | 0.8 |
| 3 | Para quién | Cristóbal | 0.9 |
| 4 | Requisitos de alto nivel | Cristóbal | 0.8 |
| 5 | Cómo se mide el éxito | Javier | 1.0 |
| 6 | Arquitectura | Javier | 1.2 |
| 7 | Por qué esta arquitectura | Javier | 0.8 |
| 8 | La app: tres pestañas | Diego | 0.8 |
| 9 | Identidad visual | Diego | 0.9 |
| 10 | Requisitos y estado | Diego | 1.2 |
| 11 | Cierre | Diego | 0.3 |

El anexo no se expone; queda para preguntas.

## 1. Portada

**Cristóbal:** Buenas. Somos el equipo Playbook. Javier Benavides lleva la API y la base de datos en Laravel, Diego Muñoz hizo la app del jugador en Ionic, y yo, Cristóbal Montoya, la app del coach en Flutter. Playbook coordina ligas universitarias de esports. Hoy mostramos la primera entrega, que es la app del jugador. Yo parto con el problema y los requisitos, Javier cuenta la arquitectura y Diego muestra la app.

## 2. El problema

**Cristóbal:** Partamos por cómo funciona hoy. Cada liga arma su calendario por separado, con grupos de WhatsApp, Discord y planillas. Les muestro un ejemplo que armamos nosotros. Diego tiene LoL a las 20:00 en una liga y Valorant a las 20:30 en otro torneo. Cada capitán lo ve libre en su propio calendario, así que nadie nota el choque hasta que la fecha ya está fija. Ahí vienen los partidos suspendidos o las derrotas por no presentación. Playbook busca cruzar los compromisos del mismo jugador antes de confirmar la fecha.

## 3. Para quién

**Cristóbal:** Esto cambia según quién lo use. Son cuatro roles y tres aplicaciones, porque el administrador de club y el de liga comparten panel. El jugador marca cuándo no puede jugar y responde sus citaciones: esa es la app Ionic de Diego, la de hoy. El coach ve la disponibilidad de su plantel y arma la nómina. Esa es la mía, en Flutter, y llega en el Hito 2. Los administradores inscriben planteles y arman el fixture en un panel web de Laravel, que hace Javier para el Hito 3. Todavía no hablamos con usuarios reales. Partimos del problema que describe el acta.

## 4. Requisitos de alto nivel

**Cristóbal:** Del acta sacamos nueve requisitos funcionales y tres no funcionales. Los agrupamos en siete grupos más uno de calidad para poder seguirlos. Acceso, bloqueos, agenda y citaciones son del jugador. Plantel es del coach. Inscripción y fixture, de los administradores. Aparte van los no funcionales: Android 10 o superior, iOS 15, una API que responda en menos de un segundo y medio, y una interfaz que cambie según el rol. Diego cruza estos mismos grupos con los componentes al final. Javier sigue con cómo medimos el éxito.

## 5. Cómo se mide el éxito

**Javier:** Para medir el éxito partimos de los problemas, no de la tecnología. Choques: cuántos detecta el sistema antes de publicar una fecha. La meta es el 100% de los que sembremos en los casos de prueba, y eso llega con el Hito 3. Quórum: cuánto se demora un coach en confirmar una nómina, comparado con preguntar por chat. Las planillas desactualizadas y las derrotas por no presentación no tienen línea base, así que las mediríamos en un piloto. Para esta entrega las metas son el bloqueo rápido, dos toques o menos, y lo que pide el acta: 90% de tareas sin errores y API bajo 1,5 segundos. Ninguna está medida todavía. Son metas. Con eso claro, sigo con la arquitectura.

## 6. Arquitectura

**Javier:** La app Ionic no llama a la red directo. Pasa por un servicio, JugadorService, que usa HttpClient y devuelve Observables. Diego lo separó así para poder cambiar la URL o el formato en un solo archivo. El servicio habla en JSON con la API en Laravel 13. Hoy son rutas simples, sin controladores y sin autenticación, y trabajan con un usuario fijo de prueba. Cubren bloqueos, agenda y citaciones. Escriben y leen en cuatro tablas: users, partidos, citaciones y bloqueos de horario, con claves foráneas que borran en cascada. La base es SQLite por ahora. El destino que dice el acta es MySQL 8. Lo de línea punteada no existe todavía: la app del coach de Cristóbal y mi panel web. Los dos van a usar esta misma API, en los hitos 2 y 3. Ahora, por qué lo armamos así.

## 7. Por qué esta arquitectura

**Javier:** Son cuatro razones. Primero, una sola API para los tres clientes, así la lógica de horarios no se repite en cada app. Segundo, base relacional: detectar un choque es unir jugador, citación y partido, y las claves foráneas evitan datos huérfanos. Tercero, Ionic con Capacitor, un código en Angular para Android e iOS, que es lo que pide el requisito de plataformas. Y cuarto, SQLite mientras desarrollamos, porque no necesita servidor. Con las migraciones de Laravel el mismo esquema pasa a MySQL 8 después. Le paso a Diego para ver la app.

## 8. La app: tres pestañas

**Diego:** Gracias, Javier. Esta es la app del jugador, la que hice en Ionic. La ven en modo oscuro y en modo claro. Es un solo componente standalone, HomePage, con una barra abajo y tres pestañas. Agenda muestra los partidos de las citaciones que el jugador ya confirmó. En Citaciones se confirma o se rechaza con un toque. En Perfil están los datos del jugador y sus bloqueos de horario, que se crean y se borran ahí mismo. Las tres pestañas tienen estados de cargando, sin datos y error, y un aviso cuando algo se guarda o falla.

## 9. Identidad visual

**Diego:** La paleta se llama Aqua y uva. Elegí los colores por lo que hacen. Dusty Grape y Pearl Aqua son tonos fríos y apagados que calzan entre sí. Medí los contrastes con WCAG: blanco sobre Carbon Black da 15,9 a 1, y aqua sobre Carbon Black casi 10 a 1. El acta pide modo oscuro, y con este fondo el aqua se lee bien como acento. También queríamos algo distinto a Discord, Twitch o Riot. Y una regla: ningún estado depende solo del color, siempre va con ícono y texto. La app de esta entrega ya usa esta paleta, en modo claro y oscuro.

## 10. Requisitos y estado

**Diego:** Cierro con esta tabla, que cruza cada requisito con lo que lo cubre. Lo del jugador está hecho, salvo el login. Bloqueos usa POST y DELETE sobre bloqueos. Agenda usa GET sobre agenda y devuelve los partidos con citación confirmada. Citaciones usa GET y un PATCH para responder. El login sigue pendiente, y por eso la API usa un usuario fijo. Plantel va en la app de Flutter, Hito 2. Inscripción y fixture van en el panel web del Hito 3, y para inscribir hay que crear la tabla de clubes. El choque de horarios, que es el centro del proyecto, se valida en ese último hito. Los no funcionales no están en la tabla. La API ya responde JSON, pero los tiempos de respuesta no los hemos medido. Lo pendiente lo dejamos como pendiente.

## 11. Cierre

**Diego:** En una línea: el problema es el choque de horarios entre ligas, hoy resolvimos la parte del jugador, y la detección de choques llega en el Hito 3. Gracias por escucharnos. Quedamos atentos a sus preguntas.
