import fitz
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r'writable/cache/logo_probe_fresh_308.pdf'
doc = fitz.open(path)
page = doc[0]
w = page.rect.width
print(f'page width={w:.1f}')
for word in page.get_text('words'):
    t = word[4]
    if any(k in t.upper() for k in ['LABORATORIO', 'QUANTUM', 'S.R.L']) or (word[0] > 350 and word[1] < 80):
        print(f'x0={word[0]:.1f} x1={word[2]:.1f} w={word[2]-word[0]:.1f} y={word[1]:.1f} text={t!r}')

imgs = page.get_images(full=True)
if imgs:
    for r in page.get_image_rects(imgs[0][0]):
        if r.y0 < 100:
            print(f'logo img x0={r.x0:.1f} x1={r.x1:.1f} w={r.width:.1f}')
