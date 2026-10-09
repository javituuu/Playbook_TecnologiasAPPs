import sys, os, re, io, glob, struct, tarfile, zipfile, shutil, subprocess, tempfile
from fontTools.ttLib import TTFont

PESOS = {100: "Thin", 200: "ExtraLight", 300: "Light", 400: "", 500: "Medium",
         600: "SemiBold", 700: "Bold", 800: "ExtraBold", 900: "Black"}
DIR = os.path.abspath("fuentes")
REL_FONT = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/font"

def nombre_familia(fam, peso, italica):
    partes = [fam, PESOS[peso]] + (["Italic"] if italica else [])
    return " ".join(p for p in partes if p)

def descargar_ttf(fam, peso, italica):
    pkg = "@fontsource/" + fam.lower().replace(" ", "-")
    tmp = tempfile.mkdtemp()
    subprocess.run(["npm", "pack", pkg, "--silent"], cwd=tmp, check=True, capture_output=True)
    tgz = glob.glob(os.path.join(tmp, "*.tgz"))[0]
    slug = fam.lower().replace(" ", "-")
    estilo = "italic" if italica else "normal"
    buscado = f"package/files/{slug}-latin-{peso}-{estilo}.woff"
    with tarfile.open(tgz) as t:
        try:
            data = t.extractfile(buscado).read()
        except KeyError:
            disp = sorted(n for n in t.getnames() if n.endswith(".woff") and "-latin-" in n and "ext" not in n)
            raise SystemExit(f"No existe {buscado}. Disponibles: {[os.path.basename(d) for d in disp]}")
    f = TTFont(io.BytesIO(data)); f.flavor = None
    return f

def renombrar(f, familia):
    n = f["name"]
    for i in (16, 17, 21, 22):
        n.removeNames(nameID=i)
    for p, e, l in [(3, 1, 0x409), (1, 0, 0)]:
        n.setName(familia, 1, p, e, l); n.setName("Regular", 2, p, e, l)
        n.setName(familia, 4, p, e, l); n.setName(familia.replace(" ", "") + "-Regular", 6, p, e, l)
    os2 = f["OS/2"]
    os2.fsSelection = (os2.fsSelection & ~0b100001) | 0x40
    os2.fsType = 0
    os2.usWeightClass = 400
    f["head"].macStyle = 0

def ttf_a_eot(ttf_bytes, f, familia):
    os2, head = f["OS/2"], f["head"]
    def u16(s): b = s.encode("utf-16le"); return struct.pack("<H", len(b)) + b
    nombres = (u16(familia) + b"\0\0" + u16("Regular") + b"\0\0" + u16("Version 1.0") + b"\0\0" +
               u16(familia) + b"\0\0" + struct.pack("<H", 0))
    cola = struct.pack("<IIHHII", 0x50475342, 0, 0, 0, 0, 0)
    panose = os2.panose
    pan = bytes([panose.bFamilyType, panose.bSerifStyle, panose.bWeight, panose.bProportion, panose.bContrast,
                 panose.bStrokeVariation, panose.bArmStyle, panose.bLetterForm, panose.bMidline, panose.bXHeight])
    cuerpo = (pan + struct.pack("<BBIHH", 1, 0, os2.usWeightClass, 0, 0x504C) +
              struct.pack("<IIII", os2.ulUnicodeRange1, os2.ulUnicodeRange2, os2.ulUnicodeRange3, os2.ulUnicodeRange4) +
              struct.pack("<II", getattr(os2, "ulCodePageRange1", 1), getattr(os2, "ulCodePageRange2", 0)) +
              struct.pack("<I", head.checkSumAdjustment) + b"\0" * 16 + b"\0\0" + nombres + cola)
    total = 16 + len(cuerpo) + len(ttf_bytes)
    return struct.pack("<IIII", total, len(ttf_bytes), 0x00020002, 0) + cuerpo + ttf_bytes

def instalar(specs):
    os.makedirs(DIR, exist_ok=True)
    dest = os.path.expanduser("~/.fonts"); os.makedirs(dest, exist_ok=True)
    for spec in specs:
        fam, _, pw = spec.partition(":")
        italica = pw.endswith("i"); peso = int(pw.rstrip("i") or 400)
        familia = nombre_familia(fam, peso, italica)
        f = descargar_ttf(fam, peso, italica)
        renombrar(f, familia)
        buf = io.BytesIO(); f.save(buf); ttf = buf.getvalue()
        base = familia.replace(" ", "_")
        open(os.path.join(dest, base + ".ttf"), "wb").write(ttf)
        open(os.path.join(DIR, base + ".fntdata"), "wb").write(ttf_a_eot(ttf, TTFont(io.BytesIO(ttf)), familia))
        print("lista:", familia)
    subprocess.run(["fc-cache", "-f"], check=False)

def incrustar(deck):
    disponibles = {os.path.basename(p)[:-8].replace("_", " "): p for p in glob.glob(os.path.join(DIR, "*.fntdata"))}
    zo = zipfile.ZipFile(deck); files = {n: zo.read(n) for n in zo.namelist()}; zo.close()
    n_fotos = 0
    def a_imagen(m):
        nonlocal n_fotos; n_fotos += 1
        return (f'<p:cNvPr id="{m.group(1)}" name="Foto {n_fotos}"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr>'
                f'<p:nvPr><p:ph type="pic" idx="{100 + n_fotos}"/></p:nvPr>')
    for n in list(files):
        if re.match(r"ppt/slides/slide\d+\.xml$", n):
            x = files[n].decode("utf8")
            if "FOTO_PH" in x:
                files[n] = re.sub(r'<p:cNvPr id="(\d+)" name="FOTO_PH"\s*(?:/>|></p:cNvPr>)<p:cNvSpPr/><p:nvPr>\s*(?:</p:nvPr>|<p:nvPr/>)', a_imagen, x).encode("utf8")
    usadas = set()
    for n, b in files.items():
        if n.startswith("ppt/slides/slide") and n.endswith(".xml"):
            usadas |= set(re.findall(r'typeface="([^"]+)"', b.decode("utf8")))
    faltan = sorted(u for u in usadas if u not in disponibles and not u.startswith("+"))
    usar = sorted(u for u in usadas if u in disponibles)
    pres = files["ppt/presentation.xml"].decode("utf8")
    rels = files["ppt/_rels/presentation.xml.rels"].decode("utf8")
    ct = files["[Content_Types].xml"].decode("utf8")
    pres = re.sub(r"<p:embeddedFontLst>.*?</p:embeddedFontLst>", "", pres, flags=re.S)
    nxt = max([int(x) for x in re.findall(r'Id="rId(\d+)"', rels)] + [0]) + 1
    entradas, nuevas = [], []
    for k, fam in enumerate(usar):
        nombre = f"fonts/font{k+1}.fntdata"
        files["ppt/" + nombre] = open(disponibles[fam], "rb").read()
        rid = f"rId{nxt}"; nxt += 1
        nuevas.append(f'<Relationship Id="{rid}" Type="{REL_FONT}" Target="{nombre}"/>')
        entradas.append(f'<p:embeddedFont><p:font typeface="{fam}" charset="1"/><p:regular r:id="{rid}"/></p:embeddedFont>')
    if entradas:
        m = re.search(r"<p:notesSz[^>]*/>(<p:smartTags[^>]*/>)?", pres)
        pres = pres[:m.end()] + "<p:embeddedFontLst>" + "".join(entradas) + "</p:embeddedFontLst>" + pres[m.end():]
        head = re.search(r"<p:presentation[^>]*>", pres).group(0); nuevo = head
        for attr in ("embedTrueTypeFonts", "saveSubsetFonts"):
            if attr + "=" not in nuevo:
                nuevo = nuevo.replace("<p:presentation", f'<p:presentation {attr}="1"', 1)
        pres = pres.replace(head, nuevo, 1)
        rels = rels.replace("</Relationships>", "".join(nuevas) + "</Relationships>")
        if 'Extension="fntdata"' not in ct:
            ct = re.sub(r"(<Types[^>]*>)", r'\1<Default Extension="fntdata" ContentType="application/x-fontdata"/>', ct, 1)
    files["ppt/presentation.xml"] = pres.encode(); files["ppt/_rels/presentation.xml.rels"] = rels.encode()
    files["[Content_Types].xml"] = ct.encode()
    tmp = deck + ".tmp"
    with zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml", files.pop("[Content_Types].xml"))
        for n, b in files.items(): z.writestr(n, b)
    shutil.move(tmp, deck)
    print("incrustadas:", usar, "| espacios para fotos:", n_fotos)
    if faltan: print("OJO, usadas pero no incrustadas:", faltan)

if __name__ == "__main__":
    {"instalar": lambda a: instalar(a), "incrustar": lambda a: incrustar(a[0])}[sys.argv[1]](sys.argv[2:])
