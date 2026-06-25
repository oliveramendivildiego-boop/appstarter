<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Aplica LayoutPlan al render PHP (mismas clases/nodos que el sistema anterior).
 */
final class LayoutPlanApplier
{
    private bool $signatureTailBundleOpen = false;

    public function __construct(
        private readonly LayoutPlan $plan,
        private readonly ReportTree $tree,
    ) {
    }

    public function plan(): LayoutPlan
    {
        return $this->plan;
    }

    public function tree(): ReportTree
    {
        return $this->tree;
    }

    public function isActive(): bool
    {
        return $this->tree->areas !== [];
    }

    public function mode(): string
    {
        return $this->plan->mode;
    }

    public function totalPages(): int
    {
        return max(1, $this->plan->totalPages);
    }

    /**
     * Metadatos de contenedor de área (.report-pdf-grupo-prueba).
     *
     * @return array{
     *   classes: string,
     *   inter_break: bool,
     *   area_page_leader: bool,
     *   grupo_style: string,
     *   separator_new_page: bool,
     *   apply_compact: bool
     * }
     */
    public function beginArea(int $areaIndex, bool $isFirstArea): array
    {
        $empty = [
            'classes'            => '',
            'inter_break'        => false,
            'area_page_leader'   => false,
            'grupo_style'        => '',
            'separator_new_page' => false,
            'apply_compact'      => false,
        ];

        $area = $this->tree->areas[$areaIndex] ?? null;
        if ($area === null) {
            return $empty;
        }

        $interBreak = ! $isFirstArea && ReportPaginationMode::usesAreaHardPageBreak($this->plan->mode);
        $classes = [];

        if ($interBreak) {
            $classes[] = 'report-pdf-grupo-prueba-new-page-start';
        }

        if ($this->areaUsesSplitSegments($area)) {
            $classes[] = 'report-pdf-grupo-prueba-split-segments-only';
        }

        $grupoStyle = $interBreak
            ? 'page-break-before:avoid;break-before:avoid;margin-top:0;padding-top:0;'
            : '';

        return [
            'classes'            => trim(implode(' ', array_unique($classes))),
            'inter_break'        => $interBreak,
            'area_page_leader'   => $interBreak,
            'grupo_style'        => $grupoStyle,
            'separator_new_page' => $interBreak,
            'apply_compact'      => false,
        ];
    }

    /**
     * Marcadores del ANALYSIS_BLOCK (.report-pdf-subgrupo-block + cabecera + segmentos).
     *
     * @return array{
     *   segment_class: string,
     *   cabecera_class: string,
     *   subgrupo_class: string,
     *   segment_classes: list<string>
     * }
     */
    public function blockMarkers(int $areaIndex, int $blockIndex): array
    {
        $empty = [
            'segment_class'   => '',
            'cabecera_class'  => '',
            'subgrupo_class'  => '',
            'segment_classes' => [],
        ];

        $blockId = ReportTreeBuilder::analysisBlockId($areaIndex, $blockIndex);
        $placement = $this->plan->findPlacement($blockId);
        if ($placement === null) {
            return $empty;
        }

        $markers = $placement->markers;
        $segmentClasses = $markers->segmentClasses;
        if ($segmentClasses === [] && $placement->tableSplits !== []) {
            foreach ($placement->tableSplits as $split) {
                $segmentClasses[$split->sectionIndex] = 'report-segment-allow-split';
            }
        }

        return [
            'segment_class'   => $segmentClasses[0] ?? '',
            'cabecera_class'  => $markers->cabeceraClass,
            'subgrupo_class'  => $markers->subgrupoClass,
            'segment_classes' => $segmentClasses,
        ];
    }

    public function segmentClass(int $areaIndex, int $blockIndex, int $sectionIndex): string
    {
        $markers = $this->blockMarkers($areaIndex, $blockIndex);
        if (isset($markers['segment_classes'][$sectionIndex])) {
            return (string) $markers['segment_classes'][$sectionIndex];
        }

        return $sectionIndex === 0 ? (string) ($markers['segment_class'] ?? '') : '';
    }

    /**
     * Salto antes del separador de área (p. ej. «QUÍMICA CLÍNICA») en impresión navegador.
     */
    public function browserPrintPageBreakBeforeAreaContent(int $areaIndex): bool
    {
        $area = $this->tree->areas[$areaIndex] ?? null;
        if ($area === null || $area->analysisBlocks === []) {
            return false;
        }

        $firstPlacement = $this->plan->findPlacement($area->analysisBlocks[0]->id);
        if ($firstPlacement === null) {
            return false;
        }

        $prevPage = $this->pageIndexBeforeArea($areaIndex);
        if ($prevPage === null) {
            return false;
        }

        return $firstPlacement->pageIndex > $prevPage;
    }

    /**
     * Salto antes de un bloque de análisis (blockIndex &gt; 0) en impresión navegador.
     */
    public function browserPrintPageBreakBeforeAnalysisBlock(int $areaIndex, int $blockIndex): bool
    {
        if ($blockIndex <= 0) {
            return false;
        }

        $blockId = ReportTreeBuilder::analysisBlockId($areaIndex, $blockIndex);
        $blockPlacement = $this->plan->findPlacement($blockId);
        if ($blockPlacement === null) {
            return false;
        }

        $prevId = ReportTreeBuilder::analysisBlockId($areaIndex, $blockIndex - 1);
        $prevPlacement = $this->plan->findPlacement($prevId);
        if ($prevPlacement === null) {
            return false;
        }

        return $blockPlacement->pageIndex > $prevPlacement->pageIndex;
    }

    public function analysisBlockPlanPageIndex(int $areaIndex, int $blockIndex): int
    {
        $blockId = ReportTreeBuilder::analysisBlockId($areaIndex, $blockIndex);
        $placement = $this->plan->findPlacement($blockId);

        return $placement !== null ? $placement->pageIndex : 0;
    }

    /**
     * @return array{splitAt: int, tailRows: int}|null
     */
    public function sectionSignatureTailSplit(int $areaIndex, int $blockIndex, int $sectionIndex): ?array
    {
        $blockId = ReportTreeBuilder::analysisBlockId($areaIndex, $blockIndex);
        $placement = $this->plan->findPlacement($blockId);
        if ($placement === null || $placement->tableSplits === []) {
            return null;
        }

        $sectionSplits = array_values(array_filter(
            $placement->tableSplits,
            static fn (TableSplitPlan $s): bool => $s->sectionIndex === $sectionIndex,
        ));
        if (count($sectionSplits) !== 2) {
            return null;
        }

        $head = $sectionSplits[0];
        $tail = $sectionSplits[1];
        if ($head->startRow !== 0 || ! $tail->repeatThead) {
            return null;
        }

        return [
            'splitAt'       => $head->rowCount,
            'tailRows'      => $tail->rowCount,
            'fullInTail'    => $head->rowCount === 0,
        ];
    }

    public function beginSignatureTailBundleMarkup(): string
    {
        if ($this->signatureTailBundleOpen) {
            return '';
        }

        $this->signatureTailBundleOpen = true;

        return '<div class="report-signature-tail-bundle">';
    }

    public function endSignatureTailBundleMarkup(): string
    {
        if (! $this->signatureTailBundleOpen) {
            return '';
        }

        $this->signatureTailBundleOpen = false;

        return '</div>';
    }

    public function isSignatureTailBundleOpen(): bool
    {
        return $this->signatureTailBundleOpen;
    }

    public function areaUsesSignatureTailBundle(int $areaIndex): bool
    {
        return $this->shouldCloseSignatureTailGroupAfterAreaFirma($areaIndex);
    }

    public function shouldCloseSignatureTailGroupAfterAreaFirma(int $areaIndex): bool
    {
        $area = $this->tree->areas[$areaIndex] ?? null;
        if ($area === null || $area->analysisBlocks === []) {
            return false;
        }

        $lastBlock = $area->analysisBlocks[count($area->analysisBlocks) - 1];
        $placement = $this->plan->findPlacement($lastBlock->id);
        if ($placement === null) {
            return false;
        }

        return $placement->markers->signatureTailGroup;
    }

    /**
     * Prueba simple (una sola tabla): cabecera del subgrupo + tabla + firma van juntas al bundle.
     */
    public function shouldOpenSignatureTailBundleBeforeSubgrupo(int $areaIndex, int $blockIndex): bool
    {
        if (! $this->areaUsesSignatureTailBundle($areaIndex)) {
            return false;
        }

        $area = $this->tree->areas[$areaIndex] ?? null;
        if ($area === null || $area->analysisBlocks === []) {
            return false;
        }

        if ($blockIndex !== count($area->analysisBlocks) - 1) {
            return false;
        }

        $block = $area->analysisBlocks[$blockIndex] ?? null;
        if ($block === null || count($block->tables) !== 1) {
            return false;
        }

        $blockId = ReportTreeBuilder::analysisBlockId($areaIndex, $blockIndex);
        $placement = $this->plan->findPlacement($blockId);
        if ($placement === null || ! $placement->markers->signatureTailGroup) {
            return false;
        }

        foreach ($placement->tableSplits as $split) {
            if ($split->startRow === 0 && ! $split->repeatThead && $split->rowCount === 0) {
                return true;
            }
        }

        return false;
    }

    private function pageIndexBeforeArea(int $areaIndex): ?int
    {
        if ($areaIndex <= 0) {
            return null;
        }

        $prevArea = $this->tree->areas[$areaIndex - 1];
        if ($prevArea->signature !== null) {
            $pl = $this->plan->findPlacement($prevArea->signature->id);
            if ($pl !== null) {
                return $pl->pageIndex;
            }
        }

        $blocks = $prevArea->analysisBlocks;
        if ($blocks !== []) {
            $pl = $this->plan->findPlacement($blocks[count($blocks) - 1]->id);
            if ($pl !== null) {
                return $pl->pageIndex;
            }
        }

        return null;
    }

    /**
     * JSON para JS (solo aplica clases ya decididas; no recalcula layout).
     *
     * @return array<string, mixed>
     */
    public function exportForClient(): array
    {
        $placements = [];
        foreach ($this->plan->placements as $placement) {
            $placements[] = [
                'nodeId'   => $placement->nodeId,
                'nodeType' => $placement->nodeType,
                'pageIndex' => $placement->pageIndex,
                'markers'  => [
                    'grupoClass'    => $placement->markers->grupoClass,
                    'subgrupoClass' => $placement->markers->subgrupoClass,
                    'cabeceraClass' => $placement->markers->cabeceraClass,
                    'segmentClasses' => $placement->markers->segmentClasses,
                    'pageBreakBeforeBlock' => $placement->markers->pageBreakBeforeBlock,
                    'signatureTailGroup' => $placement->markers->signatureTailGroup,
                ],
                'tableSplits' => array_map(static fn (TableSplitPlan $s): array => [
                    'sectionIndex' => $s->sectionIndex,
                    'startRow'     => $s->startRow,
                    'rowCount'     => $s->rowCount,
                    'repeatThead'  => $s->repeatThead,
                    'pageIndex'    => $s->pageIndex,
                ], $placement->tableSplits),
            ];
        }

        return [
            'mode'       => $this->plan->mode,
            'totalPages' => $this->totalPages(),
            'placements' => $placements,
        ];
    }

    private function areaUsesSplitSegments(AreaNode $area): bool
    {
        foreach ($area->analysisBlocks as $block) {
            if ($block->isCultivoMatrix) {
                continue;
            }
            $placement = $this->plan->findPlacement($block->id);
            if ($placement === null) {
                continue;
            }
            if ($placement->tableSplits !== []) {
                return true;
            }
            foreach ($placement->markers->segmentClasses as $class) {
                if (str_contains($class, 'allow-split')) {
                    return true;
                }
            }
        }

        return false;
    }
}
