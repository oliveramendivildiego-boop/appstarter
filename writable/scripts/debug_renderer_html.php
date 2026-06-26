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

$renderer = new \App\Libraries\Pdf\MpdfPdfRenderer();
$ref = new ReflectionClass($renderer);
$meth = $ref->getMethod('renderHtml');
$meth->setAccessible(true);

// Hook: capture html passed to WriteHTML by subclassing - simpler: grep in pipeline
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$orderSheetSlot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
$htmlW = $html;
$htmlW = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($htmlW);
$htmlW = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($htmlW);
$htmlW = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($htmlW);
[$htmlW, $footerInner] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($htmlW);
[$htmlW, $headerHtml] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($htmlW);
$htmlW = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($htmlW, $layoutSnap);
$htmlW = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($htmlW, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));

$footerP1 = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string)$footerInner, $layoutSnap);
$footerRest = $footerP1;
$useDual = \App\Libraries\Pdf\MpdfNamedFooterInjector::shouldUseDualFooters($orderSheetSlot, (string)$footerInner);
if ($useDual) {
    $footerRest = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
        $footerP1, $orderSheetSlot, \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($footerP1)
    );
}
$footerP1 = \App\Libraries\Pdf\HtmlMpdfAdapter::adaptFooterForMpdf($footerP1);
$footerRest = \App\Libraries\Pdf\HtmlMpdfAdapter::adaptFooterForMpdf($footerRest);
if ($useDual) {
    $htmlW = \App\Libraries\Pdf\MpdfNamedFooterInjector::inject($htmlW, $footerP1, $footerRest);
}

echo 'useDual: ' . ($useDual ? 'yes' : 'no') . PHP_EOL;
echo 'has @page footer rest: ' . (preg_match('/@page\s*\{[^}]*footer:\s*html_report-ft-rest/', $htmlW) ? 'yes' : 'no') . PHP_EOL;
echo 'has @page :first: ' . (str_contains($htmlW, '@page :first') ? 'yes' : 'no') . PHP_EOL;
echo 'body still has footer marker: ' . (str_contains($htmlW, 'report-pdf-footer:start') ? 'yes' : 'no') . PHP_EOL;
echo 'body still has order row: ' . (str_contains($htmlW, 'pdf-order-sheet-table-row') ? 'yes' : 'no') . PHP_EOL;
echo 'htmlpagefooter count: ' . substr_count($htmlW, '<htmlpagefooter') . PHP_EOL;

file_put_contents(WRITEPATH . 'cache/debug_pre_writehtml.html', $htmlW);

// render and check
$pdf = $renderer->renderHtml($html, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
file_put_contents(WRITEPATH . 'cache/debug_renderer_308.pdf', $pdf);
echo 'pdf bytes: ' . strlen($pdf) . PHP_EOL;
