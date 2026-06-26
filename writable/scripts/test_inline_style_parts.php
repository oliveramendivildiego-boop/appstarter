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
preg_match('/<head>(.*?)<\/head>/is', $html, $headM);
$head = $headM[1] ?? '';

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$wrapped = \App\Libraries\Pdf\MpdfFontMapper::normalizeInlineStylesInHtml('x style="' . $titleStyle . '" y');
preg_match('/style="([^"]*)"/', $wrapped, $m);
$truncated = $m[1] ?? '';
$full = $titleStyle;
$adaptedFull = preg_match('/style="([^"]*)"/', \App\Libraries\Pdf\HtmlMpdfAdapter::adapt('<div style="' . htmlspecialchars($full, ENT_QUOTES, 'UTF-8') . '"></div>', new \App\Libraries\Pdf\PdfOptions()), $m2)
    ? html_entity_decode($m2[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : $full;

echo 'truncated len=' . strlen($truncated) . ' [' . $truncated . ']' . PHP_EOL;
echo 'full len=' . strlen($adaptedFull) . PHP_EOL;

$styles = [
    'truncated' => $truncated,
    'full_adapted' => $adaptedFull,
    'no_font_family' => preg_replace('/font-family:[^;]+;?/', '', $adaptedFull) ?? $adaptedFull,
    'no_bold' => str_replace('font-weight:bold !important;', 'font-weight:normal !important;', $adaptedFull),
    'only_size_color' => 'font-size:12pt !important;color:#333333 !important;margin:0 !important;',
];

$sep = '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>';
$metodo = '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo">Método: Test</div>';

foreach ($styles as $label => $style) {
    $body = $sep . '<div class="report-pdf-grupo-cabecera"><div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $style . '">HEMOGRAMA + PLAQUETAS</div>' . $metodo . '</div>';
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($doc, new \App\Libraries\Pdf\PdfOptions());
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->WriteHTML($adapted);
    $out = WRITEPATH . 'cache/inline_' . $label . '.pdf';
    $mpdf->Output($out, \Mpdf\Output\Destination::FILE);
    echo $label . PHP_EOL;
}
