<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$svc = new \App\Services\ReportPdfLayoutService();
$layout = $svc->getActiveLayoutForRender();
$bind = $svc->getResultTemplateBindingsForReport();
echo 'pdf template id: ' . ($bind['pdf']['resolved_template_id'] ?? '?') . PHP_EOL;
$sec = $layout['section_layouts']['header'] ?? [];
echo 'header column_align_v: ' . json_encode($sec['column_align_v'] ?? null) . PHP_EOL;

foreach ($layout['instances'] ?? [] as $inst) {
    if (! is_array($inst)) {
        continue;
    }
    $secKey = (string) ($inst['section'] ?? '');
    $type = (string) ($inst['element_type'] ?? $inst['type'] ?? '');
    if ($secKey !== 'header' || ! in_array($type, ['logo', 'lab_company'], true)) {
        continue;
    }
    echo $type
        . ' col=' . ($inst['col'] ?? '?')
        . ' row=' . ($inst['row'] ?? '?')
        . ' align_v=' . json_encode($inst['align_v'] ?? null)
        . ' align_h=' . json_encode($inst['align_h'] ?? null)
        . PHP_EOL;
}
