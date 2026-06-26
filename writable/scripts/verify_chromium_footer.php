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

$registroId = (int) ($argv[1] ?? 305);
$rs = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);
$d = $rs->prepareReportData($registroId);
helper('qr');
$url = 'http://x';
$html = $rs->renderReportPdfHtml($d, $url, qr_base64($url, 120));
$adapted = \App\Libraries\Pdf\HtmlChromiumAdapter::adapt($html);

echo (preg_match('/js-order-sheet-footer-table-row/', $adapted) ? 'order-sheet class: yes' : 'order-sheet class: no') . PHP_EOL;
echo (preg_match('/bottom: calc\(var\(--print-margin-bottom-mm/', $adapted) ? 'footer calc bottom: yes' : 'footer calc bottom: no') . PHP_EOL;
echo (preg_match('/bottom:-[\d.]+mm/', $adapted) ? 'negative bottom remains: yes' : 'negative bottom remains: no') . PHP_EOL;

if (preg_match('/class="[^"]*pdf-ft-block footer-grid[^"]*"[^>]*style="([^"]*)"/i', $adapted, $m)) {
    echo 'footer inline style: ' . $m[1] . PHP_EOL;
}
if (preg_match('/--print-margin-bottom-mm: ([\d.]+)/', $adapted, $m)) {
    echo 'mb var: ' . $m[1] . PHP_EOL;
}
if (preg_match('/--print-footer-reserve-mm: ([\d.]+)/', $adapted, $m)) {
    echo 'footer reserve: ' . $m[1] . PHP_EOL;
}

$out = WRITEPATH . 'cache/chromium_adapted_' . $registroId . '.html';
file_put_contents($out, $adapted);
echo 'saved: ' . $out . PHP_EOL;
