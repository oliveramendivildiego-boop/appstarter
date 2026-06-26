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
$sec = $layout['section_layouts']['header'] ?? [];
echo 'section_layout header: ' . json_encode($sec, JSON_UNESCAPED_UNICODE) . PHP_EOL;
[$n, $items] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'header');
echo "gridItemsForSection n={$n} count=" . count($items) . PHP_EOL;
foreach ($items as $it) {
    echo json_encode($it, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
$html = '';
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(308);
helper('qr');
$layoutActive = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 100), date('d/m/Y'), $layoutActive);
if (preg_match('/pdf-hg-block.*?<table[^>]*>(.*?)<\/table>/s', $html, $m)) {
    $rows = substr_count($m[1], '<tr ');
    $cols = preg_match_all('/colspan="(\d+)"/', $m[1], $cm) ? array_sum(array_map('intval', $cm[1])) : 0;
    echo "rendered header rows={$rows} colspan sum first row=" . ($cm[1][0] ?? '?') . '+' . ($cm[1][1] ?? '?') . PHP_EOL;
    echo substr(strip_tags(str_replace(['</td>', '</tr>'], [" | ", "\n"], $m[1]), '<td><tr>'), 0, 500) . PHP_EOL;
}
