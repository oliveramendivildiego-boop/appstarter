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

use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;

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
$plan = $ctx['plan'];

echo strtoupper($modeArg) . " — registro {$registroId}\n";
$breakCount = 0;
foreach ($plan->placements as $p) {
    if ($p->markers->subgrupoClass !== '') {
        $breakCount++;
    }
    $flags = array_filter([
        $p->markers->subgrupoClass ?: null,
        $p->markers->cabeceraClass ?: null,
        $p->markers->pageBreakBeforeBlock ? 'pageBreakBefore' : null,
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
}
echo "\ntotalPages={$plan->totalPages} forceBreaks={$breakCount}\n";
