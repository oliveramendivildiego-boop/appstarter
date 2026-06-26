<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
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

if (preg_match('/<td[^>]*>[\s\S]*?<div class="header-piece header-piece-logo">[\s\S]*?<\/td>/', $html, $m)) {
    $chunk = preg_replace('/src="data:image[^"]+"/', 'src="[DATA_URI]"', $m[0]) ?? $m[0];
    echo "logo td:\n" . $chunk . "\n";
} else {
    echo "no logo td\n";
}

if (preg_match('/<td[^>]*>[\s\S]*?header-piece-qr[\s\S]*?<\/td>/', $html, $m2)) {
    $chunk2 = preg_replace('/src="data:image[^"]+"/', 'src="[DATA_URI]"', $m2[0]) ?? $m2[0];
    echo "\nqr td:\n" . substr($chunk2, 0, 1200) . "\n";
}
