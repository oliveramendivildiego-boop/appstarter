<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;

$htmlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'report_262.html';
$html = is_file($htmlFile) ? (string) file_get_contents($htmlFile) : '';

$svc = new \App\Libraries\PdfService();
$ref = new ReflectionClass($svc);
$extract = $ref->getMethod('extractPaginationSlots');
$extract->setAccessible(true);
$slots = $extract->invoke($svc, $html);
echo 'Slots: ' . count($slots) . PHP_EOL;
if ($slots === []) {
    exit(1);
}
echo json_encode($slots[0], JSON_UNESCAPED_UNICODE) . PHP_EOL;

$paint = $ref->getMethod('paintPaginationOnPage');
$paint->setAccessible(true);
$errors = [];

$dompdf = new Dompdf();
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f'     => function (int $pn, int $pc, $canvas, $fontMetrics) use ($svc, $paint, $slots, &$errors): void {
        foreach ($slots as $slot) {
            try {
                $paint->invoke($svc, $canvas, $fontMetrics, $slot, $pn, $pc);
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    },
]]);
$dompdf->loadHtml('<html><body><p>test page one</p><p style="page-break-before:always">test page two</p></body></html>');
$dompdf->render();
$pdf = $dompdf->output(['compress' => 0]);
file_put_contents(dirname(__DIR__) . '/debug/pagination_paint_test.pdf', $pdf);

echo 'errors: ' . json_encode($errors, JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo 'contains "1 de 2": ' . (str_contains($pdf, '1 de 2') ? 'yes' : 'no') . PHP_EOL;
echo 'contains "Página": ' . (str_contains($pdf, 'P') ? 'maybe bytes' : 'no') . PHP_EOL;

if (preg_match_all('/\[\(([^\)]{1,60})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'de') || str_contains($s, 'P')) {
            echo 'Tj: ' . $s . PHP_EOL;
        }
    }
}
