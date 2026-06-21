<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;

$html = <<<'HTML'
<!DOCTYPE html><html><body><p>Page one content line.</p>
<p style="page-break-before:always">Page two content line.</p>
<p style="page-break-before:always">Page three content line.</p>
</body></html>
HTML;

$logFile = dirname(__DIR__) . '/debug/page_text_log.txt';
@unlink($logFile);
$dompdf = new Dompdf();
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f' => function (int $pageNumber, int $pageCount, $canvas, $fontMetrics) use ($logFile) {
        file_put_contents($logFile, "page {$pageNumber}/{$pageCount}\n", FILE_APPEND);
        try {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            file_put_contents($logFile, "font=" . var_export($font, true) . "\n", FILE_APPEND);
            $text = "XYZPAG123 {$pageNumber}/{$pageCount}";
            $canvas->text(72, 20, $text, $font, 14, [0, 0, 0]);
            file_put_contents($logFile, "text ok\n", FILE_APPEND);
        } catch (\Throwable $e) {
            file_put_contents($logFile, 'ERR: ' . $e->getMessage() . "\n", FILE_APPEND);
        }
    },
]]);
$dompdf->loadHtml($html);
$dompdf->render();

$pdf = $dompdf->output(['compress' => false]);
$canvas = $dompdf->getCanvas();
echo 'Pages: ' . $canvas->get_page_count() . PHP_EOL;
file_put_contents(dirname(__DIR__) . '/debug/minimal_pagination.pdf', $pdf);

foreach (['XYZPAG123', 'Page one', 'Page two'] as $p) {
    echo "Contains '{$p}': " . (str_contains($pdf, $p) ? 'yes' : 'no') . PHP_EOL;
}

preg_match_all('/\(([^)]{1,60})\)\s*Tj/', $pdf, $m);
echo 'Tj strings: ' . PHP_EOL;
print_r($m[1] ?? []);

echo 'Log: ' . (is_file($logFile) ? file_get_contents($logFile) : 'MISSING') . PHP_EOL;
if ($canvas instanceof \Dompdf\Adapter\CPDF) {
    echo 'CPDF messages: ' . $canvas->get_messages() . PHP_EOL;
}
$outFile = dirname(__DIR__) . '/debug/minimal_pagination.txt';
@exec('pdftotext ' . escapeshellarg(dirname(__DIR__) . '/debug/minimal_pagination.pdf') . ' ' . escapeshellarg($outFile) . ' 2>&1', $execOut, $code);
if (is_file($outFile)) {
    echo 'pdftotext: ' . trim(file_get_contents($outFile)) . PHP_EOL;
} else {
    echo 'pdftotext not available' . PHP_EOL;
}
