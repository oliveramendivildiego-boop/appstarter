<?php
declare(strict_types=1);

$htmlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'report_262.html';
if (! is_file($htmlFile)) {
    fwrite(STDERR, "Missing {$htmlFile}. Run debug_pdf_html.php first.\n");
    exit(1);
}

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$html = (string) file_get_contents($htmlFile);
if (str_contains($html, '__PDF_TOTAL_PAGES__')) {
    $probe = new Dompdf\Dompdf();
    $probe->loadHtml($html, 'UTF-8');
    $probe->render();
    $pageCount = max(1, (int) $probe->getCanvas()->get_page_count());
    $html = str_replace('__PDF_TOTAL_PAGES__', (string) $pageCount, $html);
    echo "Replaced total pages token with {$pageCount}\n";
}

if (! preg_match('/#pdf-pag-[a-f0-9]+\.pdf-pagination-line::before/', $html)) {
    echo "WARN: no dompdf pagination inline CSS found\n";
}

$dompdf = new Dompdf\Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$pdf = $dompdf->output();
$out = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'pagination_test.pdf';
file_put_contents($out, $pdf);

$text = (string) $dompdf->getCanvas()->get_cpdf()->messages; // may not work
echo 'PDF bytes: ' . strlen($pdf) . "\n";
echo 'Pages: ' . (int) $dompdf->getCanvas()->get_page_count() . "\n";
echo 'Saved: ' . $out . "\n";

// crude text search in PDF stream
$hasPagina = str_contains($pdf, 'P') && (str_contains($pdf, 'gina') || str_contains($pdf, 'Pagina'));
echo 'Contains Pagina-like text: ' . ($hasPagina ? 'yes' : 'no') . "\n";
