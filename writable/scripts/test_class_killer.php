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
$snippet = file_get_contents(WRITEPATH . 'debug/cabecera_snippet_308.html');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);
$metodoStyle = \App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr($layout, true, true, true);

$variants = [
    'original' => $snippet,
    'no_classes_title' => preg_replace('/class="[^"]*report-pdf-grupo-cabecera-line[^"]*"/', 'class="report-pdf-grupo-cabecera-mpdf-title"', $snippet) ?? $snippet,
    'title_only_class' => preg_replace(
        '/<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title"/',
        '<div class="report-pdf-grupo-cabecera-mpdf-title"',
        $snippet
    ) ?? $snippet,
    'p_tag' => '<p style="' . $titleStyle . 'margin:0;">HEMOGRAMA + PLAQUETAS</p>' . preg_replace('/<div class="report-pdf-grupo-cabecera-line[^"]*report-pdf-grupo-cabecera-line--title"[^>]*>.*?<\/div>/s', '', $snippet),
];

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($variants as $name => $cab) {
    $body = '<div class="report-segment-title report-pdf-grupo-area-separator" style="padding:2px;text-align:center;font-weight:bold;background:#eee;">HEMATOLOGIA</div><div class="report-pdf-grupo-cabecera">' . $cab . '</div>';
    $slice = '<!DOCTYPE html><html><head>' . $head[1] . '</head><body class="pdf-engine-mpdf pdf-layout-engine pdf-dompdf-download">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/test_cls_' . $name . '.pdf', $pdf);
    echo $name . PHP_EOL;
}
