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
$start = strpos($html, '<div class="pdf-rs-block">');
$end = strpos($html, '<div class="report-grupo-inter-page-break', $start);
$block = substr($html, $start, $end - $start);

$variants = [
    'original' => $block,
    'normal_weight' => str_replace('font-weight:bold !important', 'font-weight:normal !important', $block),
    'dejavusansB' => preg_replace(
        '/report-pdf-grupo-cabecera-line--title" style="([^"]*)font-weight:bold !important;/',
        'report-pdf-grupo-cabecera-line--title" style="$1font-family:dejavusansB;font-weight:normal !important;',
        $block
    ) ?? $block,
    'no_pagebreak_css' => $block,
];

$noPb = '<style>body.pdf-engine-mpdf *{page-break-inside:auto!important;break-inside:auto!important;page-break-after:auto!important;break-after:auto!important;page-break-before:auto!important;break-before:auto!important;}</style>';

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach ($variants as $name => $body) {
    $extra = $name === 'no_pagebreak_css' ? $noPb : '';
    $slice = '<!DOCTYPE html><html><head>' . $head[1] . $extra . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
    file_put_contents(WRITEPATH . 'cache/test_fix_' . $name . '.pdf', $pdf);
    echo $name . PHP_EOL;
}
