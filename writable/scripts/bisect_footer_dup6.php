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

$id = 143;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$h = $html;
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($h);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$body, ] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
[$body, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($body);
$bodyAdapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
$wrapped = file_get_contents(WRITEPATH . 'debug/final_footer_sethtml_143.html');
$tempDir = WRITEPATH . 'cache/mpdf';
$parser = new Smalot\PdfParser\Parser();

function ordCount(string $body, string $footer, string $tempDir, Smalot\PdfParser\Parser $parser): int {
    $m = new \App\Libraries\Pdf\SafeMpdf(['mode'=>'utf-8','format'=>'Letter','tempDir'=>$tempDir,'margin_bottom'=>40,'margin_footer'=>10,'use_kwt'=>false]);
    $m->SetHTMLFooter($footer);
    $m->WriteHTML($body);
    return substr_count($parser->parseContent($m->Output('', \Mpdf\Output\Destination::STRING_RETURN))->getText(), 'No. Orden:');
}

preg_match('/<head>[\s\S]*<\/head>/', $bodyAdapted, $hm);
$fullHead = $hm[0] ?? '<head></head>';
preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $fullHead, $styles, PREG_SET_ORDER);

foreach ($styles as $i => $sm) {
    $css = $sm[1];
    $hits = [];
    foreach (['pdf-ft-', 'mpdf-ft-', 'footer-grid', 'pdf-section-table', 'pdf-cell', 'pdf-ft-piece'] as $needle) {
        $hits[$needle] = substr_count(strtolower($css), strtolower($needle));
    }
    echo "block {$i} len=" . strlen($css) . ' hits=' . json_encode($hits) . PHP_EOL;
    $doc = '<html><head><style>' . $css . '</style></head><body class="pdf-engine-mpdf"><p>x</p></body></html>';
    echo "  ordCount=" . ordCount($doc, (string) $wrapped, $tempDir, $parser) . PHP_EOL;
}

// Test: block0 after manual full strip of pdf-section-table rules too
if (isset($styles[0])) {
    $css = $styles[0][1];
    $css2 = preg_replace('/[^}]*pdf-section-table[^{]*\{[^}]*\}/s', '', $css) ?? $css;
    $doc = '<html><head><style>' . $css2 . '</style></head><body class="pdf-engine-mpdf"><p>x</p></body></html>';
    echo 'block0 no pdf-section-table rules: ' . ordCount($doc, (string) $wrapped, $tempDir, $parser) . PHP_EOL;
}
