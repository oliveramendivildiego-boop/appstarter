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

$id = (int) ($argv[1] ?? 262);
$rs = new App\Services\RegisterService();
$layout = (new App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$data = $rs->prepareReportData($id);
$emitido = $rs->reportEmitidoEnForPreviewCache($id) ?? $rs->lockReportEmitidoEnForPrintOrPdf($id);
$full = $rs->reportPdfPreviewCacheFingerprint($id, $data, $emitido, $layout);
$light = $rs->reportPdfPreviewCacheFingerprintLight($id, $emitido, $layout);
echo 'fingerprints_match: ' . ($full === $light ? 'yes' : 'no') . PHP_EOL;
helper('qr');
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));
$pdf = (new App\Libraries\PdfService())->generate($html);
$rs->writeReportPdfPreviewCache($id, $full, $pdf);
$cached = $rs->readReportPdfPreviewCache($id, $light);
echo 'cache_after_write: ' . ($cached !== null ? 'HIT ' . strlen($cached) : 'MISS') . PHP_EOL;
