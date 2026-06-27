import fitz
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r'writable/cache/logo_probe_fresh_308.pdf'
doc = fitz.open(path)
page = doc[0]
# content area approx
words = page.get_text('words')
left = [w for w in words if w[1] < 70 and 'LABORATORIO' in w[4].upper()]
right_img = []
for img in page.get_images(full=True):
    for r in page.get_image_rects(img[0]):
        if r.y0 < 80:
            right_img.append(r)
if left:
    lx1 = max(w[2] for w in left)
    print(f'company text ends x1={lx1:.1f}')
if right_img:
    r = right_img[0]
    print(f'logo x0={r.x0:.1f} x1={r.x1:.1f} w={r.width:.1f}')
    if left:
        split = r.x0 - lx1
        print(f'gap between company and logo={split:.1f}')
# page content width estimate
margin_l = min(w[0] for w in words if w[1] < 100) if words else 75
margin_r = 612 - max(w[2] for w in words if w[1] < 100)
content_w = 612 - margin_l - (612 - max(w[2] for w in page.get_text('words') if w[1] < 120))
print(f'margin_l~={margin_l:.1f}')
if left and right_img:
    total = right_img[0].x1 - margin_l
    left_w = lx1 - margin_l
    print(f'left block ~{100*left_w/total:.0f}%  right block ~{100*(right_img[0].x1-lx1)/total:.0f}%')
