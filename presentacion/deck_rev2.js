// deck_rev2.js: Playbook Ev1, rev2 (paleta Aqua y uva). Textos desde contenido.json. Salida: Playbook_Ev1_rev2.pptx
const pptxgen = require("pptxgenjs");
const React = require("react");
const RDS = require("react-dom/server");
const sharp = require("sharp");
const { ratio } = require("./contraste");
const T = require("./contenido.json");

const W = 20, H = 11.25, M = 1.2; // lienzo 20" x 11,25" y margen izquierdo
const C = { bg: "222222", sup: "343644", uva: "4B4E6D", aqua: "84DCC6", acero: "95A3B3", w: "FFFFFF",
  coral: "F28B82", ambar: "F2C879", claro: "F1F2F6", rojo: "B3261E", ambarL: "8A5A00", verde: "0E705B" };
const F = { tit: "League Spartan ExtraBold", lig: "League Spartan Light", b: "Inter", l: "Inter Light", m: "Inter Medium", s: "Inter SemiBold" };
const fmt = r => r.toFixed(1).replace(".", ",") + ":1";
const rat = (a, b) => fmt(ratio(a, b));

const p = new pptxgen();
p.defineLayout({ name: "L", width: W, height: H }); p.layout = "L";
let n = 0;

// ---------- primitivas
const txt = (s, t, o) => s.addText(t, { isTextBox: true, margin: 0, valign: "top", fontFace: F.b, color: C.w, fit: "none", ...o });
const forma = (s, tipo, o) => s.addShape(tipo, { line: { type: "none" }, ...o });
const rr = (s, x, y, w, h, fill, o = {}) => forma(s, "roundRect", { x, y, w, h, rectRadius: o.r ?? 0.22, fill: fill ? { color: fill } : { type: "none" }, ...(o.line ? { line: o.line } : {}) });
async function icono(nombre, color) {
  const svg = RDS.renderToStaticMarkup(React.createElement(require("react-icons/lu")[nombre], { size: 256, color: "#" + color, strokeWidth: 1.75 }));
  return "image/png;base64," + (await sharp(Buffer.from(svg)).png().toBuffer()).toString("base64");
}
const ico = async (s, nombre, color, x, y, d) => s.addImage({ data: await icono(nombre, color), x, y, w: d, h: d });
async function telefono(s, ruta, x, y, w) { // captura de la app en marco 390:844 con esquinas redondeadas
  const h = w * 844 / 390, r = w * 0.12, px = Math.round(w * 200), py = Math.round(h * 200);
  const mask = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${px}" height="${py}"><rect width="${px}" height="${py}" rx="${r * 200}"/></svg>`);
  const buf = await sharp(ruta).resize(px, py, { fit: "cover" }).composite([{ input: mask, blend: "dest-in" }]).png().toBuffer();
  s.addImage({ data: "image/png;base64," + buf.toString("base64"), x, y, w, h });
  forma(s, "roundRect", { x, y, w, h, rectRadius: r, fill: { type: "none" }, line: { color: C.uva, width: 3 } });
}
function flecha(s, x1, y1, x2, y2, color, guion) {
  s.addShape("line", { x: Math.min(x1, x2), y: Math.min(y1, y2), w: Math.max(Math.abs(x2 - x1), 0.001), h: Math.max(Math.abs(y2 - y1), 0.001),
    flipH: x2 < x1, flipV: y2 < y1, line: { color, width: 2.25, endArrowType: "triangle", dashType: guion ? "dash" : "solid" } });
}
// motivo: ruta de jugada (círculo, trazo punteado, flecha, cruz). Un solo motivo en todo el deck.
const RUTA = col => `<svg xmlns="http://www.w3.org/2000/svg" width="880" height="380" fill="none" stroke="#${col}" stroke-width="7" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="50" cy="320" r="26"/><path d="M80 300 C 260 240, 300 90, 520 120 S 700 60, 800 50" stroke-dasharray="4 22"/>
  <path d="M770 20 L820 50 L772 84"/><path d="M380 300 l44 44 m0 -44 l-44 44"/><circle cx="800" cy="50" r="6" fill="#84DCC6" stroke="none"/></svg>`;
let rutaPng;
async function ruta() { return rutaPng ??= "image/png;base64," + (await sharp(Buffer.from(RUTA("4B4E6D")), { density: 300 }).resize(1800).png().toBuffer()).toString("base64"); }

async function nuevo(claro = false, conRuta = true) {
  const s = p.addSlide(); n++;
  s.background = { color: claro ? C.claro : C.bg };
  if (conRuta) s.addImage({ data: await ruta(), x: 15.4, y: 0.35, w: 4.3, h: 4.3 * 380 / 880 });
  txt(s, String(n).padStart(2, "0"), { x: W - 1.9, y: H - 0.85, w: 0.7, h: 0.4, fontFace: F.m, fontSize: 14, color: claro ? C.uva : C.acero, align: "right" });
  return s;
}
// título "liviano|PESADO" alineado a la izquierda + subtítulo. Devuelve la y donde empieza el contenido.
function titulo(s, t, sub, claro = false) {
  const [l, h] = t.split("|");
  txt(s, l, { x: M, y: 0.8, w: 13, h: 0.65, fontFace: F.lig, fontSize: 30, color: claro ? C.uva : C.acero });
  txt(s, h.toUpperCase(), { x: M, y: 1.35, w: 14, h: 1.1, fontFace: F.tit, fontSize: 58, charSpacing: -2, color: claro ? C.bg : C.w });
  txt(s, sub, { x: M, y: 2.5, w: 13, h: 0.5, fontFace: F.l, fontSize: 25, color: claro ? C.uva : C.acero });
  return 3.55;
}
const notas = (s, t) => s.addNotes(t);
const rel = (b, c) => ratio(b, "FFFFFF") > ratio(b, "222222") ? "FFFFFF" : "222222"; // texto legible sobre el relleno b

(async () => {
  // ---- 1 portada
  { const d = T.portada, [liv, pes] = d.titulo.split("|"), s = await nuevo(false, false);
    forma(s, "ellipse", { x: 10.6, y: 1.7, w: 9.4, h: 9.4, fill: { color: C.uva } });
    await telefono(s, "capturas/agenda_dark.png", 12.9, 2.2, 4.7);
    txt(s, d.sobretitulo.toUpperCase(), { x: M, y: 1.1, w: 10, h: 0.5, fontFace: F.m, fontSize: 18, charSpacing: 4, color: C.acero });
    txt(s, liv, { x: M, y: 3.3, w: 11, h: 0.8, fontFace: F.lig, fontSize: 38, color: C.acero });
    txt(s, pes, { x: M, y: 3.95, w: 11, h: 2.1, fontFace: F.tit, fontSize: 128, charSpacing: -3, color: C.w });
    txt(s, d.subtitulo, { x: M, y: 6.3, w: 10, h: 0.6, fontFace: F.l, fontSize: 30, color: C.aqua });
    txt(s, d.pie, { x: M, y: 9.9, w: 10, h: 0.4, fontFace: F.m, fontSize: 18, charSpacing: 2, color: C.acero });
    notas(s, d.notas); }

  // ---- 2 problema: texto + línea de tiempo con solapamiento
  { const d = T.problema, s = await nuevo(), y0 = titulo(s, d.titulo, d.subtitulo);
    txt(s, d.texto, { x: M, y: y0, w: 8.2, h: 1.9, fontSize: 28, lineSpacingMultiple: 1.25 });
    d.bloques.forEach((b, i) => {
      const y = y0 + 2.4 + i * 1.7;
      txt(s, b.t.toUpperCase(), { x: M, y, w: 8, h: 0.4, fontFace: F.s, fontSize: 17, charSpacing: 3, color: C.aqua });
      txt(s, b.d, { x: M, y: y + 0.5, w: 8, h: 1.0, fontSize: 23, lineSpacingMultiple: 1.2 });
    });
    const px = 10.6, py = 3.0, pw = 8.2, ph = 7.0, [a, b, c, e] = d.diagrama.nodos;
    rr(s, px, py, pw, ph, C.sup, { r: 0.35 });
    rr(s, px + 0.5, py + 0.45, 3.4, 0.75, C.uva, {});
    txt(s, a.texto, { x: px + 0.5, y: py + 0.45, w: 3.4, h: 0.75, fontFace: F.s, fontSize: 21, align: "center", valign: "middle" });
    const bx = px + 0.5, cx = bx + 2.2, bw = 5.2;
    for (const x of [cx, bx + bw]) forma(s, "line", { x, y: py + 1.55, w: 0, h: 3.0, line: { color: C.coral, width: 2.5, dashType: "dash" } });
    const barra = (x, y, nodo, fill) => {
      rr(s, x, y, bw, 1.3, fill);
      txt(s, [{ text: nodo.texto, options: { fontFace: F.s, fontSize: 20, breakLine: true } }, { text: nodo.sub, options: { fontSize: 16 } }],
        { x: x + 0.3, y, w: bw - 0.5, h: 1.3, valign: "middle", color: "222222" });
    };
    barra(bx, py + 1.75, b, C.aqua); barra(cx, py + 3.05, c, C.acero);
    flecha(s, bx + bw - 0.01, py + 4.85, cx + 0.01, py + 4.85, C.coral); flecha(s, cx + 0.01, py + 4.85, bx + bw - 0.01, py + 4.85, C.coral);
    await ico(s, "LuTriangleAlert", C.coral, cx, py + 5.2, 0.5);
    txt(s, e.texto, { x: cx + 0.7, y: py + 5.2, w: 4.6, h: 0.5, fontFace: F.s, fontSize: 21, color: C.coral, valign: "middle" });
    txt(s, d.leyenda.toUpperCase(), { x: px + 0.5, y: py + 6.2, w: pw - 1, h: 0.4, fontFace: F.m, fontSize: 15, charSpacing: 3, color: C.acero });
    notas(s, d.notas); }

  // ---- 3 quién: filas por rol, con la app y el hito como etiqueta
  { const d = T.quien, s = await nuevo(), y0 = titulo(s, d.titulo, d.subtitulo);
    for (let i = 0; i < d.items.length; i++) {
      const it = d.items[i], k = it.texto.lastIndexOf(". "), cuerpo = it.texto.slice(0, k + 1), tag = it.texto.slice(k + 2).replace(/\.$/, "").replace(/: /, ", ");
      const y = y0 + 0.2 + i * 2.2, ult = i === 0;
      forma(s, "line", { x: M, y, w: 17.6, h: 0, line: { color: C.uva, width: 2.5 } });
      txt(s, it.titulo, { x: M, y: y + 0.35, w: 5.4, h: 1.5, fontFace: F.s, fontSize: 36, lineSpacingMultiple: 1.0 });
      txt(s, cuerpo, { x: 7.2, y: y + 0.4, w: 6.6, h: 1.5, fontSize: 24, lineSpacingMultiple: 1.2 });
      rr(s, 14.6, y + 0.4, 4.2, 0.8, ult ? C.aqua : C.uva, { r: 0.4 });
      txt(s, tag, { x: 14.6, y: y + 0.4, w: 4.2, h: 0.8, fontFace: F.s, fontSize: 19, color: ult ? "222222" : C.w, align: "center", valign: "middle" });
    }
    notas(s, d.notas); }

  // ---- 4 requisitos: rejilla sin tarjetas, sólo el grupo de calidad en relleno
  { const d = T.requisitos, s = await nuevo(), y0 = titulo(s, d.titulo, d.subtitulo), gap = 0.5, cw = (17.6 - gap * 3) / 4;
    d.grupos.forEach((g, i) => {
      const x = M + (i % 4) * (cw + gap), y = y0 + 0.2 + Math.floor(i / 4) * 3.3, cal = i === d.grupos.length - 1;
      if (cal) rr(s, x - 0.3, y - 0.1, cw + 0.3, 3.0, C.uva, { r: 0.3 });
      else forma(s, "line", { x, y, w: cw, h: 0, line: { color: C.aqua, width: 3 } });
      txt(s, g.id, { x, y: y + 0.3, w: cw, h: 0.6, fontFace: F.tit, fontSize: 32, charSpacing: -1, color: cal ? C.w : C.aqua });
      txt(s, g.titulo, { x, y: y + 1.0, w: cw, h: 0.5, fontFace: F.s, fontSize: 26 });
      txt(s, g.texto, { x, y: y + 1.6, w: cw - 0.1, h: 1.4, fontSize: 20, lineSpacingMultiple: 1.2 });
    });
    notas(s, d.notas); }

  // ---- 5 éxito: tabla de filas redondeadas
  { const d = T.exito, s = await nuevo(), y0 = titulo(s, d.titulo, d.subtitulo), an = [2.5, 8.6, 4.2, 2.3];
    let x = M; const xs = an.map(a => { const v = x; x += a; return v; });
    d.cabecera.forEach((t, j) => txt(s, t.toUpperCase(), { x: xs[j] + 0.3, y: y0 - 0.05, w: an[j] - 0.4, h: 0.4, fontFace: F.m, fontSize: 15, charSpacing: 3, color: C.acero }));
    d.filas.forEach((f, i) => {
      const y = y0 + 0.5 + i * 1.04;
      rr(s, M, y, 17.6, 0.92, C.sup, {});
      f.forEach((t, j) => txt(s, t, { x: xs[j] + 0.3, y, w: an[j] - 0.5, h: 0.92, valign: "middle", fontSize: j === 1 ? 18 : 20, lineSpacingMultiple: 1.05,
        ...(j === 0 ? { fontFace: F.s, fontSize: 22 } : j === 2 ? { fontFace: F.s, color: C.aqua } : j === 3 ? { fontFace: F.m, color: C.acero, fontSize: 19 } : {}) }));
    });
    notas(s, d.notas); }

  // ---- 6 arquitectura: diagrama nativo, línea punteada = por construir
  { const d = T.arquitectura, s = await nuevo(), y0 = titulo(s, d.titulo, d.subtitulo), [a, b, cc, dd, e, f] = d.nodos;
    const nw = 3.5, ny = 6.6, nh = 1.5, xs = [M, 6.05, 10.9, 15.75];
    const nodo = (nd, x, y, w, h, fill, fg, borde) => {
      rr(s, x, y, w, h, fill, { r: 0.22, line: borde ? { color: C.acero, width: 2.25, dashType: "dash" } : undefined });
      txt(s, [{ text: nd.texto, options: { fontFace: F.s, fontSize: 24, breakLine: true } }, { text: nd.sub, options: { fontSize: 17 } }],
        { x: x + 0.1, y, w: w - 0.2, h, align: "center", valign: "middle", color: fg });
      return { x, y, w, h };
    };
    const m = [nodo(a, xs[0], ny, nw, nh, C.aqua, "222222"), nodo(b, xs[1], ny, nw, nh, C.aqua, "222222"),
      nodo(cc, xs[2], ny, nw, nh, C.w, "222222"), nodo(dd, xs[3], ny, nw, nh, C.uva, C.w)];
    const g = [nodo(e, 8.9, y0 + 0.5, 3.3, 1.35, null, C.w, true), nodo(f, 12.6, y0 + 0.5, 3.3, 1.35, null, C.w, true)];
    for (let i = 0; i < 3; i++) flecha(s, m[i].x + nw + 0.08, ny + nh / 2, m[i + 1].x - 0.08, ny + nh / 2, C.w);
    g.forEach(q => flecha(s, q.x + q.w / 2, q.y + q.h + 0.08, m[2].x + nw / 2 + (q.x < 10 ? -0.8 : 0.8), ny - 0.08, C.acero, true));
    txt(s, d.etiquetas[0], { x: m[1].x + nw, y: ny + nh + 0.12, w: 1.4, h: 0.4, fontFace: F.m, fontSize: 15, color: C.acero, align: "center" });
    txt(s, d.etiquetas[1].toUpperCase() + ": " + d.etiquetas[2], { x: xs[2], y: ny + nh + 0.7, w: 8.1, h: 0.8, fontFace: F.m, fontSize: 18, color: C.acero, align: "right", lineSpacingMultiple: 1.1 });
    txt(s, d.leyenda, { x: M, y: 9.75, w: 12, h: 0.4, fontFace: F.l, fontSize: 18, color: C.acero });
    notas(s, d.notas); }

  // ---- 7 ux: dos capturas reales (oscuro y claro)
  { const d = T.ux, s = await nuevo(false, false), y0 = titulo(s, d.titulo, d.subtitulo);
    d.bloques.forEach((b, i) => {
      const y = y0 + i * 1.65;
      txt(s, b.t, { x: M, y, w: 8, h: 0.5, fontFace: F.s, fontSize: 28, color: C.aqua });
      txt(s, b.d, { x: M, y: y + 0.55, w: 7.6, h: 0.9, fontSize: 22, lineSpacingMultiple: 1.15 });
    });
    txt(s, d.pie, { x: M, y: 8.75, w: 7.6, h: 1.0, fontFace: F.l, fontSize: 21, color: C.acero, lineSpacingMultiple: 1.15 });
    await telefono(s, "capturas/agenda_dark.png", 10.3, 1.0, 4.07);
    await telefono(s, "capturas/citaciones_light.png", 14.7, 1.0, 4.07);
    txt(s, "Capturas de la app Ionic: Agenda en modo oscuro, Citaciones en modo claro", { x: 10.3, y: 10.15, w: 8.5, h: 0.4, fontFace: F.m, fontSize: 15, color: C.acero });
    notas(s, d.notas); }

  // ---- 8 identidad: paleta con rol, hex y contraste calculado
  { const d = T.identidad, s = await nuevo(false, false), y0 = titulo(s, d.titulo, d.subtitulo);
    d.puntos.forEach((q, i) => {
      const y = y0 + i * 1.6;
      txt(s, q.t, { x: M, y, w: 8.4, h: 0.45, fontFace: F.s, fontSize: 24, color: C.aqua });
      txt(s, q.d, { x: M, y: y + 0.5, w: 8.4, h: 1.0, fontSize: 19, lineSpacingMultiple: 1.15 });
    });
    const sw = [["Carbon Black", "222222", "Fondo del modo oscuro"], ["Dusty Grape", "4B4E6D", "Relleno y botón en modo claro"],
      ["Pearl Aqua", "84DCC6", "Acento y botón principal"], ["Cool Steel", "95A3B3", "Texto secundario"], ["White", "FFFFFF", "Fondo y texto claro"]];
    sw.forEach(([nom, hex, rol], i) => {
      const y = y0 + i * 1.32, x = 10.7, fg = rel(hex), fin = i === 0 ? { color: C.uva, width: 2.5 } : undefined;
      rr(s, x, y, 8.1, 1.15, hex, { r: 0.25, line: fin });
      txt(s, [{ text: nom, options: { fontFace: F.s, fontSize: 23, breakLine: true } }, { text: "#" + hex + "  ·  " + rol, options: { fontSize: 15 } }],
        { x: x + 0.35, y, w: 5.6, h: 1.15, valign: "middle", color: fg });
      txt(s, rat(fg, hex), { x: x + 5.6, y, w: 2.2, h: 1.15, valign: "middle", align: "right", fontFace: F.tit, fontSize: 32, color: fg });
    });
    notas(s, d.notas); }

  // ---- 9 por qué
  { const d = T.porque, s = await nuevo(), y0 = titulo(s, d.titulo, d.subtitulo);
    d.items.forEach((it, i) => {
      const x = M + (i % 2) * 9.1, y = y0 + 0.3 + Math.floor(i / 2) * 3.6;
      txt(s, String(i + 1).padStart(2, "0"), { x, y, w: 1.8, h: 1.3, fontFace: F.tit, fontSize: 72, charSpacing: -2, color: C.aqua });
      txt(s, it.titulo, { x: x + 1.9, y: y + 0.1, w: 6.6, h: 0.55, fontFace: F.s, fontSize: 28 });
      txt(s, it.texto, { x: x + 1.9, y: y + 0.8, w: 6.6, h: 1.8, fontSize: 21, lineSpacingMultiple: 1.2 });
    });
    notas(s, d.notas); }

  // ---- 10 trazabilidad: estado con ícono, color y texto
  { const d = T.trazabilidad, s = await nuevo(), y0 = titulo(s, d.titulo, d.subtitulo), an = [3.2, 3.2, 5.0, 3.3, 2.9];
    let x = M; const xs = an.map(a => { const v = x; x += a; return v; });
    d.cabecera.forEach((t, j) => txt(s, t.toUpperCase(), { x: xs[j], y: y0 - 0.1, w: an[j] - 0.2, h: 0.4, fontFace: F.m, fontSize: 15, charSpacing: 3, color: C.acero }));
    for (let i = 0; i < d.filas.length; i++) {
      const f = d.filas[i], y = y0 + 0.4 + i * 0.88, est = f[4];
      forma(s, "line", { x: M, y, w: 17.6, h: 0, line: { color: C.uva, width: 2 } });
      f.slice(0, 4).forEach((t, j) => txt(s, t, { x: xs[j], y, w: an[j] - 0.3, h: 0.88, valign: "middle", fontSize: 17, lineSpacingMultiple: 1.05,
        ...(j === 0 ? { fontFace: F.s, fontSize: 19 } : j === 3 ? { color: C.acero } : {}) }));
      const [ic, col] = est === "Hecho" ? ["LuCheck", C.aqua] : est === "Por hacer" ? ["LuClock", C.ambar] : ["LuCalendar", C.acero];
      await ico(s, ic, col, xs[4], y + 0.24, 0.4);
      txt(s, est, { x: xs[4] + 0.55, y, w: 2.2, h: 0.88, valign: "middle", fontFace: F.s, fontSize: 19, color: col });
    }
    notas(s, d.notas); }

  // ---- 11 cierre
  { const d = T.cierre, [liv, pes] = d.titulo.split("|"), s = await nuevo(false, false);
    forma(s, "ellipse", { x: 10.6, y: 1.7, w: 9.4, h: 9.4, fill: { color: C.uva } });
    await telefono(s, "capturas/agenda_light.png", 12.9, 2.2, 4.7);
    txt(s, liv, { x: M, y: 3.3, w: 11, h: 0.8, fontFace: F.lig, fontSize: 38, color: C.acero });
    txt(s, pes.toUpperCase(), { x: M, y: 3.95, w: 11, h: 2.1, fontFace: F.tit, fontSize: 128, charSpacing: -3, color: C.w });
    txt(s, d.subtitulo, { x: M, y: 6.3, w: 10, h: 0.7, fontFace: F.l, fontSize: 36, color: C.aqua });
    txt(s, d.pie, { x: M, y: 9.9, w: 10, h: 0.4, fontFace: F.m, fontSize: 18, charSpacing: 2, color: C.acero });
    notas(s, d.notas); }

  // ================= ANEXO (modo claro)
  // ---- A1 paleta
  { const d = T.anexo.paleta, s = await nuevo(true), y0 = titulo(s, d.titulo, d.subtitulo, true);
    const sw = [["Carbon Black", "222222", "Fondo oscuro"], ["Dusty Grape", "4B4E6D", "Relleno claro"], ["Pearl Aqua", "84DCC6", "Acento oscuro"],
      ["Cool Steel", "95A3B3", "Texto tenue"], ["White", "FFFFFF", "Fondo claro"]], gap = 0.3, cw = (17.6 - gap * 4) / 5;
    sw.forEach(([nom, hex, rol], i) => {
      const x = M + i * (cw + gap), fg = rel(hex);
      rr(s, x, y0, cw, 3.2, hex, { r: 0.3, line: i === 4 ? { color: C.acero, width: 1.5 } : undefined });
      txt(s, nom, { x: x + 0.3, y: y0 + 0.3, w: cw - 0.5, h: 0.5, fontFace: F.s, fontSize: 24, color: fg });
      txt(s, "#" + hex, { x: x + 0.3, y: y0 + 0.85, w: cw - 0.5, h: 0.4, fontSize: 19, color: fg });
      txt(s, rol, { x: x + 0.3, y: y0 + 2.5, w: cw - 0.5, h: 0.4, fontSize: 17, color: fg });
    });
    const pill = async (x, y, fondo, col, nombre, hex, icn) => {
      rr(s, x, y, 4.5, 0.95, fondo, { r: 0.47, line: fondo === "FFFFFF" ? { color: col, width: 2 } : undefined });
      await ico(s, icn, col, x + 0.3, y + 0.25, 0.45);
      txt(s, [{ text: nombre + "   ", options: { fontFace: F.s, fontSize: 21 } }, { text: "#" + hex, options: { fontSize: 16 } }], { x: x + 0.95, y, w: 3.4, h: 0.95, valign: "middle", color: col });
    };
    const filas = [["Modo oscuro", "222222", [["Conflicto", C.coral, "LuTriangleAlert"], ["Pendiente", C.ambar, "LuClock"], ["Confirmado", C.aqua, "LuCheck"]]],
      ["Modo claro", "FFFFFF", [["Conflicto", C.rojo, "LuTriangleAlert"], ["Pendiente", C.ambarL, "LuClock"], ["Confirmado", C.verde, "LuCheck"]]]];
    for (let r = 0; r < 2; r++) {
      const [lab, fondo, ps] = filas[r], y = y0 + 3.75 + r * 1.4;
      txt(s, lab, { x: M, y, w: 3.2, h: 0.95, fontFace: F.s, fontSize: 22, color: C.bg, valign: "middle" });
      for (let k = 0; k < 3; k++) await pill(M + 3.3 + k * 4.75, y, fondo, ps[k][1], ps[k][0], ps[k][1], ps[k][2]);
    }
    notas(s, d.notas); }

  // ---- A2 tipografía
  { const d = T.anexo.tipografia, s = await nuevo(true), y0 = titulo(s, d.titulo, d.subtitulo, true);
    const pesos = ["ExtraBold y Light", "Light, Regular, Medium y SemiBold"], fam = [F.tit, F.b];
    d.filas.forEach((f, i) => {
      const x = M + i * 9.1;
      rr(s, x, y0, 8.5, 6.2, C.w, { r: 0.35 });
      txt(s, f.rol.toUpperCase(), { x: x + 0.5, y: y0 + 0.45, w: 7, h: 0.4, fontFace: F.m, fontSize: 16, charSpacing: 4, color: C.uva });
      txt(s, "Aa", { x: x + 0.5, y: y0 + 0.9, w: 7.5, h: 2.4, fontFace: fam[i], fontSize: 150, color: C.bg });
      txt(s, f.fuente, { x: x + 0.5, y: y0 + 3.4, w: 7.5, h: 0.7, fontFace: fam[i], fontSize: 40, color: C.bg });
      txt(s, pesos[i], { x: x + 0.5, y: y0 + 4.15, w: 7.5, h: 0.4, fontSize: 17, color: C.uva });
      txt(s, f.uso, { x: x + 0.5, y: y0 + 4.75, w: 7.4, h: 1.1, fontSize: 22, color: C.bg, lineSpacingMultiple: 1.15 });
    });
    notas(s, d.notas); }

  // ---- A3 estados: oscuro y claro lado a lado, siempre ícono + texto
  { const d = T.anexo.estados, s = await nuevo(true), y0 = titulo(s, d.titulo, d.subtitulo, true);
    const dk = [C.aqua, C.ambar, C.coral, C.coral], lt = [C.verde, C.ambarL, C.rojo, C.rojo], ic = ["LuCheck", "LuClock", "LuX", "LuTriangleAlert"];
    txt(s, "MODO OSCURO", { x: 9.3, y: y0 - 0.15, w: 4, h: 0.35, fontFace: F.m, fontSize: 14, charSpacing: 3, color: C.uva });
    txt(s, "MODO CLARO", { x: 13.6, y: y0 - 0.15, w: 4, h: 0.35, fontFace: F.m, fontSize: 14, charSpacing: 3, color: C.uva });
    for (let i = 0; i < d.filas.length; i++) {
      const f = d.filas[i], y = y0 + 0.35 + i * 1.6;
      txt(s, f.estado, { x: M, y, w: 7.8, h: 0.5, fontFace: F.s, fontSize: 26, color: C.bg });
      txt(s, f.color + "\n" + f.señal, { x: M, y: y + 0.55, w: 7.8, h: 0.8, fontSize: 17, color: C.uva, lineSpacingMultiple: 1.1 });
      for (const [x, fondo, col] of [[9.3, "222222", dk[i]], [13.6, "FFFFFF", lt[i]]]) {
        rr(s, x, y + 0.1, 4.0, 1.0, fondo, { r: 0.5, line: fondo === "FFFFFF" ? { color: col, width: 2 } : undefined });
        await ico(s, ic[i], col, x + 0.35, y + 0.37, 0.46);
        txt(s, f.estado, { x: x + 1.05, y: y + 0.1, w: 2.8, h: 1.0, valign: "middle", fontFace: F.s, fontSize: 22, color: col });
      }
    }
    notas(s, d.notas); }

  // ---- A4 usar / no usar
  { const d = T.anexo.usar, s = await nuevo(true), y0 = titulo(s, d.titulo, d.subtitulo, true);
    for (const [k, lista, col, icn, tit] of [[0, d.si, C.verde, "LuCheck", "Usar"], [1, d.no, C.rojo, "LuX", "No usar"]]) {
      const x = M + k * 9.1;
      rr(s, x, y0, 8.5, 6.3, C.w, { r: 0.35 });
      await ico(s, icn, col, x + 0.5, y0 + 0.4, 0.55);
      txt(s, tit, { x: x + 1.25, y: y0 + 0.35, w: 5, h: 0.65, fontFace: F.tit, fontSize: 32, color: col, valign: "middle" });
      lista.forEach((t, i) => {
        const y = y0 + 1.3 + i * 1.22;
        forma(s, "line", { x: x + 0.5, y, w: 7.5, h: 0, line: { color: C.claro, width: 2 } });
        txt(s, t, { x: x + 0.5, y: y + 0.1, w: 7.4, h: 1.0, valign: "middle", fontSize: 21, color: C.bg, lineSpacingMultiple: 1.1 });
      });
    }
    notas(s, d.notas); }

  await p.writeFile({ fileName: "Playbook_Ev1_rev2.pptx" });
  console.log("ok", n);
})();
