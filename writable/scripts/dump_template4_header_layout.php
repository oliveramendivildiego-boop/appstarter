<?php
declare(strict_types=1);
putenv('CI_ENVIRONMENT=development');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$model = new \App\Models\ReportPdfTemplateModel();
$t = $model->find(4);
$t = is_object($t) ? (array) $t : $t;
$raw = $t['layout_json'] ?? $t['layout'] ?? null;
$layout = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
$sec = $layout['section_layouts']['header'] ?? [];
echo json_encode($sec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo "\ninstances:\n";
foreach ($layout['instances'] ?? [] as $inst) {
    if (($inst['section'] ?? '') !== 'header') continue;
    echo json_encode([
        'type' => $inst['element_type'] ?? '',
        'col' => $inst['column'] ?? '',
        'span' => $inst['column_span'] ?? 1,
        'align_h' => $inst['align_h'] ?? null,
        'align_v' => $inst['align_v'] ?? null,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
