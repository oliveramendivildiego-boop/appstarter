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

helper('registro');
$lab = (new \App\Services\ConfigService())->getAllAsArray();
$rel = trim((string) ($lab['logo'] ?? 'images/logo-john.png'));
$fileSrc = report_image_dompdf_src($rel);
$dataSrc = report_image_data_uri($rel);

$cases = [
    'file_path' => $fileSrc,
    'data_uri'  => $dataSrc,
];

foreach ($cases as $name => $src) {
    $html = '<!DOCTYPE html><html><body class="pdf-engine-mpdf pdf-hg-block header header-grid">'
        . '<div class="header-piece header-piece-logo">'
        . '<img src="' . str_replace('"', '&quot;', $src) . '" alt="Logo" style="max-height:70px;max-width:240px;width:238px;height:70px;display:block;">'
        . '</div></body></html>';
    $adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
    $pdf = (new \App\Libraries\PdfService())->generate($adapted, 'test.pdf', null);
    $out = WRITEPATH . 'cache/logo_only_' . $name . '.pdf';
    file_put_contents($out, $pdf);
    echo $name . ' -> ' . $out . ' (' . strlen($pdf) . " bytes)\n";
}
