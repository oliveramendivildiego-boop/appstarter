<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$opts = new Options();
$opts->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($opts);
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f'     => static function (int $pn, int $pc, $canvas, $fm): void {
        $font = $fm->getFont('DejaVu Sans', 'normal');
        $canvas->text(72, 72, "PAG-TEST {$pn} de {$pc}", $font, 14, [0, 0, 0]);
    },
]]);
$html = '<html><body><p>Page1</p><p style="page-break-before:always">Page2</p></body></html>';
$dompdf->loadHtml($html);
$dompdf->render();
$bin = $dompdf->output(['compress' => 0]);
$out = dirname(__DIR__) . '/cache/end_document_text_probe.pdf';
file_put_contents($out, $bin);
echo 'has PAG-TEST: ' . (str_contains($bin, 'PAG-TEST') ? 'yes' : 'no') . PHP_EOL;
echo 'has 1 de 2: ' . (str_contains($bin, '1 de 2') ? 'yes' : 'no') . PHP_EOL;
