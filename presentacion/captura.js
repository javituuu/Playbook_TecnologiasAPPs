// Captura la app Ionic real (www/) con Chromium vía CDP. Requiere Laravel en :8000 y www/ servido en :4200.
const { spawn } = require("child_process"), fs = require("fs");
const sleep = ms => new Promise(r => setTimeout(r, ms));
(async () => {
  const ch = spawn("chromium", ["--headless=new", "--no-sandbox", "--disable-gpu", "--remote-debugging-port=9333", "--user-data-dir=/tmp/claude-1000/chrome-prof", "about:blank"], { stdio: "ignore" });
  await sleep(2500);
  const tabs = await (await fetch("http://127.0.0.1:9333/json")).json();
  const ws = new WebSocket(tabs.find(t => t.type === "page").webSocketDebuggerUrl);
  await new Promise(r => (ws.onopen = r));
  let id = 0; const pend = {};
  ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && pend[m.id]) pend[m.id](m.result); };
  const cdp = (method, params = {}) => new Promise(r => { const i = ++id; pend[i] = r; ws.send(JSON.stringify({ id: i, method, params })); });
  await cdp("Emulation.setDeviceMetricsOverride", { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
  for (const esquema of ["dark", "light"]) {
    await cdp("Emulation.setEmulatedMedia", { features: [{ name: "prefers-color-scheme", value: esquema }] });
    await cdp("Page.navigate", { url: "http://127.0.0.1:4200/" });
    await sleep(3500);
    for (const [i, pest] of ["agenda", "citaciones", "perfil"].entries()) {
      await cdp("Runtime.evaluate", { expression: `document.querySelectorAll('ion-tab-button')[${i}].click()` });
      await sleep(900);
      const foto = async n => { const { data } = await cdp("Page.captureScreenshot", { format: "png" }); fs.writeFileSync(`capturas/${n}_${esquema}.png`, Buffer.from(data, "base64")); };
      await foto(pest);
      if (pest === "perfil") { await cdp("Runtime.evaluate", { expression: `document.querySelector('.seccion-titulo ion-button')?.click()` }); await sleep(700); await foto("perfil_form"); }
      console.log("ok", pest, esquema);
    }
  }
  ws.close(); ch.kill();
})();
