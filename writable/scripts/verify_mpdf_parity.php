<?php
declare(strict_types=1);

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
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, \App\Services\RegisterService::formatNowForReport(), $layout);
$html = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html);
$html = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(['key' => 'letter']));
$html = \App\Libraries\Pdf\HtmlMpdfAdapter::injectOrderSheetFooter($html);

echo 'injected pagination: ' . substr_count($html, 'mpdf-injected-pagination') . PHP_EOL;
echo 'watermark layer: ' . substr_count($html, 'pdf-watermark-layer') . PHP_EOL;
preg_match_all('/var\(--/', $html, $m);
echo 'var(): ' . count($m[0]) . PHP_EOL;
echo 'order sheet band: ' . substr_count($html, 'mpdf-order-sheet-band') . PHP_EOL;
file_put_contents(WRITEPATH . 'cache/mpdf_adapted_308.html', $html);
echo 'saved html: ' . WRITEPATH . 'cache/mpdf_adapted_308.html' . PHP_EOL;
