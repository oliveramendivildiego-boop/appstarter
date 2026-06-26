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

$id = 305;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$rs->clearReportPdfPreviewCache($id);

$data = $rs->prepareReportData($id, false, false);
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$emitidoEn = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$fpFull = $rs->reportPdfPreviewCacheFingerprint($id, $data, $emitidoEn, $layout);
$fpLight = $rs->reportPdfPreviewCacheFingerprintLight($id, $emitidoEn, $layout);

echo "full:  $fpFull\n";
echo "light: $fpLight\n";
echo "equal: " . ($fpFull === $fpLight ? 'yes' : 'no') . "\n";

$ok = $rs->warmReportPdfPreviewCache($id);
echo "warm: " . ($ok ? 'ok' : 'fail') . "\n";

$meta = trim((string) file_get_contents(WRITEPATH . 'cache/report_pdf_preview/registro_' . $id . '.pdf.meta'));
echo "meta:  $meta\n";
echo "meta=full:  " . ($meta === $fpFull ? 'yes' : 'no') . "\n";
echo "meta=light: " . ($meta === $fpLight ? 'yes' : 'no') . "\n";
echo "read full:  " . ($rs->readReportPdfPreviewCache($id, $fpFull) !== null ? 'hit' : 'miss') . "\n";
echo "read light: " . ($rs->readReportPdfPreviewCache($id, $fpLight) !== null ? 'hit' : 'miss') . "\n";
