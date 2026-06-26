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

$registroId = (int) ($argv[1] ?? 308);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$layout = is_array($data['pdf_layout'] ?? null) ? $data['pdf_layout'] : [];

foreach ($layout['instances'] ?? [] as $inst) {
    if (! is_array($inst) || ($inst['section'] ?? '') !== 'footer' || empty($inst['enabled'])) {
        continue;
    }
    echo json_encode([
        'type'  => $inst['element_type'] ?? '',
        'col'   => $inst['column'] ?? 0,
        'span'  => $inst['column_span'] ?? 1,
        'row'   => $inst['grid_row'] ?? 0,
        'stack' => $inst['grid_stack'] ?? 0,
        'align' => $inst['align_h'] ?? '',
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
