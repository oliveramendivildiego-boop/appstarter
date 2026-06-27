<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$data = $rs->prepareReportData($id);
helper('qr');
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
$needle = 'class="header header-grid pdf-hg-block"';
$pos = strpos($html, $needle);
echo 'header div pos=' . ($pos === false ? 'MISSING' : (string) $pos) . PHP_EOL;
if ($pos !== false) {
    $slice = substr($html, $pos, 2500);
    echo $slice . PHP_EOL;
}
// compare cached preview pdf
$fp = $rs->reportPdfPreviewCacheFingerprint($id, $data, $emitido, $layout);
$cached = $rs->readReportPdfPreviewCache($id, $fp);
echo PHP_EOL . 'cache fingerprint=' . substr($fp, 0, 16) . '... hit=' . ($cached !== null ? 'yes' : 'no') . PHP_EOL;
if ($cached !== null) {
    file_put_contents(WRITEPATH . 'cache/viewreport_cached_308.pdf', $cached);
    echo 'saved cached pdf' . PHP_EOL;
}
