import fitz
import sys

sys.stdout.reconfigure(encoding='utf-8', errors='replace')
doc = fitz.open(r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf')
page = doc[0]
for block in page.get_text('dict')['blocks']:
    if block.get('type') != 0:
        continue
    for line in block.get('lines', []):
        text = ''.join(span.get('text', '') for span in line.get('spans', []))
        sizes = [span.get('size') for span in line.get('spans', [])]
        bbox = line.get('bbox')
        if bbox and bbox[1] < 160 and len(text.strip()) <= 3:
            print(f'bbox={tuple(round(v,1) for v in bbox)} text={repr(text)} sizes={sizes}')
