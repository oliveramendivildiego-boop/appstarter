<?php
declare(strict_types=1);
$registroId = (int) ($argv[1] ?? 253);
$modeArg = $argv[2] ?? 'mode1';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\ReportLayout\ReportNodeMeasurer;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;

$mode = match ($modeArg) {
    'mode2' => ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
    default => ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
};

$registerService = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $registerService->prepareReportData($registroId);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = $mode;
$labConfig = $registerService->getLabConfig();
$ctx = $registerService->buildReportLayoutContext($data, $layout, $labConfig);
$tree = $ctx['tree'];
$plan = $ctx['plan'];
$measurer = new ReportNodeMeasurer($plan->metrics);

echo "Mode={$mode} registro={$registroId}\n\n";
foreach ($tree->areas as $area) {
    echo "AREA {$area->index}: {$area->name}\n";
    foreach ($area->analysisBlocks as $block) {
        $h = $measurer->measureAnalysisBlock($block);
        $header = $measurer->measureBlockHeader($block);
        $tables = [];
        foreach ($block->tables as $t) {
            $tables[] = sprintf(
                'sec%d rows=%d segTitle=%s thead=%s matrix=%s h=%.1f',
                $t->sectionIndex,
                $t->rowCount,
                $t->hasSegmentTitle ? 'Y' : 'N',
                $t->includeThead ? 'Y' : 'N',
                $t->isMatrix ? 'Y' : 'N',
                $measurer->measureTableSection($t),
            );
        }
        $placement = $plan->findPlacement($block->id);
        echo sprintf(
            "  %s title=%s sub=%s h=%.1f (hdr=%.1f) p%d markers=%s\n    %s\n",
            $block->id,
            $block->groupTitle,
            $block->isSubPrueba ? 'Y' : 'N',
            $h,
            $header,
            ($placement?->pageIndex ?? -1) + 1,
            $placement?->markers->subgrupoClass ?: '-',
            implode("\n    ", $tables),
        );
    }
    echo "\n";
}
