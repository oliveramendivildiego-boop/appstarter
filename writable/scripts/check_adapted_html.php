<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);

$id = (int) ($argv[1] ?? 288);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id, false, false);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html);
    preg_match_all('/report-pdf-grupo-cabecera-line--title[^>]*>([^<]{2,120})</', $adapted, $m);
    echo "ID $id adapted titles: " . implode(' | ', array_unique($m[1])) . "\n";
    echo 'adapted len: ' . strlen($adapted) . ' vs raw ' . strlen($html) . "\n";
    echo 'grupo-prueba raw=' . substr_count($html, 'report-pdf-grupo-prueba') . ' adapted=' . substr_count($adapted, 'report-pdf-grupo-prueba') . "\n";
    echo 'tables adapted=' . substr_count($adapted, 'class="results"') . "\n";
    echo "broken style=style: " . substr_count($adapted, 'style="style=') . " double>>: " . substr_count($adapted, '>>') . "\n";
