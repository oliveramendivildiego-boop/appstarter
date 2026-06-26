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

preg_match_all('/<tr class="pdf-section-row"[\s\S]*?<\/tr>/', $html, $rows);
echo 'rows=' . count($rows[0]) . PHP_EOL;
foreach ($rows[0] as $i => $row) {
    $types = [];
    if (str_contains($row, 'header-piece-logo')) $types[] = 'logo';
    if (str_contains($row, 'header-piece-company')) $types[] = 'company';
    if (str_contains($row, 'header-piece-qr')) $types[] = 'qr';
    if ($types !== []) {
        echo "row $i: " . implode(',', $types) . PHP_EOL;
    }
}
