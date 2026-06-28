<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
if ($data === null) {
    fwrite(STDERR, "No data\n");
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout, $id);

$parser = new Smalot\PdfParser\Parser();
$doc = $parser->parseContent($pdf);
$pages = $doc->getPages();
echo 'pages=' . count($pages) . ' revision=' . \App\Libraries\Pdf\HtmlMpdfAdapter::CACHE_REVISION . PHP_EOL;

$ok = true;
foreach ($pages as $i => $page) {
    $n = $i + 1;
    $t = $page->getText();
    $hasOrderRow = (bool) preg_match('/Paciente:.*No\.\s*Orden/is', $t);
    $expect = $n > 1;
    $status = $hasOrderRow === $expect ? 'OK' : 'FAIL';
    if ($status === 'FAIL') {
        $ok = false;
    }
    echo "page {$n}: order_row=" . ($hasOrderRow ? 'yes' : 'no') . " expected=" . ($expect ? 'yes' : 'no') . " [{$status}]" . PHP_EOL;
}

exit($ok ? 0 : 1);
