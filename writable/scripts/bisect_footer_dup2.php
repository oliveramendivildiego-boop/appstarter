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
$orderSheetSlot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$body, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
[$body, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($body);
$body = \App\Libraries\Pdf\MpdfFooterExtractor::purgeFooterBlocksFromBody($body);
$opts = \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null);
$wrapped = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string) $fi, $layoutSnap);
if (\App\Libraries\Pdf\MpdfOrderSheetFooterInjector::shouldPrependOrderSheetBand($orderSheetSlot, (string) $fi)) {
    $wrapped = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
        $wrapped, $orderSheetSlot, \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($wrapped),
    );
}
$metrics = \App\Libraries\Pdf\MpdfFooterStyles::resolveLayoutMetrics($layoutSnap);
$tempDir = WRITEPATH . 'cache/mpdf';
$parser = new Smalot\PdfParser\Parser();

function countPac(string $bin, Smalot\PdfParser\Parser $parser): int {
    return substr_count($parser->parseContent($bin)->getText(), 'Paciente:');
}

function render(string $bodyHtml, string $footer, array $metrics, string $tempDir): string {
    $m = new \App\Libraries\Pdf\SafeMpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
        'margin_left' => (float) $metrics['left'], 'margin_right' => (float) $metrics['right'],
        'margin_top' => (float) $metrics['top'],
        'margin_bottom' => max(0, (float) $metrics['bottom']) + max(0, (float) $metrics['footer_reserve_mm']),
        'margin_footer' => max(0, (float) $metrics['bottom']),
        'use_kwt' => false,
    ]);
    $m->SetHTMLFooter($footer);
    $m->WriteHTML($bodyHtml);
    return $m->Output('', \Mpdf\Output\Destination::STRING_RETURN);
}

$cases = [
    'raw_body' => $body,
    'footer_css_only' => \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($body, $layoutSnap),
    'adapt_only' => \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, $opts),
    'css_then_adapt' => \App\Libraries\Pdf\HtmlMpdfAdapter::adapt(
        \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($body, $layoutSnap),
        $opts,
    ),
];

foreach ($cases as $name => $bodyHtml) {
    $bin = render($bodyHtml, $wrapped, $metrics, $tempDir);
    echo "{$name}: Paciente:count=" . countPac($bin, $parser) . "\n";
}

// footer CSS on minimal body
$min = '<html><head></head><body class="pdf-engine-mpdf"><p>x</p></body></html>';
$minCss = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($min, $layoutSnap);
echo 'minimal_with_footer_css: Paciente:count=' . countPac(render($minCss, $wrapped, $metrics, $tempDir), $parser) . "\n";
