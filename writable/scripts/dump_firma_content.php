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

$registroId = (int) ($argv[1] ?? 308);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

foreach ($data['report_lab_firmas'] ?? [] as $f) {
    if (! is_array($f)) continue;
    echo json_encode([
        'area' => $f['prueba_nombre'] ?? '',
        'validator' => $f['validator_name'] ?? '',
        'approver' => $f['approver_name'] ?? '',
        'sig' => $f['approver_signature'] ?? '',
        'seal' => $f['approver_seal'] ?? '',
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo 'HTML has ATENTAMENTE: ' . (str_contains($html, 'ATENTAMENTE') ? 'yes' : 'no') . PHP_EOL;
echo 'HTML has Verificado: ' . (str_contains($html, 'Verificado') ? 'yes' : 'no') . PHP_EOL;
echo 'HTML lab-firmas-body-grid: ' . substr_count($html, 'lab-firmas-body-grid') . PHP_EOL;

if (preg_match('/lab-firmas-body-grid[\s\S]{0,2000}/', $html, $m)) {
    echo substr(strip_tags($m[0]), 0, 300) . PHP_EOL;
}
