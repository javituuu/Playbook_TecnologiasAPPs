import json, sys
from docx import Document
from docx.shared import Pt, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH
d = json.load(open(sys.argv[1] if len(sys.argv) > 1 else "contenido_b.json", encoding="utf8"))
nom = {"portada":"Portada","problema":"El problema","quien":"Para quién","requisitos":"Requisitos de alto nivel","exito":"Cómo se mide el éxito","arquitectura":"Arquitectura","porque":"Por qué esta arquitectura","ux":"La app: tres pestañas","identidad":"Identidad visual","trazabilidad":"Requisitos y estado","cierre":"Cierre"}
orden = ["portada","problema","quien","requisitos","exito","arquitectura","porque","ux","identidad","trazabilidad","cierre"]
wpm = 115
doc = Document()
for s in doc.sections: s.left_margin = s.right_margin = Cm(2.5); s.top_margin = s.bottom_margin = Cm(2.2)
st = doc.styles["Normal"]; st.font.name = "Calibri"; st.font.size = Pt(11.5)
doc.add_heading("Guion de presentación: Playbook, Evaluación 1", 0)
doc.add_paragraph("Expositores: Cristóbal Montoya, Javier Benavides y Diego Muñoz. Duración aproximada: 9 a 10 minutos, calculada a 115 palabras por minuto.")
t = doc.add_table(rows=1, cols=4); t.style = "Table Grid"
for c, h in zip(t.rows[0].cells, ["N°", "Diapositiva", "Orador", "Minutos"]):
    c.text = ""; r = c.paragraphs[0].add_run(h); r.bold = True
tot = {}
for i, k in enumerate(orden, 1):
    m = len(d[k]["notas"].split()) / wpm; o = d[k]["orador"]; tot[o] = tot.get(o, 0) + m
    cells = t.add_row().cells
    for c, v in zip(cells, [str(i), nom[k], o, f"{m:.1f}".replace(".", ",")]): c.text = v
doc.add_paragraph()
doc.add_paragraph("Tiempo por persona: " + ", ".join(f"{o} {m:.1f} min".replace(".", ",") for o, m in tot.items()) + ".")
doc.add_paragraph("El anexo con el manual de marca no se expone; queda para preguntas.")
for i, k in enumerate(orden, 1):
    doc.add_heading(f"{i}. {nom[k]}", 2)
    p = doc.add_paragraph(); r = p.add_run(d[k]["orador"] + ": "); r.bold = True
    p.add_run(d[k]["notas"])
doc.save(sys.argv[2] if len(sys.argv) > 2 else "Guion_Playbook_Ev1.docx")
