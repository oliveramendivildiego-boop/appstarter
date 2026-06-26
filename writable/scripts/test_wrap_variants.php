<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$html = file_get_contents(WRITEPATH . 'debug/adapted_body_308.html');
preg_match('/<head>(.*?)<\/head>/is', $html, $head);
preg_match('/<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator"[^>]*>HEMATOLOGIA<\/div>\s*<div class="report-pdf-subgrupo-block"[^>]*>\s*<div class="report-pdf-grupo-cabecera">(.*?)<\/div>/s', $html, $cab);

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);

$minimal = '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;text-align:center;">HEMATOLOGIA</div>'
    . '<div class="report-pdf-grupo-cabecera">' . $cab[1] . '</div>';

$wraps = [
    'bare' => $minimal,
    'grupo_first' => '<div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">' . $minimal . '</div>',
    'pdf_rs' => '<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">' . $minimal . '</div></div>',
    'subgrupo' => '<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first"><div class="report-pdf-subgrupo-block">' . $minimal . '</div></div></div>',
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($wraps as $name => $body) {
    $slice = '<!DOCTYPE html><html><head>' . $head[1] . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/test_wrap_' . $name . '.pdf', $pdf);
    echo $name . PHP_EOL;
}
