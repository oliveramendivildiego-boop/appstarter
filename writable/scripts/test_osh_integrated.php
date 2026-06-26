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

$slot = \App\Libraries\Pdf\ChromiumPdfOrderSheetStamper::extractSlot($html);
echo 'slot patient: ' . ($slot['patient'] ?? 'null') . PHP_EOL;

$pdfPath = WRITEPATH . 'cache/chromium_test_305.pdf';
$pdf = (string) file_get_contents($pdfPath);
$temp = WRITEPATH . 'cache/chromium_pdf';

$slots = \App\Libraries\Pdf\ChromiumPdfPaginationStamper::extractFooterPaginationSlots($html);
$stamped = \App\Libraries\Pdf\ChromiumPdfPaginationPhpStamper::stampBinary($pdf, $slots, $temp, $slot);
file_put_contents(WRITEPATH . 'cache/chromium_test_305_osh_int.pdf', $stamped ?? $pdf);
echo 'stamped: ' . ($stamped !== null ? strlen($stamped) : 'null') . PHP_EOL;
