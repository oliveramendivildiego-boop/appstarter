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
$block = substr($html, $start, 8000);

preg_match('/<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title"[^>]*>HEMOGRAMA \+ PLAQUETAS<\/div>/', $block, $titleM);
preg_match('/<div class="report-metodo-prueba[^"]*"[^>]*>Método:[^<]*<\/div>/', $block, $metodoM);
$realTitle = $titleM[0] ?? '';
$realMetodo = $metodoM[0] ?? '';

$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr(
    (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender(),
    false, true, true,
);
$wrapped = \App\Libraries\Pdf\MpdfFontMapper::normalizeInlineStylesInHtml('x style="' . $titleStyle . '" y');
preg_match('/style="([^"]*)"/', $wrapped, $m);
$simpleTitle = '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . ($m[1] ?? $titleStyle) . '">HEMOGRAMA + PLAQUETAS</div>';
$simpleMetodo = '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo report-pdf-grupo-cabecera-line--last">Método: Test</div>';

$sep = '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>';
$wrap = static fn (string $cab): string => '<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">' . $sep
    . '<div class="report-pdf-subgrupo-block"><div class="report-pdf-grupo-cabecera">' . $cab . '</div></div></div></div>';

$tests = [
    'both_simple' => $simpleTitle . $simpleMetodo,
    'real_title' => $realTitle . $simpleMetodo,
    'real_metodo' => $simpleTitle . $realMetodo,
    'both_real' => $realTitle . $realMetodo,
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($tests as $label => $cab) {
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-dompdf-download pdf-engine-mpdf">' . $wrap($cab) . '</body></html>';
    $pdf = $renderer->renderHtml($doc, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/cab_' . $label . '.pdf', $pdf);
    echo $label . PHP_EOL;
    echo '  title style: ' . substr($realTitle, 0, 120) . PHP_EOL;
}
