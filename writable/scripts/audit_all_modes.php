<?php
declare(strict_types=1);
$id = (int) ($argv[1] ?? 253);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;

$modes = [
    'MODE1' => ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
    'MODE2' => ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
    'MODE3' => ReportPaginationMode::AREA_HARD_PAGE_BREAK,
    'MODE4' => ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE,
];

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$lab = $rs->getLabConfig();

echo "Registro #{$id} — resumen por modo\n";
echo str_repeat('-', 72) . "\n";

foreach ($modes as $label => $mode) {
    $layout['page_style']['pagination_mode'] = $mode;
    $ctx = $rs->buildReportLayoutContext($data, $layout, $lab);
    $plan = $ctx['plan'];
    $breaks = 0;
    $areaBreaks = 0;
    foreach ($plan->placements as $p) {
        if ($p->markers->subgrupoClass !== '') {
            $breaks++;
        }
    }
    foreach ($ctx['tree']->areas as $i => $area) {
        if ($i > 0 && ReportPaginationMode::usesAreaHardPageBreak($mode)) {
            $areaBreaks++;
        }
    }
    printf(
        "%s pages=%d blockBreaks=%d areas=%d interArea=%s sigRelocate=%s\n",
        $label,
        $plan->totalPages,
        $breaks,
        count($ctx['tree']->areas),
        ReportPaginationMode::usesAreaHardPageBreak($mode) ? 'YES' : 'NO',
        match ($mode) {
            ReportPaginationMode::FLOW_NO_LONE_SIGNATURE => 'all pages',
            ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE => 'last page only',
            ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE => 'per area',
            default => 'no',
        }
    );
}

$resolved = ReportPdfLayoutService::resolvePaginationModeFromLayout($layout);
echo "\nPlantilla activa pagination_mode resuelto: {$resolved}\n";
$printLayout = (new ReportPdfLayoutService())->getPrintLayoutForRender();
echo 'Plantilla impresión: ' . ReportPdfLayoutService::resolvePaginationModeFromLayout($printLayout) . "\n";
