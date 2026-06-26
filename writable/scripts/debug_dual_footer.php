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

$id = 308;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

$slot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
echo 'slot: ' . ($slot ? json_encode($slot, JSON_UNESCAPED_UNICODE) : 'null') . PHP_EOL;

[$body, $footerInner] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html);
echo 'footerInner len: ' . strlen((string)$footerInner) . PHP_EOL;
echo 'footer has order row: ' . (str_contains((string)$footerInner, 'pdf-order-sheet-table-row') ? 'yes' : 'no') . PHP_EOL;
echo 'should dual: ' . (\App\Libraries\Pdf\MpdfNamedFooterInjector::shouldUseDualFooters($slot, (string)$footerInner) ? 'yes' : 'no') . PHP_EOL;

// Simulate renderer preprocessing
$html2 = $body;
$html2 = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($html2);
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$html2 = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($html2, $layoutSnap);
$html2 = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html2, new \App\Libraries\Pdf\PdfOptions('A4'));

$footerP1 = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string)$footerInner, $layoutSnap);
$footerRest = $footerP1;
if (\App\Libraries\Pdf\MpdfNamedFooterInjector::shouldUseDualFooters($slot, (string)$footerInner)) {
    $footerRest = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
        $footerP1, $slot, \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($footerP1)
    );
}
echo 'p1 has row: ' . (str_contains($footerP1, 'pdf-order-sheet-table-row') ? 'yes' : 'no') . PHP_EOL;
echo 'rest has row: ' . (str_contains($footerRest, 'pdf-order-sheet-table-row') ? 'yes' : 'no') . PHP_EOL;

$footerP1a = \App\Libraries\Pdf\HtmlMpdfAdapter::adaptFooterForMpdf($footerP1);
$footerResta = \App\Libraries\Pdf\HtmlMpdfAdapter::adaptFooterForMpdf($footerRest);
$html3 = \App\Libraries\Pdf\MpdfNamedFooterInjector::inject($html2, $footerP1a, $footerResta);
echo 'has htmlpagefooter p1: ' . (str_contains($html3, 'name="report-ft-p1"') ? 'yes' : 'no') . PHP_EOL;
echo 'has @page :first: ' . (str_contains($html3, '@page :first') ? 'yes' : 'no') . PHP_EOL;
echo 'has @page after adapt stripped theme: ' . (preg_match('/@page\s*\{[^}]*margin/i', $html3) ? 'yes(margin)' : 'no margin @page') . PHP_EOL;

if (preg_match('/@page\s*:first\s*\{[^}]+\}/', $html3, $m)) {
    echo 'first rule: ' . trim($m[0]) . PHP_EOL;
}
