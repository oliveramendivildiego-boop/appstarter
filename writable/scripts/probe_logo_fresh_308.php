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

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$rs->clearReportPdfPreviewCache($id);
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
if (preg_match('/<img[^>]+alt="Logo"[^>]*>/', $html, $m)) {
    $tag = preg_replace('/src="data:image[^"]+"/', 'src="[DATA]"', $m[0]) ?? $m[0];
    echo "logo style in html: $tag\n";
}

try {
    $pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout, $id);
    $out = WRITEPATH . 'cache/logo_probe_fresh_308.pdf';
    file_put_contents($out, $pdf);
    echo 'OK bytes=' . strlen($pdf) . ' saved=' . $out . PHP_EOL;
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
