<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$cm = model(\App\Models\AppConfigModel::class);
echo 'pdf_result_template_id=' . $cm->getValue('pdf_result_template_id') . PHP_EOL;
echo 'print_result_template_id=' . $cm->getValue('print_result_template_id') . PHP_EOL;

$svc = new \App\Services\ReportPdfLayoutService();
$bind = $svc->getResultTemplateBindingsForReport();
echo 'pdf resolved id=' . ($bind['pdf']['resolved_template_id'] ?? '?') . ' name=' . ($bind['pdf']['name'] ?? '?') . PHP_EOL;

$id = (int) ($bind['pdf']['resolved_template_id'] ?? 0);
if ($id > 0) {
    $row = model(\App\Models\ReportPdfTemplateModel::class)->find($id);
    echo 'template row id=' . ($row->id ?? '?') . ' name=' . ($row->name ?? '?') . PHP_EOL;
}
