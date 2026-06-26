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

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$metodoStyle = \App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr($layout, true, true, true);

$cases = [
    'div_class' => '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $titleStyle . '">HEMOGRAMA + PLAQUETAS</div>',
    'p_inline' => '<p style="' . $titleStyle . 'margin:0;">HEMOGRAMA + PLAQUETAS</p>',
    'p_bold_font' => '<p style="font-family:dejavusansB;font-size:12pt;color:#333;margin:0;padding:4px 0;">HEMOGRAMA + PLAQUETAS</p>',
    'table_td' => '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="' . $titleStyle . '">HEMOGRAMA + PLAQUETAS</td></tr></table>',
];

$baseCss = file_get_contents(FCPATH . '../public/assets/css/report_pdf.css');
$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();

foreach ($cases as $name => $titleHtml) {
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $baseCss . '</style></head><body class="pdf-engine-mpdf pdf-layout-engine pdf-dompdf-download">'
        . '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;text-align:center;">HEMATOLOGIA</div>'
        . '<div class="report-pdf-grupo-cabecera">' . $titleHtml
        . '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo" style="' . $metodoStyle . '">Método: Test</div></div>'
        . '<p>MARKER_AFTER</p></body></html>';
    $html = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, new \App\Libraries\Pdf\PdfOptions());
    $pdf = $renderer->renderHtml($html, new \App\Libraries\Pdf\PdfOptions());
    $out = WRITEPATH . 'cache/test_title_' . $name . '.pdf';
    file_put_contents($out, $pdf);
    echo $name . ': ' . strlen($pdf) . ' bytes -> ' . $out . PHP_EOL;
}
