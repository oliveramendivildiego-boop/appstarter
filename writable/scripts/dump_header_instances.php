<?php
declare(strict_types=1);
$id = (int) ($argv[1] ?? 4);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$m = model(\App\Models\ReportPdfTemplateModel::class);
$t = $m->find($id);
$layout = json_decode((string) ($t->layout_json ?? '{}'), true);
foreach ($layout['instances'] ?? [] as $inst) {
    if (! is_array($inst) || ($inst['section'] ?? '') !== 'header') continue;
    echo (($inst['enabled'] ?? false) ? '[ON] ' : '[OFF] ')
        . ($inst['element_type'] ?? '?')
        . ' col=' . ($inst['column'] ?? '?')
        . ' span=' . ($inst['column_span'] ?? 1)
        . ' row=' . ($inst['grid_row'] ?? '-')
        . ' stack=' . ($inst['grid_stack'] ?? '-')
        . ' align_h=' . ($inst['align_h'] ?? '-')
        . ' align_v=' . ($inst['align_v'] ?? '-')
        . PHP_EOL;
}
