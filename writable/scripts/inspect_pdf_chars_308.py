import fitz
import sys

sys.stdout.reconfigure(encoding='utf-8', errors='replace')
doc = fitz.open(r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf')
page = doc[0]
chars = page.get_text('dict')['blocks']
for bi, block in enumerate(chars):
    if block.get('type') != 0:
        continue
    for line in block.get('lines', []):
        text = ''.join(span.get('text', '') for span in line.get('spans', []))
        if any(k in text.upper() for k in ['HEMOGRAMA', 'HEMATOLOG', 'METODO', 'H', 'E', 'M']):
            bbox = line.get('bbox')
            if bbox and bbox[1] < 170:
                print(f'bbox={tuple(round(v,1) for v in bbox)} text={repr(text)} size={[round(s.get("size",0),1) for s in line.get("spans",[])]} font={[s.get("font") for s in line.get("spans",[])]}')
