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

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, \App\Services\RegisterService::formatNowForReport(), $layout);
$opts = \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(['key' => 'letter']);

function countPages(string $html): int {
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->WriteHTML($html);
    return (int) $mpdf->page;
}

$h = $html;
$h = str_replace(['pdf-dompdf-download', 'pdf-chromium-print'], ['pdf-mpdf-download', 'pdf-mpdf-print'], $h);
echo 'body_class: ' . countPages($h) . PHP_EOL;

$h2 = preg_replace('/<!--\s*pdf-pagination:[A-Za-z0-9+\/=_-]+\s*-->/', '', $h) ?? $h;
echo 'no_pag_comments: ' . countPages($h2) . PHP_EOL;

$h3 = preg_replace('/position\s*:\s*fixed/i', 'position:static', $h2) ?? $h2;
echo 'neutralize_fixed: ' . countPages($h3) . PHP_EOL;

$h4 = str_replace('__PDF_TOTAL_PAGES__', '{PAGENO} de {nbpg}', $h3);
echo 'pagination_tokens: ' . countPages($h4) . PHP_EOL;

$inject = '<style>@page { size: letter portrait; } body.pdf-mpdf-download .pdf-ft-block.footer-grid { position: static !important; }</style>';
$h5 = str_ireplace('</head>', $inject . '</head>', $h4);
echo 'compat_minimal: ' . countPages($h5) . PHP_EOL;

$h6 = str_ireplace('</head>', '<style>@page { size: letter portrait; }</style></head>', $h);
echo 'atpage_only: ' . countPages($h6) . PHP_EOL;

$h7 = str_ireplace('</head>', '<style>body.pdf-mpdf-download .pdf-ft-block.footer-grid { position: static !important; }</style></head>', $h);
echo 'static_footer_only: ' . countPages($h7) . PHP_EOL;

echo 'full_adapted: ' . countPages(\App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, $opts)) . PHP_EOL;
