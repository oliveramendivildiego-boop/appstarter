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
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$body, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
$wrapped = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string) $fi, $layoutSnap);
$simplified = \App\Libraries\Pdf\MpdfFooterGridSimplifier::simplify($wrapped);

$tempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
$bodyMin = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body class="pdf-engine-mpdf"><p>Page content</p></body></html>';

function renderFooterOnly(string $footer, string $bodyHtml, string $tempDir): string {
    $m = new \App\Libraries\Pdf\SafeMpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
        'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 15, 'margin_bottom' => 30,
        'margin_footer' => 10, 'default_font' => 'dejavusans', 'use_kwt' => false,
    ]);
    $m->SetHTMLFooter($footer);
    $m->WriteHTML($bodyHtml);
    return $m->Output('', \Mpdf\Output\Destination::STRING_RETURN);
}

function renderFooterInBodyAndSet(string $footer, string $bodyHtml, string $tempDir): string {
    $bodyWithFooter = str_ireplace('</body>', $footer . '</body>', $bodyHtml);
    $m = new \App\Libraries\Pdf\SafeMpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
        'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 15, 'margin_bottom' => 30,
        'margin_footer' => 10, 'default_font' => 'dejavusans', 'use_kwt' => false,
    ]);
    $m->SetHTMLFooter($footer);
    $m->WriteHTML($bodyWithFooter);
    return $m->Output('', \Mpdf\Output\Destination::STRING_RETURN);
}

function countTextInPdf(string $pdf, string $needle): int {
    return substr_count(strtolower($pdf), strtolower($needle));
}

$needle = 'Aniceto Arce';
$tests = [
    'footer_only_wrapped' => renderFooterOnly($wrapped, $bodyMin, $tempDir),
    'footer_only_simplified' => renderFooterOnly($simplified, $bodyMin, $tempDir),
    'body_plus_sethtmlfooter' => renderFooterInBodyAndSet($wrapped, $bodyMin, $tempDir),
];

foreach ($tests as $name => $pdf) {
    $c = countTextInPdf($pdf, $needle);
    echo "{$name}: '{$needle}' appears {$c}x in PDF stream\n";
    file_put_contents(WRITEPATH . 'debug/footer_dup_test_' . $name . '.pdf', $pdf);
}

echo "colspan in wrapped: " . substr_count($wrapped, 'colspan') . "\n";
echo "colspan in simplified: " . substr_count($simplified, 'colspan') . "\n";
