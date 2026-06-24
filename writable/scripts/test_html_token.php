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
helper('qr');
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));
echo 'token_in_html: ' . (str_contains($html, '__PDF_TOTAL_PAGES__') ? 'yes' : 'no') . PHP_EOL;
echo 'pagination_comments: ' . preg_match_all('/pdf-pagination:/', $html) . PHP_EOL;
