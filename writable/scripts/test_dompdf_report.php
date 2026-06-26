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

$registroId = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);
$data = $rs->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro no encontrado\n");
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($registroId);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($registroId);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

echo 'renderer: ' . config('Pdf')->renderer . PHP_EOL;
$t0 = microtime(true);
$bin = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
$ms = round((microtime(true) - $t0) * 1000, 1);
$engine = \App\Libraries\Pdf\PdfRendererFactory::lastRenderEngine();
$out = WRITEPATH . 'cache/dompdf_test_' . $registroId . '.pdf';
file_put_contents($out, $bin);
echo "engine: {$engine}\n";
echo "ms: {$ms}\n";
echo "kb: " . round(strlen($bin) / 1024, 1) . "\n";
echo "saved: {$out}\n";
