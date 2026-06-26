import fitz
path = r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf'
doc = fitz.open(path)
page = doc[0]
for w in sorted(page.get_text('words'), key=lambda x: x[1])[:25]:
    print(f'y={w[1]:.0f} x={w[0]:.0f} "{w[4]}"')
doc.close()
