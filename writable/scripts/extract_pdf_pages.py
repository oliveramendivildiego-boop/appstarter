from pypdf import PdfReader
path = r"c:\wamp64\www\laboratorio\writable\debug\ft_test_4_full.pdf"
reader = PdfReader(path)
needles = ["Direccion", "Celular", "Tarija", "Página", "Pagina", "68724938", "Méndez", "Mendez"]
for i, p in enumerate(reader.pages, 1):
    text = p.extract_text() or ""
    hits = [n for n in needles if n.lower() in text.lower()]
    print(f"--- page {i} --- hits={hits}")
    for line in text.splitlines()[-8:]:
        print(" ", line[:100])
