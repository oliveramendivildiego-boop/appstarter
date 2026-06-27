<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$sl = is_array($layout['section_layouts']['header'] ?? null) ? $layout['section_layouts']['header'] : [];
echo 'header columns=' . ($sl['columns'] ?? '?') . ' rows=' . ($sl['rows'] ?? '?') . PHP_EOL;
foreach ($layout['instances'] ?? [] as $it) {
    if (! is_array($it) || ($it['section'] ?? '') !== 'header' || empty($it['enabled'])) {
        continue;
    }
    echo sprintf(
        "  %s col=%d span=%d row=%s stack=%s uid=%s\n",
        (string) ($it['element_type'] ?? '?'),
        (int) ($it['column'] ?? 0),
        (int) ($it['column_span'] ?? 1),
        isset($it['grid_row']) ? (string) $it['grid_row'] : '-',
        isset($it['grid_stack']) ? (string) $it['grid_stack'] : '-',
        substr((string) ($it['uid'] ?? ''), 0, 8),
    );
}

[$n, $items] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'header');
echo PHP_EOL . "gridItemsForSection: n=$n count=" . count($items) . PHP_EOL;
foreach ($items as $it) {
    echo sprintf(
        "  %s col=%d span=%d row=%s\n",
        (string) ($it['element_type'] ?? '?'),
        (int) ($it['column'] ?? 0),
        (int) ($it['column_span'] ?? 1),
        isset($it['grid_row']) ? (string) $it['grid_row'] : '-',
    );
}
