<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$html = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
[$body, $footerInner] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html);
$footerWrapped = $footerInner ? \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($footerInner, $layoutSnap ?: $layout) : null;

echo 'footer extracted: ' . ($footerInner !== null && $footerInner !== '' ? 'yes' : 'NO') . PHP_EOL;
echo 'layout marker: ' . ($layoutSnap !== [] ? 'yes' : 'no') . PHP_EOL;
echo 'footer inner len: ' . strlen((string) $footerInner) . PHP_EOL;
echo 'footer wrapped len: ' . strlen((string) $footerWrapped) . PHP_EOL;
$metrics = $layoutSnap !== [] || $layout !== []
    ? \App\Libraries\Pdf\MpdfFooterStyles::resolveLayoutMetrics($layoutSnap ?: $layout)
    : \App\Libraries\Pdf\MpdfLayoutMetrics::fromHtml($html);
echo 'metrics: ' . json_encode($metrics) . PHP_EOL;

if ($footerWrapped) {
    file_put_contents(WRITEPATH . 'debug/mpdf_footer_final_308.html', $footerWrapped);
    echo "saved: writable/debug/mpdf_footer_final_308.html\n";
    echo (str_contains($footerWrapped, 'padding-padding') ? "ERROR: corrupted padding\n" : "OK: no corrupted padding\n");
    echo (str_contains($footerWrapped, '<style>') ? "OK: embedded styles\n" : "MISSING: embedded styles\n");
}

// minimal mpdf test with just footer
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'Letter',
    'margin_left' => 25,
    'margin_right' => 10,
    'margin_top' => 5,
    'margin_bottom' => 2,
    'margin_footer' => 22,
    'default_font' => 'dejavusans',
]);
if ($footerWrapped) {
    $mpdf->SetHTMLFooter($footerWrapped);
}
$mpdf->WriteHTML('<body><p>Test body page 1</p><pagebreak /><p>Test body page 2</p></body>');
$out = WRITEPATH . 'debug/mpdf_footer_minimal_test.pdf';
file_put_contents($out, $mpdf->Output('', Destination::STRING_RETURN));
echo "minimal test: $out\n";
