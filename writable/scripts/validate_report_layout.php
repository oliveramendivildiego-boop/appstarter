<?php
declare(strict_types=1);
/**
 * Validación automatizada LayoutEngine (plan, HTML, PDF, PRINT) en los 4 modos.
 * Uso: php writable/scripts/validate_report_layout.php [registro_id] [--pdf]
 */
$registroId = (int) ($argv[1] ?? 253);
$generatePdf = in_array('--pdf', $argv, true);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Libraries\PdfService;
use App\Services\ReportLayout\LayoutPlacement;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;

$modeLabels = [
    ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE => 'MODE1',
    ReportPaginationMode::FLOW_NO_LONE_SIGNATURE => 'MODE2',
    ReportPaginationMode::AREA_HARD_PAGE_BREAK => 'MODE3',
    ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE => 'MODE4',
];

$legacyMarkers = [
    'ReportPdfDompdfGrupoPageBreakService',
    'report_pdf_grupo_page_break_script',
    'pdf-gpb-grupo-intact',
    'pdf-gpb-keep-together',
];

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro {$registroId} no encontrado.\n");
    exit(1);
}

$baseLayout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$lab = $rs->getLabConfig();
$pageSize = ReportPdfLayoutService::resolveGlobalPageSizeMm($lab);
$reportUrl = 'http://localhost/registers/viewreport/' . $registroId;
$qr = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

$failures = [];
$warnings = [];
$passed = 0;

function fail(array &$failures, string $msg): void
{
    $failures[] = $msg;
}

function warn(array &$warnings, string $msg): void
{
    $warnings[] = $msg;
}

function countKeepIntactInDom(string $html): int
{
    return preg_match_all(
        '/<div\b[^>]*data-layout-block-id="[^"]+"[^>]*class="[^"]*report-subgrupo-keep-intact/',
        $html,
    ) + preg_match_all(
        '/<div\b[^>]*class="[^"]*report-subgrupo-keep-intact[^"]*"[^>]*data-layout-block-id="/',
        $html,
    );
}

function readBlockClass(string $html, string $nodeId): ?string
{
    $id = preg_quote($nodeId, '/');
    if (preg_match('/<div\b(?=[^>]*data-layout-block-id="' . $id . '")[^>]*class="([^"]*)"[^>]*>/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/<div\b(?=[^>]*class="([^"]*)")[^>]*data-layout-block-id="' . $id . '"[^>]*>/', $html, $m)) {
        return $m[1];
    }

    return null;
}

function auditPlanVsHtml(
    \App\Services\ReportLayout\LayoutPlan $plan,
    string $html,
    string $channel,
    string $label,
    array &$failures,
    int &$passed,
): void {
    foreach ($plan->placements as $p) {
        if ($p->nodeType !== LayoutPlacement::TYPE_ANALYSIS) {
            continue;
        }
        if (! str_contains($html, 'data-layout-block-id="' . $p->nodeId . '"')) {
            fail($failures, "{$label} {$channel}: falta bloque {$p->nodeId} en HTML");
            continue;
        }
        $classes = readBlockClass($html, $p->nodeId);
        if ($classes === null) {
            fail($failures, "{$label} {$channel}: no se leyó class de {$p->nodeId}");
            continue;
        }
        if ($p->markers->subgrupoClass !== '' && ! str_contains($classes, $p->markers->subgrupoClass)) {
            fail($failures, "{$label} {$channel}: {$p->nodeId} falta {$p->markers->subgrupoClass}");
        }
        if ($p->markers->subgrupoClass === '' && str_contains($classes, 'report-subgrupo-force-break-before')) {
            fail($failures, "{$label} {$channel}: {$p->nodeId} force-break en HTML sin plan");
        }
        if ($p->markers->cabeceraClass !== ''
            && ! preg_match('/data-layout-block-id="' . preg_quote($p->nodeId, '/') . '".*report-cabecera-force-break-before/s', $html)) {
            fail($failures, "{$label} {$channel}: {$p->nodeId} falta cabecera-force-break");
        }
        if ($p->markers->cabeceraClass === ''
            && ReportPaginationMode::usesFlowContinuousPagination($plan->mode)
            && preg_match('/data-layout-block-id="' . preg_quote($p->nodeId, '/') . '".*report-cabecera-force-break-before/s', $html)) {
            fail($failures, "{$label} {$channel}: {$p->nodeId} cabecera-force-break indebido (modo flujo)");
        }
        $passed++;
    }
}

function validatePlanInvariants(
    string $label,
    string $mode,
    \App\Services\ReportLayout\LayoutPlan $plan,
    \App\Services\ReportLayout\ReportTree $tree,
    array &$failures,
    array &$warnings,
): void {
    if ($plan->mode !== $mode) {
        fail($failures, "{$label}: plan.mode={$plan->mode} != {$mode}");
    }
    if ($plan->totalPages < 1) {
        fail($failures, "{$label}: totalPages inválido");
    }

    $flow = ReportPaginationMode::usesFlowContinuousPagination($mode);
    $area = ReportPaginationMode::usesAreaHardPageBreak($mode);

    foreach ($plan->placements as $p) {
        if ($p->nodeType !== LayoutPlacement::TYPE_ANALYSIS) {
            continue;
        }
        if ($flow && $p->markers->cabeceraClass !== '') {
            fail($failures, "{$label}: {$p->nodeId} no debe tener cabecera-force-break en modo flujo");
        }
        if ($area && $p->markers->subgrupoClass !== '' && $p->markers->cabeceraClass === '') {
            fail($failures, "{$label}: {$p->nodeId} subgrupo-force-break sin cabecera-force-break (modo área)");
        }
    }

    if ($area && count($tree->areas) > 1) {
        $firstBlockArea1 = $plan->findPlacement('area-1-block-0');
        if ($firstBlockArea1 !== null && $firstBlockArea1->pageIndex < 1) {
            fail($failures, "{$label}: area-1-block-0 debe empezar en página >= 1 (inter-área)");
        }
    }

    $sigPlacements = array_filter(
        $plan->placements,
        static fn ($p) => $p->nodeType === LayoutPlacement::TYPE_SIGNATURE,
    );
    if ($sigPlacements === [] && ($tree->globalSignature !== null || array_filter(
        $tree->areas,
        static fn ($a) => $a->signature !== null,
    ) !== [])) {
        warn($warnings, "{$label}: hay firmas en árbol pero ningún placement signature");
    }
}

echo "Validación LayoutEngine — registro #{$registroId}\n";
echo str_repeat('=', 72) . "\n\n";

foreach (ReportPaginationMode::ALL as $mode) {
    $label = $modeLabels[$mode] ?? $mode;
    echo "{$label} ({$mode})\n";
    echo str_repeat('-', 72) . "\n";

    $layout = $baseLayout;
    if (! isset($layout['page_style']) || ! is_array($layout['page_style'])) {
        $layout['page_style'] = ReportPdfLayoutService::defaultPageStyleStatic();
    }
    $layout['page_style']['pagination_mode'] = $mode;

    $ctx = $rs->buildReportLayoutContext($data, $layout, $lab);
    $plan = $ctx['plan'];
    $tree = $ctx['tree'];

    $blockBreaks = 0;
    foreach ($plan->placements as $p) {
        if ($p->markers->subgrupoClass !== '') {
            $blockBreaks++;
        }
    }

    echo sprintf(
        "  Plan: pages=%d areas=%d blockBreaks=%d placements=%d\n",
        $plan->totalPages,
        count($tree->areas),
        $blockBreaks,
        count($plan->placements),
    );

    validatePlanInvariants($label, $mode, $plan, $tree, $failures, $warnings);

    $bodyClass = ReportPdfLayoutService::grupoPruebaPageBreakBodyClass($layout);
    if (! str_contains($bodyClass, 'pdf-layout-engine')) {
        fail($failures, "{$label}: body class sin pdf-layout-engine");
    }
    if (! str_contains($bodyClass, 'pdf-pagination-' . str_replace('_', '-', $mode))) {
        fail($failures, "{$label}: body class sin modo de paginación");
    }

    $pdfHtml = $rs->renderReportPdfHtml($data, $reportUrl, $qr, '01/01/2026 12:00:00', $layout);
    $printHtml = $rs->renderReportPrintHtml($data, $reportUrl, $qr, $registroId, '01/01/2026 12:00:00', false, $layout);

    auditPlanVsHtml($plan, $pdfHtml, 'PDF', $label, $failures, $passed);
    auditPlanVsHtml($plan, $printHtml, 'PRINT', $label, $failures, $passed);

    foreach (['PDF' => $pdfHtml, 'PRINT' => $printHtml] as $ch => $html) {
        $keepIntact = countKeepIntactInDom($html);
        $flow = ReportPaginationMode::usesFlowContinuousPagination($mode);
        $area = ReportPaginationMode::usesAreaHardPageBreak($mode);

        if ($flow && $keepIntact > 0) {
            fail($failures, "{$label} {$ch}: keep-intact={$keepIntact} en modo flujo");
        }
        if ($area) {
            $interBreaks = substr_count($html, 'report-grupo-inter-page-break');
            $expectedInter = max(0, count($tree->areas) - 1);
            if ($interBreaks < $expectedInter) {
                fail($failures, "{$label} {$ch}: inter-page-break={$interBreaks}, esperado {$expectedInter}");
            }
        }
        foreach ($legacyMarkers as $marker) {
            if (str_contains($html, $marker)) {
                fail($failures, "{$label} {$ch}: legacy «{$marker}»");
            }
        }
        if (! str_contains($html, 'pdf-layout-engine')) {
            fail($failures, "{$label} {$ch}: HTML sin pdf-layout-engine");
        }
        if ($ch === 'PRINT' && ! str_contains($html, 'reportLayoutPlan')) {
            fail($failures, "{$label} PRINT: sin script reportLayoutPlan");
        }

        echo sprintf(
            "  %s: keep-intact=%d force-break=%d inter-break=%d OK\n",
            $ch,
            $keepIntact,
            substr_count($html, 'report-subgrupo-force-break-before'),
            substr_count($html, 'report-grupo-inter-page-break'),
        );
    }

    if ($generatePdf) {
        try {
            ini_set('memory_limit', '512M');
            $bytes = (new PdfService())->generate($pdfHtml, 'validate.pdf', $pageSize);
            if ($bytes === '' || ! str_starts_with($bytes, '%PDF')) {
                fail($failures, "{$label}: Dompdf salida inválida");
            } else {
                $outDir = WRITEPATH . 'debug';
                if (! is_dir($outDir)) {
                    mkdir($outDir, 0775, true);
                }
                $safe = str_replace('_', '-', $mode);
                $path = $outDir . DIRECTORY_SEPARATOR . "validate_{$registroId}_{$safe}.pdf";
                file_put_contents($path, $bytes);
                echo "  Dompdf: " . strlen($bytes) . " bytes → {$path}\n";
            }
        } catch (\Throwable $e) {
            fail($failures, "{$label}: Dompdf — " . $e->getMessage());
        }
    }

    echo "\n";
    unset($pdfHtml, $printHtml);
    gc_collect_cycles();
}

$printLayout = (new ReportPdfLayoutService())->getPrintLayoutForRender();
echo "Plantillas activas\n";
echo str_repeat('-', 72) . "\n";
echo '  PDF:   ' . ReportPdfLayoutService::resolvePaginationModeFromLayout($baseLayout) . "\n";
echo '  PRINT: ' . ReportPdfLayoutService::resolvePaginationModeFromLayout($printLayout) . "\n\n";

echo str_repeat('=', 72) . "\n";
echo "Checks plan↔HTML: {$passed}\n";
if ($warnings !== []) {
    echo "Advertencias (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  ⚠ {$w}\n";
    }
}
if ($failures !== []) {
    echo "FALLOS (" . count($failures) . "):\n";
    foreach ($failures as $f) {
        echo "  ✗ {$f}\n";
    }
    exit(1);
}

echo "RESULTADO: validación automatizada OK — listo para revisión visual.\n";
if ($generatePdf) {
    echo "PDFs en writable/debug/validate_{$registroId}_*.pdf\n";
} else {
    echo "Tip: añade --pdf para generar PDFs de prueba en writable/debug/\n";
}
exit(0);
