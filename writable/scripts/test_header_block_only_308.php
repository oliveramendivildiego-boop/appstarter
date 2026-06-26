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

if (! preg_match('/(<div class="[^"]*pdf-hg-block[^"]*"[\s\S]*?<\/table>\s*<\/div>)/', $html, $m)) {
    echo "header block not found\n";
    // fallback search
    $pos = stripos($html, 'header-piece-logo');
    if ($pos !== false) {
        echo substr($html, max(0, $pos - 300), 600) . "\n";
    }
    exit(1);
}

$mini = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body class="pdf-engine-mpdf pdf-layout-engine">'
    . $m[1]
    . '</body></html>';

$pdf = (new \App\Libraries\PdfService())->generate($mini, 'mini.pdf', null);
$out = WRITEPATH . 'cache/header_block_only_308.pdf';
file_put_contents($out, $pdf);
echo 'saved ' . $out . ' bytes=' . strlen($pdf) . PHP_EOL;
