<?php
declare(strict_types=1);
putenv('CI_ENVIRONMENT=development');
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 4);
$m = model(\App\Models\ReportPdfTemplateModel::class);
$t = $m->find($id);
if (!$t) {
    fwrite(STDERR, "Template $id not found\n");
    exit(1);
}
$t = (array) $t;
echo 'name: ' . ($t['name'] ?? '') . PHP_EOL;
echo 'is_active: ' . ($t['is_active'] ?? '') . PHP_EOL;
$layout = json_decode($t['layout_json'] ?? '{}', true);
echo 'footer section_layout: ' . json_encode($layout['section_layouts']['footer'] ?? null, JSON_PRETTY_PRINT) . PHP_EOL;
echo 'footer_grid: ' . json_encode($layout['page_style']['footer_grid'] ?? null, JSON_PRETTY_PRINT) . PHP_EOL;
$footerInst = array_values(array_filter($layout['instances'] ?? [], fn($i) => ($i['section'] ?? '') === 'footer'));
echo 'footer instances: ' . json_encode($footerInst, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo 'margins: ' . json_encode($layout['margins_mm'] ?? null) . PHP_EOL;
echo 'footer reserve mm: ' . \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($layout) . PHP_EOL;
$hg = $layout['page_style']['header_grid'] ?? [];
foreach (['show_label_lab_address','show_label_lab_phone','show_label_lab_email','label_lab_address','label_lab_phone','label_lab_email'] as $k) {
    if (array_key_exists($k, $hg)) echo "hg.$k: " . json_encode($hg[$k], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo 'order_sheet enabled: ' . (\App\Services\ReportPdfLayoutService::isOrderSheetHeaderEnabledForLayout($layout) ? 'yes' : 'no') . PHP_EOL;
