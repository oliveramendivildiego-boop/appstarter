<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$db = new mysqli('localhost', 'root', '', 'laboratorio');
if ($db->connect_error) {
    fwrite(STDERR, 'DB fail: ' . $db->connect_error . PHP_EOL);
    exit(1);
}

$r = $db->query('SELECT id, name, layout_json FROM dom_report_pdf_templates WHERE id=4');
if (!$r || !($row = $r->fetch_assoc())) {
    fwrite(STDERR, "not found\n");
    exit(1);
}

echo 'name: ' . $row['name'] . PHP_EOL;
$layout = json_decode((string) $row['layout_json'], true);
$sl = $layout['section_layouts']['footer'] ?? [];
echo 'footer cols: ' . ($sl['columns'] ?? '?') . ' rows: ' . ($sl['rows'] ?? '?') . PHP_EOL;
foreach ($layout['instances'] ?? [] as $i) {
    if (!is_array($i)) {
        continue;
    }
    if (($i['section'] ?? '') !== 'footer') {
        continue;
    }
    if (($i['enabled'] ?? true) === false) {
        continue;
    }
    if ((int) ($i['column'] ?? -1) < 0) {
        continue;
    }
    echo 'inst: ' . ($i['element_type'] ?? '')
        . ' col=' . ($i['column'] ?? '')
        . ' span=' . ($i['column_span'] ?? 1)
        . ' row=' . ($i['grid_row'] ?? '')
        . ' align_h=' . ($i['align_h'] ?? '')
        . PHP_EOL;
}
