<?php
declare(strict_types=1);
$registroId = (int) ($argv[1] ?? 253);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;

$registerService = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $registerService->prepareReportData($registroId);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE;
$labConfig = $registerService->getLabConfig();
$ctx = $registerService->buildReportLayoutContext($data, $layout, $labConfig);
$plan = $ctx['plan'];
$metrics = $plan->metrics;

echo "MODE 1 — registro {$registroId}\n";
echo sprintf("maxContent=%.1fmm contentStart=%.1fmm rowH=%.2fmm sigH=%.1fmm\n\n",
    $metrics->maxContentHeightMm, $metrics->contentStartMm, $metrics->rowHeightMm, $metrics->signatureHeightMm);

foreach ($plan->placements as $p) {
    $m = $p->markers;
    $flags = array_filter([
        $m->subgrupoClass ?: null,
        $m->cabeceraClass ?: null,
        implode('|', array_filter($m->segmentClasses)) ?: null,
        $m->pageBreakBeforeBlock ? 'pageBreakBefore' : null,
    ]);
    echo sprintf(
        "p%d y=%.1f h=%.1f %s %s %s\n",
        $p->pageIndex + 1,
        $p->yStartMm,
        $p->heightMm,
        $p->nodeType,
        $p->nodeId,
        $flags ? implode(', ', $flags) : '(sin marcadores)'
    );
    if ($p->tableSplits !== []) {
        foreach ($p->tableSplits as $s) {
            echo sprintf("  split sec=%d rows=%d..+%d page=%d\n", $s->sectionIndex, $s->startRow, $s->rowCount, $s->pageIndex + 1);
        }
    }
}
echo "\ntotalPages=" . $plan->totalPages . "\n";
