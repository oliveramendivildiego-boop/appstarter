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
$layout = json_decode((string) ($m->find($id)->layout_json ?? '{}'), true);
$pd = $layout['page_style']['patient_doctor_grid'] ?? [];
echo 'section_layout: ' . json_encode($layout['section_layouts']['patient_doctor'] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo 'pd_grid base font_size: ' . ($pd['font_size_pt'] ?? '?') . PHP_EOL;
[$n, $items] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'patient_doctor');
echo "n={$n} items=" . count($items) . PHP_EOL;
foreach ($items as $it) {
    echo ($it['element_type'] ?? '?')
        . ' col=' . ($it['column'] ?? '?')
        . ' span=' . ($it['column_span'] ?? 1)
        . ' row=' . ($it['grid_row'] ?? '-')
        . ' align_h=' . ($it['align_h'] ?? '-')
        . ' align_v=' . ($it['align_v'] ?? '-')
        . ' fs=' . ($it['text_style']['font_size_pt'] ?? '?')
        . PHP_EOL;
}
