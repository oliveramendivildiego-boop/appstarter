import fitz
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r'writable\cache\logo_probe_fresh_308.pdf'
doc = fitz.open(path)
page = doc[0]
print('IMAGES:')
for img in page.get_images(full=True):
    xref = img[0]
    for r in page.get_image_rects(xref):
        print(f'  y0={r.y0:.1f} y1={r.y1:.1f} cy={(r.y0+r.y1)/2:.1f} h={r.height:.1f} x0={r.x0:.1f}')
print('WORDS top area:')
for w in sorted(page.get_text('words'), key=lambda x: x[1]):
    if w[1] < 120:
        print(f'  y={w[1]:.1f} cy={(w[1]+w[3])/2:.1f} h={w[3]-w[1]:.1f} x={w[0]:.1f} "{w[4]}"')
