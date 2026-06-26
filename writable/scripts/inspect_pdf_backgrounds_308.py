import fitz
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r'c:\wamp64\www\laboratorio\writable\cache\mpdf_pipeline_308.pdf'
doc = fitz.open(path)
for pi in [0, 1]:
    page = doc[pi]
    print(f'=== PAGE {pi+1} ===')
    words = [w for w in page.get_text('words') if w[1] < 200]
    for w in words:
        if any(k in w[4].upper() for k in ['HEMATOLOG', 'COAGULO', 'SERIE', 'ANÁLISIS', 'ANALISIS']):
            print(f'  word y={w[1]:.1f} x={w[0]:.1f} "{w[4]}"')
    # filled rects in top area
    rects = []
    for path in page.get_drawings():
        fill = path.get('fill')
        rect = path.get('rect')
        if fill and rect and rect.y1 < 200:
            rects.append((rect.y0, rect.y1, rect.x0, rect.x1, fill))
    rects.sort(key=lambda r: r[0])
    print(f'  fills y<200: {len(rects)}')
    for r in rects[:15]:
        print(f'    y={r[0]:.1f}-{r[1]:.1f} x={r[2]:.1f}-{r[3]:.1f} fill={r[4]}')
