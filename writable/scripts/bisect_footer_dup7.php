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

function splitCssRules(string $css): array {
    preg_match_all('/([^{@]+)\{((?:[^{}]++|\{(?:[^{}]++|\{[^{}]*+\})*+\})*+)\}/s', $css, $m, PREG_SET_ORDER);
    return array_map(static fn ($x) => $x[0], $m);
}

preg_match('/<head>[\s\S]*<\/head>/', $bodyAdapted, $hm);
preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $hm[0] ?? '', $styles, PREG_SET_ORDER);
$css = $styles[0][1] ?? '';
$rules = splitCssRules($css);
echo 'block0 rules: ' . count($rules) . PHP_EOL;

function bisectRules(array $rules, string $wrapped, string $tempDir, Smalot\PdfParser\Parser $parser, int $depth = 0): void {
    if ($rules === []) {
        return;
    }
    $doc = static fn (array $r): string => '<html><head><style>' . implode('', $r) . '</style></head><body class="pdf-engine-mpdf"><p>x</p></body></html>';
    $c = ordCount($doc($rules), $wrapped, $tempDir, $parser);
    if ($c <= 1 || count($rules) <= 1) {
        if ($c > 1 && count($rules) === 1) {
            echo str_repeat('  ', $depth) . 'CULPRIT: ' . substr(preg_replace('/\s+/', ' ', $rules[0]), 0, 200) . PHP_EOL;
        }
        return;
    }
    $mid = (int) ceil(count($rules) / 2);
    $a = array_slice($rules, 0, $mid);
    $b = array_slice($rules, $mid);
    $ca = ordCount($doc($a), $wrapped, $tempDir, $parser);
    $cb = ordCount($doc($b), $wrapped, $tempDir, $parser);
    echo str_repeat('  ', $depth) . "split {$mid}/" . count($rules) . " ca={$ca} cb={$cb}" . PHP_EOL;
    if ($ca > 1) {
        bisectRules($a, $wrapped, $tempDir, $parser, $depth + 1);
    }
    if ($cb > 1) {
        bisectRules($b, $wrapped, $tempDir, $parser, $depth + 1);
    }
}

bisectRules($rules, (string) $wrapped, $tempDir, $parser);
