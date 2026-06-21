<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;

$html = <<<'HTML'
<!DOCTYPE html><html><body><p>Line one.</p>
<p style="page-break-before:always">Line two.</p>
</body></html>
HTML;

$dompdf = new Dompdf();
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f' => function (int $pageNumber, int $pageCount, $canvas, $fontMetrics) {
        $cpdf = $canvas->get_cpdf();
        $font = $fontMetrics->getFont('Helvetica', 'normal');
        $cpdf->selectFont($font);
        $cpdf->addText(72, 770, 12, "FOOT{$pageNumber}/{$pageCount}", 0);
    },
]]);
$dompdf->loadHtml($html);
$dompdf->render();
$pdf = $dompdf->output(['compress' => 0]);
file_put_contents(dirname(__DIR__) . '/debug/cpdf_direct.pdf', $pdf);

echo (str_contains($pdf, 'FOOT1') ? 'found FOOT1' : 'no FOOT1') . PHP_EOL;
echo (str_contains($pdf, 'FOOT2') ? 'found FOOT2' : 'no FOOT2') . PHP_EOL;

// dump stream snippets
if (preg_match_all('/stream\r?\n(.{0,500}?)\r?\nendstream/s', $pdf, $m)) {
    foreach ($m[1] as $i => $chunk) {
        if (str_contains($chunk, 'FOOT') || str_contains($chunk, 'Line')) {
            echo "Stream {$i}: " . substr($chunk, 0, 200) . PHP_EOL;
        }
    }
}
