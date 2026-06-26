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

$variants = [
    'raw' => $html,
    'adapted' => \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, $opts),
    'no_style' => preg_replace('/<style[\s\S]*?<\/style>/i', '', \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, $opts)) ?? '',
];

foreach ($variants as $label => $variantHtml) {
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->WriteHTML($variantHtml);
    $pages = (int) $mpdf->page;
    echo "{$label}: pages={$pages}\n";
}
