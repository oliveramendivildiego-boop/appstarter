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

$id = (int) ($argv[1] ?? 305);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();

$data = $rs->prepareReportData($id, true);
if ($data === null) {
    echo "no data\n";
    exit(1);
}

$emitidoEnPreview = $rs->reportEmitidoEnForPreviewCache($id);
$emitidoEnLock = $rs->lockReportEmitidoEnForPrintOrPdf($id);
echo "emitido preview: " . ($emitidoEnPreview ?? 'null') . "\n";
echo "emitido lock:    $emitidoEnLock\n";

$fpPreview = $rs->reportPdfPreviewCacheFingerprint($id, $data, $emitidoEnPreview ?? $emitidoEnLock, $layout);
$fpl = $rs->reportPdfPreviewCacheFingerprintLight($id, $emitidoEnPreview ?? $emitidoEnLock, $layout);
$fp = $rs->reportPdfPreviewCacheFingerprint($id, $data, $emitidoEnLock, $layout);

echo "fingerprint lock:  $fp\n";
echo "fingerprint prev:  $fpPreview\n";
echo "fingerprint light: $fpl\n";
echo "match light/full: " . ($fp === $fpl ? 'yes' : 'no') . "\n";

$read1 = $rs->readReportPdfPreviewCache($id, $fp);
echo "read before write: " . ($read1 === null ? 'miss' : 'hit (' . strlen($read1) . ' bytes)') . "\n";

$ok = $rs->warmReportPdfPreviewCache($id);
echo "warm: " . ($ok ? 'ok' : 'fail') . "\n";

$read2 = $rs->readReportPdfPreviewCache($id, $fp);
echo "read after warm: " . ($read2 === null ? 'miss' : 'hit (' . strlen($read2) . ' bytes)') . "\n";

$path = WRITEPATH . 'cache/report_pdf_preview/registro_' . $id . '.pdf';
$meta = $path . '.meta';
echo "pdf file exists: " . (is_file($path) ? 'yes' : 'no') . "\n";
echo "meta file exists: " . (is_file($meta) ? 'yes' : 'no') . "\n";
if (is_file($meta)) {
    echo "meta matches fp: " . (trim((string) file_get_contents($meta)) === $fp ? 'yes' : 'no') . "\n";
}

$oldFp = hash('sha256', implode("\n", ['old-salt-test']));
$readStale = $rs->readReportPdfPreviewCache($id, $oldFp);
echo "read wrong fingerprint: " . ($readStale === null ? 'miss (ok)' : 'hit (bad)') . "\n";

$dataCache = new \App\Services\Report\ReportDataCacheService();
$dfp = $dataCache->computeFingerprint($id);
$dataRead = $dataCache->read($id, $dfp);
echo "data cache read: " . ($dataRead === null ? 'miss' : 'hit') . "\n";
