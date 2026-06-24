<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/report_pdf.css');
$html = <<<HTML
<!DOCTYPE html><html><head><style>{$css}
.pdf-ft-block.footer-grid { position: fixed; left: 0; right: 0; bottom: 0; min-height: 40pt; background: #fff; border-top: 1px solid #ddd; }
.pdf-ft-block .header-piece-pagination { position: static !important; }
.pdf-section-table { width: 100%; table-layout: fixed; }
td.pdf-cell--right { text-align: right !important; }
</style></head>
<body class="pdf-dompdf-download">
<div class="pdf-main-stack"><p>Page one content</p><p style="page-break-before:always">Page two</p></div>
<div class="footer footer-grid pdf-ft-block">
<table class="pdf-section-table"><tr><td class="pdf-cell pdf-cell--right" style="text-align:right!important">
<div class="pdf-el-item"><div class="header-piece header-piece-pagination">
<span class="pdf-pagination-line" data-prefix="Página " data-total="2"></span>
</div></div></td></tr></table>
</div>
</body></html>
HTML;

$opts = new Options();
$opts->set('isRemoteEnabled', false);
$opts->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($opts);
$dompdf->loadHtml($html);
$dompdf->render();
$pdf = $dompdf->output(['compress' => 0]);
file_put_contents(dirname(__DIR__) . '/debug/pagination_minimal_footer.pdf', $pdf);

// extract literal strings from uncompressed streams
if (preg_match_all('/\(([^\\\\\)]{2,40})\)/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'de') || str_contains($s, 'gina') || str_contains($s, 'P')) {
            echo $s . PHP_EOL;
        }
    }
}
