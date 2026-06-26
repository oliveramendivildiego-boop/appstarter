import fitz
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf'
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
doc = fitz.open(path)
page = doc[0]
keywords = ['HEMATO', 'HEMOGRAM', 'LABORATORIO', 'Método', 'Mtodo', 'PLAQUET', 'Tiempo']
for block in page.get_text('dict')['blocks']:
    if block.get('type') != 0:
        continue
    for line in block.get('lines', []):
        text = ''.join(s.get('text', '') for s in line.get('spans', []))
        u = text.upper()
        if any(k in u for k in keywords) or '+' in text:
            for span in line.get('spans', []):
                bbox = span.get('bbox', line.get('bbox'))
                print(f"y={bbox[1]:.1f} size={span.get('size')} font={span.get('font')!r} flags={span.get('flags')} text={span.get('text')!r}")
