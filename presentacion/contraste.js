// Contraste WCAG 2.x. Uso: node contraste.js  (edita PARES)
const lum = h => { const c = [0,2,4].map(i => parseInt(h.slice(i,i+2),16)/255).map(v => v<=0.03928 ? v/12.92 : ((v+0.055)/1.055)**2.4); return 0.2126*c[0]+0.7152*c[1]+0.0722*c[2]; };
const ratio = (a,b) => { const [x,y] = [lum(a),lum(b)].sort((p,q)=>q-p); return (x+0.05)/(y+0.05); };
module.exports = { ratio };
if (require.main === module) {
  const pares = JSON.parse(process.argv[2] || "[]");
  for (const [n,fg,bg] of pares) console.log(n.padEnd(34), fg, "sobre", bg, ratio(fg,bg).toFixed(2));
}
