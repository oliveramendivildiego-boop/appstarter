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
$html = $rs->renderReportPdfHtml($d, 'http://x', qr_base64('http://x', 120));

if (! preg_match('/<!--\s*pdf-pagination:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $m)) {
    echo "no slot\n";
    exit(1);
}

$slot = json_decode(base64_decode($m[1], true), true);
echo json_encode($slot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
