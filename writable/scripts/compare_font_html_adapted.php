<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
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
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html);

foreach (['RAW' => $html, 'ADAPTED' => $adapted] as $label => $h) {
    echo "=== $label ===\n";
    if (preg_match('/header-piece-company[\s\S]*?<h1 style="([^"]+)"/', $h, $m)) {
        echo 'h1: ' . html_entity_decode($m[1]) . "\n";
    }
    if (preg_match('/pdf-cell--v-middle[^>]*style="([^"]+)"/', $h, $m2)) {
        echo 'td: ' . html_entity_decode(substr($m2[1], 0, 200)) . "\n";
    }
}
