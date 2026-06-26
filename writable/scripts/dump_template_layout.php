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
if ($t === null) {
    fwrite(STDERR, "Template {$id} not found\n");
    exit(1);
}
$layout = json_decode((string) ($t->layout_json ?? '{}'), true);
if (! is_array($layout)) {
    $layout = [];
}
$hg = is_array($layout['page_style']['header_grid'] ?? null) ? $layout['page_style']['header_grid'] : [];
echo 'header_grid: ' . json_encode($hg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
foreach ($layout['instances'] ?? [] as $inst) {
    if (! is_array($inst)) {
        continue;
    }
    if ((string) ($inst['section'] ?? '') !== 'header') {
        continue;
    }
    if (! in_array((string) ($inst['element_type'] ?? ''), ['logo', 'lab_company'], true)) {
        continue;
    }
    echo (string) $inst['element_type'] . ': ' . json_encode($inst, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
