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

use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportLayout\ReportTreeBuilder;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;

$registroId = 283;
$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$mode = ReportPdfLayoutService::resolvePaginationModeFromLayout($layout);
echo "pagination_mode={$mode}\n";

$grupos = $data['grupos'];
echo 'grupos count=' . count($grupos) . "\n";
$i = 0;
foreach ($grupos as $padre => $items) {
    $cultivo = 0;
    $normal = 0;
    foreach ($items as $it) {
        $o = is_array($it) ? (object) $it : $it;
        if (! empty($o->es_cultivo_matriz)) {
            $cultivo++;
        } else {
            $normal++;
        }
    }
    echo "HTML idx {$i}: {$padre} (items=" . count($items) . ", cultivo={$cultivo}, normal={$normal})\n";
    $i++;
}

$tree = ReportTreeBuilder::build(
    $grupos,
    $data['report_pria_tipo_muestra_nombre'] ?? [],
    $data['report_pria_metodo_nombre'] ?? [],
    $data['report_pria_refs_consolidada'] ?? [],
    $data['report_lab_firmas'] ?? [],
    $layout,
);
echo 'tree areas=' . count($tree->areas) . "\n";
foreach ($tree->areas as $a) {
    $sig = $a->signature !== null ? 'yes' : 'no';
    echo "  tree idx {$a->index}: {$a->name} blocks=" . count($a->analysisBlocks) . " sig={$sig}\n";
    foreach ($a->analysisBlocks as $b) {
        $c = $b->isCultivoMatrix ? 'yes' : 'no';
        $rows = $b->tables[0]->rowCount ?? 0;
        echo "    block {$b->blockIndex}: {$b->groupTitle} cultivo={$c} rows={$rows}\n";
    }
}

$layout['page_style']['pagination_mode'] = ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE;
$ctx = $rs->buildReportLayoutContext($data, $layout, $rs->getLabConfig());
echo "\nplan pages={$ctx['plan']->totalPages}\n";
foreach ($ctx['plan']->placements as $p) {
    if ($p->nodeType === 'analysis' || str_contains($p->nodeId, 'signature')) {
        echo "  {$p->nodeId} page={$p->pageIndex} type={$p->nodeType}\n";
    }
}

$html = $rs->renderReportPdfHtml($data, 'http://test', '', '', $layout);
file_put_contents(WRITEPATH . 'debug/inspect_283.html', $html);

// Per-area HTML markers
$idx = 0;
foreach ($grupos as $padre => $items) {
    $areaMeta = $ctx['applier']->beginArea($idx, $idx === 0);
    echo "\nHTML area {$idx} ({$padre}): inter_break=" . ($areaMeta['inter_break'] ? 'YES' : 'no')
        . " area_page_leader=" . ($areaMeta['area_page_leader'] ? 'YES' : 'no') . "\n";
    $idx++;
}

echo "\ninter-page-break count: " . substr_count($html, 'report-grupo-inter-page-break-pdf') . "\n";
echo "new-page-start count: " . substr_count($html, 'report-pdf-grupo-prueba-new-page-start') . "\n";
