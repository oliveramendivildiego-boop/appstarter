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
use App\Services\ReportLayout\ReportLayoutPlanService;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(264);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_NO_LONE_SIGNATURE;

$ctx = (new ReportLayoutPlanService())->buildContext(
    $data['grupos'] ?? [],
    $data['report_pria_tipo_muestra_nombre'] ?? [],
    $data['report_pria_metodo_nombre'] ?? [],
    $data['report_pria_refs_consolidada'] ?? [],
    $data['report_lab_firmas'] ?? [],
    $layout,
    $data['lab_config'] ?? [],
);
$plan = $ctx['plan'];
$tree = $ctx['tree'];

echo 'mode=' . $plan->mode . ' totalPages=' . $plan->totalPages . PHP_EOL;
echo 'areas=' . count($tree->areas) . PHP_EOL;
foreach ($tree->areas as $a) {
    echo '  area ' . $a->index . ' ' . $a->name . ' blocks=' . count($a->analysisBlocks) . ' sig=' . ($a->signature !== null ? 'yes' : 'no') . PHP_EOL;
}
echo 'globalSig=' . ($tree->globalSignature !== null ? 'yes' : 'no') . PHP_EOL;
foreach ($plan->placements as $p) {
    $splits = array_map(static fn ($s) => json_encode([
        'sec' => $s->sectionIndex,
        'start' => $s->startRow,
        'rows' => $s->rowCount,
        'thead' => $s->repeatThead,
    ]), $p->tableSplits);
    $tailBreak = $p->markers->signatureTailGroup ? ' tailGroup=1' : '';
    echo $p->nodeId . ' page=' . ($p->pageIndex + 1) . ' y=' . $p->yStartMm . ' h=' . $p->heightMm
        . ( $splits !== [] ? ' splits=[' . implode(',', $splits) . ']' : '')
        . $tailBreak . PHP_EOL;
}
