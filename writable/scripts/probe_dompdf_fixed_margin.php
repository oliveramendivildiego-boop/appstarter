<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$ml = 25;
$mr = 10;
$html = <<<HTML
<!DOCTYPE html><html><head><style>
@page { margin: 15mm {$mr}mm 15mm {$ml}mm; }
body { margin:0; font-family: DejaVu Sans; font-size: 10pt; }
.marker-left { position:fixed; left:0; bottom:0; background:red; color:white; padding:2px; }
.marker-content { position:fixed; left:0; bottom:20pt; background:blue; color:white; }
.body-line { margin:0; }
</style></head><body>
<p class="body-line">BODY START (should be at left margin)</p>
<div class="marker-left">FIXED left:0</div>
<p style="page-break-before:always">Page 2</p>
</body></html>
HTML;

$opts = new Options();
$opts->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($opts);
$dompdf->loadHtml($html);
$dompdf->render();
$out = dirname(__DIR__) . '/debug/dompdf_fixed_margin_probe.pdf';
@mkdir(dirname($out), 0777, true);
file_put_contents($out, $dompdf->output());
echo "saved: $out\n";
echo "pageW pt: " . $dompdf->getCanvas()->get_width() . "\n";
echo "pageH pt: " . $dompdf->getCanvas()->get_height() . "\n";