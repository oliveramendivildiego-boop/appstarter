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
$html2 = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$html2 = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html2);
$html2 = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($html2);
[$body, ] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html2);
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));

if (preg_match('/<img[^>]+alt="Logo"[^>]*>/', $adapted, $m)) {
    echo "adapted img:\n" . $m[0] . "\n";
} else {
    echo "NO logo img after adapt\n";
}

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
$out = WRITEPATH . 'cache/logo_probe_308.pdf';
file_put_contents($out, $pdf);
echo 'pdf bytes: ' . strlen($pdf) . PHP_EOL;
echo 'has /Subtype /Image: ' . (str_contains($pdf, '/Subtype /Image') ? 'yes' : 'NO') . PHP_EOL;
echo 'has logo filename fragment: ' . (str_contains($pdf, 'logo-lab') ? 'yes' : 'no') . PHP_EOL;
echo 'saved: ' . $out . PHP_EOL;
