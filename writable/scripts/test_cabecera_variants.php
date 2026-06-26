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

$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr(
    (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender(),
    false,
    true,
    true,
);
$wrapped = \App\Libraries\Pdf\MpdfFontMapper::normalizeInlineStylesInHtml('x style="' . $titleStyle . '" y');
preg_match('/style="([^"]*)"/', $wrapped, $m);
$titleStyleAdapted = $m[1] ?? $titleStyle;

$variants = [
    'as_in_html' => '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $titleStyleAdapted . '">HEMOGRAMA + PLAQUETAS</div>',
    'normal_weight' => '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="font-family:\'DejaVu Sans\';font-size:12pt !important;font-weight:normal !important;color:#333333 !important;margin:0 !important;">HEMOGRAMA + PLAQUETAS</div>',
    'h4_bold' => '<h4 class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="font-family:\'DejaVu Sans\';font-size:12pt !important;font-weight:bold !important;color:#333333 !important;margin:0 !important;">HEMOGRAMA + PLAQUETAS</h4>',
    'strong_wrap' => '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="font-family:\'DejaVu Sans\';font-size:12pt !important;font-weight:normal !important;color:#333333 !important;margin:0 !important;"><strong>HEMOGRAMA + PLAQUETAS</strong></div>',
];

foreach ($variants as $label => $titleHtml) {
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">'
        . '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>'
        . '<div class="report-pdf-grupo-cabecera">' . $titleHtml
        . '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo">Método: Test</div></div></body></html>';
    $adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($doc, new \App\Libraries\Pdf\PdfOptions());
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->WriteHTML($adapted);
    $out = WRITEPATH . 'cache/cabecera_variant_' . $label . '.pdf';
    $mpdf->Output($out, \Mpdf\Output\Destination::FILE);
    echo $label . ' -> ' . $out . PHP_EOL;
}
