<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$model = model(\App\Models\ReportPdfTemplateModel::class);
$config = model(\App\Models\AppConfigModel::class);
$tid = (int) $config->getValue('pdf_result_template_id');
echo 'active template id=' . $tid . PHP_EOL;
$row = $model->find($tid);
if (! $row) {
    echo "template not found\n";
    exit(1);
}
$layout = json_decode((string) ($row->layout_json ?? ''), true);
if (! is_array($layout)) {
    echo "invalid layout json\n";
    exit(1);
}
echo 'section header columns=' . ($layout['section_layouts']['header']['columns'] ?? '?') . PHP_EOL;
$headerInst = array_filter($layout['instances'] ?? [], static fn ($i) => is_array($i) && ($i['section'] ?? '') === 'header');
echo 'header instances=' . count($headerInst) . PHP_EOL;
foreach ($headerInst as $it) {
    echo sprintf(
        "  %s enabled=%s col=%s span=%s row=%s\n",
        (string) ($it['element_type'] ?? '?'),
        ! empty($it['enabled']) ? 'yes' : 'NO',
        (string) ($it['column'] ?? '-'),
        (string) ($it['column_span'] ?? '-'),
        isset($it['grid_row']) ? (string) $it['grid_row'] : '-',
    );
}
$headerBlock = null;
foreach ($layout['blocks'] ?? [] as $b) {
    if (is_array($b) && ($b['id'] ?? '') === 'header') {
        $headerBlock = $b;
    }
}
echo 'header block enabled=' . (! empty($headerBlock['enabled']) ? 'yes' : 'NO') . PHP_EOL;
