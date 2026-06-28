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

$id = (int) ($argv[1] ?? 143);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$slot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
[$html, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html);
$useDual = \App\Libraries\Pdf\MpdfNamedFooterInjector::shouldUseDualFooters($slot, (string) $fi);
echo "register={$id} useDualFooters=" . ($useDual ? 'yes' : 'no') . PHP_EOL;

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout, $id);
$doc = (new Smalot\PdfParser\Parser())->parseContent($pdf);
$pages = $doc->getPages();
echo 'pages=' . count($pages) . PHP_EOL;

foreach ($pages as $i => $page) {
    $t = $page->getText();
    $footerBand = (bool) preg_match('/Paciente:\s+[A-ZÁÉÍÓÚÑ].+No\.\s*Orden:/isu', $t);
    $hasDir = str_contains($t, 'Direccion');
    echo 'page ' . ($i + 1) . ": footer_band=" . ($footerBand ? 'yes' : 'no') . " direccion=" . ($hasDir ? 'yes' : 'no') . PHP_EOL;
}
