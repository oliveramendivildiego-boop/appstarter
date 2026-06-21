<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Medición teórica determinista de nodos del ReportTree.
 */
final class ReportNodeMeasurer
{
    public function __construct(
        private readonly ReportLayoutMetrics $metrics,
    ) {
    }

    public function measureAnalysisBlock(AnalysisBlockNode $block): float
    {
        $height = 0.0;

        if ($block->isSubPrueba && ! $this->metrics->flowCompactSpacing) {
            $height += $this->metrics->subgrupoGapMm;
        }

        if ($block->blockIndex === 0 && $block->areaIndex > 0) {
            $height += $this->metrics->grupoPruebaGapMm;
        }

        $height += $this->measureBlockHeader($block);

        foreach ($block->tables as $sectionIndex => $section) {
            $compactTop = $this->metrics->flowCompactSpacing
                && ($block->isSubPrueba || $sectionIndex > 0);
            $height += $this->measureTableSection($section, $compactTop);
        }

        if ($this->metrics->analysisBlockHeightFactor !== 1.0) {
            $height *= $this->metrics->analysisBlockHeightFactor;
        }

        return round($height, 2);
    }

    public function measureBlockHeader(AnalysisBlockNode $block): float
    {
        $height = 0.0;

        if ($block->groupTitle !== '') {
            $marginTop = 0.0;
            if ($block->blockIndex === 0
                && $block->areaIndex === 0
                && ! $this->metrics->cabeceraAreaSeparatorEnabled) {
                $marginTop = $this->metrics->firstReportGroupTitleMarginTopMm;
            }

            $height += $marginTop
                + $this->metrics->groupTitleLineHeightMm
                + $this->metrics->groupTitleMarginBottomMm;
        }

        if ($this->metrics->cabeceraShowTipoMuestra && $block->tipoMuestra !== '') {
            $height += $this->metrics->cabeceraMetaLineHeightMm
                + $this->metrics->cabeceraMetaMarginBottomMm;
        }

        if ($this->metrics->cabeceraShowMetodo && $block->metodo !== '') {
            $height += $this->metrics->cabeceraMetaLineHeightMm
                + $this->metrics->cabeceraMetaMarginBottomMm;
        }

        return round($height, 2);
    }

    public function measureTableSection(TableSectionNode $section, bool $compactTopMargin = false): float
    {
        if ($section->rowCount <= 0) {
            return 0.0;
        }

        $height = 0.0;
        if ($section->hasSegmentTitle) {
            $height += $this->metrics->segmentTitleHeightMm;
        }
        if ($section->includeThead) {
            $height += $this->metrics->theadHeightMm;
        }
        $height += $section->rowCount * $this->metrics->rowHeightMm;
        $height += $section->rowCount * $this->metrics->tableRowBorderMm;
        if ($this->metrics->flowCompactSpacing) {
            if (! $compactTopMargin) {
                $height += $this->metrics->tableMarginTopMm;
            }
            $height += $this->metrics->tableMarginBottomMm;
        } else {
            $height += $this->metrics->tableMarginTopMm + $this->metrics->tableMarginBottomMm;
            $height += $this->metrics->segmentWrapMarginBottomMm;
        }

        return round($height, 2);
    }

    public function measureSignatureBlock(): float
    {
        $height = $this->metrics->signatureHeightMm;
        if ($this->metrics->signatureHeightFactor !== 1.0) {
            $height *= $this->metrics->signatureHeightFactor;
        }

        return round($height, 2);
    }

    public function measureAreaSeparator(bool $isFirstArea): float
    {
        if ($isFirstArea) {
            return 0.0;
        }

        return round($this->metrics->areaSeparatorHeightMm, 2);
    }

    /**
     * Altura mínima de cabecera de área (separador + primer bloque parcial).
     */
    public function measureAreaHeadMin(AreaNode $area): float
    {
        $first = $area->analysisBlocks[0] ?? null;
        if ($first === null) {
            return 0.0;
        }

        return round(
            $this->measureBlockHeader($first) + $this->measureAreaSeparator(false),
            2,
        );
    }

    /**
     * Parte un bloque cuyas tablas exceden el espacio disponible en la página.
     *
     * @return list<TableSplitPlan>
     */
    public function splitTablesForRemainingHeight(
        AnalysisBlockNode $block,
        int $pageIndex,
        float $availableMm,
    ): array {
        $splits = [];
        $used = 0.0;
        if ($block->isSubPrueba && ! $this->metrics->flowCompactSpacing) {
            $used += $this->metrics->subgrupoGapMm;
        }
        $used += $this->measureBlockHeader($block);

        foreach ($block->tables as $section) {
            $sectionHeight = $this->measureTableSection($section);
            if ($sectionHeight <= 0) {
                continue;
            }

            if ($used + $sectionHeight <= $availableMm) {
                $used += $sectionHeight;
                continue;
            }

            $prefix = 0.0;
            if ($section->hasSegmentTitle) {
                $prefix += $this->metrics->segmentTitleHeightMm;
            }
            if ($section->includeThead) {
                $prefix += $this->metrics->theadHeightMm;
            }

            $rowsArea = max(0.0, $availableMm - $used - $prefix);
            $rowsFit = (int) floor($rowsArea / max(0.01, $this->metrics->rowHeightMm));
            $rowsFit = max(0, min($rowsFit, $section->rowCount));

            if ($rowsFit > 0 && $rowsFit < $section->rowCount) {
                $splits[] = new TableSplitPlan(
                    sectionIndex: $section->sectionIndex,
                    startRow: 0,
                    rowCount: $rowsFit,
                    repeatThead: false,
                    pageIndex: $pageIndex,
                );
                $remaining = $section->rowCount - $rowsFit;
                $nextPage = $pageIndex + 1;
                while ($remaining > 0) {
                    $chunk = (int) min(
                        $remaining,
                        max(1, (int) floor(
                            ($this->metrics->maxContentHeightMm - ($section->includeThead ? $this->metrics->theadHeightMm : 0.0))
                            / max(0.01, $this->metrics->rowHeightMm),
                        )),
                    );
                    $splits[] = new TableSplitPlan(
                        sectionIndex: $section->sectionIndex,
                        startRow: $section->rowCount - $remaining,
                        rowCount: $chunk,
                        repeatThead: $section->includeThead,
                        pageIndex: $nextPage,
                    );
                    $remaining -= $chunk;
                    $nextPage++;
                }
            }

            $used = $this->metrics->maxContentHeightMm;
        }

        return $splits;
    }

    /**
     * Parte la última tabla del bloque para que filas finales queden con la firma (MODE 2/4).
     *
     * @return list<TableSplitPlan>
     */
    public function buildSignatureTailSplitPlans(
        AnalysisBlockNode $block,
        float $signatureHeightMm,
    ): array {
        if ($block->tables === []) {
            return [];
        }

        $lastSection = $block->tables[count($block->tables) - 1];
        if ($lastSection->rowCount < 1) {
            return [];
        }

        // Secciones cortas (p. ej. Creatinina 1 fila, sedimento ≤6): tabla completa + firma juntas.
        if ($lastSection->rowCount <= 6) {
            return [
                new TableSplitPlan(
                    sectionIndex: $lastSection->sectionIndex,
                    startRow: 0,
                    rowCount: 0,
                    repeatThead: false,
                    pageIndex: 0,
                ),
                new TableSplitPlan(
                    sectionIndex: $lastSection->sectionIndex,
                    startRow: 0,
                    rowCount: $lastSection->rowCount,
                    repeatThead: $lastSection->includeThead,
                    pageIndex: 0,
                ),
            ];
        }

        $sigReserve = $signatureHeightMm + $this->metrics->flowFitSafetyMm;
        $budget = max(0.0, $this->metrics->maxContentHeightMm - $sigReserve);

        $prefix = 0.0;
        if ($lastSection->hasSegmentTitle) {
            $prefix += $this->metrics->segmentTitleHeightMm;
        }
        if ($lastSection->includeThead) {
            $prefix += $this->metrics->theadHeightMm;
        }
        if ($this->metrics->flowCompactSpacing) {
            $prefix += $this->metrics->tableMarginBottomMm;
        } else {
            $prefix += $this->metrics->tableMarginTopMm
                + $this->metrics->tableMarginBottomMm
                + $this->metrics->segmentWrapMarginBottomMm;
        }

        $rowUnit = max(0.01, $this->metrics->rowHeightMm + $this->metrics->tableRowBorderMm);
        $rowsOnTailPage = (int) floor(max(0.0, $budget - $prefix) / $rowUnit);
        $rowsOnTailPage = max(1, min($rowsOnTailPage, $lastSection->rowCount - 1));
        if ($lastSection->rowCount >= 3) {
            $rowsOnTailPage = max(2, $rowsOnTailPage);
            $rowsOnTailPage = min($rowsOnTailPage, $lastSection->rowCount - 1);
        }

        $splitAt = $lastSection->rowCount - $rowsOnTailPage;

        return [
            new TableSplitPlan(
                sectionIndex: $lastSection->sectionIndex,
                startRow: 0,
                rowCount: $splitAt,
                repeatThead: false,
                pageIndex: 0,
            ),
            new TableSplitPlan(
                sectionIndex: $lastSection->sectionIndex,
                startRow: $splitAt,
                rowCount: $rowsOnTailPage,
                repeatThead: $lastSection->includeThead,
                pageIndex: 0,
            ),
        ];
    }

    /**
     * Altura del bloque hasta el inicio del tail group (secciones previas + cabecera + filas cabeza).
     *
     * @param list<TableSplitPlan> $splits
     */
    public function measureBlockBeforeSignatureTail(AnalysisBlockNode $block, array $splits): float
    {
        $tailSectionIndex = null;
        $headRowCount = 0;
        foreach ($splits as $split) {
            if ($split->repeatThead) {
                $tailSectionIndex = $split->sectionIndex;
            } elseif ($split->startRow === 0 && $tailSectionIndex !== null && $split->sectionIndex === $tailSectionIndex) {
                $headRowCount = $split->rowCount;
            }
        }

        if ($tailSectionIndex === null) {
            return $this->measureAnalysisBlock($block);
        }

        $height = 0.0;
        if ($block->isSubPrueba && ! $this->metrics->flowCompactSpacing) {
            $height += $this->metrics->subgrupoGapMm;
        }
        if ($block->blockIndex === 0 && $block->areaIndex > 0) {
            $height += $this->metrics->grupoPruebaGapMm;
        }
        $height += $this->measureBlockHeader($block);

        foreach ($block->tables as $sectionIndex => $section) {
            $compactTop = $this->metrics->flowCompactSpacing
                && ($block->isSubPrueba || $sectionIndex > 0);
            if ($section->sectionIndex < $tailSectionIndex) {
                $height += $this->measureTableSection($section, $compactTop);

                continue;
            }
            if ($section->sectionIndex === $tailSectionIndex && $headRowCount > 0) {
                $height += $this->measureTableSectionRows(
                    $section,
                    $headRowCount,
                    $compactTop,
                    true,
                );
            }
        }

        if ($this->metrics->analysisBlockHeightFactor !== 1.0) {
            $height *= $this->metrics->analysisBlockHeightFactor;
        }

        return round($height, 2);
    }

    /**
     * Tabla cola + firma que deben permanecer juntas (MODE 2).
     *
     * @param list<TableSplitPlan> $splits
     */
    public function measureSignatureTailBundle(AnalysisBlockNode $block, array $splits, float $signatureHeightMm): float
    {
        $tailSplit = null;
        foreach ($splits as $split) {
            if ($split->repeatThead) {
                $tailSplit = $split;
                break;
            }
        }

        if ($tailSplit === null) {
            return round($signatureHeightMm, 2);
        }

        $section = null;
        foreach ($block->tables as $tableSection) {
            if ($tableSection->sectionIndex === $tailSplit->sectionIndex) {
                $section = $tableSection;
                break;
            }
        }

        $height = $signatureHeightMm;
        if ($section !== null && $tailSplit->rowCount > 0) {
            $height += $this->measureTableSectionRows(
                $section,
                $tailSplit->rowCount,
                false,
                $tailSplit->startRow === 0,
            );
        }

        return round($height, 2);
    }

    private function measureTableSectionRows(
        TableSectionNode $section,
        int $rowCount,
        bool $compactTopMargin = false,
        bool $includeTitle = true,
    ): float {
        if ($rowCount <= 0) {
            return 0.0;
        }

        $height = 0.0;
        if ($includeTitle && $section->hasSegmentTitle) {
            $height += $this->metrics->segmentTitleHeightMm;
        }
        if ($section->includeThead) {
            $height += $this->metrics->theadHeightMm;
        }
        $height += $rowCount * $this->metrics->rowHeightMm;
        $height += $rowCount * $this->metrics->tableRowBorderMm;
        if ($this->metrics->flowCompactSpacing) {
            if (! $compactTopMargin) {
                $height += $this->metrics->tableMarginTopMm;
            }
            $height += $this->metrics->tableMarginBottomMm;
        } else {
            $height += $this->metrics->tableMarginTopMm + $this->metrics->tableMarginBottomMm;
            $height += $this->metrics->segmentWrapMarginBottomMm;
        }

        return round($height, 2);
    }
}
