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

$id = 308;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

if (! preg_match('/<tr[^>]*>[\s\S]*?<div class="header-piece header-piece-logo">/', $html, $m, PREG_OFFSET_CAPTURE)) {
    echo "no logo row\n";
    exit(1);
}
$pos = $m[0][1];
$chunk = substr($html, $pos, 4000);
$chunk = preg_replace('/src="data:image[^"]+"/', 'src="[DATA]"', $chunk) ?? $chunk;
$chunk = html_entity_decode($chunk, ENT_QUOTES | ENT_HTML5, 'UTF-8');
echo $chunk;
