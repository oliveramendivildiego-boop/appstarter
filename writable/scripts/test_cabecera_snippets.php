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

$cabeceras = [
    'from_report' => file_get_contents(WRITEPATH . 'debug/cabecera_snippet_308.html'),
    'manual_p' => '<p style="' . $titleStyle . 'margin:0;">HEMOGRAMA + PLAQUETAS</p><div style="' . $metodoStyle . '">Método: Test</div>',
    'manual_table' => '<table width="100%" cellpadding="4" cellspacing="0"><tr><td style="' . $titleStyle . '">HEMOGRAMA + PLAQUETAS</td></tr><tr><td style="' . $metodoStyle . '">Método: Test</td></tr></table>',
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($cabeceras as $name => $cab) {
    $body = '<div class="report-segment-title report-pdf-grupo-area-separator" style="padding:2px;text-align:center;font-weight:bold;background:#eee;">HEMATOLOGIA</div><div class="report-pdf-grupo-cabecera">' . $cab . '</div>';
    $slice = '<!DOCTYPE html><html><head>' . $head[1] . '</head><body class="pdf-engine-mpdf pdf-layout-engine pdf-dompdf-download">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/test_cab_' . $name . '.pdf', $pdf);
    echo $name . PHP_EOL;
}
