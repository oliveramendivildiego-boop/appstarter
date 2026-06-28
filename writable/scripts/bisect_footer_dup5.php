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

$id = 143;
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

$cases = [
    'bare' => '<html><head></head><body><p>x</p></body></html>',
    'bare_adapt' => \App\Libraries\Pdf\HtmlMpdfAdapter::adapt('<html><head></head><body class="pdf-engine-mpdf"><p>x</p></body></html>', \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null)),
    'full_head' => '<html>' . $fullHead . '<body class="pdf-engine-mpdf"><p>x</p></body></html>',
];

foreach ($cases as $label => $doc) {
    echo "{$label}: " . ordCount($doc, (string)$wrapped, $tempDir, $parser) . "\n";
}

// Extract individual style blocks from full head and test
preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $fullHead, $styles, PREG_SET_ORDER);
echo 'Style blocks in head: ' . count($styles) . "\n";
foreach ($styles as $i => $sm) {
    $doc = '<html><head><style>' . $sm[1] . '</style></head><body class="pdf-engine-mpdf"><p>x</p></body></html>';
    $c = ordCount($doc, (string)$wrapped, $tempDir, $parser);
    if ($c > 1) {
        echo "  block {$i} CAUSES DUP count={$c} len=" . strlen($sm[1]) . " snippet=" . substr(preg_replace('/\s+/', ' ', $sm[1]), 0, 120) . "\n";
    }
}
