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
$wm = $layout['watermark'] ?? [];
echo 'watermark enabled: ' . (! empty($wm['enabled']) ? 'yes' : 'NO') . PHP_EOL;
echo 'watermark file: ' . ($wm['file'] ?? '(none)') . PHP_EOL;
echo 'opacity: ' . ($wm['opacity'] ?? '?') . PHP_EOL;
echo 'size_percent: ' . ($wm['size_percent'] ?? '?') . PHP_EOL;

$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
echo 'has dompdf marker: ' . (str_contains($html, 'pdf-watermark-dompdf:') ? 'yes' : 'NO') . PHP_EOL;
echo 'has watermark css: ' . (str_contains($html, '.pdf-watermark-layer') ? 'yes' : 'NO') . PHP_EOL;
echo 'has watermark div: ' . (str_contains($html, '<div class="pdf-watermark-layer"') ? 'yes' : 'NO') . PHP_EOL;

$payload = \App\Services\ReportPdfLayoutService::watermarkRenderPayloadForLayout($layout, null, FCPATH . 'images/logo-john.png');
echo 'payload uri len: ' . ($payload ? strlen($payload['uri']) : 0) . PHP_EOL;
echo 'payload path: ' . ($payload['path'] ?? 'null') . PHP_EOL;

$extracted = \App\Libraries\Pdf\HtmlMpdfAdapter::extractWatermarkData($html);
echo 'extractWatermarkData: ' . ($extracted ? 'yes opacity=' . ($extracted['opacity'] ?? '?') : 'NULL') . PHP_EOL;
