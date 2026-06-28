<?php
declare(strict_types=1);
$id = (int) ($argv[1] ?? 143);
$_SERVER['CI_ENVIRONMENT'] = 'production';
define('ENVIRONMENT', 'production');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

$rs = new \App\Services\RegisterService();
$data = $rs->prepareReportData($id);
if (! $data) {
    fwrite(STDERR, "no data for {$id}\n");
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
try {
    $pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout, $id);
    echo 'OK ' . strlen($pdf) . " bytes\n";
} catch (\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
