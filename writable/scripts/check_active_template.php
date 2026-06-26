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

$s = new \App\Services\ReportPdfLayoutService();
$active = $s->getActiveLayoutForRender();
echo 'template_id: ' . ($active['template_id'] ?? '?') . PHP_EOL;
echo 'template_name: ' . ($active['template_name'] ?? '?') . PHP_EOL;

$m = model(\App\Models\ReportPdfTemplateModel::class);
$t4 = $m->find(4);
echo 'template 4 active flag: ' . ($t4->is_active ?? '?') . PHP_EOL;

$hg = \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle($active['page_style']['header_grid'] ?? []);
foreach (['lab_address', 'lab_phone', 'lab_email', 'pdf_pagination'] as $k) {
    echo "label_{$k}: " . ($hg["label_{$k}"] ?? '') . PHP_EOL;
    echo "  color: " . ($hg["label_{$k}_text_color"] ?? 'default') . PHP_EOL;
    echo "  size: " . ($hg["label_{$k}_font_size_pt"] ?? 'default') . PHP_EOL;
}
