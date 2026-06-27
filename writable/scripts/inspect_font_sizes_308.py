import fitz
import sys
import re

path = sys.argv[1] if len(sys.argv) > 1 else r'writable/cache/logo_probe_fresh_308.pdf'
doc = fitz.open(path)
page = doc[0]
for block in page.get_text('dict')['blocks']:
    if block.get('type') != 0:
        continue
    for line in block.get('lines', []):
        for span in line.get('spans', []):
            t = span.get('text', '')
            if 'LABORATORIO' in t or 'QUANTUM' in t or 'Paciente' in t:
                print(f"text={t!r} size={span.get('size')} font={span.get('font')}")

# also check raw html adapted
html_path = r'writable/debug/adapted_body_308.html'
try:
    with open(html_path, encoding='utf-8', errors='replace') as f:
        html = f.read()
    m = re.search(r'header-piece-company[\s\S]{0,400}', html)
    if m:
        chunk = m.group(0)[:500]
        print('\nADAPTED chunk:', chunk[:400])
except FileNotFoundError:
    pass
