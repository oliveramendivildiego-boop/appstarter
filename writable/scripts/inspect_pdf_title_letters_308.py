import fitz
import sys

sys.stdout.reconfigure(encoding='utf-8', errors='replace')
doc = fitz.open(r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf')
page = doc[0]
title_chars = 'HEMOGRAMA + PLAQUETAS'
found = []
for block in page.get_text('dict')['blocks']:
    if block.get('type') != 0:
        continue
    for line in block.get('lines', []):
        for span in line.get('spans', []):
            t = span.get('text', '')
            if t.strip() in title_chars or t.strip() in ['H', 'E', 'M', 'O', 'G', 'R', 'A', '+', 'P', 'L', 'Q', 'U', 'T', 'S', ' ']:
                bbox = span.get('bbox', line.get('bbox'))
                found.append((bbox[1], bbox[0], t, span.get('size'), span.get('font')))

found.sort()
print('letter spans count', len(found))
for row in found[:40]:
    print(f'y={row[0]:.1f} x={row[1]:.1f} size={row[3]} font={row[4]!r} char={row[2]!r}')
