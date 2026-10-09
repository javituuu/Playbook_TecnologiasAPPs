const sharp = require("sharp");
const { ratio } = require("./contraste");
const mz = (a, b, t) => { const p = (h, i) => parseInt(h.slice(i, i + 2), 16); return [0, 2, 4].map(i => Math.round(p(a, i) + (p(b, i) - p(a, i)) * t).toString(16).padStart(2, "0")).join("").toUpperCase(); };
const CARBON = "222222", UVA = "4B4E6D", AQUA = "84DCC6", ACERO = "95A3B3", BLANCO = "FFFFFF";
const oscuro = { bg: CARBON, surf: mz(CARBON, UVA, .45), borde: UVA, texto: BLANCO, tenue: ACERO, acc: AQUA, btn: AQUA, btnTxt: CARBON, conf: "F28B82", pend: "F2C879", ok: AQUA };
const claro = { bg: BLANCO, surf: "ECEEF1", borde: "D5D9E0", texto: CARBON, tenue: UVA, acc: UVA, btn: UVA, btnTxt: BLANCO, conf: "B3261E", pend: "8A5A00", ok: "0E705B" };
const pagina = "F1F2F6", tinta = CARBON, tenueP = "5B5E73";
const F = w => `font-family="${w}"`, T = (x, y, s, size, fill, w = "Inter", ex = "") => `<text x="${x}" y="${y}" ${F(w)} font-size="${size}" fill="#${fill}" ${ex}>${s}</text>`;
const fmt = r => r.toFixed(1).replace(".", ",");

function telefono(X, Y, W, H, c, id) {
  const ix = X + 22, iw = W - 44, tri = (cx, cy, col) => `<path d="M${cx} ${cy - 8} L${cx + 9} ${cy + 7} L${cx - 9} ${cy + 7} Z" fill="none" stroke="#${col}" stroke-width="2" stroke-linejoin="round"/><line x1="${cx}" y1="${cy - 2}" x2="${cx}" y2="${cy + 2}" stroke="#${col}" stroke-width="2"/>`;
  const pill = (x, y, txt, fill, fg) => `<rect x="${x}" y="${y}" width="${txt.length * 8.4 + 22}" height="23" rx="11.5" fill="#${fill}"/>` + T(x + 11, y + 16, txt, 12, fg, "Inter SemiBold");
  const carta = (yy, hora, titulo, chip, cf, cfg, conflicto) => `<rect x="${ix}" y="${yy}" width="${iw}" height="108" rx="16" fill="#${c.surf}" stroke="#${c.borde}" stroke-width="1.5"/>` +
    T(ix + 16, yy + 30, hora, 15, c.texto, "Inter Medium") + pill(ix + 140, yy + 13, chip, cf, cfg) + T(ix + 16, yy + 66, titulo, 18, c.texto, "Inter SemiBold") +
    T(ix + 16, yy + 92, "Online · LAS", 13, c.tenue) +
    (conflicto ? `<rect x="${ix + iw - 118}" y="${yy + 74}" width="104" height="25" rx="12.5" fill="none" stroke="#${c.conf}" stroke-width="1.6"/>` + tri(ix + iw - 100, yy + 86, c.conf) + T(ix + iw - 86, yy + 91, "Conflicto", 13, c.conf, "Inter SemiBold") : "");
  return `<clipPath id="${id}"><rect x="${X}" y="${Y}" width="${W}" height="${H}" rx="40"/></clipPath>` +
    `<rect x="${X}" y="${Y}" width="${W}" height="${H}" rx="40" fill="#${c.bg}"/>` +
    T(ix, Y + 70, "Mi Agenda", 26, c.texto, "Inter SemiBold") + `<rect x="${ix}" y="${Y + 86}" width="44" height="3.5" rx="2" fill="#${c.acc}"/>` +
    carta(Y + 112, "20:00 – 21:30", "LoL: Titans vs UNAB", "PARTIDO", AQUA, CARBON, true) + carta(Y + 236, "20:30 – 22:00", "Valorant: Nova vs INACAP", "PARTIDO", UVA, BLANCO, true) +
    `<rect x="${ix}" y="${Y + 372}" width="${iw}" height="52" rx="14" fill="#${c.btn}"/>` + T(X + W / 2, Y + 405, "Aceptar citación", 18, c.btnTxt, "Inter SemiBold", 'text-anchor="middle"') +
    `<rect x="${ix}" y="${Y + 436}" width="${iw}" height="52" rx="14" fill="#${c.bg}" stroke="#${c.conf}" stroke-width="2"/>` + T(X + W / 2, Y + 469, "Rechazar", 18, c.conf, "Inter SemiBold", 'text-anchor="middle"') +
    `<rect x="${ix}" y="${Y + 506}" width="92" height="28" rx="14" fill="none" stroke="#${c.ok}" stroke-width="1.6"/>` + T(ix + 12, Y + 525, "Confirmado", 13, c.ok, "Inter SemiBold") +
    `<rect x="${ix + 102}" y="${Y + 506}" width="84" height="28" rx="14" fill="none" stroke="#${c.pend}" stroke-width="1.6"/>` + T(ix + 114, Y + 525, "Pendiente", 13, c.pend, "Inter SemiBold") +
    `<rect x="${X}" y="${Y + H - 70}" width="${W}" height="70" fill="#${c.surf}" clip-path="url(#${id})"/>` +
    [["Agenda", 0], ["Citaciones", 1], ["Perfil", 2]].map(([n, i]) => { const cx = X + W / 6 + i * W / 3, on = i === 0; return `<circle cx="${cx}" cy="${Y + H - 46}" r="7.5" fill="${on ? "#" + c.acc : "none"}" stroke="#${on ? c.acc : c.tenue}" stroke-width="2"/>` + T(cx, Y + H - 20, n, 13, on ? c.acc : c.tenue, "Inter Medium", 'text-anchor="middle"'); }).join("") +
    `<rect x="${X}" y="${Y}" width="${W}" height="${H}" rx="40" fill="none" stroke="#${c === oscuro ? "6A6E92" : "FFFFFF"}" stroke-width="3"/>`;
}
function tile(x, y, w, h, hex, nombre, uso, fg, borde) {
  return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="26" fill="#${hex}"${borde ? ` stroke="#D5D9E0" stroke-width="2"` : ""}/>` + T(x + 20, y + 38, nombre, 22, fg, "League Spartan ExtraBold") + T(x + 20, y + 60, "#" + hex, 14, fg, "Inter Medium", 'opacity="0.85"') + T(x + 20, y + h - 16, uso, 13, fg, "Inter", 'opacity="0.9"');
}
const par = (lbl, f, b) => { const r = ratio(f, b), ok = r >= 4.5; return { lbl, r, ok }; };
const fila = (x, y, p) => `<circle cx="${x + 6}" cy="${y - 5}" r="5" fill="#${p.ok ? "2E8B6E" : "C0392B"}"/>` + T(x + 22, y, p.lbl, 15, tinta, "Inter Medium") + T(x + 330, y, fmt(p.r) + ":1", 15, p.ok ? tinta : "C0392B", "Inter SemiBold", 'text-anchor="end"');
const pares1 = [par("Blanco sobre Carbon", BLANCO, CARBON), par("Aqua sobre Carbon", AQUA, CARBON), par("Acero sobre Carbon", ACERO, CARBON), par("Carbon sobre botón Aqua", CARBON, AQUA), par("Uva sobre Carbon", UVA, CARBON)];
const pares2 = [par("Carbon sobre Blanco", CARBON, BLANCO), par("Uva sobre Blanco", UVA, BLANCO), par("Blanco sobre botón Uva", BLANCO, UVA), par("Aqua sobre Blanco", AQUA, BLANCO), par("Acero sobre Blanco", ACERO, BLANCO)];
const estados = [par("Conflicto coral / superficie", oscuro.conf, oscuro.surf), par("Pendiente ámbar / superficie", oscuro.pend, oscuro.surf), par("Conflicto rojo / blanco", claro.conf, BLANCO), par("Pendiente ámbar / blanco", claro.pend, BLANCO), par("Confirmado verde / blanco", claro.ok, BLANCO)];
let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1000"><rect width="1600" height="1000" fill="#${pagina}"/>` +
  T(70, 96, "D · Aqua y uva", 54, tinta, "League Spartan ExtraBold") + T(70, 134, "Paleta del equipo (Coolors, 5 colores) más cinco colores de estado añadidos", 19, tenueP) +
  tile(70, 176, 380, 250, CARBON, "Carbon Black", "Fondo del modo oscuro · texto en claro", BLANCO) +
  tile(462, 176, 298, 118, UVA, "Dusty Grape", "Acento del modo claro · relleno", BLANCO) +
  tile(462, 306, 144, 120, AQUA, "Pearl Aqua", "Acento oscuro", CARBON) + tile(618, 306, 142, 120, ACERO, "Cool Steel", "Texto tenue", CARBON) +
  tile(70, 438, 380, 104, BLANCO, "White", "Fondo claro · texto sobre uva", CARBON, true) +
  `<rect x="462" y="438" width="298" height="96" rx="26" fill="none" stroke="#C5C9D6" stroke-width="2" stroke-dasharray="7 7"/>` + T(484, 472, "Añadidos para el estado", 16, tinta, "Inter SemiBold") +
  [[oscuro.conf, 0], [oscuro.pend, 1], [claro.conf, 2], [claro.pend, 3], [claro.ok, 4]].map(([h, i]) => `<circle cx="${493 + i * 56}" cy="506" r="17" fill="#${h}"/>`).join("") +
  T(70, 590, "Modo oscuro", 18, tinta, "League Spartan ExtraBold") + pares1.map((p, i) => fila(70, 620 + i * 30, p)).join("") +
  T(430, 590, "Modo claro", 18, tinta, "League Spartan ExtraBold") + pares2.map((p, i) => fila(430, 620 + i * 30, p)).join("") +
  T(70, 800, "Estados añadidos (el origen no trae rojo ni ámbar)", 18, tinta, "League Spartan ExtraBold") + estados.slice(0, 3).map((p, i) => fila(70, 830 + i * 30, p)).join("") + estados.slice(3).map((p, i) => fila(430, 830 + i * 30, p)).join("") +
  T(70, 962, "AA: 4,5:1 texto, 3:1 componentes. Rojo = no cumple; esos pares solo sirven como relleno, nunca como texto.", 14, tenueP) +
  `<rect x="810" y="60" width="730" height="820" rx="48" fill="#${UVA}"/>` + telefono(850, 130, 320, 640, oscuro, "p1") + telefono(1190, 200, 320, 640, claro, "p2") + `</svg>`;
sharp(Buffer.from(svg), { density: 144 }).png().toFile("paletas/Paleta_D.png").then(() => console.log("ok"));
