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
helper('qr');
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData(308);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));
echo 'pdf-ft-pagination-num: ' . substr_count($html, 'pdf-ft-pagination-num') . PHP_EOL;
echo 'pdf-pagination comments: ' . substr_count($html, 'pdf-pagination:') . PHP_EOL;
if (preg_match('/pdf-ft-pagination-num[^>]+data-total="([^"]+)"/', $html, $m)) {
    echo 'data-total sample: ' . $m[1] . PHP_EOL;
}
