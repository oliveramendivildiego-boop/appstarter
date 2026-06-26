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
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$metodoStyle = \App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr($layout, true, true, true);

$titleDiv = '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $titleStyle . '">HEMOGRAMA + PLAQUETAS</div>';
$titleP = '<p style="' . $titleStyle . 'margin:0;">HEMOGRAMA + PLAQUETAS</p>';
$metodoDiv = '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo report-pdf-grupo-cabecera-line--last" style="' . $metodoStyle . '">Método: Test</div>';
$metodoPlain = '<div style="' . $metodoStyle . '">Método: Test</div>';

$variants = [
    'div_only' => $titleDiv,
    'div_plus_metodo_div' => $titleDiv . $metodoDiv,
    'div_plus_metodo_plain' => $titleDiv . $metodoPlain,
    'p_plus_metodo_div' => $titleP . $metodoDiv,
    'p_plus_metodo_plain' => $titleP . $metodoPlain,
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($variants as $name => $cab) {
    $body = '<div class="report-segment-title report-pdf-grupo-area-separator" style="padding:2px;text-align:center;font-weight:bold;background:#eee;">HEMATOLOGIA</div><div class="report-pdf-grupo-cabecera">' . $cab . '</div>';
    $slice = '<!DOCTYPE html><html><head>' . $head[1] . '</head><body class="pdf-engine-mpdf pdf-layout-engine pdf-dompdf-download">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/test_combo_' . $name . '.pdf', $pdf);
    echo $name . PHP_EOL;
}
