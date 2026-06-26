<?php
declare(strict_types=1);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$db = \Config\Database::connect();
$row = $db->query('SELECT id, name, layout_json FROM dom_report_pdf_templates WHERE id = 4')->getRowArray();
if (! $row) {
    echo "template 4 not found\n";
    exit(1);
}
$layout = json_decode((string) $row['layout_json'], true);
echo 'name: ' . $row['name'] . PHP_EOL;
echo 'blocks: ' . json_encode($layout['blocks'] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo 'section_layouts keys: ' . implode(', ', array_keys($layout['section_layouts'] ?? [])) . PHP_EOL;
$instSections = [];
foreach ($layout['instances'] ?? [] as $inst) {
    if (! is_array($inst)) {
        continue;
    }
    $sec = (string) ($inst['section'] ?? '');
    $instSections[$sec] = ($instSections[$sec] ?? 0) + 1;
}
echo 'instances by section: ' . json_encode($instSections, JSON_UNESCAPED_UNICODE) . PHP_EOL;
$rs = \App\Services\ReportPdfLayoutService::normalizeResultsTableStyle($layout['page_style']['results_table'] ?? []);
echo 'card_header: ' . json_encode(\App\Services\ReportPdfLayoutService::normalizeCardHeaderStyle($layout['page_style']['card_header'] ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo 'results_table: ' . json_encode($rs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
