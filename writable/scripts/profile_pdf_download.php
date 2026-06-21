<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$registroId = (int) ($argv[1] ?? 262);
$t0 = microtime(true);
$mark = static function (string $label) use (&$t0): void {
    $now = microtime(true);
    printf("%-28s %6.0f ms (+%6.0f ms)\n", $label, ($now - $GLOBALS['tStart']) * 1000, ($now - $GLOBALS['t0']) * 1000);
    $GLOBALS['t0'] = $now;
};
$GLOBALS['tStart'] = $t0;
$GLOBALS['t0'] = $t0;

$registerService = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);

$data = $registerService->prepareReportData($registroId);
$mark('prepareReportData');

helper('qr');
$reportUrl = 'https://example.test/r/' . $registroId;
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$qrPx = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout);
$qrDataUri = qr_base64($reportUrl, $qrPx);
$mark('qr + layout');

$html = $registerService->renderReportPdfHtml($data, $reportUrl, $qrDataUri, date('d/m/Y H:i:s'));
$mark('renderReportPdfHtml');

echo 'HTML size: ' . round(strlen($html) / 1024, 1) . " KB\n";
echo 'style blocks: ' . substr_count($html, '<style') . "\n";
echo 'layout plan script: ' . (str_contains($html, 'report_layout_plan') ? 'yes' : 'no') . "\n";

$pageSize = \App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm($registerService->getLabConfig());
$pdfService = new \App\Libraries\PdfService();
$pdf = $pdfService->generate($html, 'test.pdf', $pageSize);
$mark('PdfService::generate');

echo 'PDF size: ' . round(strlen($pdf) / 1024, 1) . " KB\n";
echo 'TOTAL: ' . round((microtime(true) - $GLOBALS['tStart']) * 1000) . " ms\n";
