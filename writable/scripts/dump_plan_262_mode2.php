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

use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;
use App\Services\ReportLayout\ReportLayoutPlanService;
use App\Services\ReportLayout\ReportPaginationMode;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(262);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_NO_LONE_SIGNATURE;
$plan = (new ReportLayoutPlanService())->buildPlan(
    $data['grupos'],
    $data['report_pria_tipo_muestra_nombre'] ?? [],
    $data['report_pria_metodo_nombre'] ?? [],
    $data['report_pria_refs_consolidada'] ?? [],
    $data['report_lab_firmas'] ?? [],
    $layout,
    $rs->getLabConfig(),
);
echo 'mode=' . $plan->mode . ' totalPages=' . $plan->totalPages . PHP_EOL;
foreach ($plan->placements as $p) {
    $extra = $p->tableSplits !== [] ? ' splits=' . json_encode(array_map(fn ($s) => [
        'sec' => $s->sectionIndex,
        'start' => $s->startRow,
        'rows' => $s->rowCount,
        'thead' => $s->repeatThead,
        'page' => $s->pageIndex,
    ], $p->tableSplits)) : '';
    $tailBreak = $p->markers->signatureTailGroup ? ' tailGroup=1' : '';
    echo $p->nodeId . ' page=' . ($p->pageIndex + 1) . ' y=' . $p->yStartMm . ' h=' . $p->heightMm . $extra . $tailBreak . PHP_EOL;
}
