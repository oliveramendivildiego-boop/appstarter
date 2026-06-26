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

$renderer = new App\Libraries\Pdf\DompdfPdfRenderer();
$ref = new ReflectionClass($renderer);
$extract = $ref->getMethod('extractPaginationSlots');
$extract->setAccessible(true);
$slots = $extract->invoke($renderer, $html);

echo 'total slots: ' . count($slots) . PHP_EOL;
foreach ($slots as $i => $slot) {
    echo '--- slot ' . $i . ' ---' . PHP_EOL;
    echo json_encode($slot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
