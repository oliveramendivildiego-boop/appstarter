<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$html = <<<'HTML'
<html><body style="margin:0">
<div style="height:900px;background:#eee">Page1 content</div>
<div style="page-break-before:always;height:900px;background:#ddd">Page2 content</div>
</body></html>
HTML;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->setPaper('letter', 'portrait');

$mmToPt = 72 / 25.4;

$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f'     => static function (int $pageNumber, int $pageCount, $pdf, $fontMetrics) use ($mmToPt): void {
        if ($pageNumber <= 1) {
            return;
        }
        $font = $fontMetrics->getFont('DejaVu Sans', 'bold');
        $size = 9.0;
        $offsetMm = 15 + 22 + 1.5;
        $y = $pdf->get_height() - ($offsetMm * $mmToPt) - ($size * 0.85);
        $pdf->text(50, $y, 'Paciente: TEST', $font, $size, [0, 0, 0]);
        $pdf->text(400, $y, 'No. Orden: 123', $font, $size, [0, 0, 0]);
    },
]]);

$dompdf->loadHtml($html);
$dompdf->render();

$out = __DIR__ . '/test_order_header.pdf';
file_put_contents($out, $dompdf->output());

echo 'pages=' . $dompdf->getCanvas()->get_page_count() . PHP_EOL;
echo 'height=' . $dompdf->getCanvas()->get_height() . PHP_EOL;
echo 'written ' . $out . PHP_EOL;
