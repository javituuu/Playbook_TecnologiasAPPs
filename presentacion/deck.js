const { Deck, W, H } = require("./motor");
(async () => {
  const d = new Deck({ modoOscuro: true }), c = d.c, f = d.f;

  await d.portada({
    sobretitulo: "Tecnologías y Aplicaciones Web y Móviles",
    titulo: "Ligas universitarias de esports|PLAYBOOK",
    subtitulo: "Evaluación 1 · App del jugador en Ionic",
    pie: "Diego Muñoz  ·  Equipo Playbook  ·  2026",
    notas: "Buenas. Soy Diego y les presento Playbook, un sistema para coordinar ligas universitarias de esports. Hoy muestro la primera entrega, la app del jugador en Ionic, y voy a cubrir tres cosas: el caso, la arquitectura y cómo cada requisito se cubre con un componente.",
  });

  // ---- C1
  await d.concepto({
    titulo: "Horarios|A CIEGAS", subtitulo: "El problema que resolvemos",
    texto: "Cada liga agenda por separado. Un alumno queda citado en dos torneos a la vez y nadie lo nota a tiempo.",
    bloques: [{ t: "Hoy", d: "WhatsApp, Discord y planillas que quedan desactualizadas." }, { t: "Consecuencia", d: "Partidos suspendidos y derrotas por no presentación." }],
    leyenda: "Un mismo jugador, dos ligas",
    visual: async (s, x, y) => d.diagrama(s, { ox: x, oy: y + 0.2, tam: 24, nodos: [
      { id: "a", x: 0.4, y: 0, w: 5.4, h: 0.95, texto: "Diego, jugador", estilo: "oscuro" },
      { id: "b", x: 0.4, y: 1.55, w: 5.4, h: 1.35, texto: "20:00 · LoL vs UNAB", sub: "Liga Metropolitana Esports", estilo: "medio" },
      { id: "c", x: 0.4, y: 3.5, w: 5.4, h: 1.35, texto: "20:30 · Valorant vs INACAP", sub: "Torneo Interfacultades", estilo: "medio" },
      { id: "d", x: 0.4, y: 5.4, w: 5.4, h: 0.9, texto: "30 min de solapamiento", estilo: "linea" }],
      flechas: [{ de: "a", a: "b" }, { de: "b", a: "c", doble: true }, { de: "c", a: "d" }] }),
    notas: "Hoy cada liga arma su calendario por su cuenta, con grupos de WhatsApp, Discord y planillas. El ejemplo del diagrama es real: yo puedo jugar LoL a las 20:00 en una liga y Valorant a las 20:30 en otro torneo. Los dos capitanes me ven libre, y el choque aparece cuando la fecha ya está fija. Eso termina en partidos suspendidos o en derrotas por no presentación.",
  });

  await d.tarjetas({
    titulo: "Para|QUIÉN", subtitulo: "Cuatro roles, tres aplicaciones",
    items: [
      { icono: "LuGamepad2", titulo: "Jugador", texto: "Marca cuándo no puede jugar y responde sus citaciones. App Ionic: esta entrega." },
      { icono: "LuClipboardList", titulo: "Coach", texto: "Ve la disponibilidad del plantel y arma la nómina. App Flutter: Hito 2." },
      { icono: "LuCalendarDays", titulo: "Admin de club y de liga", texto: "Inscriben planteles y arman el fixture con aviso de choques. Panel Laravel: Hito 3." }],
    notas: "Hay cuatro roles. El jugador marca cuándo no puede jugar y responde sus citaciones: eso es lo que hice en Ionic. El coach ve la disponibilidad de su plantel y arma la nómina; eso va en Flutter, en el Hito 2. Los administradores de club y de liga inscriben planteles y arman el fixture en el panel web de Laravel, en el Hito 3.",
  });

  // requisitos: misma agrupación que la tabla de trazabilidad
  {
    const s = d.nuevo(); await d.decorar(s, "contenido");
    let y = d.titulo(s, "Requisitos|ALTO NIVEL", { align: "center", y: 1.0, ratio: 0.6 });
    y = d.subtitulo(s, "Nueve funcionales y tres no funcionales, en siete grupos", 2.4, y, { align: "center" });
    const g = [
      ["RF.01", "Acceso", "Inicio de sesión con los cuatro roles."],
      ["RF.02", "Bloqueos", "El jugador bloquea franjas y días en que no puede jugar."],
      ["RF.03", "Agenda", "Una sola agenda con los partidos de todas sus ligas."],
      ["RF.04", "Citaciones", "Confirmar o rechazar una citación a partido."],
      ["RF.05–06", "Plantel", "Disponibilidad semanal del equipo y nómina con quórum."],
      ["RF.07", "Inscripción", "Registrar clubes y jugadores e inscribirlos en torneos."],
      ["RF.08–09", "Fixture", "Programar partidos y avisar si un jugador ya tiene otro a esa hora."],
      ["RNF.01–03", "Calidad", "Android 10+ e iOS 15+, API bajo 1,5 s, interfaz según el rol."]];
    const gap = 0.35, cw = (15.2 - gap * 3) / 4, ch = 3.0, y0 = y + 0.75;
    g.forEach(([id, t, dsc], i) => {
      const x = 2.4 + (i % 4) * (cw + gap), yy = y0 + Math.floor(i / 4) * (ch + gap), ult = i === 7;
      d.forma(s, "roundRect", { x, y: yy, w: cw, h: ch, rectRadius: 0.25, fill: { color: c.blanco }, line: { color: ult ? c.claro : c.medio, width: 2 } });
      d.txt(s, id, { x: x + 0.3, y: yy + 0.25, w: cw - 0.6, h: 0.6, fontFace: f.titulo, fontSize: 30, color: c.medio, charSpacing: -1 });
      d.txt(s, t, { x: x + 0.3, y: yy + 0.9, w: cw - 0.6, h: 0.5, fontFace: f.enfasis, fontSize: 23, color: c.oscuro });
      d.txt(s, dsc, { x: x + 0.3, y: yy + 1.5, w: cw - 0.6, h: ch - 1.65, fontSize: 19, lineSpacingMultiple: 1.2 });
    });
    d.pagina(s);
    s.addNotes("Resumí los requisitos del acta en siete grupos para poder seguirlos después. Acceso, bloqueos, agenda y citaciones son del lado del jugador. Plantel es del coach. Inscripción y fixture son de los administradores. Aparte están los no funcionales: Android 10 o superior, iOS 15, y una API que responda en menos de un segundo y medio. Estos mismos siete grupos son los que cruzo con componentes en la última diapositiva.");
  }

  await d.tabla({
    titulo: "Cómo se mide|EL ÉXITO", subtitulo: "Cada métrica responde a un problema del caso", tamTitulo: 56, fs: 19,
    cabecera: ["Problema", "Métrica", "Meta", "Cuándo"],
    anchos: [2.6, 6.6, 3.0, 1.6],
    filas: [
      ["Choques", "Choques detectados antes de publicar la fecha, sobre los sembrados en las pruebas", "100%", "Hito 3"],
      ["Quórum", "Tiempo del coach para confirmar una nómina, frente a preguntar por chat", "Menor; base por medir", "Hito 2"],
      ["Planillas", "Jugadores con la disponibilidad cargada cada semana", "Por medir en piloto", "Piloto"],
      ["Sin avisar", "Derrotas por no presentación en cada torneo", "Por medir en piloto", "Piloto"],
      ["Bloqueo", "Toques para bloquear una franja en la app", "2 o menos", "Ev1"],
      ["Calidad", "Tareas principales sin errores; respuesta de la API", "90% · bajo 1,5 s", "Ev1"]],
    notas: "Para medir el éxito partí de los problemas, no de la tecnología. Choques: cuántos detecta el sistema antes de publicar una fecha, con un 100% sobre los casos de prueba; eso es del Hito 3. Quórum: cuánto tarda el coach en confirmar una nómina comparado con preguntar por chat. Planillas desactualizadas: cuántos jugadores cargan su disponibilidad cada semana. Y las derrotas por no presentación. Esas tres últimas no tienen línea base todavía, así que las medimos en un piloto. Lo que sí podemos medir hoy es el bloqueo rápido, dos toques o menos, y la calidad del acta: 90% de tareas sin errores y API bajo 1,5 segundos.",
  });

  // ---- C2 arquitectura
  {
    const s = d.nuevo(); await d.decorar(s, "contenido");
    let y = d.titulo(s, "Arquitectura|CLIENTE Y API", { x: 2.4, y: 0.8, tam: 52, ratio: 0.55 });
    d.subtitulo(s, "Lo que funciona hoy, y lo que se suma en los hitos 2 y 3", 2.4, y, { tam: 22 });
    d.diagrama(s, { ox: 0.9, oy: 3.9, tam: 24, nodos: [
      { id: "a", x: 0, y: 0, w: 3.5, h: 1.7, texto: "HomePage", sub: "Ionic 9 · Angular 22", estilo: "oscuro" },
      { id: "b", x: 4.5, y: 0, w: 3.8, h: 1.7, texto: "JugadorService", sub: "HttpClient · Observables", estilo: "medio" },
      { id: "c", x: 10.3, y: 0, w: 3.4, h: 1.7, texto: "API REST", sub: "Laravel · JSON", estilo: "oscuro" },
      { id: "d", x: 14.7, y: -0.15, w: 3.1, h: 2.0, texto: "Base de datos", sub: "SQLite hoy · MySQL 8", estilo: "claro", forma: "can", tam: 22 },
      { id: "e", x: 6.0, y: 3.6, w: 3.7, h: 1.5, texto: "App del coach", sub: "Flutter · Hito 2", estilo: "linea", guion: true },
      { id: "f", x: 11.3, y: 3.6, w: 3.7, h: 1.5, texto: "Panel web", sub: "Laravel · Hito 3", estilo: "linea", guion: true }],
      flechas: [{ de: "a", a: "b" }, { de: "b", a: "c", texto: "HTTP + JSON", dx: -1.0, dy: -0.45, w: 2.0, align: "center", tam: 17 }, { de: "c", a: "d" }, { de: "e", a: "c", guion: true }, { de: "f", a: "c", guion: true }] });
    d.txt(s, [{ text: "Tablas", options: { fontFace: f.etiqueta, color: c.medio, breakLine: true } }, { text: "users · partidos · citaciones · bloqueos_horarios" }],
      { x: 14.9, y: 6.4, w: 3.9, h: 1.5, fontSize: 18, lineSpacingMultiple: 1.2, align: "center" });
    d.txt(s, "Línea continua: construido en Ev1.   Línea punteada: por construir.", { x: 2.4, y: 9.7, w: 12, h: 0.5, fontFace: f.subtitulo, fontSize: 19, color: c.medio });
    d.pagina(s);
    s.addNotes("Esta es la arquitectura. En la app Ionic hay un componente standalone, HomePage, con tres pestañas. No llama a la red directamente: pasa por JugadorService, que usa HttpClient y devuelve Observables. El servicio habla en JSON con la API REST de Laravel, y la API lee y escribe en la base de datos con cuatro tablas: users, partidos, citaciones y bloqueos de horario. Hoy la base es SQLite y el destino es MySQL 8. Con línea punteada están la app del coach en Flutter y el panel web, que van a consumir esta misma API en los hitos 2 y 3.");
  }

  // UX
  {
    const s = d.nuevo(); await d.decorar(s, "contenido");
    const fh = 8.6, fw = fh * 390 / 844, fy = (H - fh) / 2;
    await d.marcoFoto(s, 2.4, fy, fw, fh, { foto: "ionic-jugador-agenda.png", radio: 0.4 });
    d.txt(s, "MOCKUP EN FIGMA", { x: 2.4, y: fy + fh + 0.08, w: fw, h: 0.35, fontFace: f.etiqueta, fontSize: 12, charSpacing: 3, color: c.medio, align: "center" });
    const tx = 2.4 + fw + 1.3, w = W - 2.4 - tx;
    let y = d.titulo(s, "Una app|TRES PESTAÑAS", { x: tx, y: 1.5, w, tam: 56, ratio: 0.55 });
    y = d.subtitulo(s, "Una acción principal por pantalla", tx, y, { w, tam: 24 }) + 0.3;
    [["Agenda", "Los partidos de todas sus ligas, con el próximo destacado."],
     ["Citaciones", "Confirmar o rechazar una citación con un toque."],
     ["Perfil", "Datos del jugador y sus horarios bloqueados: crear y eliminar."]].forEach(([t, dsc], i) => {
      d.txt(s, [{ text: t, options: { fontFace: f.enfasis, fontSize: 25, color: c.oscuro, breakLine: true } }, { text: dsc, options: { fontSize: 22 } }], { x: tx, y: y + i * 1.55, w, h: 1.4, lineSpacingMultiple: 1.2 });
    });
    d.forma(s, "line", { x: tx, y: y + 4.75, w: 1.5, h: 0, line: { color: c.claro, width: 1.5 } });
    d.txt(s, "En la app de Ev1, cada pestaña muestra cargando, sin datos y error, y avisa al guardar o fallar.", { x: tx, y: y + 4.95, w, h: 1.0, fontSize: 21, lineSpacingMultiple: 1.25, color: c.medio });
    d.pagina(s);
    s.addNotes("Esta es la interfaz. A la izquierda está el mockup en Figma, que es la versión objetivo: incluye el aviso de conflicto y más pestañas. La app de esta entrega implementa una barra inferior con tres pestañas. Agenda muestra los partidos de todas sus ligas. Citaciones permite confirmar o rechazar con un toque. Perfil muestra los datos del jugador y sus bloqueos de horario, que se crean y se eliminan ahí mismo. En las tres pestañas manejé tres estados: cargando, sin datos y error, y un aviso cuando se guarda algo o falla.");
  }

  await d.tarjetas({
    titulo: "Por qué|ESTA ARQUITECTURA", subtitulo: "Cuatro decisiones y su razón",
    items: [
      { icono: "LuPlug", titulo: "Una sola API", texto: "Ionic, Flutter y el panel usan los mismos endpoints. Las reglas de horario viven en un lugar.", tam: 20 },
      { icono: "LuDatabase", titulo: "Base relacional", texto: "Cruzar horarios es unir jugador, citación y partido. Las claves foráneas evitan datos huérfanos.", tam: 20 },
      { icono: "LuSmartphone", titulo: "Ionic y Capacitor", texto: "Un solo código Angular para Android e iOS, con datos por Observables.", tam: 20 },
      { icono: "LuLayers", titulo: "SQLite primero", texto: "Sin servidor al desarrollar. Las migraciones pasan el esquema a MySQL 8.", tam: 20 }],
    notas: "Elegí esto por cuatro razones. Primero, una sola API para tres clientes: así la lógica de horarios está en un solo lugar y no se repite en cada app. Segundo, base relacional, porque detectar choques es unir jugador, citación y partido, y las claves foráneas evitan datos huérfanos. Tercero, Ionic con Capacitor: un solo código para Android e iOS. Y cuarto, SQLite mientras desarrollamos porque no necesita servidor, y las migraciones de Laravel permiten pasar el mismo esquema a MySQL 8.",
  });

  // ---- C3
  await d.tabla({
    titulo: "Problema|Y SOLUCIÓN", subtitulo: "Cada requisito, el componente que lo cubre y su estado", tamTitulo: 52, fs: 18,
    cabecera: ["Requisito", "Componente", "Servicio / endpoint", "Tabla", "Estado"],
    anchos: [2.9, 3.2, 4.3, 3.0, 2.0],
    filas: [
      ["RF.01 Acceso", "Pantalla de login", "Autenticación con token", "users", "Por hacer"],
      ["RF.02 Bloqueos", "HomePage · Perfil", "POST y DELETE /bloqueos", "bloqueos_horarios", "Hecho"],
      ["RF.03 Agenda", "HomePage · Agenda", "GET /agenda", "partidos", "Hecho"],
      ["RF.04 Citaciones", "HomePage · Citaciones", "GET /citaciones y PATCH …/responder", "citaciones", "Hecho"],
      ["RF.05–06 Plantel", "App del coach", "Consulta de disponibilidad", "bloqueos, citaciones", "Hito 2"],
      ["RF.07 Inscripción", "Panel web", "Altas de clubes y jugadores", "clubes (nueva)", "Hito 3"],
      ["RF.08–09 Fixture", "Panel web", "Validación de choque al agendar", "partidos, citaciones", "Hito 3"]],
    notas: "Esta tabla cruza cada requisito con el componente que lo cubre. Los tres del jugador ya están hechos en Ionic: bloqueos con POST y DELETE sobre bloqueos, agenda con GET sobre agenda, y citaciones con GET y PATCH para responder. El acceso con login sigue pendiente, y hoy la API usa un usuario fijo de prueba. Plantel va en la app de Flutter, el Hito 2. Inscripción y fixture van en el panel web del Hito 3, y para inscripción hay que crear la tabla de clubes. El choque de horarios, que es el corazón del proyecto, se valida en ese último hito.",
  });

  await d.cierre({ titulo: "Muchas|GRACIAS", subtitulo: "¿Preguntas?", pie: "Diego Muñoz  ·  Equipo Playbook", notas: "Eso es todo. Quedo atento a sus preguntas." });
  await d.guardar("Playbook_Ev1.pptx");
})().catch(e => { console.error(e); process.exit(1); });
