// deck_rev2b.js: Playbook Ev1, rev2-b. Base clara, banda lateral a sangre codificada por orador, esquinas rectas, rejilla visible.
// Textos desde contenido_b.json. Salida: Playbook_Ev1_rev2b.pptx
const pptxgen = require("pptxgenjs");
const React = require("react");
const RDS = require("react-dom/server");
const sharp = require("sharp");
const { ratio } = require("./contraste");
const T = require(process.env.CONTENIDO || "./contenido_b.json"); // CONTENIDO=./contenido_c.json SALIDA=Playbook_Ev1_rev3b.pptx node deck_rev2b.js

const W = 20, H = 11.25, CX = 5.3, CW = 13.7, CR = CX + CW; // contenido a la derecha de la banda (4,4")
const C = { bg: "222222", sup: "343644", uva: "4B4E6D", aqua: "84DCC6", acero: "95A3B3", w: "FFFFFF",
  coral: "F28B82", ambar: "F2C879", claro: "F1F2F6", rojo: "B3261E", ambarL: "8A5A00", verde: "0E705B" };
const F = { tit: "League Spartan ExtraBold", lig: "League Spartan Light", b: "Inter", l: "Inter Light", m: "Inter Medium", s: "Inter SemiBold" };
const fmt = r => r.toFixed(1).replace(".", ",") + ":1";
const rel = b => ratio(b, "FFFFFF") > ratio(b, "222222") ? "FFFFFF" : "222222";
const fallos = [];
const chk = (n, fg, bg, min = 4.5) => { const r = ratio(fg, bg); if (r < min) fallos.push(`${n}: ${fg} sobre ${bg} = ${r.toFixed(2)}`); return r; };

const p = new pptxgen();
p.defineLayout({ name: "L", width: W, height: H }); p.layout = "L";

// ---------- primitivas
const txt = (s, t, o) => s.addText(t, { isTextBox: true, margin: 0, valign: "top", fontFace: F.b, color: C.bg, fit: "none", ...o });
const rect = (s, x, y, w, h, fill, line) => s.addShape("rect", { x, y, w, h, fill: fill ? { color: fill } : { type: "none" }, line: line || { type: "none" } });
const hl = (s, x, y, w, color, ancho = 1.5) => s.addShape("line", { x, y, w, h: 0, line: { color, width: ancho } });
const vl = (s, x, y, h, color, ancho = 1.5) => s.addShape("line", { x, y, w: 0, h, line: { color, width: ancho } });
async function ico(s, nombre, color, x, y, d) {
  const svg = RDS.renderToStaticMarkup(React.createElement(require("react-icons/lu")[nombre], { size: 256, color: "#" + color, strokeWidth: 1.75 }));
  s.addImage({ data: "image/png;base64," + (await sharp(Buffer.from(svg)).png().toBuffer()).toString("base64"), x, y, w: d, h: d });
}
async function telefono(s, ruta, x, y, w, borde) { // captura 390:844 con esquinas redondeadas
  const h = w * 844 / 390, r = w * 0.12, px = Math.round(w * 200), py = Math.round(h * 200);
  const mask = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${px}" height="${py}"><rect width="${px}" height="${py}" rx="${r * 200}"/></svg>`);
  const buf = await sharp(ruta).resize(px, py, { fit: "cover" }).composite([{ input: mask, blend: "dest-in" }]).png().toBuffer();
  s.addImage({ data: "image/png;base64," + buf.toString("base64"), x, y, w, h });
  s.addShape("roundRect", { x, y, w, h, rectRadius: r, fill: { type: "none" }, line: { color: borde, width: 2.5 } });
  return h;
}
function flecha(s, x1, y1, x2, y2, color, o = {}) {
  s.addShape("line", { x: Math.min(x1, x2), y: Math.min(y1, y2), w: Math.max(Math.abs(x2 - x1), 0.001), h: Math.max(Math.abs(y2 - y1), 0.001),
    flipH: x2 < x1, flipV: y2 < y1, line: { color, width: 2.5, endArrowType: "triangle", beginArrowType: o.doble ? "triangle" : undefined, dashType: o.guion ? "dash" : "solid" } });
}

// ---------- banda lateral por orador: color funcional (quien habla)
const ORADOR = { "Cristóbal": { bg: C.uva, fg: C.w, num: C.aqua }, "Javier": { bg: C.bg, fg: C.w, num: C.aqua }, "Diego": { bg: C.aqua, fg: C.bg, num: C.bg } };
for (const [k, o] of Object.entries(ORADOR)) { chk("banda " + k, o.fg, o.bg); chk("numero " + k, o.num, o.bg, 3); }
const NOMBRES = { "Cristóbal": "Cristóbal", "Javier": "Javier", "Diego": "Diego" };

// título en una sola línea: parte liviana + PARTE PESADA (otro modo que rev2: sin apilar)
function titulo(s, t, sub, oscuro = false, x = CX, w = CW) {
  const [l, h] = t.split("|");
  const c1 = oscuro ? C.acero : C.uva, c2 = oscuro ? C.w : C.bg;
  txt(s, [{ text: l + " ", options: { fontFace: F.lig, fontSize: 50, color: c1 } }, { text: h.toUpperCase(), options: { fontFace: F.tit, fontSize: 50, charSpacing: -1, color: c2 } }],
    { x, y: 0.7, w, h: 1.0 });
  txt(s, sub, { x, y: 1.75, w, h: 0.5, fontFace: F.l, fontSize: 24, color: oscuro ? C.acero : C.uva });
  hl(s, x, 2.5, w, oscuro ? C.uva : C.bg, 2.5);
  return 2.95;
}
chk("sub claro", C.uva, C.claro); chk("sub oscuro", C.acero, C.bg);

let ordenNotas = [];
function nuevoCuerpo(idx, orador, clave) { // diapositivas 2..10 con banda
  const s = p.addSlide(); s.background = { color: C.claro };
  const o = ORADOR[orador];
  rect(s, 0, 0, 4.4, H, o.bg);
  txt(s, String(idx).padStart(2, "0"), { x: 0.6, y: 0.55, w: 3.4, h: 1.8, fontFace: F.tit, fontSize: 120, charSpacing: -3, color: o.num });
  for (let i = 1; i <= 11; i++) { // regla de avance: 11 diapositivas de cuerpo
    const x = 0.6 + (i - 1) * 0.32;
    s.addShape("rect", { x, y: 2.75, w: 0.22, h: 0.22, fill: i === idx ? { color: o.fg } : { type: "none" }, line: { color: o.fg, width: 1.25 } });
  }
  notas(s, clave, orador);
  return s;
}
function notas(s, clave, orador) { const d = clave.notas; s.addNotes(`[${orador}] ${d}`); }

(async () => {
  const strip = async (s, d, y, resalta, etiqueta, pieCol) => { // franja inferior: equipo con nombres completos
    rect(s, 0, y, W, H - y, C.claro);
    txt(s, d.pie.replace(/\s+/g, " ").toUpperCase(), { x: 1.0, y: y + 0.95, w: 3.2, h: 0.9, fontFace: F.m, fontSize: 15, charSpacing: 3, color: C.uva, lineSpacingMultiple: 1.2 });
    d.equipo.forEach((q, i) => {
      const x = 4.6 + i * 5.1, es = q.nombre.split(" ")[0] === resalta;
      rect(s, x, y + 0.5, 4.7, 0.14, es ? C.uva : C.acero);
      if (es) txt(s, etiqueta, { x, y: y + 0.8, w: 4.7, h: 0.35, fontFace: F.m, fontSize: 14, charSpacing: 4, color: C.uva });
      txt(s, q.nombre, { x, y: y + 1.2, w: 4.7, h: 0.6, fontFace: F.tit, fontSize: 30, color: C.bg });
      txt(s, q.rol, { x, y: y + 1.85, w: 4.7, h: 0.45, fontFace: F.l, fontSize: 19, color: C.uva });
    });
  };
  const hero = async (s, bg, foto, borde) => {
    rect(s, 0, 0, 12.6, 8.4, bg); rect(s, 12.6, 0, 7.4, 8.4, C.aqua);
    await telefono(s, foto, 14.65, 0.7, 3.3, borde);
    txt(s, "Captura de la app Ionic", { x: 12.6, y: 8.0, w: 7.4, h: 0.3, fontFace: F.m, fontSize: 13, align: "center", color: C.bg });
  };

  // ---- 1 portada
  { const d = T.portada, [liv, pes] = d.titulo.split("|"), s = p.addSlide(); s.background = { color: C.claro };
    await hero(s, C.bg, "capturas/agenda_dark.png", C.uva);
    txt(s, d.sobretitulo.toUpperCase(), { x: 1.0, y: 0.8, w: 11, h: 0.4, fontFace: F.m, fontSize: 16, charSpacing: 4, color: C.acero });
    txt(s, liv, { x: 1.0, y: 2.6, w: 11.2, h: 0.8, fontFace: F.lig, fontSize: 40, color: C.w });
    txt(s, pes, { x: 1.0, y: 3.4, w: 11.4, h: 2.3, fontFace: F.tit, fontSize: 132, charSpacing: -3, color: C.w });
    txt(s, d.subtitulo, { x: 1.0, y: 6.2, w: 11, h: 0.6, fontFace: F.l, fontSize: 30, color: C.aqua });
    chk("portada aqua", C.aqua, C.bg); chk("acero", C.acero, C.bg);
    await strip(s, d, 8.4, d.orador, "ABRE");
    s.addNotes(`[${d.orador}] ${d.notas}`); }

  // ---- 2 problema
  { const d = T.problema, s = nuevoCuerpo(2, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo);
    txt(s, d.texto, { x: CX, y: y0 + 0.1, w: 6.4, h: 2.2, fontSize: 25, lineSpacingMultiple: 1.2 });
    d.bloques.forEach((b, i) => {
      const x = 12.3 + i * 3.45;
      hl(s, x, y0 + 0.15, 3.2, C.uva, 3);
      txt(s, b.t.toUpperCase(), { x, y: y0 + 0.35, w: 3.2, h: 0.35, fontFace: F.s, fontSize: 15, charSpacing: 3, color: C.uva });
      txt(s, b.d, { x, y: y0 + 0.8, w: 3.2, h: 1.6, fontSize: 18, lineSpacingMultiple: 1.15 });
    });
    const py = 5.5, ph = 4.7, [a, b, c, e] = d.diagrama.nodos;
    rect(s, CX, py, CW, ph, C.w);
    rect(s, CX + 0.4, py + 1.4, 2.8, 0.9, C.uva);
    txt(s, a.texto, { x: CX + 0.4, y: py + 1.4, w: 2.8, h: 0.9, fontFace: F.s, fontSize: 20, color: C.w, align: "center", valign: "middle" });
    const b1x = CX + 3.9, b2x = CX + 6.7, bw = 5.6;
    for (const x of [b2x, b1x + bw]) s.addShape("line", { x, y: py + 0.3, w: 0, h: 3.0, line: { color: C.rojo, width: 2.25, dashType: "dash" } });
    const barra = (x, y, nodo, fill) => {
      rect(s, x, y, bw, 1.15, fill);
      txt(s, [{ text: nodo.texto, options: { fontFace: F.s, fontSize: 20, breakLine: true } }, { text: nodo.sub, options: { fontSize: 15 } }], { x: x + 0.3, y, w: bw - 0.5, h: 1.15, valign: "middle" });
    };
    barra(b1x, py + 0.5, b, C.aqua); barra(b2x, py + 1.95, c, C.acero); chk("barra acero", C.bg, C.acero);
    flecha(s, b1x, py + 3.6, b2x, py + 3.6, C.rojo, { doble: true });
    await ico(s, "LuTriangleAlert", C.rojo, b1x, py + 3.85, 0.45);
    txt(s, e.texto, { x: b1x + 0.6, y: py + 3.85, w: 4, h: 0.45, fontFace: F.s, fontSize: 20, color: C.rojo, valign: "middle" });
    txt(s, d.leyenda.toUpperCase(), { x: CX + 0.4, y: py + 3.5, w: 3.3, h: 0.8, fontFace: F.m, fontSize: 13, charSpacing: 2, color: C.uva, lineSpacingMultiple: 1.2 });
    chk("rojo/blanco", C.rojo, C.w); }

  // ---- 3 quién
  { const d = T.quien, s = nuevoCuerpo(3, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo), gap = 0.3, cw = (CW - 2 * gap) / 3;
    d.items.forEach((it, i) => {
      const k = it.texto.lastIndexOf(". "), cuerpo = it.texto.slice(0, k + 1), tag = it.texto.slice(k + 2).replace(/\.$/, "").replace(/: /, ", ");
      const x = CX + i * (cw + gap), y = y0 + 0.1, h = 7.1, dk = i === 0, fg = dk ? C.w : C.bg;
      rect(s, x, y, cw, h, dk ? C.bg : C.w);
      txt(s, String(i + 1).padStart(2, "0"), { x: x + 0.4, y: y + 0.3, w: 2, h: 1, fontFace: F.tit, fontSize: 54, color: dk ? C.aqua : C.uva });
      txt(s, it.titulo, { x: x + 0.4, y: y + 1.5, w: cw - 0.8, h: 1.5, fontFace: F.s, fontSize: 32, color: fg, lineSpacingMultiple: 1.0 });
      txt(s, cuerpo, { x: x + 0.4, y: y + 3.1, w: cw - 0.8, h: 2.3, fontSize: 21, color: fg, lineSpacingMultiple: 1.2 });
      rect(s, x + 0.4, y + h - 1.3, cw - 0.8, 0.8, dk ? C.aqua : C.uva);
      txt(s, tag, { x: x + 0.4, y: y + h - 1.3, w: cw - 0.8, h: 0.8, fontFace: F.s, fontSize: 18, color: dk ? C.bg : C.w, align: "center", valign: "middle" });
    }); chk("q aqua", C.aqua, C.bg, 3); }

  // ---- 4 requisitos: rejilla visible 4 x 2
  { const d = T.requisitos, s = nuevoCuerpo(4, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo), cw = CW / 4, ch = 3.65, gy = y0 + 0.1;
    rect(s, CX, gy, CW, ch * 2, C.w);
    d.grupos.forEach((g, i) => {
      const x = CX + (i % 4) * cw, y = gy + Math.floor(i / 4) * ch, cal = i === 7;
      if (cal) rect(s, x, y, cw, ch, C.uva);
      txt(s, g.id, { x: x + 0.3, y: y + 0.3, w: cw - 0.5, h: 0.6, fontFace: F.tit, fontSize: 26, color: cal ? C.w : C.uva });
      txt(s, g.titulo, { x: x + 0.3, y: y + 1.0, w: cw - 0.5, h: 0.5, fontFace: F.s, fontSize: 25, color: cal ? C.w : C.bg });
      txt(s, g.texto, { x: x + 0.3, y: y + 1.6, w: cw - 0.55, h: 1.9, fontSize: 18, color: cal ? C.w : C.bg, lineSpacingMultiple: 1.15 });
    });
    for (let i = 1; i < 4; i++) vl(s, CX + i * cw, gy, ch * 2, C.claro, 2);
    hl(s, CX, gy + ch, CW, C.claro, 2); }

  // ---- 5 éxito: tabla
  { const d = T.exito, s = nuevoCuerpo(5, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo), an = [2.2, 6.9, 2.8, 1.8];
    let x = CX; const xs = an.map(a => { const v = x; x += a; return v; });
    rect(s, CX, y0, CW, 0.65, C.bg);
    d.cabecera.forEach((t, j) => txt(s, t.toUpperCase(), { x: xs[j] + 0.3, y: y0, w: an[j] - 0.4, h: 0.65, fontFace: F.m, fontSize: 14, charSpacing: 3, color: C.w, valign: "middle" }));
    const hf = 1.07;
    d.filas.forEach((f, i) => {
      const y = y0 + 0.65 + 0.08 + i * (hf + 0.06);
      rect(s, CX, y, CW, hf, C.w); rect(s, CX, y, 0.12, hf, C.aqua);
      f.forEach((t, j) => txt(s, t, { x: xs[j] + 0.3, y, w: an[j] - 0.45, h: hf, valign: "middle", fontSize: j === 1 ? 17 : 19, lineSpacingMultiple: 1.05,
        ...(j === 0 ? { fontFace: F.s, fontSize: 21 } : j === 2 ? { fontFace: F.s, fontSize: 18 } : j === 3 ? { fontFace: F.s, color: C.uva, fontSize: 18 } : {}) }));
    }); }

  // ---- 6 arquitectura
  { const d = T.arquitectura, s = nuevoCuerpo(6, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo), [a, b, cc, dd, e, f] = d.nodos;
    const nw = 2.95, g = 0.63, ny = 6.2, nh = 1.6, xs = [0, 1, 2, 3].map(i => CX + i * (nw + g));
    const nodo = (nd, x, y, w, h, fill, fg, borde) => {
      if (borde) s.addShape("rect", { x, y, w, h, fill: { type: "none" }, line: { color: C.uva, width: 2.5, dashType: "dash" } }); else rect(s, x, y, w, h, fill);
      txt(s, [{ text: nd.texto, options: { fontFace: F.s, fontSize: 23, breakLine: true } }, { text: nd.sub, options: { fontSize: 16 } }],
        { x: x + 0.1, y, w: w - 0.2, h, align: "center", valign: "middle", color: fg });
      return { x, y, w, h };
    };
    const m = [nodo(a, xs[0], ny, nw, nh, C.aqua, C.bg), nodo(b, xs[1], ny, nw, nh, C.aqua, C.bg), nodo(cc, xs[2], ny, nw, nh, C.bg, C.w), nodo(dd, xs[3], ny, nw, nh, C.uva, C.w)];
    const gq = [nodo(e, CX + 5.2, y0 + 0.2, 3.4, 1.4, null, C.bg, true), nodo(f, CX + 9.3, y0 + 0.2, 3.4, 1.4, null, C.bg, true)];
    for (let i = 0; i < 3; i++) flecha(s, m[i].x + nw + 0.04, ny + nh / 2, m[i + 1].x - 0.04, ny + nh / 2, C.bg);
    gq.forEach((q, i) => flecha(s, q.x + q.w / 2, q.y + q.h + 0.06, m[2].x + nw / 2 + (i ? 0.8 : -0.8), ny - 0.06, C.uva, { guion: true }));
    txt(s, d.etiquetas[0], { x: m[1].x + nw - 0.6, y: ny + nh + 0.12, w: 1.85, h: 0.4, fontFace: F.m, fontSize: 15, color: C.uva, align: "center" });
    txt(s, d.etiquetas[1].toUpperCase() + ":  " + d.etiquetas[2], { x: xs[2], y: ny + nh + 0.75, w: CR - xs[2], h: 0.7, fontFace: F.m, fontSize: 15, color: C.uva, align: "right" });
    txt(s, d.leyenda, { x: CX, y: 9.85, w: 12, h: 0.4, fontFace: F.l, fontSize: 18, color: C.uva }); }

  // ---- 7 por qué
  { const d = T.porque, s = nuevoCuerpo(7, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo);
    d.items.forEach((it, i) => {
      const y = y0 + 0.1 + i * 1.8;
      hl(s, CX, y, CW, C.bg, 2);
      txt(s, String(i + 1).padStart(2, "0"), { x: CX, y: y + 0.25, w: 1.5, h: 1.2, fontFace: F.tit, fontSize: 56, color: C.uva });
      txt(s, it.titulo, { x: CX + 1.8, y: y + 0.3, w: 4.2, h: 1.2, fontFace: F.s, fontSize: 28 });
      txt(s, it.texto, { x: CX + 6.3, y: y + 0.3, w: CW - 6.4, h: 1.3, fontSize: 20, lineSpacingMultiple: 1.2 });
    }); }

  // ---- 8 ux
  { const d = T.ux, s = nuevoCuerpo(8, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo);
    d.bloques.forEach((b, i) => {
      const y = y0 + 0.1 + i * 1.75;
      rect(s, CX, y, 0.12, 1.4, C.aqua);
      txt(s, b.t, { x: CX + 0.4, y, w: 4.6, h: 0.5, fontFace: F.s, fontSize: 28 });
      txt(s, b.d, { x: CX + 0.4, y: y + 0.55, w: 4.6, h: 0.9, fontSize: 19, lineSpacingMultiple: 1.1 });
    });
    txt(s, d.pie, { x: CX, y: 8.55, w: 5.0, h: 1.4, fontFace: F.l, fontSize: 19, color: C.uva, lineSpacingMultiple: 1.15 });
    rect(s, 10.9, 2.95, W - 10.9, H - 2.95, C.bg);
    const fotos = ["agenda_light", "citaciones_dark", "perfil_light"];
    for (let i = 0; i < 3; i++) await telefono(s, `capturas/${fotos[i]}.png`, 11.4 + i * 2.8, 3.35, 2.5, C.acero);
    txt(s, d.pie_imagen + ": Agenda, Citaciones y Perfil, en modo claro y oscuro", { x: 11.4, y: 9.3, w: 7.7, h: 0.7, fontFace: F.m, fontSize: 14, color: C.acero, lineSpacingMultiple: 1.15 }); }

  // ---- 9 identidad: ratios calculados
  { const d = T.identidad, s = nuevoCuerpo(9, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo), cw = CW / 4;
    d.puntos.forEach((q, i) => {
      const x = CX + i * cw;
      hl(s, x, y0 + 0.1, cw - 0.3, C.uva, 3);
      txt(s, q.t, { x, y: y0 + 0.3, w: cw - 0.3, h: 0.5, fontFace: F.s, fontSize: 21, color: C.bg });
      txt(s, q.d, { x, y: y0 + 0.9, w: cw - 0.35, h: 2.4, fontSize: 16, lineSpacingMultiple: 1.15 });
    });
    const sw = [["Carbon Black", "222222", "Fondo oscuro"], ["Dusty Grape", "4B4E6D", "Relleno y botón"], ["Pearl Aqua", "84DCC6", "Acento"], ["Cool Steel", "95A3B3", "Texto secundario"], ["White", "FFFFFF", "Fondo claro"]], sy = 6.4, sh = 3.8, sw_ = CW / 5;
    sw.forEach(([nom, hex, rol], i) => {
      const x = CX + i * sw_, fg = rel(hex);
      rect(s, x, sy, sw_, sh, hex, i === 4 ? { color: C.acero, width: 1 } : undefined);
      txt(s, nom, { x: x + 0.25, y: sy + 0.25, w: sw_ - 0.4, h: 0.4, fontFace: F.s, fontSize: 19, color: fg });
      txt(s, "#" + hex, { x: x + 0.25, y: sy + 0.7, w: sw_ - 0.4, h: 0.35, fontSize: 15, color: fg });
      txt(s, rol, { x: x + 0.25, y: sy + 1.05, w: sw_ - 0.4, h: 0.35, fontSize: 14, color: fg });
      txt(s, fmt(ratio(fg, hex)), { x: x + 0.25, y: sy + sh - 1.1, w: sw_ - 0.4, h: 0.8, fontFace: F.tit, fontSize: 34, color: fg });
      chk("swatch " + nom, fg, hex);
    }); }

  // ---- 10 trazabilidad
  { const d = T.trazabilidad, s = nuevoCuerpo(10, d.orador, d), y0 = titulo(s, d.titulo, d.subtitulo), an = [2.9, 2.6, 3.5, 2.3, 2.4];
    let x = CX; const xs = an.map(a => { const v = x; x += a; return v; });
    rect(s, CX, y0, CW, 0.6, C.bg);
    d.cabecera.forEach((t, j) => txt(s, t.toUpperCase(), { x: xs[j] + 0.2, y: y0, w: an[j] - 0.3, h: 0.6, fontFace: F.m, fontSize: 13, charSpacing: 2, color: C.w, valign: "middle" }));
    for (let i = 0; i < d.filas.length; i++) {
      const f = d.filas[i], y = y0 + 0.66 + i * 0.97, est = f[4];
      rect(s, CX, y, CW, 0.92, C.w);
      f.slice(0, 4).forEach((t, j) => txt(s, t, { x: xs[j] + 0.2, y, w: an[j] - 0.3, h: 0.92, valign: "middle", fontSize: 15, lineSpacingMultiple: 1.05, ...(j === 0 ? { fontFace: F.s, fontSize: 17 } : {}) }));
      const [ic, col] = est === "Hecho" ? ["LuCheck", C.verde] : est === "Por hacer" ? ["LuClock", C.ambarL] : ["LuCalendar", C.uva];
      chk("estado " + est, col, C.w);
      await ico(s, ic, col, xs[4] + 0.2, y + 0.26, 0.4);
      txt(s, est, { x: xs[4] + 0.75, y, w: 1.6, h: 0.92, valign: "middle", fontFace: F.s, fontSize: 17, color: col });
    } }

  // ---- 11 cierre
  { const d = T.cierre, [liv, pes] = d.titulo.split("|"), s = p.addSlide(); s.background = { color: C.claro };
    await hero(s, C.uva, "capturas/agenda_light.png", C.bg);
    txt(s, liv, { x: 1.0, y: 2.3, w: 11.2, h: 0.8, fontFace: F.lig, fontSize: 40, color: C.w });
    txt(s, pes.toUpperCase(), { x: 1.0, y: 3.1, w: 11.4, h: 2.3, fontFace: F.tit, fontSize: 132, charSpacing: -3, color: C.w });
    txt(s, d.subtitulo, { x: 1.0, y: 5.95, w: 11, h: 0.8, fontFace: F.l, fontSize: 36, color: C.aqua });
    chk("cierre aqua/uva", C.aqua, C.uva, 3);
    await strip(s, d, 8.4, d.orador, "CIERRA");
    s.addNotes(`[${d.orador}] ${d.notas}`); }

  // ================= ANEXO (oscuro: invierte el esquema del cuerpo)
  const M = 1.4, AW = 17.2;
  const anexo = (n, d) => { const s = p.addSlide(); s.background = { color: C.bg }; rect(s, 0, 0, 0.35, H, C.aqua);
    txt(s, "ANEXO  ·  A" + n, { x: 14, y: 0.45, w: 4.6, h: 0.35, fontFace: F.m, fontSize: 14, charSpacing: 4, color: C.acero, align: "right" });
    const y = titulo(s, d.titulo, d.subtitulo, true, M, AW); if (d.notas) s.addNotes(d.notas); return [s, y]; };

  // ---- A1 paleta
  { const d = T.anexo.paleta, [s, y0] = anexo(1, d);
    const sw = [["Carbon Black", "222222", "Fondo oscuro"], ["Dusty Grape", "4B4E6D", "Relleno claro"], ["Pearl Aqua", "84DCC6", "Acento oscuro"], ["Cool Steel", "95A3B3", "Texto tenue"], ["White", "FFFFFF", "Fondo claro"]], cw = AW / 5;
    sw.forEach(([nom, hex, rol], i) => {
      const x = M + i * cw, fg = rel(hex);
      rect(s, x, y0, cw, 3.3, hex, i === 0 ? { color: C.uva, width: 2 } : undefined);
      txt(s, nom, { x: x + 0.3, y: y0 + 0.3, w: cw - 0.5, h: 0.5, fontFace: F.s, fontSize: 23, color: fg });
      txt(s, "#" + hex, { x: x + 0.3, y: y0 + 0.85, w: cw - 0.5, h: 0.4, fontSize: 18, color: fg });
      txt(s, rol, { x: x + 0.3, y: y0 + 2.6, w: cw - 0.5, h: 0.4, fontSize: 16, color: fg });
      chk("A1 " + nom, fg, hex);
    });
    const filas = [["Modo oscuro", "222222", [["Conflicto", C.coral, "LuTriangleAlert"], ["Pendiente", C.ambar, "LuClock"], ["Confirmado", C.aqua, "LuCheck"]]],
      ["Modo claro", "FFFFFF", [["Conflicto", C.rojo, "LuTriangleAlert"], ["Pendiente", C.ambarL, "LuClock"], ["Confirmado", C.verde, "LuCheck"]]]];
    for (let r = 0; r < 2; r++) {
      const [lab, fondo, ps] = filas[r], y = y0 + 3.85 + r * 1.5;
      txt(s, lab, { x: M, y, w: 2.9, h: 1.0, fontFace: F.s, fontSize: 21, color: C.w, valign: "middle" });
      for (let k = 0; k < 3; k++) {
        const x = M + 3.0 + k * 4.75, [nom, col, ic] = ps[k];
        rect(s, x, y, 4.5, 1.0, fondo, fondo === "222222" ? { color: C.acero, width: 1.5 } : undefined); chk("A1 estado " + nom, col, fondo);
        await ico(s, ic, col, x + 0.3, y + 0.27, 0.46);
        txt(s, [{ text: nom + "   ", options: { fontFace: F.s, fontSize: 20 } }, { text: "#" + col, options: { fontSize: 15 } }], { x: x + 0.95, y, w: 3.4, h: 1.0, valign: "middle", color: col });
      }
    } }

  // ---- A2 tipografía
  { const d = T.anexo.tipografia, [s, y0] = anexo(2, d), pesos = ["ExtraBold y Light", "Light, Regular, Medium y SemiBold"], fam = [F.tit, F.b], bgs = [C.aqua, C.w];
    d.filas.forEach((f, i) => {
      const x = M + i * 8.8;
      rect(s, x, y0, 8.4, 6.4, bgs[i]);
      txt(s, f.rol.toUpperCase(), { x: x + 0.5, y: y0 + 0.45, w: 7, h: 0.4, fontFace: F.m, fontSize: 15, charSpacing: 4, color: C.bg });
      txt(s, "Aa", { x: x + 0.5, y: y0 + 0.9, w: 7.5, h: 2.4, fontFace: fam[i], fontSize: 150, color: C.bg });
      txt(s, f.fuente, { x: x + 0.5, y: y0 + 3.45, w: 7.5, h: 0.7, fontFace: fam[i], fontSize: 38, color: C.bg });
      txt(s, pesos[i], { x: x + 0.5, y: y0 + 4.2, w: 7.5, h: 0.4, fontSize: 17, color: C.bg });
      txt(s, f.uso, { x: x + 0.5, y: y0 + 4.85, w: 7.4, h: 1.2, fontSize: 21, color: C.bg, lineSpacingMultiple: 1.15 });
    }); }

  // ---- A3 estados
  { const d = T.anexo.estados, [s, y0] = anexo(3, d);
    const dk = [C.aqua, C.ambar, C.coral, C.coral], lt = [C.verde, C.ambarL, C.rojo, C.rojo], ic = ["LuCheck", "LuClock", "LuX", "LuTriangleAlert"];
    txt(s, "MODO OSCURO", { x: 9.4, y: y0 - 0.1, w: 4.4, h: 0.35, fontFace: F.m, fontSize: 13, charSpacing: 3, color: C.acero });
    txt(s, "MODO CLARO", { x: 14.2, y: y0 - 0.1, w: 4.4, h: 0.35, fontFace: F.m, fontSize: 13, charSpacing: 3, color: C.acero });
    for (let i = 0; i < d.filas.length; i++) {
      const f = d.filas[i], y = y0 + 0.4 + i * 1.7;
      hl(s, M, y, AW, C.uva, 2);
      txt(s, f.estado, { x: M, y: y + 0.2, w: 7.6, h: 0.5, fontFace: F.s, fontSize: 26, color: C.w });
      txt(s, f.color + "\n" + f.señal, { x: M, y: y + 0.75, w: 7.6, h: 0.8, fontSize: 16, color: C.acero, lineSpacingMultiple: 1.1 });
      for (const [x, fondo, col] of [[9.4, "222222", dk[i]], [14.2, "FFFFFF", lt[i]]]) {
        rect(s, x, y + 0.3, 4.4, 1.0, fondo, fondo === "222222" ? { color: C.acero, width: 1.5 } : undefined); chk("A3 " + f.estado, col, fondo);
        await ico(s, ic[i], col, x + 0.35, y + 0.57, 0.46);
        txt(s, f.estado, { x: x + 1.05, y: y + 0.3, w: 3.2, h: 1.0, valign: "middle", fontFace: F.s, fontSize: 21, color: col });
      }
    } }

  // ---- A4 usar / no usar
  { const d = T.anexo.usar, [s, y0] = anexo(4, d);
    for (const [k, lista, bg, fg, icn, tit] of [[0, d.si, C.aqua, C.bg, "LuCheck", "USAR"], [1, d.no, C.rojo, C.w, "LuX", "NO USAR"]]) {
      const x = M + k * 8.8;
      rect(s, x, y0, 8.4, 0.95, bg); chk("A4 cab " + tit, fg, bg);
      await ico(s, icn, fg, x + 0.4, y0 + 0.24, 0.5);
      txt(s, tit, { x: x + 1.15, y: y0, w: 5, h: 0.95, fontFace: F.tit, fontSize: 30, color: fg, valign: "middle" });
      rect(s, x, y0 + 0.95, 8.4, 5.3, C.sup);
      lista.forEach((t, i) => {
        const y = y0 + 1.1 + i * 1.28;
        if (i) hl(s, x + 0.4, y - 0.08, 7.6, C.uva, 1.5);
        txt(s, t, { x: x + 0.4, y, w: 7.6, h: 1.1, valign: "middle", fontSize: 20, color: C.w, lineSpacingMultiple: 1.1 });
      });
    }
    chk("A4 texto", C.w, C.sup); }

  await p.writeFile({ fileName: process.env.SALIDA || "Playbook_Ev1_rev2b.pptx" });
  console.log(fallos.length ? "CONTRASTE BAJO:\n" + fallos.join("\n") : "contrastes ok", "| diapositivas:", p.slides.length);
})();
