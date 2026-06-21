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

use App\Services\ReportPdfLayoutService;
use App\Services\ReportLayout\ReportPaginationMode;

$s = new ReportPdfLayoutService();
$pdf = $s->getActiveLayoutForRender();
$print = $s->getPrintLayoutForRender();
$bindings = $s->getResultTemplateBindingsForReport();

echo "PDF plantilla: {$bindings['pdf']['name']} (id {$bindings['pdf']['resolved_template_id']})\n";
echo "  pagination_mode: " . ReportPdfLayoutService::resolvePaginationModeFromLayout($pdf) . "\n";
echo "  legacy gpb: " . json_encode($pdf['page_style']['grupo_prueba_page_break']['mode'] ?? null) . "\n";

echo "PRINT plantilla: {$bindings['print']['name']} (id {$bindings['print']['resolved_template_id']})\n";
echo "  pagination_mode: " . ReportPdfLayoutService::resolvePaginationModeFromLayout($print) . "\n";
echo "  legacy gpb: " . json_encode($print['page_style']['grupo_prueba_page_break']['mode'] ?? null) . "\n";
