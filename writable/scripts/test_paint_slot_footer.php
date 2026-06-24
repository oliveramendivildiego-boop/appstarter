<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;

$slot = [
    'prefix' => 'Página ',
    'zone' => 'footer',
    'align' => 'right',
    'fontSize' => 10,
    'fontFamily' => 'DejaVu Sans',
    'color' => '#333333',
    'mt' => 2,
    'mr' => 10,
    'mb' => 5,
    'ml' => 25,
    'footerReserveMm' => 22,
    'gridColumn' => 3,
    'gridColumnSpan' => 2,
    'gridRow' => 0,
    'gridStack' => 1,
    'footerColumns' => 5,
    'footerRows' => 1,
    'footerRowGapPx' => 6,
    'lineHeight' => 1.35,
];

$html = '<!DOCTYPE html><html><body><p>One</p><p style="page-break-before:always">Two</p></body></html>';

$svc = new \App\Libraries\PdfService();
$ref = new ReflectionClass($svc);
$paint = $ref->getMethod('paintPaginationOnPage');
$paint->setAccessible(true);
$hex = $ref->getMethod('hexColorToRgb');
$hex->setAccessible(true);

$dompdf = new Dompdf();
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f' => function (int $pn, int $pc, $canvas, $fm) use ($svc, $paint, $slot, $hex): void {
        $paint->invoke($svc, $canvas, $fm, $slot, $pn, $pc);
    },
]]);
$dompdf->loadHtml($html);
$dompdf->render();
$pdf = $dompdf->output(['compress' => 0]);
file_put_contents(dirname(__DIR__) . '/debug/pagination_slot_test.pdf', $pdf);

echo shell_exec('where pdftotext 2>nul') ? 'pdftotext available' : 'no pdftotext';
if (preg_match_all('/\[\(([^\)]{1,40})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'de')) echo $s . PHP_EOL;
    }
}
