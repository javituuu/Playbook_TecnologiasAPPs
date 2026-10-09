const sharp = require("sharp");
const { ratio } = require("./contraste");
const base = { fondo: "0B0E14", tarjeta: "161B25", borde: "222C3A", texto: "FFFFFF", tenue: "8B95A5", conflicto: "F87171", ok: "34D399", pend: "FBBF24", valorant: "F472B6", lol: "60A5FA", practica: "1CA2B7" };
const P = {
  A: { nombre: "A · Noche azul", sub: "Figma real con dos correcciones de contraste", c: { ...base, acento: "3B82F6", boton: "2563EB", botonTxt: "FFFFFF" } },
  B: { nombre: "B · Cancha lima", sub: "Un acento lima eléctrico; riesgo: se parece al verde de «confirmado»", c: { ...base, acento: "C6F432", boton: "C6F432", botonTxt: "0B0E14" } },
  C: { nombre: "C · Azul y ámbar", sub: "Armonía complementaria; riesgo: el ámbar también es «pendiente»", c: { ...base, acento: "3B82F6", boton: "F5A524", botonTxt: "0B0E14", sec: "F5A524" } },
};
const F = (w) => `font-family="Inter${w ? " " + w : ""}"`;
const t = (x, y, s, size, fill, w = "", extra = "") => `<text x="${x}" y="${y}" ${F(w)} font-size="${size}" fill="#${fill}" ${extra}>${s}</text>`;
function hoja(k) {
  const { nombre, sub, c } = P[k];
  const roles = [["Fondo", c.fondo, c.texto, "texto"], ["Tarjeta", c.tarjeta, c.texto, "texto"], ["Acento", c.acento, c.fondo, "fondo"], ["Botón", c.boton, c.botonTxt, "texto del botón"],
    ["Texto tenue", c.tenue, c.fondo, "sobre fondo", true], ["Conflicto", c.conflicto, c.tarjeta, "sobre tarjeta", true], ["Confirmado", c.ok, c.tarjeta, "sobre tarjeta", true],
    ["Pendiente", c.pend, c.tarjeta, "sobre tarjeta", true], ["Valorant", c.valorant, c.tarjeta, "sobre tarjeta", true], ["LoL", c.lol, c.tarjeta, "sobre tarjeta", true], ["Práctica", c.practica, c.tarjeta, "sobre tarjeta", true]];
  if (c.sec) roles.splice(4, 0, ["Secundario", c.sec, c.tarjeta, "sobre tarjeta", true]);
  let sw = "", y = 190;
  for (const [n, hex, other, lbl, inv] of roles) {
    const r = inv ? ratio(hex, other) : ratio(hex, other);
    const ok = r >= 4.5 ? "AA" : r >= 3 ? "solo 3:1" : "falla";
    sw += `<rect x="70" y="${y}" width="88" height="56" rx="12" fill="#${hex}" stroke="#${c.borde}" stroke-width="2"/>`;
    sw += t(180, y + 24, n, 24, c.texto, "SemiBold") + t(180, y + 50, `#${hex}`, 19, c.tenue);
    sw += t(560, y + 24, `${r.toFixed(1).replace(".", ",")}:1 ${ok}`, 22, r >= 4.5 ? c.ok : r >= 3 ? c.pend : c.conflicto, "SemiBold", 'text-anchor="end"') + t(560, y + 50, lbl, 17, c.tenue, "", 'text-anchor="end"');
    y += roles.length > 11 ? 60 : 66;
  }
  // mini app
  const X = 720, Y = 150, W = 360, H = 780, ix = X + 24, iw = W - 48;
  const tri = (cx, cy, col) => `<path d="M${cx} ${cy - 9} L${cx + 10} ${cy + 8} L${cx - 10} ${cy + 8} Z" fill="none" stroke="#${col}" stroke-width="2.2" stroke-linejoin="round"/><line x1="${cx}" y1="${cy - 2}" x2="${cx}" y2="${cy + 3}" stroke="#${col}" stroke-width="2.2"/>`;
  const carta = (yy, hora, titulo, chip, chipCol, conflicto) => `<rect x="${ix}" y="${yy}" width="${iw}" height="118" rx="16" fill="#${c.tarjeta}" stroke="#${c.borde}" stroke-width="2"/>` +
    t(ix + 18, yy + 32, hora, 17, chipCol, "Medium") + `<rect x="${ix + 176}" y="${yy + 14}" width="${chip.length * 9 + 20}" height="24" rx="12" fill="#${chipCol}" fill-opacity="0.18"/>` + t(ix + 186, yy + 31, chip, 13, chipCol, "SemiBold") +
    (conflicto ? `<rect x="${ix + iw - 140}" y="${yy + 80}" width="124" height="26" rx="13" fill="#${c.conflicto}" fill-opacity="0.16" stroke="#${c.conflicto}"/>` + tri(ix + iw - 122, yy + 93, c.conflicto) + t(ix + iw - 106, yy + 98, "Conflicto", 14, c.conflicto, "SemiBold") : "") +
    t(ix + 18, yy + 72, titulo, 20, c.texto, "SemiBold") + t(ix + 18, yy + 100, "Online · LAS", 15, c.tenue);
  const app = `<rect x="${X}" y="${Y}" width="${W}" height="${H}" rx="38" fill="#${c.fondo}" stroke="#${c.borde}" stroke-width="3"/>` +
    t(ix, Y + 62, "Mi Agenda", 28, c.texto, "SemiBold") + `<rect x="${ix}" y="${Y + 84}" width="52" height="3" rx="2" fill="#${c.acento}"/>` +
    carta(Y + 112, "20:00 – 21:30", "LoL: Titans vs UNAB", "PARTIDO", c.lol, true) + carta(Y + 244, "20:30 – 22:00", "Valorant: Nova vs INACAP", "PARTIDO", c.valorant, true) + carta(Y + 376, "Jue 19:00 – 21:00", "Práctica LoL", "PRÁCTICA", c.practica, false) +
    `<rect x="${ix}" y="${Y + 520}" width="${iw}" height="56" rx="14" fill="#${c.boton}"/>` + t(X + W / 2, Y + 555, "Aceptar citación", 20, c.botonTxt, "SemiBold", 'text-anchor="middle"') +
    `<rect x="${ix}" y="${Y + 590}" width="${iw}" height="56" rx="14" fill="none" stroke="#${c.conflicto}" stroke-width="2"/>` + t(X + W / 2, Y + 625, "Rechazar", 20, c.conflicto, "SemiBold", 'text-anchor="middle"') +
    `<rect x="${ix}" y="${Y + 664}" width="96" height="30" rx="15" fill="#${c.ok}" fill-opacity="0.16"/>` + t(ix + 14, Y + 685, "Confirmado", 14, c.ok, "SemiBold") + `<rect x="${ix + 108}" y="${Y + 664}" width="84" height="30" rx="15" fill="#${c.pend}" fill-opacity="0.16"/>` + t(ix + 122, Y + 685, "Pendiente", 14, c.pend, "SemiBold") +
    `<clipPath id="ph"><rect x="${X}" y="${Y}" width="${W}" height="${H}" rx="38"/></clipPath><rect x="${X}" y="${Y + H - 70}" width="${W}" height="70" fill="#${c.tarjeta}" clip-path="url(#ph)"/><rect x="${X}" y="${Y}" width="${W}" height="${H}" rx="38" fill="none" stroke="#${c.borde}" stroke-width="3"/>` + [["Agenda", 0], ["Citaciones", 1], ["Perfil", 2]].map(([n, i]) => `<circle cx="${X + 60 + i * 120}" cy="${Y + H - 50}" r="8" fill="${i == 0 ? "#" + c.acento : "none"}" stroke="#${i == 0 ? c.acento : c.tenue}" stroke-width="2"/>` + t(X + 60 + i * 120, Y + H - 18, n, 14, i == 0 ? c.acento : c.tenue, "Medium", 'text-anchor="middle"')).join("");
  // espectro comparativo: texto e iconos de estado sin color
  const nota = t(1140, 190, "Estado sin depender del color", 22, c.texto, "SemiBold") + t(1140, 222, "Cada aviso lleva ícono y palabra:", 17, c.tenue) +
    [[c.conflicto, "Conflicto", "triángulo + texto"], [c.ok, "Confirmado", "texto en el chip"], [c.pend, "Pendiente", "texto en el chip"]].map(([col, n, d], i) => `<rect x="1140" y="${250 + i * 74}" width="360" height="58" rx="14" fill="#${c.tarjeta}" stroke="#${c.borde}" stroke-width="2"/><circle cx="1172" cy="${279 + i * 74}" r="9" fill="#${col}"/>` + t(1196, 285 + i * 74, n, 20, c.texto, "SemiBold") + t(1486, 285 + i * 74, d, 15, c.tenue, "", 'text-anchor="end"')).join("") +
    t(1140, 520, (k === "B" ? "Un solo acento" : k === "C" ? "Dos acentos" : "Dos tonos de azul"), 22, c.texto, "SemiBold") + t(1140, 550, k === "B" ? "El acento lima no necesita variante." : "Relleno #3B82F6 (5,3:1 sobre fondo).", 17, c.tenue) + t(1140, 574, k === "B" ? "Texto oscuro encima: 15,1:1." : k === "A" ? "Botón #2563EB con texto blanco: 5,2:1." : "Botón ámbar con texto oscuro: 9,5:1.", 17, c.tenue);
  return `<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1000"><rect width="1600" height="1000" fill="#${c.fondo}"/>` +
    t(70, 84, nombre, 44, c.texto, "SemiBold") + t(70, 124, sub, 21, c.tenue) + t(70, 168, "Rol, hex y contraste WCAG", 17, c.acento, "Medium", 'letter-spacing="2"') + sw + app + nota +
    t(70, 960, "Contrastes calculados con la fórmula WCAG 2.x (contraste.js). AA: 4,5:1 texto, 3:1 componentes.", 16, c.tenue) + `</svg>`;
}
(async () => { for (const k of Object.keys(P)) { await sharp(Buffer.from(hoja(k)), { density: 144 }).png().toFile(`paletas/paleta_${k}.png`); console.log("ok", k); } })();
