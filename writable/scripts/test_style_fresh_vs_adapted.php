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
$fresh = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$adapted = 'font-family:dejavusans;font-size:12pt !important;font-weight:bold !important;color:#333333 !important;line-height:1.2 !important;text-shadow:none !important;padding-top:0px !important;padding-bottom:0px !important;margin:0 !important;';
$metodo = \App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr($layout, true, true, true);

$variants = [
    'fresh_style' => $fresh,
    'adapted_style' => $adapted,
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($variants as $name => $style) {
    $cab = '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $style . '">HEMOGRAMA + PLAQUETAS</div>'
        . '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo report-pdf-grupo-cabecera-line--last" style="' . $metodo . '">Método: Test</div>';
    $body = '<div class="report-segment-title report-pdf-grupo-area-separator" style="padding:2px;text-align:center;font-weight:bold;background:#eee;">HEMATOLOGIA</div><div class="report-pdf-grupo-cabecera">' . $cab . '</div>';
    $slice = '<!DOCTYPE html><html><head>' . $head[1] . '</head><body class="pdf-engine-mpdf pdf-layout-engine pdf-dompdf-download">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/test_style_' . $name . '.pdf', $pdf);
    echo $name . ': fresh=' . ($fresh === $adapted ? 'same' : 'diff') . PHP_EOL;
}
echo 'fresh=' . $fresh . PHP_EOL;
echo 'adapted=' . $adapted . PHP_EOL;
