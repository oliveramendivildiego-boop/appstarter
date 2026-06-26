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
preg_match('/<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title"[^>]*>HEMOGRAMA \+ PLAQUETAS<\/div>/', $html, $titleM);
preg_match('/style="([^"]*)"/', $titleM[0] ?? '', $realStyleM);
$realStyle = $realStyleM[1] ?? '';

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$freshStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$freshAdapted = $freshStyle;
$docProbe = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt(
    '<div style="' . htmlspecialchars($freshStyle, ENT_QUOTES, 'UTF-8') . '">x</div>',
    new \App\Libraries\Pdf\PdfOptions(),
);
if (preg_match('/style="([^"]*)"/', $docProbe, $freshM)) {
    $freshAdapted = html_entity_decode($freshM[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

echo 'real len=' . strlen($realStyle) . ' fresh len=' . strlen($freshAdapted) . PHP_EOL;
echo 'equal=' . ($realStyle === $freshAdapted ? 'yes' : 'no') . PHP_EOL;
if ($realStyle !== $freshAdapted) {
    $ra = preg_split('/;/', $realStyle);
    $fa = preg_split('/;/', $freshAdapted);
    foreach (array_unique(array_merge($ra, $fa)) as $part) {
        $part = trim($part);
        if ($part === '') continue;
        $inR = str_contains($realStyle, $part);
        $inF = str_contains($freshAdapted, $part);
        if ($inR xor $inF) {
            echo 'diff: ' . $part . PHP_EOL;
        }
    }
}

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
preg_match('/<head>(.*?)<\/head>/is', $html, $headM);
$head = $headM[1] ?? '';
$sep = '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>';
$metodo = '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo">Método: Test</div>';

foreach ([
    'fresh_minimal' => $sep . '<div class="report-pdf-grupo-cabecera"><div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $freshAdapted . '">HEMOGRAMA + PLAQUETAS</div>' . $metodo . '</div>',
    'real_minimal' => $sep . '<div class="report-pdf-grupo-cabecera">' . ($titleM[0] ?? '') . $metodo . '</div>',
    'fresh_rs' => '<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">' . $sep . '<div class="report-pdf-subgrupo-block"><div class="report-pdf-grupo-cabecera"><div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $freshAdapted . '">HEMOGRAMA + PLAQUETAS</div>' . $metodo . '</div></div></div></div>',
    'real_rs' => '<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">' . $sep . '<div class="report-pdf-subgrupo-block"><div class="report-pdf-grupo-cabecera">' . ($titleM[0] ?? '') . $metodo . '</div></div></div></div>',
] as $label => $body) {
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($doc, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/stylecmp_' . $label . '.pdf', $pdf);
    echo $label . PHP_EOL;
}
