<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);

$id = (int) ($argv[1] ?? 298);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id, false, false);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$fp = $rs->reportPdfPreviewCacheFingerprint($id, $data, $emitido, $layout);
$cached = $rs->readReportPdfPreviewCache($id, $fp);
$fresh = $rs->generateReportPdfBinary($data, $url, qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout)), $emitido, $layout);

echo "ID $id fingerprint match cache: " . ($cached !== null ? 'yes len=' . strlen($cached) : 'no') . "\n";
echo 'fresh sha256: ' . hash('sha256', $fresh) . "\n";
if ($cached !== null) {
    echo 'cached sha256: ' . hash('sha256', $cached) . "\n";
    echo 'identical: ' . (hash_equals(hash('sha256', $fresh), hash('sha256', $cached)) ? 'yes' : 'no') . "\n";
}
