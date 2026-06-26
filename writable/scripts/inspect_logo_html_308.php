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

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
if ($data === null) {
    echo "no data for {$id}\n";
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

$lab = $rs->getLabConfig();
$logoRel = (string) ($lab['logo'] ?? '');
echo 'logo rel: ' . $logoRel . PHP_EOL;
echo 'dompdf src: ' . report_image_dompdf_src($logoRel !== '' ? $logoRel : 'images/logo-john.png') . PHP_EOL;
echo 'data uri len: ' . strlen(report_image_data_uri($logoRel !== '' ? $logoRel : 'images/logo-john.png')) . PHP_EOL;

if (preg_match('/header-piece-logo[\s\S]{0,1200}/', $html, $m)) {
    echo "--- logo block ---\n" . $m[0] . "\n";
} else {
    echo "NO header-piece-logo in HTML\n";
}

if (preg_match('/<img[^>]+alt="Logo"[^>]*>/', $html, $m2)) {
    echo "--- img tag ---\n" . $m2[0] . "\n";
    $src = '';
    if (preg_match('/src="([^"]*)"/', $m2[0], $sm)) {
        $src = $sm[1];
    }
    echo 'src prefix: ' . substr($src, 0, 80) . PHP_EOL;
    echo 'is data uri: ' . (str_starts_with($src, 'data:') ? 'yes' : 'no') . PHP_EOL;
    echo 'is file: ' . (is_file($src) ? 'yes' : 'no') . PHP_EOL;
} else {
    echo "NO img alt=Logo\n";
}
