<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Motor único de paginación: ReportTree + mode + métricas → LayoutPlan.
 */
final class ReportLayoutEngine
{
    private ReportNodeMeasurer $measurer;

    private float $cursorY;

    private int $cursorPage;

    /** @var list<LayoutPlacement> */
    private array $placements = [];

    private ?ReportTree $tree = null;

    public function __construct(
        private readonly ReportLayoutMetrics $metrics,
    ) {
        $this->measurer = new ReportNodeMeasurer($metrics);
    }

    public function buildLayoutPlan(ReportTree $tree, string $mode): LayoutPlan
    {
        if (! ReportPaginationMode::isValid($mode)) {
            $mode = ReportPaginationMode::default();
        }

        $this->placements = [];
        $this->tree = $tree;
        $this->cursorPage = 0;
        $this->cursorY = $this->metrics->contentStartMm;

        $totalAreas = count($tree->areas);
        foreach ($tree->areas as $areaIdx => $area) {
            $isFirstArea = $areaIdx === 0;
            $isLastArea = $areaIdx >= $totalAreas - 1;

            if (ReportPaginationMode::usesAreaHardPageBreak($mode) && ! $isFirstArea) {
                $this->forceNextPage();
            }

            $this->placeAreaStart($area, $isFirstArea, $mode);

            $blockCount = count($area->analysisBlocks);
            foreach ($area->analysisBlocks as $blockIdx => $block) {
                $isLastBlockInArea = $blockIdx >= $blockCount - 1;
                $this->placeAnalysisBlock(
                    $block,
                    $mode,
                    $isLastBlockInArea,
                    $area,
                    $isLastArea,
                );
            }

            if ($area->signature !== null) {
                $this->placeSignature(
                    $area->signature,
                    $mode,
                    $isLastArea && $tree->globalSignature === null,
                );
            }
        }

        if ($tree->globalSignature !== null) {
            $this->placeSignature($tree->globalSignature, $mode, true);
        }

        return new LayoutPlan(
            mode: $mode,
            metrics: $this->metrics,
            placements: $this->placements,
            totalPages: $this->cursorPage + 1,
        );
    }

    private function placeAreaStart(AreaNode $area, bool $isFirstArea, string $mode): void
    {
        $sepH = $this->measurer->measureAreaSeparator($isFirstArea);
        if ($sepH <= 0) {
            return;
        }

        $firstBlock = $area->analysisBlocks[0] ?? null;
        if ($firstBlock !== null) {
            $firstH = $this->measurer->measureAnalysisBlock($firstBlock);
            $remaining = $this->remainingMm();
            if (ReportPaginationMode::usesFlowContinuousPagination($mode) && $this->metrics->flowFitSafetyMm > 0) {
                $remaining = max(0.0, $remaining - $this->metrics->flowFitSafetyMm);
            }

            if ($sepH + $firstH > $remaining && ! $this->atPageStart()) {
                $this->forceNextPage();
            }
        }

        $this->bumpCursor($sepH);
    }

    private function placeAnalysisBlock(
        AnalysisBlockNode $block,
        string $mode,
        bool $isLastBlockInArea,
        AreaNode $area,
        bool $isLastArea,
    ): void {
        $blockH = $this->measurer->measureAnalysisBlock($block);
        if ($blockH <= 0) {
            return;
        }

        $remaining = $this->remainingMm();
        if (ReportPaginationMode::usesFlowContinuousPagination($mode) && $this->metrics->flowFitSafetyMm > 0) {
            $remaining = max(0.0, $remaining - $this->metrics->flowFitSafetyMm);
        }
        $markers = new LayoutDomMarkers();
        $tableSplits = [];

        if ($blockH > $this->metrics->maxContentHeightMm) {
            $tableSplits = $this->measurer->splitTablesForRemainingHeight(
                $block,
                $this->cursorPage,
                $this->remainingMm(),
            );
            if ($this->cursorY > $this->metrics->contentStartMm + 0.01) {
                $this->forceNextPage();
            }
            $markers = new LayoutDomMarkers(
                segmentClasses: $this->segmentClassesForBlock($block, true),
            );
            $this->registerPlacement($block, $blockH, $markers, $tableSplits);
            $this->advancePastBlock($blockH, $tableSplits);

            return;
        }

        if ($blockH > $remaining) {
            $this->forceNextPage();
            // Tras forceNextPage() el cursor ya está al inicio de página; page-break-before
            // en CSS duplicaría el salto y dejaría una hoja en blanco (p. ej. Glucosa).
        }

        $this->registerPlacement($block, $blockH, $markers, $tableSplits);
        $this->bumpCursor($blockH);

        if ($isLastBlockInArea && $area->signature !== null) {
            $this->applySignatureFitRules(
                $area,
                $mode,
                $block,
                $this->isLastReportSignatureContext($area, $isLastArea),
            );
        } elseif ($isLastBlockInArea && $isLastArea && $this->tree?->globalSignature !== null) {
            $this->applySignatureFitRules(
                $area,
                $mode,
                $block,
                true,
            );
        }
    }

    /**
     * Última firma del reporte: firma del último área (sin firma global al cierre).
     */
    private function isLastReportSignatureContext(AreaNode $area, bool $isLastArea): bool
    {
        if ($this->tree === null || $this->tree->globalSignature !== null) {
            return false;
        }

        return $isLastArea && $area->signature !== null;
    }

    private function placeSignature(SignatureBlockNode $signature, string $mode, bool $isLastSignatureInReport): void
    {
        $sigH = $this->measurer->measureSignatureBlock();
        if ($sigH <= 0) {
            return;
        }

        if (ReportPaginationMode::avoidsLoneSignatureOnLastReportSignature($mode) && $isLastSignatureInReport) {
            $this->ensureSignatureHasCompanionContent($signature->areaIndex);
        } elseif ($sigH > $this->remainingMm()) {
            if (($isLastSignatureInReport && ReportPaginationMode::optimizesLoneSignatureOnLastPage($mode))
                || ReportPaginationMode::softFitSignatureInArea($mode)) {
                $this->relocateLastAnalysisBlockBeforeSignature($signature->areaIndex);
            }

            if ($sigH > $this->remainingMm()) {
                $this->forceNextPage();
            }
        }

        $this->registerSignaturePlacement($signature, $sigH);
        $this->bumpCursor($sigH);
    }

    /**
     * MODE 2: garantiza contenido de análisis junto a la firma (tail-split o bloque previo).
     */
    private function ensureSignatureHasCompanionContent(int $areaIndex): bool
    {
        $areaIdx = $this->resolveAreaIndexForSignature($areaIndex);
        if ($areaIdx === null || $this->tree === null) {
            return false;
        }

        $area = $this->tree->areas[$areaIdx] ?? null;
        if ($area === null || $area->analysisBlocks === []) {
            return false;
        }

        if (! $this->atPageStart()) {
            $sigH = $this->measurer->measureSignatureBlock();
            if ($sigH <= 0 || $sigH <= $this->remainingMm()) {
                return true;
            }
        }

        $lastBlock = $area->analysisBlocks[count($area->analysisBlocks) - 1];
        $placement = $this->findPlacement($lastBlock->id);
        if ($placement !== null && $placement->markers->signatureTailGroup) {
            return true;
        }

        if ($this->applySignatureTailSplit($lastBlock, $areaIdx)) {
            return true;
        }

        $blockH = $this->measurer->measureAnalysisBlock($lastBlock);
        $sigH = $this->measurer->measureSignatureBlock();
        $maxBundle = $this->metrics->maxContentHeightMm;
        if ($this->metrics->flowFitSafetyMm > 0) {
            $maxBundle -= $this->metrics->flowFitSafetyMm;
        }

        if ($blockH + $sigH <= $maxBundle) {
            $this->relocateLastAnalysisBlockBeforeSignature($areaIdx);

            return ! $this->atPageStart();
        }

        return false;
    }

    private function resolveAreaIndexForSignature(int $areaIndex): ?int
    {
        if ($areaIndex >= 0) {
            return $areaIndex;
        }

        if ($this->tree === null || $this->tree->areas === []) {
            return null;
        }

        return $this->tree->areas[count($this->tree->areas) - 1]->index;
    }

    private function findPlacement(string $nodeId): ?LayoutPlacement
    {
        foreach ($this->placements as $placement) {
            if ($placement->nodeId === $nodeId) {
                return $placement;
            }
        }

        return null;
    }

    /**
     * MODE 2: tail-split solo en la última firma del reporte (resto = MODE 1).
     */
    private function applySignatureFitRules(
        AreaNode $area,
        string $mode,
        AnalysisBlockNode $lastBlock,
        bool $isLastReportSignature,
    ): void {
        if ($area->signature === null && ! $isLastReportSignature) {
            return;
        }

        $needsTailSplit = $isLastReportSignature
            && (ReportPaginationMode::avoidsLoneSignatureOnLastReportSignature($mode)
                || ReportPaginationMode::softFitSignatureInArea($mode));

        if ($needsTailSplit && $this->applySignatureTailSplit($lastBlock, $area->index)) {
            $tailRows = $this->lastBlockLastSectionRowCount($lastBlock);
            if ($tailRows <= 6) {
                $this->relocateLastAnalysisBlockBeforeSignature($area->index);
            }

            return;
        }

        $sigH = $this->measurer->measureSignatureBlock();
        $remaining = $this->remainingMm();

        if ($sigH <= 0 || $sigH <= $remaining) {
            return;
        }

        if ($needsTailSplit && $isLastReportSignature) {
            $this->relocateLastAnalysisBlockBeforeSignature($area->index);
        }
    }

    private function lastBlockLastSectionRowCount(AnalysisBlockNode $block): int
    {
        if ($block->tables === []) {
            return 0;
        }

        return $block->tables[count($block->tables) - 1]->rowCount;
    }

    private function applySignatureTailSplit(AnalysisBlockNode $block, int $areaIndex): bool
    {
        $sigH = $this->measurer->measureSignatureBlock();
        $splits = $this->measurer->buildSignatureTailSplitPlans($block, $sigH);
        if ($splits === []) {
            return false;
        }

        $lastBlockKey = null;
        $lastBlockPlacement = null;
        foreach ($this->placements as $key => $placement) {
            if ($placement->nodeId !== $block->id) {
                continue;
            }
            $lastBlockKey = $key;
            $lastBlockPlacement = $placement;
        }

        if ($lastBlockPlacement === null || $lastBlockKey === null) {
            return false;
        }

        $segmentClasses = $this->segmentClassesForBlock($block, true);
        foreach ($splits as $split) {
            if ($split->repeatThead || $split->rowCount <= 0) {
                continue;
            }
            $segmentClasses[$split->sectionIndex] = 'report-segment-allow-split';
        }

        $this->placements[$lastBlockKey] = new LayoutPlacement(
            nodeId: $lastBlockPlacement->nodeId,
            nodeType: $lastBlockPlacement->nodeType,
            pageIndex: $lastBlockPlacement->pageIndex,
            yStartMm: $lastBlockPlacement->yStartMm,
            heightMm: $lastBlockPlacement->heightMm,
            markers: new LayoutDomMarkers(
                segmentClasses: $segmentClasses,
                signatureTailGroup: true,
            ),
            tableSplits: $splits,
        );

        return true;
    }

    private function relocateLastAnalysisBlockBeforeSignature(int $areaIndex): void
    {
        $lastBlockPlacement = null;
        $lastBlockKey = null;
        foreach ($this->placements as $key => $placement) {
            if ($placement->nodeType !== LayoutPlacement::TYPE_ANALYSIS) {
                continue;
            }
            if (! str_starts_with($placement->nodeId, 'area-' . $areaIndex . '-block-')) {
                continue;
            }
            $lastBlockPlacement = $placement;
            $lastBlockKey = $key;
        }

        if ($lastBlockPlacement === null || $lastBlockKey === null) {
            return;
        }

        $this->forceNextPage();

        $updated = new LayoutPlacement(
            nodeId: $lastBlockPlacement->nodeId,
            nodeType: $lastBlockPlacement->nodeType,
            pageIndex: $this->cursorPage,
            yStartMm: $this->cursorY,
            heightMm: $lastBlockPlacement->heightMm,
            markers: $lastBlockPlacement->markers,
            tableSplits: $lastBlockPlacement->tableSplits,
        );

        $this->placements[$lastBlockKey] = $updated;
        $this->bumpCursor($lastBlockPlacement->heightMm);
    }

    private function registerPlacement(
        AnalysisBlockNode $block,
        float $heightMm,
        LayoutDomMarkers $markers,
        array $tableSplits,
    ): void {
        $this->placements[] = new LayoutPlacement(
            nodeId: $block->id,
            nodeType: LayoutPlacement::TYPE_ANALYSIS,
            pageIndex: $this->cursorPage,
            yStartMm: $this->cursorY,
            heightMm: $heightMm,
            markers: $markers,
            tableSplits: $tableSplits,
        );
    }

    private function registerSignaturePlacement(SignatureBlockNode $signature, float $heightMm): void
    {
        $this->placements[] = new LayoutPlacement(
            nodeId: $signature->id,
            nodeType: LayoutPlacement::TYPE_SIGNATURE,
            pageIndex: $this->cursorPage,
            yStartMm: $this->cursorY,
            heightMm: $heightMm,
            markers: new LayoutDomMarkers(),
        );
    }

    /**
     * @return list<string>
     */
    private function segmentClassesForBlock(AnalysisBlockNode $block, bool $allowSplit): array
    {
        $classes = [];
        foreach ($block->tables as $section) {
            $classes[$section->sectionIndex] = $allowSplit ? 'report-segment-allow-split' : '';
        }

        return $classes;
    }

    /**
     * @param list<TableSplitPlan> $tableSplits
     */
    private function advancePastBlock(float $blockH, array $tableSplits): void
    {
        if ($tableSplits === []) {
            $this->bumpCursor($blockH);

            return;
        }

        $maxPage = $this->cursorPage;
        foreach ($tableSplits as $split) {
            $maxPage = max($maxPage, $split->pageIndex);
        }
        $this->cursorPage = $maxPage;
        $this->cursorY = $this->metrics->contentStartMm + ($blockH % $this->metrics->maxContentHeightMm);
        if ($this->cursorY >= $this->pageBottomMm()) {
            $this->cursorPage++;
            $this->cursorY = $this->metrics->contentStartMm;
        }
    }

    private function remainingMm(): float
    {
        return max(0.0, round($this->pageBottomMm() - $this->cursorY, 2));
    }

    private function pageBottomMm(): float
    {
        return $this->metrics->contentStartMm + $this->metrics->maxContentHeightMm;
    }

    private function atPageStart(): bool
    {
        return abs($this->cursorY - $this->metrics->contentStartMm) < 0.05;
    }

    private function forceNextPage(): void
    {
        $this->cursorPage++;
        $this->cursorY = $this->metrics->contentStartMm;
    }

    private function bumpCursor(float $heightMm): void
    {
        if ($heightMm <= 0) {
            return;
        }

        $this->cursorY += $heightMm;
        while ($this->cursorY > $this->pageBottomMm() + 0.01) {
            $overflow = $this->cursorY - $this->pageBottomMm();
            $this->cursorPage++;
            $this->cursorY = $this->metrics->contentStartMm + $overflow;
        }
    }
}
