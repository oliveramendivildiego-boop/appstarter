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

$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
echo 'html len: ' . strlen($html) . PHP_EOL;
echo 'has footer marker: ' . (str_contains($html, 'report-pdf-footer:start') ? 'yes' : 'NO') . PHP_EOL;
echo 'has mpdf layout: ' . (str_contains($html, 'pdf-mpdf-layout:') ? 'yes' : 'NO') . PHP_EOL;

$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$html2 = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$html2 = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html2);
$html2 = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($html2);
[$body, $footerInner] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html2);
echo 'footer inner after extract: ' . ($footerInner ? strlen($footerInner) : 0) . PHP_EOL;

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
$out = WRITEPATH . 'cache/mpdf_pipeline_308.pdf';
file_put_contents($out, $pdf);
echo 'pdf kb: ' . round(strlen($pdf)/1024, 1) . PHP_EOL;
echo 'engine: ' . \App\Libraries\Pdf\PdfRendererFactory::lastRenderEngine() . PHP_EOL;
echo 'saved: ' . $out . PHP_EOL;

// Decompress-ish search
$raw = $pdf;
foreach (['Direccion', 'Celular', 'Tarija', 'quantumlaboratorio', 'Página'] as $needle) {
    echo $needle . ': ' . (str_contains($raw, $needle) ? 'YES' : 'no') . PHP_EOL;
}
