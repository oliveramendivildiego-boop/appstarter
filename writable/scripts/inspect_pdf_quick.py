import fitz
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r'c:\wamp64\www\laboratorio\writable\cache\test_rs_block_slice_308.pdf'
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
doc = fitz.open(path)
page = doc[0]
text = page.get_text()
print('FILE:', path)
for line in text.splitlines():
    u = line.upper()
    if any(k in u for k in ['HEMATO', 'HEMOGRAM', 'METODO', 'SERIE ROJA', 'MARKER']):
        print(repr(line))
print('--- words y<200 ---')
for w in sorted(page.get_text('words'), key=lambda x: x[1]):
    if w[1] < 200:
        print(f'y={w[1]:.1f} "{w[4]}"')
