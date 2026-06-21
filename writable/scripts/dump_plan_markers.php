<?php
declare(strict_types=1);
$registroId = (int) ($argv[1] ?? 253);
$modeArg = $argv[2] ?? 'mode3';
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
    'mode1' => ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
    'mode2' => ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
    'mode4' => ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE,
    default => ReportPaginationMode::AREA_HARD_PAGE_BREAK,
};

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = $mode;
$ctx = $rs->buildReportLayoutContext($data, $layout, $rs->getLabConfig());
$html = $rs->renderReportPdfHtml($data, 'http://test', '', '', $layout);

echo "mode={$mode} pages={$ctx['plan']->totalPages}\n\n";
foreach ($ctx['plan']->placements as $p) {
    if ($p->nodeType !== 'analysis') {
        continue;
    }
    $id = preg_quote($p->nodeId, '/');
    $htmlSub = '';
    $htmlCab = '';
    if (preg_match('/<div\b(?=[^>]*data-layout-block-id="' . $id . '")[^>]*class="([^"]*)"[^>]*>/', $html, $m)
        || preg_match('/<div\b(?=[^>]*class="([^"]*)")[^>]*data-layout-block-id="' . $id . '"[^>]*>/', $html, $m)) {
        $htmlSub = str_contains($m[1], 'report-subgrupo-force-break-before') ? 'YES' : 'no';
    }
    if (preg_match('/data-layout-block-id="' . $id . '".*?report-cabecera-force-break-before/s', $html)) {
        $htmlCab = 'YES';
    } else {
        $htmlCab = 'no';
    }
    echo sprintf(
        "%s page=%d planSub=%s planCab=%s htmlSub=%s htmlCab=%s\n",
        $p->nodeId,
        $p->pageIndex,
        $p->markers->subgrupoClass !== '' ? 'YES' : 'no',
        $p->markers->cabeceraClass !== '' ? 'YES' : 'no',
        $htmlSub,
        $htmlCab,
    );
}
