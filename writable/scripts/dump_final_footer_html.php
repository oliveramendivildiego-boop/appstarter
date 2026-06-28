<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 143);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);

$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$orderSheetSlot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$body, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
$body = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
$body = \App\Libraries\Pdf\MpdfFooterExtractor::purgeFooterBlocksFromBody($body);

$wrapped = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string) $fi, $layoutSnap);
if (\App\Libraries\Pdf\MpdfOrderSheetFooterInjector::shouldPrependOrderSheetBand($orderSheetSlot, (string) $fi)) {
    $wrapped = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
        $wrapped,
        $orderSheetSlot,
        \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($wrapped),
    );
}
$wrapped = \App\Libraries\Pdf\MpdfFooterStyles::finalizeSetHtmlFooterFragment($wrapped, $layoutSnap);

$count = static function (string $s, string $needle): int {
    return substr_count(strip_tags($s), $needle);
};

echo "=== Final SetHTMLFooter HTML ===\n";
foreach (['Paciente', 'Direccion', 'Dirección', 'Correo:', 'mpdf-ft-root', 'mpdf-order-sheet-above', 'mpdf-ft-table'] as $n) {
    echo "  {$n}: " . substr_count($wrapped, $n) . "\n";
}
echo '  mpdf-ft-root divs: ' . preg_match_all('/<div[^>]*mpdf-ft-root/', $wrapped, $m) . "\n";

echo "\n=== Body after pipeline ===\n";
foreach (['Paciente', 'Direccion', 'mpdf-ft-root', 'footer-grid', 'report-pdf-footer-payload'] as $n) {
    echo "  {$n}: " . substr_count($body, $n) . "\n";
}

file_put_contents(WRITEPATH . 'debug/final_footer_sethtml_' . $id . '.html', $wrapped);
file_put_contents(WRITEPATH . 'debug/final_body_' . $id . '.html', $body);

// Render minimal PDF and extract text with smalot if available
$tempDir = WRITEPATH . 'cache/mpdf';
$m = new \App\Libraries\Pdf\SafeMpdf([
    'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
    'margin_bottom' => 35, 'margin_footer' => 10, 'use_kwt' => false,
]);
$m->SetHTMLFooter($wrapped);
$m->WriteHTML('<html><body class="pdf-engine-mpdf"><p>Test</p></body></html>');
$pdfOnlyFooter = $m->Output('', \Mpdf\Output\Destination::STRING_RETURN);

$m2 = new \App\Libraries\Pdf\SafeMpdf([
    'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
    'margin_bottom' => 35, 'margin_footer' => 10, 'use_kwt' => false,
]);
$m2->SetHTMLFooter($wrapped);
$m2->WriteHTML(str_ireplace('</body>', $wrapped . '</body>', '<html><body class="pdf-engine-mpdf"><p>Test</p></body></html>'));
$pdfBoth = $m2->Output('', \Mpdf\Output\Destination::STRING_RETURN);

file_put_contents(WRITEPATH . 'debug/dup_test_footer_only.pdf', $pdfOnlyFooter);
file_put_contents(WRITEPATH . 'debug/dup_test_footer_plus_body.pdf', $pdfBoth);

echo "\nPDF sizes: footer_only=" . strlen($pdfOnlyFooter) . " footer+body=" . strlen($pdfBoth) . "\n";

if (class_exists(\Smalot\PdfParser\Parser::class)) {
    $parser = new \Smalot\PdfParser\Parser();
    foreach (['footer_only' => $pdfOnlyFooter, 'footer+body' => $pdfBoth] as $label => $bin) {
        $text = $parser->parseContent($bin)->getText();
        echo "\n--- {$label} extracted text (footer zone) ---\n";
        echo trim($text) . "\n";
        echo "Paciente count: " . substr_count($text, 'Paciente') . "\n";
        echo "Direccion count: " . substr_count($text, 'Direccion') . "\n";
    }
}
