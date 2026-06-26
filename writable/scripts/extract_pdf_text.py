from pypdf import PdfReader
import sys
import glob
import os

base = r"c:\wamp64\www\laboratorio\writable\debug"
files = sorted(glob.glob(os.path.join(base, "ft_test_*.pdf")))
needles = ["PIE MINIMO", "Direccion", "Calle 1", "FOOTER FIXED", "Tarija", "Celular", "quantum", "Página", "BODY TEST"]

for path in files:
    name = os.path.basename(path)
    try:
        reader = PdfReader(path)
        text = "\n".join((p.extract_text() or "") for p in reader.pages)
        hits = [n for n in needles if n.lower() in text.lower()]
        print(f"{name}: pages={len(reader.pages)} hits={hits or 'NONE'}")
        if hits:
            for line in text.splitlines():
                if any(n.lower() in line.lower() for n in needles):
                    print("  ", line[:120])
    except Exception as e:
        print(f"{name}: ERROR {e}")
