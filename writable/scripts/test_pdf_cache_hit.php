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
$emitido = $rs->reportEmitidoEnForPreviewCache($id);
echo 'emitido: ' . ($emitido ?? 'null') . PHP_EOL;
if ($emitido === null) {
    exit(0);
}
$fp = $rs->reportPdfPreviewCacheFingerprintLight($id, $emitido, $layout);
$cached = $rs->readReportPdfPreviewCache($id, $fp);
echo 'fingerprint: ' . substr($fp, 0, 16) . '...' . PHP_EOL;
echo 'cache: ' . ($cached !== null ? strlen($cached) . ' bytes HIT' : 'MISS') . PHP_EOL;
