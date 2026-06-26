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
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$wrapped = \App\Libraries\Pdf\MpdfFontMapper::normalizeInlineStylesInHtml('x style="' . $titleStyle . '" y');
preg_match('/style="([^"]*)"/', $wrapped, $m);
$titleStyleAdapted = $m[1] ?? $titleStyle;

$cabecera = '<div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first"><div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>'
    . '<div class="report-pdf-subgrupo-block"><div class="report-pdf-grupo-cabecera">'
    . '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $titleStyleAdapted . '">HEMOGRAMA + PLAQUETAS</div>'
    . '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo report-pdf-grupo-cabecera-line--last" style="font-family:\'DejaVu Sans\';font-size:8.5pt;font-weight:normal;color:#333;margin:0;">Método: Test</div>'
    . '</div>';

$table = '<div class="report-segment-table-wrap"><div class="report-segment-title pdf-card-header">Serie Roja</div>'
    . '<table class="results" width="100%"><thead><tr><th>ANÁLISIS</th><th>RESULTADO</th></tr></thead>'
    . '<tbody><tr><td>Hemoglobina</td><td>12</td></tr></tbody></table></div></div></div>';

foreach (['cabecera_only' => $cabecera . '</div></div>', 'with_table' => $cabecera . $table] as $label => $body) {
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-dompdf-download pdf-engine-mpdf"><div class="pdf-rs-block">' . $body . '</div></body></html>';
    $adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($doc, new \App\Libraries\Pdf\PdfOptions());
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->WriteHTML($adapted);
    $out = WRITEPATH . 'cache/cabecera_bisect_' . $label . '.pdf';
    $mpdf->Output($out, \Mpdf\Output\Destination::FILE);
    echo $label . ' -> ' . $out . PHP_EOL;
}
