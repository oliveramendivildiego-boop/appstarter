<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('CI_DEBUG', true);
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$rs = new App\Services\RegisterService();
$data = $rs->prepareReportData(262);
helper(['qr', 'registro']);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

echo 'table row: ' . (str_contains($html, 'pdf-order-sheet-table-row') ? 'yes' : 'no') . PHP_EOL;
echo 'in section table: ' . (preg_match('/pdf-section-table[^>]*>[\s\S]*?pdf-order-sheet-table-row/', $html) ? 'yes' : 'no') . PHP_EOL;
echo 'old band div in html: ' . (preg_match('/<div class="pdf-order-sheet-footer-band/', $html) ? 'yes' : 'no') . PHP_EOL;

if (preg_match('/<table class="pdf-section-table"[^>]*>[\s\S]{0,800}pdf-order-sheet-table-row[\s\S]{0,400}/', $html, $m)) {
    file_put_contents(dirname(__DIR__) . '/debug/order_sheet_row_snippet.html', $m[0]);
    echo 'snippet written' . PHP_EOL;
}
