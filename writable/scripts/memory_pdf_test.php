<?php
declare(strict_types=1);

ini_set('memory_limit', '128M');
echo 'memory_limit=' . ini_get('memory_limit') . PHP_EOL;

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$registerService = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);

$data = $registerService->prepareReportData(262);
helper('qr');
$html = $registerService->renderReportPdfHtml($data, 'https://example.test/r/262', qr_base64('https://example.test/r/262', 120));

echo 'HTML bytes: ' . strlen($html) . PHP_EOL;
echo 'Token in HTML: ' . (str_contains($html, '__PDF_TOTAL_PAGES__') ? 'yes' : 'no') . PHP_EOL;
echo 'Peak before PDF: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . ' MB' . PHP_EOL;

$pageSize = \App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm($registerService->getLabConfig());
$pdf = (new \App\Libraries\PdfService())->generate($html, 'test.pdf', $pageSize);

echo 'PDF bytes: ' . strlen($pdf) . PHP_EOL;
echo 'Peak after PDF: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . ' MB' . PHP_EOL;
