<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$html = file_get_contents(WRITEPATH . 'debug/adapted_body_308.html');
preg_match('/<head>(.*?)<\/head>/is', $html, $headMatch);
$head = $headMatch[1] ?? '';
$start = strpos($html, '<div class="pdf-rs-block">');
$end = strpos($html, '<div class="report-grupo-inter-page-break', $start);
$block = substr($html, $start, $end - $start);

preg_match('/<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator"[^>]*>HEMATOLOGIA<\/div>/', $block, $sepM);
$realSep = $sepM[0] ?? '';

preg_match('/<div class="report-pdf-grupo-cabecera">[\s\S]*?<\/div>\s*<div class="report-segment-table-wrap">/', $block, $cabM);
$cabeceraBlock = preg_replace('/\s*<div class="report-segment-table-wrap">$/', '', $cabM[0] ?? '') ?? '';

$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr(
    (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender(),
    false, true, true,
);
$wrapped = \App\Libraries\Pdf\MpdfFontMapper::normalizeInlineStylesInHtml('x style="' . $titleStyle . '" y');
preg_match('/style="([^"]*)"/', $wrapped, $m);
$titleStyleAdapted = $m[1] ?? $titleStyle;
$simpleCab = '<div class="report-pdf-grupo-cabecera">'
    . '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $titleStyleAdapted . '">HEMOGRAMA + PLAQUETAS</div>'
    . '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo report-pdf-grupo-cabecera-line--last">Método: Test</div></div>';

$simpleSep = '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>';

$tests = [
    'minimal' => $simpleSep . $simpleCab,
    'real_sep' => $realSep . $simpleCab,
    'real_cabecera' => $simpleSep . $cabeceraBlock,
    'subgrupo_wrap' => $simpleSep . '<div class="report-pdf-subgrupo-block">' . $simpleCab . '</div>',
    'grupo_wrap' => '<div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">' . $simpleSep . '<div class="report-pdf-subgrupo-block">' . $simpleCab . '</div></div>',
    'rs_block' => '<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">' . $simpleSep . '<div class="report-pdf-subgrupo-block">' . $simpleCab . '</div></div></div>',
    'real_all' => '<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first" data-layout-area-index="0">' . $realSep . '<div class="report-pdf-subgrupo-block" data-layout-block-id="area-0-block-0">' . $cabeceraBlock . '</div></div></div>',
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($tests as $label => $body) {
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($doc, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/wrap_' . $label . '.pdf', $pdf);
    echo $label . PHP_EOL;
}
