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

$id = 308;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$html2 = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$html2 = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html2);
$html2 = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($html2);
[$body, ] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html2);
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
file_put_contents(WRITEPATH . 'debug/adapted_body_308.html', $adapted);

$broken = preg_match('/font-family:"DejaVu Sans"/', $adapted) ? 'YES' : 'no';
$fixed = preg_match("/font-family:'DejaVu Sans'/", $adapted) ? 'yes' : 'NO';
echo "broken double quotes: {$broken}\n";
echo "fixed single quotes: {$fixed}\n";

if (preg_match('/header-piece-company[\s\S]{0,400}/', $adapted, $m)) {
    echo substr($m[0], 0, 350) . "\n";
}

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
file_put_contents(WRITEPATH . 'cache/mpdf_pipeline_308.pdf', $pdf);
$fp = $rs->reportPdfPreviewCacheFingerprint($id, $data, $emitido, $layout);
$rs->writeReportPdfPreviewCache($id, $fp, $pdf);
echo 'pdf kb: ' . round(strlen($pdf) / 1024, 1) . PHP_EOL;
