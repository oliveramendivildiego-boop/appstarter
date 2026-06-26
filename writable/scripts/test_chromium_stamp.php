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

$rs = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);
$d = $rs->prepareReportData(305);
helper('qr');
$sourceHtml = $rs->renderReportPdfHtml($d, 'http://x', qr_base64('http://x', 120));
$slots = \App\Libraries\Pdf\ChromiumPdfPaginationStamper::extractFooterPaginationSlots($sourceHtml);
echo 'footer slots: ' . count($slots) . PHP_EOL;
if ($slots !== []) {
    echo json_encode($slots[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

$pdf = file_get_contents(WRITEPATH . 'cache/chromium_test_305.pdf');
$stamped = \App\Libraries\Pdf\ChromiumPdfPaginationStamper::stamp(
    is_string($pdf) ? $pdf : '',
    $sourceHtml,
    WRITEPATH . 'cache/chromium_pdf'
);
file_put_contents(WRITEPATH . 'cache/chromium_stamped_305.pdf', $stamped);
echo 'stamped size: ' . strlen($stamped) . PHP_EOL;
