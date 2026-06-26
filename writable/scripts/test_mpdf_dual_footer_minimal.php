<?php
require 'vendor/autoload.php';
use Mpdf\Mpdf;

$html = <<<'HTML'
<!DOCTYPE html><html><head>
<style>
@page { footer: html_rest; margin-footer: 15mm; }
@page :first { footer: html_p1; }
body { font-family: sans-serif; font-size: 12pt; }
</style>
</head><body>
<htmlpagefooter name="p1" style="display:none">
<div style="border-top:2px solid green;padding:4px;">PAGE1 FOOTER only</div>
</htmlpagefooter>
<htmlpagefooter name="rest" style="display:none">
<div style="border-top:2px solid red;padding:4px;">REST FOOTER with ORDER ROW</div>
<div>Paciente: TEST | No. Orden: 123</div>
</htmlpagefooter>
<p>Page one content filler</p>
<div style="page-break-before:always"></div>
<p>Page two content</p>
<div style="page-break-before:always"></div>
<p>Page three content</p>
</body></html>
HTML;

$mpdf = new Mpdf(['mode'=>'utf-8','format'=>'A4','margin_bottom'=>25,'margin_footer'=>15]);
$mpdf->WriteHTML($html);
$out = 'c:/wamp64/www/laboratorio/writable/cache/mpdf_dual_footer_minimal.pdf';
$mpdf->Output($out, 'F');
echo "wrote $out\n";

$py = <<<'PY'
import sys
from pypdf import PdfReader
r = PdfReader(sys.argv[1])
for i,p in enumerate(r.pages,1):
    t = p.extract_text() or ''
    print(i, 'P1FOOT' in t, 'REST' in t, 'Paciente' in t)
PY;
file_put_contents('c:/wamp64/www/laboratorio/writable/cache/_min.py', $py);
passthru('python c:/wamp64/www/laboratorio/writable/cache/_min.py ' . escapeshellarg($out));
