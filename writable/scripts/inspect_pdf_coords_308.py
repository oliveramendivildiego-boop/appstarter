import fitz
import sys

sys.stdout.reconfigure(encoding='utf-8', errors='replace')
doc = fitz.open(r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf')
page = doc[0]
print('FULL TEXT PAGE 1:')
print(page.get_text())
print('---ALL WORDS y<280---')
for w in sorted(page.get_text('words'), key=lambda x: x[1]):
    if w[1] < 280:
        print(f'y={w[1]:.1f} x={w[0]:.1f} h={w[3]-w[1]:.1f} "{w[4]}"')
