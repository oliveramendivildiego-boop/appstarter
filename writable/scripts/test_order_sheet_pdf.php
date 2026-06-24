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

$id = (int) ($argv[1] ?? 262);
$rs = new App\Services\RegisterService();
$data = $rs->prepareReportData($id);
helper(['qr', 'registro']);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

echo 'marker: ' . (str_contains($html, 'pdf-order-sheet-header:') ? 'yes' : 'no') . PHP_EOL;
echo 'table row: ' . (str_contains($html, 'pdf-order-sheet-table-row') ? 'yes' : 'no') . PHP_EOL;

$pdf = (new App\Libraries\PdfService())->generate($html);
$out = dirname(__DIR__) . '/debug/test_order_sheet_' . $id . '.pdf';
file_put_contents($out, $pdf);
echo 'written: ' . $out . ' (' . strlen($pdf) . ' bytes)' . PHP_EOL;
