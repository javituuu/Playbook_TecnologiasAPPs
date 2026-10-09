// motor.js (recorte de la skill ppt-estilo-canva: solo tema geometrico y los diseños usados)
const pptxgen = require("pptxgenjs");
const React = require("react");
const RDS = require("react-dom/server");
const sharp = require("sharp");
const W = 20, H = 11.25;
const TEMA = {
  colores: { fondo: "F6F9FC", oscuro: "17324D", medio: "3E6D91", claro: "C5D7E6", suave: "E6EEF5", texto: "22405C", blanco: "FFFFFF" },
  fuentes: { titulo: "League Spartan ExtraBold", ligero: "League Spartan Light", cuerpo: "Inter", subtitulo: "Inter Light", etiqueta: "Inter Medium", enfasis: "Inter SemiBold" },
  titulo: { mayus: true, spc: -2, spcLigero: 0 },
};
async function icono(n, color, px = 256) {
  const mod = require("react-icons/lu");
  if (!mod[n]) throw new Error("Ícono no existe: " + n);
  const svg = RDS.renderToStaticMarkup(React.createElement(mod[n], { size: px, color: "#" + color, strokeWidth: 1.5 }));
  return "image/png;base64," + (await sharp(Buffer.from(svg)).png().toBuffer()).toString("base64");
}
async function svgPng(svg, px) { return "image/png;base64," + (await sharp(Buffer.from(svg), { density: 300 }).resize(px).png().toBuffer()).toString("base64"); }
function mezcla(a, b, t) {
  const p = (h, i) => parseInt(h.slice(i, i + 2), 16);
  return [0, 2, 4].map(i => Math.round(p(a, i) + (p(b, i) - p(a, i)) * t).toString(16).padStart(2, "0")).join("").toUpperCase();
}
function paletaOscura(c) {
  return { ...c, fondo: mezcla(c.oscuro, "000000", 0.45), blanco: mezcla(c.oscuro, "000000", 0.22), oscuro: mezcla(c.suave, "FFFFFF", 0.55),
    texto: mezcla(c.claro, "FFFFFF", 0.6), medio: mezcla(c.claro, "FFFFFF", 0.1), claro: mezcla(c.medio, c.oscuro, 0.45), suave: mezcla(c.oscuro, "000000", 0.05) };
}
async function fotoRedondeada(ruta, wIn, hIn, radioIn = 0.35) {
  const pxW = Math.round(wIn * 150), pxH = Math.round(hIn * 150), r = Math.round(radioIn * 150);
  const mascara = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${pxW}" height="${pxH}"><rect width="${pxW}" height="${pxH}" rx="${r}" ry="${r}"/></svg>`);
  const buf = await sharp(ruta).resize(pxW, pxH, { fit: "cover" }).composite([{ input: mascara, blend: "dest-in" }]).png().toBuffer();
  return "image/png;base64," + buf.toString("base64");
}

class Deck {
  constructor({ modoOscuro = true } = {}) {
    this.t = JSON.parse(JSON.stringify(TEMA));
    if (modoOscuro) this.t.colores = paletaOscura(this.t.colores);
    this.p = new pptxgen(); this.p.defineLayout({ name: "CANVA", width: W, height: H }); this.p.layout = "CANVA";
    this.n = 0; this.cache = {};
  }
  get c() { return this.t.colores; }
  get f() { return this.t.fuentes; }
  txt(s, texto, o) { s.addText(texto, { isTextBox: true, margin: 0, valign: "top", fontFace: this.f.cuerpo, color: this.c.texto, ...o }); }
  forma(s, tipo, o) { s.addShape(tipo, { line: { type: "none" }, ...o }); }
  circ(s, cx, cy, d, color, o = {}) { this.forma(s, "ellipse", { x: cx - d / 2, y: cy - d / 2, w: d, h: d, fill: { color }, ...o }); }
  aro(s, cx, cy, r, a0, a1, g, color) { this.forma(s, "blockArc", { x: cx - r, y: cy - r, w: 2 * r, h: 2 * r, angleRange: [a0, a1], arcThicknessRatio: g / r, fill: { color } }); }
  arcoFino(s, cx, cy, r, a0, a1, color, ancho = 1.25) { s.addShape("arc", { x: cx - r, y: cy - r, w: 2 * r, h: 2 * r, angleRange: [a0, a1], line: { color, width: ancho } }); }
  trama(s, x, y, cols, filas, paso, color, d = 0.07) { for (let i = 0; i < cols; i++) for (let j = 0; j < filas; j++) this.circ(s, x + i * paso, y + j * paso, d, color); }
  async rayado(s, x, y, tam, color, esquina = "bi") {
    const k = `ray${color}${esquina}`;
    if (!this.cache[k]) {
      const cx = esquina.includes("i") ? 0 : 400, cy = esquina.includes("b") ? 400 : 0;
      let l = ""; for (let i = -400; i < 800; i += 34) l += `<line x1="${i}" y1="400" x2="${i + 400}" y2="0" stroke="#${color}" stroke-width="9"/>`;
      this.cache[k] = await svgPng(`<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><defs><clipPath id="c"><circle cx="${cx}" cy="${cy}" r="400"/></clipPath></defs><g clip-path="url(#c)">${l}</g></svg>`, 600);
    }
    s.addImage({ data: this.cache[k], x, y, w: tam, h: tam });
  }
  flecha(s, x1, y1, x2, y2, color, o = {}) {
    s.addShape("line", { x: Math.min(x1, x2), y: Math.min(y1, y2), w: Math.max(Math.abs(x2 - x1), 0.001), h: Math.max(Math.abs(y2 - y1), 0.001),
      flipH: x2 < x1, flipV: y2 < y1, line: { color, width: o.ancho || 2, endArrowType: o.sinPunta ? undefined : "triangle", beginArrowType: o.doble ? "triangle" : undefined, dashType: o.guion ? "dash" : "solid" } });
  }
  // Título apilado: "liviano|GRUESO". Devuelve la y final.
  titulo(s, texto, o = {}) {
    const tt = this.t.titulo, tam = o.tam || 60, x = o.x ?? 2.4, w = o.w ?? 15.2, y0 = o.y ?? 1.2, al = o.align || "left";
    let [ligero, fuerte] = texto.includes("|") ? texto.split("|") : [null, texto];
    if (tt.mayus) fuerte = fuerte.toUpperCase();
    const porLinea = Math.max(1, Math.floor(w / ((tam / 72) * 0.68)));
    const lineas = (t) => t.split("\n").reduce((a, l) => a + Math.max(1, Math.ceil(l.length / porLinea)), 0);
    const base = { fontFace: this.f.titulo, fontSize: tam, charSpacing: tt.spc, color: this.c.oscuro };
    const tamL = Math.round(tam * (o.ratio || 0.5));
    const h = (ligero ? (tamL * 1.25) / 72 : 0) + (tam * lineas(fuerte)) / 72 + 0.08, runs = [];
    if (ligero) runs.push({ text: ligero, options: { fontFace: this.f.ligero, fontSize: tamL, charSpacing: 0, color: this.c.medio, breakLine: true } });
    runs.push({ text: fuerte, options: base });
    this.txt(s, runs, { x, y: y0, w, h, align: al, lineSpacingMultiple: 0.95, fit: "none" });
    return y0 + h;
  }
  subtitulo(s, texto, x, y, o = {}) {
    const centrado = o.align === "center", ancho = o.linea || 1.5, w = o.w || 15.2, lx = centrado ? x + w / 2 - ancho / 2 : x;
    this.forma(s, "line", { x: lx, y: y + 0.22, w: ancho, h: 0, line: { color: this.c.medio, width: 2.25 } });
    if (!texto) return y + 0.45;
    this.txt(s, texto, { x, y: y + 0.45, w, h: 0.6, fontFace: this.f.subtitulo, fontSize: o.tam || 26, color: this.c.medio, align: o.align || "left" });
    return y + 1.1;
  }
  nuevo() { const s = this.p.addSlide(); this.n++; s.background = { color: this.c.fondo }; return s; }
  pagina(s) { this.txt(s, String(this.n).padStart(2, "0"), { x: W - 1.9, y: H - 0.85, w: 0.8, h: 0.4, fontSize: 13, fontFace: this.f.etiqueta, color: this.c.medio, align: "right" }); }
  async decorar(s, v) {
    const { medio, claro, oscuro } = this.c, fuerte = v !== "contenido";
    this.aro(s, W, 0, fuerte ? 2.6 : 1.9, 90, 180, fuerte ? 0.75 : 0.55, medio);
    this.arcoFino(s, W, 0, fuerte ? 3.5 : 2.6, 90, 180, medio);
    this.circ(s, W - (fuerte ? 3.5 : 2.6) * Math.cos(Math.PI / 5), (fuerte ? 3.5 : 2.6) * Math.sin(Math.PI / 5), 0.22, medio);
    await this.rayado(s, W - 1.0, 0, 1.0, medio, "sd");
    this.aro(s, 0, H, fuerte ? 2.2 : 1.6, 270, 360, fuerte ? 0.7 : 0.5, medio);
    await this.rayado(s, 0, H - (fuerte ? 1.2 : 0.9), fuerte ? 1.2 : 0.9, medio, "bi");
    this.arcoFino(s, 0, H, fuerte ? 3.2 : 2.4, 270, 360, medio);
    if (fuerte) {
      this.aro(s, 0, 0, 1.7, 0, 90, 0.5, claro); this.arcoFino(s, 0, 0, 2.5, 0, 90, medio);
      this.aro(s, W, H, 2.0, 180, 270, 0.6, oscuro); this.arcoFino(s, W, H, 2.9, 180, 270, medio);
      this.circ(s, W - 2.9 * Math.cos(Math.PI / 3.2), H - 2.9 * Math.sin(Math.PI / 3.2), 0.22, medio);
    }
    this.trama(s, 0.45, 4.6, 3, 6, 0.24, claro); this.trama(s, W - 0.93, 4.6, 3, 6, 0.24, claro);
  }
  diagrama(s, { nodos, flechas = [], ox = 0, oy = 0, esc = 1, tam = 20 }) {
    const c = this.c, m = {};
    const est = { oscuro: [c.oscuro, c.blanco, null], medio: [c.medio, c.blanco, null], claro: [c.claro, c.oscuro, null], suave: [c.suave, c.oscuro, null], linea: [c.blanco, c.oscuro, c.medio] };
    for (const n of nodos) {
      const [bg, fg, borde] = est[n.estilo || "claro"];
      const x = ox + n.x * esc, y = oy + n.y * esc, w = n.w * esc, h = n.h * esc;
      m[n.id] = { x, y, w, h, cx: x + w / 2, cy: y + h / 2 };
      const tipo = n.forma || "roundRect";
      this.forma(s, tipo, { x, y, w, h, ...(tipo === "roundRect" ? { rectRadius: Math.min(0.2, h / 3.5) } : {}), fill: { color: bg },
        line: borde ? { color: borde, width: n.guion ? 2 : 1.75, dashType: n.guion ? "dash" : "solid" } : { type: "none" } });
      if (!n.texto) continue;
      const partes = [{ text: n.texto, options: { fontFace: this.f.enfasis, fontSize: n.tam || tam, color: fg, breakLine: !!n.sub } }];
      if (n.sub) partes.push({ text: n.sub, options: { fontFace: this.f.cuerpo, fontSize: (n.tam || tam) * 0.72, color: fg } });
      this.txt(s, partes, { x: x + 0.1, y, w: w - 0.2, h, align: "center", valign: "middle", lineSpacingMultiple: 1.05 });
    }
    const borde = (a, b) => { const dx = b.cx - a.cx, dy = b.cy - a.cy, k = Math.min(Math.abs((a.w / 2) / (dx || 1e-9)), Math.abs((a.h / 2) / (dy || 1e-9))); return [a.cx + dx * k, a.cy + dy * k]; };
    for (const e of flechas) {
      const A = m[e.de], B = m[e.a], [x1, y1] = borde(A, B), [x2, y2] = borde(B, A);
      const g = 0.09, L = Math.hypot(x2 - x1, y2 - y1), ux = (x2 - x1) / L, uy = (y2 - y1) / L;
      this.flecha(s, x1 + ux * g, y1 + uy * g, x2 - ux * g, y2 - uy * g, c.medio, { doble: e.doble, guion: e.guion });
      if (e.texto) this.txt(s, e.texto, { x: (x1 + x2) / 2 + (e.dx ?? 0.12), y: (y1 + y2) / 2 - 0.2 + (e.dy || 0), w: e.w || 2.6, h: 0.4, fontSize: e.tam || tam * 0.7, fontFace: this.f.etiqueta, color: c.medio, align: e.align || "left" });
    }
  }
  async marcoFoto(s, x, y, w, h, { foto = true, radio = 0.35, borde = true } = {}) {
    const rr = Math.min(radio, w / 2, h / 2);
    if (typeof foto === "string") {
      s.addImage({ data: await fotoRedondeada(foto, w, h, rr), x, y, w, h });
      if (borde) this.forma(s, "roundRect", { x, y, w, h, rectRadius: rr, fill: { type: "none" }, line: { color: this.c.medio, width: 3 } });
    } else s.addShape("roundRect", { x, y, w, h, rectRadius: rr, fill: { color: this.c.suave }, line: borde ? { color: this.c.medio, width: 3 } : { type: "none" }, objectName: "FOTO_PH" });
  }

  async portada({ sobretitulo, titulo, subtitulo, pie, notas }) {
    const s = this.nuevo(); await this.decorar(s, "portada");
    let y = 2.3;
    if (sobretitulo) { this.txt(s, sobretitulo.toUpperCase(), { x: 3, y, w: 14, h: 0.55, fontFace: this.f.etiqueta, fontSize: 22, charSpacing: 6, color: this.c.medio, align: "center" }); y += 0.8; }
    y = this.titulo(s, titulo, { x: 2.2, y, w: 15.6, tam: 118, ratio: 0.45, align: "center" });
    y = this.subtitulo(s, subtitulo, 2.2, y + 0.05, { w: 15.6, align: "center", tam: 30 });
    if (pie) this.txt(s, pie, { x: 5, y: Math.max(y + 0.5, 8.6), w: 10, h: 0.5, fontFace: this.f.etiqueta, fontSize: 18, charSpacing: 3, color: this.c.texto, align: "center" });
    if (notas) s.addNotes(notas); return s;
  }
  async concepto({ titulo, subtitulo, texto, bloques = [], visual, leyenda, notas }) {
    const s = this.nuevo(); await this.decorar(s, "contenido");
    const vx = 10.9, tx = 2.4, vw = 6.8, vy = 1.35, vh = 7.8;
    let y = this.titulo(s, titulo, { x: tx, y: 1.25, w: 7.9, tam: 60, ratio: 0.6 });
    y = this.subtitulo(s, subtitulo, tx, y, { w: 7.9, tam: 24 }) + 0.3;
    if (texto) { this.txt(s, texto, { x: tx, y, w: 7.7, h: 1.6, fontSize: 24, lineSpacingMultiple: 1.3 }); y += 1.85; }
    for (const b of bloques) {
      this.txt(s, [{ text: b.t, options: { fontFace: this.f.enfasis, fontSize: 23, color: this.c.oscuro, breakLine: true } }, { text: b.d, options: { fontSize: 22 } }], { x: tx, y, w: 7.7, h: 1.5, lineSpacingMultiple: 1.25 });
      y += 1.65;
    }
    this.forma(s, "roundRect", { x: vx, y: vy, w: vw, h: vh, rectRadius: 0.35, fill: { color: this.c.blanco }, line: { color: this.c.medio, width: 3 } });
    const hv = leyenda ? vh - 1.2 : vh - 0.6;
    if (visual) await visual(s, vx + 0.3, vy + 0.3, vw - 0.6, hv - 0.1);
    if (leyenda) {
      this.forma(s, "line", { x: vx + 0.35, y: vy + vh - 0.85, w: vw - 0.7, h: 0, line: { color: this.c.claro, width: 1.25 } });
      this.txt(s, leyenda.toUpperCase(), { x: vx, y: vy + vh - 0.8, w: vw, h: 0.65, fontFace: this.f.etiqueta, fontSize: 16, charSpacing: 3, color: this.c.medio, align: "center", valign: "middle" });
    }
    this.pagina(s); if (notas) s.addNotes(notas); return s;
  }
  async tarjetas({ titulo, subtitulo, items, notas }) {
    const s = this.nuevo(); await this.decorar(s, "contenido");
    let y = this.titulo(s, titulo, { align: "center", y: 1.0, ratio: 0.6 });
    y = this.subtitulo(s, subtitulo, 2.4, y, { align: "center" });
    const n = items.length, gap = 0.45, cw = (15.2 - gap * (n - 1)) / n, cy = y + 1.15, ch = Math.min(4.6, H - 1.4 - cy);
    for (let i = 0; i < n; i++) {
      const x = 2.4 + i * (cw + gap), it = items[i];
      this.forma(s, "roundRect", { x, y: cy, w: cw, h: ch, rectRadius: 0.3, fill: { color: this.c.blanco }, line: { color: i % 2 ? this.c.claro : this.c.medio, width: 2 } });
      this.circ(s, x + cw / 2, cy, 1.35, i % 2 ? this.c.suave : this.c.claro, { line: { color: this.c.fondo, width: 6 } });
      s.addImage({ data: await icono(it.icono, this.c.oscuro), x: x + cw / 2 - 0.35, y: cy - 0.35, w: 0.7, h: 0.7 });
      this.txt(s, it.titulo, { x: x + 0.3, y: cy + 0.95, w: cw - 0.6, h: 0.6, fontFace: this.f.enfasis, fontSize: 25, color: this.c.oscuro, align: "center" });
      this.txt(s, it.texto, { x: x + 0.4, y: cy + 1.7, w: cw - 0.8, h: ch - 1.85, fontSize: it.tam || 21, align: "center", lineSpacingMultiple: 1.25 });
    }
    this.pagina(s); if (notas) s.addNotes(notas); return s;
  }
  async tabla({ titulo, subtitulo, cabecera, filas, anchos, fs = 19, tamTitulo = 60, notas }) {
    const s = this.nuevo(); await this.decorar(s, "contenido");
    let y = this.titulo(s, titulo, { align: "center", y: 0.9, ratio: 0.6, tam: tamTitulo });
    y = this.subtitulo(s, subtitulo, 2.4, y, { align: "center" }) + 0.25;
    const x0 = 2.4, wt = 15.2, total = anchos.reduce((a, b) => a + b, 0), ws = anchos.map(a => (a / total) * wt);
    const hc = 0.7, gap = 0.12, hf = Math.min(1.0, (H - 1.3 - y - hc - gap * (filas.length + 1)) / filas.length);
    this.forma(s, "roundRect", { x: x0, y, w: wt, h: hc, rectRadius: 0.2, fill: { color: this.c.oscuro } });
    let cx = x0;
    cabecera.forEach((t, j) => { this.txt(s, t.toUpperCase(), { x: cx + 0.25, y, w: ws[j] - 0.5, h: hc, fontFace: this.f.etiqueta, fontSize: 16, charSpacing: 2, color: this.c.blanco, valign: "middle", align: j === 0 ? "center" : "left" }); cx += ws[j]; });
    filas.forEach((f, i) => {
      const fy = y + hc + gap + i * (hf + gap);
      this.forma(s, "roundRect", { x: x0, y: fy, w: wt, h: hf, rectRadius: 0.2, fill: { color: i % 2 ? this.c.blanco : this.c.suave }, line: { color: this.c.claro, width: 1 } });
      this.forma(s, "roundRect", { x: x0 + 0.08, y: fy + 0.08, w: ws[0] - 0.16, h: hf - 0.16, rectRadius: 0.15, fill: { color: this.c.medio } });
      let cx2 = x0;
      f.forEach((t, j) => {
        this.txt(s, t, { x: cx2 + 0.25, y: fy, w: ws[j] - 0.5, h: hf, fontSize: fs, valign: "middle", align: j === 0 ? "center" : "left", ...(j === 0 ? { fontFace: this.f.enfasis, color: this.c.blanco } : {}) });
        cx2 += ws[j];
      });
    });
    this.pagina(s); if (notas) s.addNotes(notas); return s;
  }
  async cierre({ titulo = "Muchas|Gracias", subtitulo, pie, notas }) {
    const s = this.nuevo(); await this.decorar(s, "cierre");
    let y = 2.9;
    y = this.titulo(s, titulo, { x: 2.2, y, w: 15.6, tam: 140, ratio: 0.45, align: "center" });
    y = this.subtitulo(s, subtitulo, 2.2, y + 0.05, { w: 15.6, align: "center", tam: 28 });
    if (pie) this.txt(s, pie, { x: 5, y: Math.max(y + 0.4, 8.7), w: 10, h: 0.5, fontFace: this.f.etiqueta, fontSize: 18, charSpacing: 3, color: this.c.texto, align: "center" });
    if (notas) s.addNotes(notas); return s;
  }
  guardar(ruta) { return this.p.writeFile({ fileName: ruta }); }
}
module.exports = { Deck, W, H };
