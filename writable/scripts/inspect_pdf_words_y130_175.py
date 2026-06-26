import fitz
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf'
page = fitz.open(path)[0]
for w in sorted(page.get_text('words'), key=lambda x: (x[1], x[0])):
    if 130 <= w[1] <= 175:
        print(f'y={w[1]:.1f} x={w[0]:.0f} "{w[4]}"')
