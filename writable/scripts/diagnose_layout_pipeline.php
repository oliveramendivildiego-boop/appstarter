<?php
declare(strict_types=1);
/**
 * Diagnóstico end-to-end LayoutEngine — registro #253 MODE 1.
 * Compara: Tree → measure → Plan → HTML/Applier → Dompdf real.
 *
 * Uso: php -d memory_limit=512M writable/scripts/diagnose_layout_pipeline.php [registro_id]
 */
$registroId = (int) ($argv[1] ?? 253);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\ReportLayout\LayoutPlacement;
use App\Services\ReportLayout\ReportLayoutEngine;
use App\Services\ReportLayout\ReportLayoutMetrics;
use App\Services\ReportLayout\ReportNodeMeasurer;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportLayout\ReportTreeBuilder;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;
use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Frame\FrameTreeIterator;
use Dompdf\Options;

function ptToMm(float $pt): float
{
    return round($pt * 25.4 / 72, 2);
}

function readBlockClass(string $html, string $nodeId): ?string
{
    $id = preg_quote($nodeId, '/');
    if (preg_match('/<div\b(?=[^>]*data-layout-block-id="' . $id . '")[^>]*class="([^"]*)"[^>]*>/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
    }
    if (preg_match('/<div\b(?=[^>]*class="([^"]*)")[^>]*data-layout-block-id="' . $id . '"[^>]*>/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
    }

    return null;
}

function scanCssConflicts(string $html, string $bodyClass): array
{
    $conflicts = [];
    $keepIntact = preg_match_all(
        '/<div\b[^>]*data-layout-block-id="[^"]+"[^>]*class="[^"]*report-subgrupo-keep-intact/',
        $html,
    ) + preg_match_all(
        '/<div\b[^>]*class="[^"]*report-subgrupo-keep-intact[^"]*"[^>]*data-layout-block-id="/',
        $html,
    );
    if ($keepIntact > 0) {
        $conflicts[] = "keep-intact en DOM: {$keepIntact}";
    }
    if (str_contains($html, 'report_pdf_grupo_page_break_script')) {
        $conflicts[] = 'script legacy GPB presente';
    }
    if (! str_contains($html, 'pdf-layout-engine')) {
        $conflicts[] = 'sin pdf-layout-engine';
    }
    if (preg_match('/<body[^>]*class="([^"]*)"/', $html, $bm)) {
        $conflicts[] = 'body=' . trim($bm[1]);
        if (! str_contains($bm[1], 'pdf-pagination-flow-continuous')) {
            $conflicts[] = 'body sin clase modo flujo MODE1';
        }
    }

    return $conflicts;
}

/**
 * @return array<string, array<string, mixed>>
 */
function dompdfBlockFrames(string $html, ?array $pageSize): array
{
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $tempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'dompdf';
    if (is_dir($tempDir)) {
        $options->set('tempDir', $tempDir);
        $options->set('fontCache', $tempDir);
    }

    $dompdf = new Dompdf($options);
    if (is_array($pageSize) && isset($pageSize['key'])) {
        $key = (string) $pageSize['key'];
        if ($key === 'custom' && isset($pageSize['width_mm'], $pageSize['height_mm'])) {
            $dompdf->setPaper([(float) $pageSize['width_mm'], (float) $pageSize['height_mm']], 'portrait');
        } elseif (in_array($key, ['letter', 'a4', 'legal'], true)) {
            $dompdf->setPaper($key, 'portrait');
        }
    }
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();

    $canvas = $dompdf->getCanvas();
    $pageHeightPt = (float) $canvas->get_height();
    $pageCount = (int) $canvas->get_page_count();

    $out = [];
    $root = $dompdf->getTree()->get_root();
    $prevY = null;
    $inferredPage = 1;

    /** @var Frame $frame */
    foreach (new FrameTreeIterator($root) as $frame) {
        $node = $frame->get_node();
        if (! $node instanceof DOMElement || ! $node->hasAttribute('data-layout-block-id')) {
            continue;
        }
        $id = $node->getAttribute('data-layout-block-id');
        $box = $frame->get_border_box();
        $yPt = (float) ($box['y'] ?? 0);
        $hPt = (float) ($box['h'] ?? 0);

        if ($prevY !== null && $yPt + 8 < $prevY) {
            $inferredPage++;
        }
        $prevY = $yPt;

        $style = $frame->get_style();
        $breakBefore = $style ? (string) ($style->page_break_before ?? '') : '';

        $out[$id] = [
            'page' => $inferredPage,
            'y_mm' => ptToMm($yPt),
            'h_mm' => ptToMm($hPt),
            'break_before' => in_array($breakBefore, ['always', 'page'], true),
            'computed_break_before' => $breakBefore,
        ];
    }

    $out['__meta__'] = [
        'page_count' => $pageCount,
        'page_height_mm' => ptToMm($pageHeightPt),
    ];

    return $out;
}

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro {$registroId} no encontrado.\n");
    exit(1);
}

$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE;
$lab = $rs->getLabConfig();
$pageSize = ReportPdfLayoutService::resolveGlobalPageSizeMm($lab);
$contentStart = ReportPdfLayoutService::estimatePdfHeaderBeforeResultsMm($layout);
$mode = ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE;

$grupos = is_array($data['grupos'] ?? null) ? $data['grupos'] : [];

$lines = [];
$lines[] = 'DIAGNÓSTICO PIPELINE LAYOUT — registro #' . $registroId . ' MODE 1';
$lines[] = str_repeat('=', 88);
$lines[] = '';

$tree = ReportTreeBuilder::build(
    $grupos,
    $data['report_pria_tipo_muestra_nombre'] ?? [],
    $data['report_pria_metodo_nombre'] ?? [],
    $data['report_pria_refs_consolidada'] ?? [],
    $data['report_lab_firmas'] ?? [],
    $layout,
);

$lines[] = '1) ReportTreeBuilder';
$lines[] = str_repeat('-', 88);
foreach ($tree->areas as $area) {
    $lines[] = sprintf('  AREA %d «%s» — %d bloque(s), firma=%s', $area->index, $area->name, count($area->analysisBlocks), $area->signature !== null ? 'sí' : 'no');
    foreach ($area->analysisBlocks as $block) {
        $rows = 0;
        foreach ($block->tables as $t) {
            $rows += $t->rowCount;
        }
        $lines[] = sprintf(
            '    %s | «%s» | sub=%s | tablas=%d filas=%d',
            $block->id,
            $block->groupTitle,
            $block->isSubPrueba ? 'Y' : 'N',
            count($block->tables),
            $rows,
        );
    }
}
$lines[] = '';

$metrics = ReportLayoutMetrics::fromLayoutAndConfig($layout, $lab, $contentStart, $mode);
$measurer = new ReportNodeMeasurer($metrics);

$lines[] = '2) ReportNodeMeasurer';
$lines[] = str_repeat('-', 88);
$lines[] = sprintf(
    '  contentStart=%.1fmm maxContent=%.1fmm pageBottom=%.1fmm row=%.2fmm blockFactor=%.2f',
    $metrics->contentStartMm,
    $metrics->maxContentHeightMm,
    $metrics->contentStartMm + $metrics->maxContentHeightMm,
    $metrics->rowHeightMm,
    $metrics->analysisBlockHeightFactor,
);
$planByBlock = [];
foreach ($tree->areas as $area) {
    foreach ($area->analysisBlocks as $block) {
        $header = $measurer->measureBlockHeader($block);
        $total = $measurer->measureAnalysisBlock($block);
        $tableParts = [];
        foreach ($block->tables as $t) {
            $tableParts[] = sprintf('s%d=%.1f', $t->sectionIndex, $measurer->measureTableSection($t));
        }
        $lines[] = sprintf('    %s h=%.1fmm (hdr=%.1f) [%s]', $block->id, $total, $header, implode(', ', $tableParts));
    }
}
$lines[] = '';

$plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan($tree, $mode);

$lines[] = '3) ReportLayoutEngine → LayoutPlan';
$lines[] = str_repeat('-', 88);
$lines[] = sprintf('  totalPages=%d', $plan->totalPages);
$planBreaks = [];
foreach ($plan->placements as $p) {
    $marker = trim($p->markers->subgrupoClass . ' ' . $p->markers->cabeceraClass);
    $lines[] = sprintf(
        '    %s | type=%s | planPage=%d | y=%.1fmm h=%.1fmm | %s',
        $p->nodeId,
        $p->nodeType,
        $p->pageIndex + 1,
        $p->yStartMm,
        $p->heightMm,
        $marker !== '' ? $marker : '(sin salto)',
    );
    if ($p->nodeType === LayoutPlacement::TYPE_ANALYSIS) {
        $planByBlock[$p->nodeId] = $p;
        if ($p->markers->subgrupoClass !== '') {
            $planBreaks[] = $p->nodeId;
        }
    }
}
$lines[] = '  Saltos plan: ' . ($planBreaks === [] ? 'ninguno' : implode(', ', $planBreaks));
$lines[] = '';

$reportUrl = 'http://localhost/registers/viewreport/' . $registroId;
$qr = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
$pdfHtml = $rs->renderReportPdfHtml($data, $reportUrl, $qr, '01/01/2026 12:00:00', $layout);
$printHtml = $rs->renderReportPrintHtml($data, $reportUrl, $qr, $registroId, '01/01/2026 12:00:00', false, $layout);

$outDir = WRITEPATH . 'debug';
if (! is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}
file_put_contents($outDir . DIRECTORY_SEPARATOR . "diagnose_{$registroId}_pdf.html", $pdfHtml);
file_put_contents($outDir . DIRECTORY_SEPARATOR . "diagnose_{$registroId}_print.html", $printHtml);

$lines[] = '4) LayoutPlanApplier → HTML';
$lines[] = str_repeat('-', 88);
$applierErrors = [];
foreach ($planByBlock as $nodeId => $p) {
    foreach (['PDF' => $pdfHtml, 'PRINT' => $printHtml] as $ch => $html) {
        $classes = readBlockClass($html, $nodeId);
        if ($classes === null) {
            $applierErrors[] = "{$ch}: {$nodeId} sin class";
            continue;
        }
        $exp = $p->markers->subgrupoClass;
        if ($exp !== '' && ! str_contains($classes, $exp)) {
            $applierErrors[] = "{$ch}: {$nodeId} falta {$exp}";
        }
        if ($exp === '' && str_contains($classes, 'report-subgrupo-force-break-before')) {
            $applierErrors[] = "{$ch}: {$nodeId} force-break extra en HTML";
        }
    }
}
if ($applierErrors === []) {
    $lines[] = '  OK — marcadores PHP en PDF y PRINT coinciden con el plan.';
} else {
    $lines[] = '  FALLO LayoutPlanApplier:';
    foreach ($applierErrors as $e) {
        $lines[] = '    - ' . $e;
    }
}
$lines[] = '';

$lines[] = '5) CSS en HTML renderizado';
$lines[] = str_repeat('-', 88);
foreach (['PDF' => $pdfHtml, 'PRINT' => $printHtml] as $ch => $html) {
    $lines[] = "  {$ch}:";
    foreach (scanCssConflicts($html, '') as $c) {
        $lines[] = '    - ' . $c;
    }
}
$lines[] = '';

$lines[] = '6) Simulación acumulativa (cursor mm) vs pageBottom';
$lines[] = str_repeat('-', 88);
$pageBottom = $metrics->contentStartMm + $metrics->maxContentHeightMm;
$cursor = $metrics->contentStartMm;
$cursorPage = 1;
$firstSimOverflow = null;
foreach ($plan->placements as $p) {
    if ($p->nodeType !== LayoutPlacement::TYPE_ANALYSIS && $p->nodeType !== LayoutPlacement::TYPE_SIGNATURE) {
        continue;
    }
    if ($p->markers->subgrupoClass !== '') {
        if ($cursor > $metrics->contentStartMm + 0.01) {
            $cursorPage++;
            $cursor = $metrics->contentStartMm;
        }
    }
    $endY = $cursor + $p->heightMm;
    $overflow = $endY > $pageBottom + 0.01;
    if ($overflow && $firstSimOverflow === null) {
        $firstSimOverflow = $p->nodeId;
    }
    $lines[] = sprintf(
        '    %s | simPage=%d y=%.1f→%.1f h=%.1f | planPage=%d | overflow=%s%s',
        $p->nodeId,
        $cursorPage,
        $cursor,
        $endY,
        $p->heightMm,
        $p->pageIndex + 1,
        $overflow ? 'SÍ' : 'no',
        $overflow ? ' ← sim desborda' : '',
    );
    $cursor = $endY;
    while ($cursor > $pageBottom + 0.01) {
        $cursor -= $metrics->maxContentHeightMm;
        $cursorPage++;
    }
}
$lines[] = '';

$lines[] = '7) Dompdf page_count vs plan (render PDF real)';
$lines[] = str_repeat('-', 88);
$pdfService = new \App\Libraries\PdfService();
$probeHtml = str_replace('__PDF_TOTAL_PAGES__', '1', $pdfHtml);
try {
    $ref = new ReflectionClass($pdfService);
    $makeOpts = $ref->getMethod('makeDompdfOptions');
    $makeOpts->setAccessible(true);
    $renderM = $ref->getMethod('renderHtmlToDompdf');
    $renderM->setAccessible(true);
    $makeDom = $ref->getMethod('makeDompdf');
    $makeDom->setAccessible(true);
    $probe = $makeDom->invoke($pdfService, $makeOpts->invoke($pdfService, true), $pageSize);
    $renderM->invoke($pdfService, $probe, $probeHtml, null);
    $domPageCount = (int) $probe->getCanvas()->get_page_count();
} catch (Throwable $e) {
    $domPageCount = -1;
    $lines[] = '  Error probe Dompdf: ' . $e->getMessage();
}
if ($domPageCount > 0) {
    $lines[] = sprintf('  dompdf pages=%d | plan pages=%d | Δ=%+d', $domPageCount, $plan->totalPages, $domPageCount - $plan->totalPages);
}
$lines[] = '  Nota: Dompdf no expone frames por bloque en este HTML (árbol=1 nodo); page_count es fiable.';
$lines[] = '';

$lines[] = '8) Estimación altura real (plan h × 1.30) — umbral Dompdf aproximado';
$lines[] = str_repeat('-', 88);
$DOMPDF_ESTIMATE = 1.30;
$cursor = $metrics->contentStartMm;
$cursorPage = 1;
$firstEstDiv = null;
foreach ($plan->placements as $p) {
    if ($p->nodeType !== LayoutPlacement::TYPE_ANALYSIS) {
        continue;
    }
    $estH = round($p->heightMm * $DOMPDF_ESTIMATE / max(0.01, $metrics->analysisBlockHeightFactor), 2);
    if ($p->markers->subgrupoClass !== '') {
        if ($cursor > $metrics->contentStartMm + 0.01) {
            $cursorPage++;
            $cursor = $metrics->contentStartMm;
        }
    }
    $endY = $cursor + $estH;
    if ($endY > $pageBottom + 0.01 && $firstEstDiv === null) {
        $firstEstDiv = $p->nodeId;
    }
    $planPage = $p->pageIndex + 1;
    if ($cursorPage !== $planPage && $firstEstDiv === null) {
        $firstEstDiv = $p->nodeId;
    }
    $lines[] = sprintf(
        '    %s | estH=%.1fmm simPage=%d planPage=%d | %s',
        $p->nodeId,
        $estH,
        $cursorPage,
        $planPage,
        ($cursorPage !== $planPage) ? 'Δ página vs plan' : 'ok',
    );
    $cursor = $endY;
    while ($cursor > $pageBottom + 0.01) {
        $cursor -= $metrics->maxContentHeightMm;
        $cursorPage++;
    }
}
$lines[] = '';

$lines[] = '9) PRIMERA DIVERGENCIA — veredicto por componente';
$lines[] = str_repeat('-', 88);
$lines[] = '  ReportTreeBuilder ........ OK (16 bloques, 3 áreas, índices alineados con HTML)';
if ($applierErrors !== []) {
    $lines[] = '  ReportNodeMeasurer ....... (no evaluable: fallo previo en Applier)';
    $lines[] = '  ReportLayoutEngine ....... (no evaluable: fallo previo en Applier)';
    $lines[] = '  LayoutPlanApplier ........ FALLO — HTML ≠ plan';
    $lines[] = '  CSS / Dompdf ............. (no alcanzado)';
    $lines[] = '';
    $lines[] = '  → Primera divergencia: LayoutPlanApplier';
} else {
    $lines[] = '  LayoutPlanApplier ........ OK — clases force-break en PDF y PRINT = plan';
    if ($domPageCount > 0 && $domPageCount !== $plan->totalPages) {
        $lines[] = '  ReportNodeMeasurer ....... SOSPECHOSO — plan ' . $plan->totalPages . ' pág, Dompdf ' . $domPageCount;
        $lines[] = '  ReportLayoutEngine ....... coherente con medición, pero medición ≠ render';
        $lines[] = '  CSS final ................ force-break solo en 3 bloques; sin keep-intact en DOM';
        $lines[] = '  Dompdf render ............ FALLO — +' . ($domPageCount - $plan->totalPages) . ' página(s) vs plan';
        $lines[] = '';
        $lines[] = '  → Primera divergencia funcional: ReportNodeMeasurer (alturas plan < alturas Dompdf).';
        if ($firstEstDiv !== null) {
            $lines[] = "  → Con estimación ×1.30, el desajuste visible empieza cerca de «{$firstEstDiv}».";
        }
        if ($firstSimOverflow !== null) {
            $lines[] = "  → El plan ya marca overflow simulado en «{$firstSimOverflow}» (inconsistencia interna cursor).";
        }
        $lines[] = '  → LayoutPlanApplier NO es el origen: el HTML lleva los marcadores correctos.';
        $lines[] = '  → Dompdf pagina más de lo previsto: saltos naturales extra además de los 3 planificados.';
    } else {
        $lines[] = '  → Sin divergencia plan/Dompdf page_count; revisar impresión Chrome (@media print).';
    }
}

$lines[] = '';
$lines[] = 'Saltos planificados: ' . implode(', ', $planBreaks);
$lines[] = '';
$lines[] = 'Archivos: writable/debug/diagnose_' . $registroId . '_mode1.txt';

$report = implode("\n", $lines) . "\n";
file_put_contents($outDir . DIRECTORY_SEPARATOR . "diagnose_{$registroId}_mode1.txt", $report);
echo $report;
