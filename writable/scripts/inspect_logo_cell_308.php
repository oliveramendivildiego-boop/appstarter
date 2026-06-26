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
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

if (preg_match('/<img[^>]+alt="Logo"[^>]*>/', $html, $m)) {
    $img = preg_replace('/src="data:image[^"]+"/', 'src="[DATA]"', $m[0]) ?? $m[0];
    echo "LOGO IMG:\n" . $img . "\n\n";
    $pos = strpos($html, $m[0]);
    $ctx = substr($html, max(0, $pos - 1200), 2400);
    $ctx = preg_replace('/src="data:image[^"]+"/', 'src="[DATA]"', $ctx) ?? $ctx;
    echo "CONTEXT:\n" . $ctx . "\n";
} else {
    echo "NO logo img\n";
}
