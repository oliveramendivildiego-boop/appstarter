<?php
declare(strict_types=1);
$registroId = (int) ($argv[1] ?? 253);
$factor = (float) ($argv[2] ?? 1.0);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\ReportLayout\ReportLayoutEngine;
use App\Services\ReportLayout\ReportLayoutMetrics;
use App\Services\ReportLayout\ReportNodeMeasurer;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE;
$lab = $rs->getLabConfig();
$start = ReportPdfLayoutService::estimatePdfHeaderBeforeResultsMm($layout);
$base = ReportLayoutMetrics::fromLayoutAndConfig($layout, $lab, $start);
$metrics = new ReportLayoutMetrics(
    pageHeightMm: $base->pageHeightMm,
    pageWidthMm: $base->pageWidthMm,
    marginTopMm: $base->marginTopMm,
    marginBottomMm: $base->marginBottomMm,
    footerReserveMm: $base->footerReserveMm,
    contentStartMm: $base->contentStartMm,
    maxContentHeightMm: $base->maxContentHeightMm,
    rowHeightMm: $base->rowHeightMm,
    theadHeightMm: $base->theadHeightMm,
    segmentTitleHeightMm: $base->segmentTitleHeightMm,
    blockHeaderHeightMm: $base->blockHeaderHeightMm,
    areaSeparatorHeightMm: $base->areaSeparatorHeightMm,
    subgrupoGapMm: $base->subgrupoGapMm,
    signatureHeightMm: $base->signatureHeightMm,
    paperKey: $base->paperKey,
    cabeceraShowTipoMuestra: $base->cabeceraShowTipoMuestra,
    cabeceraShowMetodo: $base->cabeceraShowMetodo,
    cabeceraAreaSeparatorEnabled: $base->cabeceraAreaSeparatorEnabled,
    groupTitleLineHeightMm: $base->groupTitleLineHeightMm,
    groupTitleMarginBottomMm: $base->groupTitleMarginBottomMm,
    cabeceraMetaLineHeightMm: $base->cabeceraMetaLineHeightMm,
    cabeceraMetaMarginBottomMm: $base->cabeceraMetaMarginBottomMm,
    firstReportGroupTitleMarginTopMm: $base->firstReportGroupTitleMarginTopMm,
    grupoPruebaGapMm: $base->grupoPruebaGapMm,
    segmentWrapMarginBottomMm: $base->segmentWrapMarginBottomMm,
    tableRowBorderMm: $base->tableRowBorderMm,
    tableMarginTopMm: $base->tableMarginTopMm,
    tableMarginBottomMm: $base->tableMarginBottomMm,
    analysisBlockHeightFactor: $factor,
);

$tree = App\Services\ReportLayout\ReportTreeBuilder::build(
    $data['grupos'],
    $data['report_pria_tipo_muestra_nombre'] ?? [],
    $data['report_pria_metodo_nombre'] ?? [],
    $data['report_pria_refs_consolidada'] ?? [],
    $data['report_lab_firmas'] ?? [],
    $layout,
);
$plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan($tree, ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE);
$breaks = 0;
foreach ($plan->placements as $p) {
    if ($p->markers->subgrupoClass !== '') {
        $breaks++;
    }
}
echo "factor={$factor} pages={$plan->totalPages} breaks={$breaks}\n";
