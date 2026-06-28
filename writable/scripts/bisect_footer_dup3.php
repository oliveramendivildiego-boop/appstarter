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

echo 'No. Orden in adapted body HTML: ' . substr_count($bodyAdapted, 'No. Orden') . "\n";
echo 'Direccion: in adapted body: ' . substr_count($bodyAdapted, 'Direccion:') . "\n";
echo 'mpdf-ft-root divs in body: ' . preg_match_all('/<div[^>]*mpdf-ft-root/', $bodyAdapted, $m) . "\n";
echo 'mpdf-order-sheet in body: ' . substr_count($bodyAdapted, 'mpdf-order-sheet') . "\n";

// Strip injectMpdfCompatStyles block manually - test without footer hide CSS
$withoutHide = preg_replace('/\/\* Pie en body:.*?(?=<\/style>)/s', '/* pie hide removed */', $bodyAdapted) ?? $bodyAdapted;

$wrapped = file_get_contents(WRITEPATH . 'debug/final_footer_sethtml_143.html');
$parser = new Smalot\PdfParser\Parser();
$tempDir = WRITEPATH . 'cache/mpdf';
$metrics = \App\Libraries\Pdf\MpdfFooterStyles::resolveLayoutMetrics($layoutSnap);

function renderPdf(string $body, string $footer, array $metrics, string $tempDir): string {
    $m = new \App\Libraries\Pdf\SafeMpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
        'margin_bottom' => 40, 'margin_footer' => 10, 'use_kwt' => false,
    ]);
    $m->SetHTMLFooter($footer);
    $m->WriteHTML($body);
    return $m->Output('', \Mpdf\Output\Destination::STRING_RETURN);
}

foreach (['adapted' => $bodyAdapted, 'without_hide_css' => $withoutHide] as $label => $b) {
    $t = $parser->parseContent(renderPdf($b, (string) $wrapped, $metrics, $tempDir))->getText();
    echo "{$label} No. Orden count: " . substr_count($t, 'No. Orden:') . "\n";
}

// Test WITHOUT injectDocumentFooterCss in pipeline
[$body2, ] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
[$body2, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($body2);
$body2a = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body2, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
$t2 = $parser->parseContent(renderPdf($body2a, (string) $wrapped, $metrics, $tempDir))->getText();
echo 'adapt_no_footer_css No. Orden count: ' . substr_count($t2, 'No. Orden:') . "\n";

// Test WITHOUT injectDocumentFooterCss AND without adapt
$t3 = $parser->parseContent(renderPdf($body2, (string) $wrapped, $metrics, $tempDir))->getText();
echo 'raw_body No. Orden count: ' . substr_count($t3, 'No. Orden:') . "\n";
