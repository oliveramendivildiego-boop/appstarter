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

$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$d = $rs->prepareReportData(305);
helper('qr');
$html = $rs->renderReportPdfHtml($d, 'http://x', qr_base64('http://x', 120));

echo 'marker: ' . (str_contains($html, 'pdf-order-sheet-header:') ? 'yes' : 'no') . PHP_EOL;
echo 'attr: ' . (str_contains($html, 'data-order-sheet-from-page-two') ? 'yes' : 'no') . PHP_EOL;
$slot = \App\Libraries\Pdf\ChromiumPdfOrderSheetStamper::extractSlot($html);
echo 'slot: ' . ($slot !== null ? 'ok' : 'null') . PHP_EOL;

$pdf = (string) file_get_contents(WRITEPATH . 'cache/chromium_test_305.pdf');
$temp = WRITEPATH . 'cache/chromium_pdf';
$masked = \App\Libraries\Pdf\ChromiumPdfOrderSheetStamper::stampFromPageTwo($pdf, $html, $temp);
file_put_contents(WRITEPATH . 'cache/chromium_test_305_masked.pdf', $masked);
echo 'masked size: ' . strlen($masked) . PHP_EOL;
