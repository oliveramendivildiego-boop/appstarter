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
preg_match('/<div class="pdf-rs-block"><div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first".*?</div>\s*(?=<div class="report-grupo-inter-page-break|<\/div>\s*<\/body>)/s', $html, $block);

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$metodoStyle = \App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr($layout, true, true, true);

$titles = [
    'plus' => 'HEMOGRAMA + PLAQUETAS',
    'simple' => 'Glucosa',
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($titles as $name => $titleText) {
    $body = str_replace('HEMOGRAMA + PLAQUETAS', $titleText, $block[0]);
    $slice = '<!DOCTYPE html><html><head>' . $head[1] . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
    $out = WRITEPATH . 'cache/test_first_area_' . $name . '.pdf';
    file_put_contents($out, $pdf);
    echo $name . ' -> ' . $out . PHP_EOL;
}
