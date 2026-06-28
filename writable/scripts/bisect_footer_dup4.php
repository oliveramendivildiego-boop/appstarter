<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
putenv('PDF_RENDERER=mpdf');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 143);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$h = $html;
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($h);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$body, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
[$body, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($body);
$body = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($body, $layoutSnap);
$bodyAdapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
$wrapped = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string) $fi, $layoutSnap);
$slot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
if (\App\Libraries\Pdf\MpdfOrderSheetFooterInjector::shouldPrependOrderSheetBand($slot, (string) $fi)) {
    $wrapped = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter($wrapped, $slot, 5);
}
$metrics = \App\Libraries\Pdf\MpdfFooterStyles::resolveLayoutMetrics($layoutSnap);
$tempDir = WRITEPATH . 'cache/mpdf';
$parser = new Smalot\PdfParser\Parser();

function ordCount(string $body, string $footer, array $metrics, string $tempDir, Smalot\PdfParser\Parser $parser): int {
    $m = new \App\Libraries\Pdf\SafeMpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
        'margin_bottom' => 40, 'margin_footer' => 10, 'use_kwt' => false,
    ]);
    $m->SetHTMLFooter($footer);
    $m->WriteHTML($body);
    return substr_count($parser->parseContent($m->Output('', \Mpdf\Output\Destination::STRING_RETURN))->getText(), 'No. Orden:');
}

// Empty main stack keep head
$emptyStack = preg_replace('/<div class="pdf-main-stack">[\s\S]*<\/div>\s*(?=<\/body>)/', '<div class="pdf-main-stack"><p>stub</p></div>', $bodyAdapted, 1) ?? $bodyAdapted;

// Head only
preg_match('/<head>[\s\S]*<\/head>/', $bodyAdapted, $hm);
$headOnly = '<html>' . ($hm[0] ?? '') . '<body class="pdf-engine-mpdf"><p>stub</p></body></html>';

echo 'full adapted: ' . ordCount($bodyAdapted, $wrapped, $metrics, $tempDir, $parser) . "\n";
echo 'empty stack: ' . ordCount($emptyStack, $wrapped, $metrics, $tempDir, $parser) . "\n";
echo 'head only: ' . ordCount($headOnly, $wrapped, $metrics, $tempDir, $parser) . "\n";

// Without injectDocumentFooterCss in head
$bodyNoFtCss = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt(
    preg_replace('/<style>\s*\/\* mPDF footer \(plantilla\) \*\/[\s\S]*?<\/style>\s*/', '', $body) ?? $body,
    \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null),
);
echo 'adapt without footer css block in head: ' . ordCount($bodyNoFtCss, $wrapped, $metrics, $tempDir, $parser) . "\n";

// Footer CSS only in SetHTMLFooter fragment
$wrappedWithCss = '<style>' . \App\Libraries\Pdf\MpdfFooterStyles::buildFooterCssRules($layoutSnap) . '</style>' . $wrapped;
$bodyNoHeadFooterCss = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
echo 'footer css in SetHTMLFooter only: ' . ordCount($bodyNoHeadFooterCss, $wrappedWithCss, $metrics, $tempDir, $parser) . "\n";
