<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Paginación de grupos de prueba para descarga PDF (Dompdf no ejecuta JS).
 * Réplica simplificada de keep_together_if_fits + salto inter-área + firma huérfana.
 */
class ReportPdfDompdfGrupoPageBreakService
{
    private const PAGE_HEIGHT_MM = 279.4;

    private array $layout;

    /** @var array{mode: string, repeat_header_on_split: bool, compact_min_scale_percent: int, compact_cell_padding_px: int, compact_aggressive: bool, min_remaining_mm_to_force_break: float} */
    private array $gpb;

    private float $marginTopMm;

    private float $marginBottomMm;

    private float $footerReserveMm;

    private float $basePageContentMm;

    private float $maxSliceMm;

    private float $rowHeightMm;

    private float $theadHeightMm;

    private float $segmentTitleHeightMm;

    private float $subgrupoCabeceraMm;

    private float $areaSeparatorMm;

    private float $firmaHeightMm;

    private float $contentStartMm = 0.0;

    private bool $grupoOnFreshPage = false;

    private int $cursorPage = 0;

    private float $cursorY = 0.0;

    private bool $grupoAllowSplit = false;

    private bool $grupoHasFirma = false;

    private bool $usesIfFitsMode = false;

    public static function create(array $layout, float $contentStartMm = 0.0): self
    {
        return new self($layout, $contentStartMm);
    }

    public function __construct(array $layout, float $contentStartMm = 0.0)
    {
        $this->layout = $layout;
        $ps           = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $this->gpb           = ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
        $this->usesIfFitsMode = ReportPdfLayoutService::grupoPruebaPageBreakUsesIfFitsMode($this->gpb);

        $mm = is_array($layout['margins_mm'] ?? null)
            ? $layout['margins_mm']
            : ReportPdfLayoutService::defaultMarginsMmStatic();
        $this->marginTopMm    = (float) ($mm['top'] ?? 15.0);
        $this->marginBottomMm = (float) ($mm['bottom'] ?? 15.0);

        $footerEnabled = false;
        foreach (is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [] as $block) {
            if (! empty($block['enabled']) && (string) ($block['id'] ?? '') === 'footer') {
                $footerEnabled = true;
                break;
            }
        }
        $this->footerReserveMm = $footerEnabled
            ? max(10.0, ReportPdfLayoutService::estimatePdfFooterReserveMm($layout))
            : 0.0;

        $this->basePageContentMm = max(
            40.0,
            self::PAGE_HEIGHT_MM - $this->marginTopMm - $this->marginBottomMm - $this->footerReserveMm
        );
        $this->maxSliceMm = $this->basePageContentMm;

        $rs = ReportPdfLayoutService::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $lf = ReportPdfLayoutService::normalizeLabFirmasStyle($ps['lab_firmas'] ?? []);

        $fontPt    = max(6.0, (float) ($rs['font_size_pt'] ?? 9.0));
        $lineHeight = max(1.0, (float) ($rs['line_height'] ?? 1.35));
        $cellPadPx = max(0, (int) ($rs['cell_padding_v_px'] ?? 6));
        $cellPadMm = $cellPadPx * 25.4 / 96;

        $this->rowHeightMm          = ($fontPt * $lineHeight * 25.4 / 72) + ($cellPadMm * 2);
        $this->theadHeightMm        = $this->rowHeightMm + 1.5;
        $this->segmentTitleHeightMm = $fontPt * 1.2 * 25.4 / 72 + 3.0;
        $this->subgrupoCabeceraMm   = 14.0;
        $this->areaSeparatorMm      = max(4.0, (int) ($rs['grupo_area_separator_margin_top_px'] ?? 10) * 25.4 / 96)
            + max(4.0, (int) ($rs['grupo_area_separator_margin_bottom_px'] ?? 10) * 25.4 / 96) + 6.0;

        $sealMm = (int) ($lf['seal_max_height_px'] ?? 110) * 25.4 / 96;
        $sigMm  = (int) ($lf['signature_max_height_px'] ?? 72) * 25.4 / 96;
        $marginFirmaMm = ((float) ($lf['inline_margin_top_pt'] ?? 8) + (float) ($lf['inline_margin_bottom_pt'] ?? 6)) * 25.4 / 72;
        $this->firmaHeightMm = $marginFirmaMm + max($sealMm, $sigMm) + 18.0;

        $this->contentStartMm = max(0.0, $contentStartMm);
        $this->cursorY        = $this->contentStartMm;
    }

    public function isActive(): bool
    {
        return ReportPdfLayoutService::grupoPruebaPageBreakUsesIfFitsMode($this->gpb)
            || ReportPdfLayoutService::grupoPruebaPageBreakUsesGrupoIntactCss($this->gpb);
    }

    /**
     * @param list<mixed> $items
     *
     * @return array{classes: string, inter_break: bool, grupo_style: string}
     */
    public function beginGrupo(bool $isFirstGrupo, array $items, bool $hasFirma): array
    {
        $classes    = [];
        $grupoStyle = '';
        $interBreak = false;

        $this->grupoHasFirma   = $hasFirma;
        $this->grupoAllowSplit = false;
        $this->grupoOnFreshPage = false;

        if (! $isFirstGrupo && ReportPdfLayoutService::shouldRenderGrupoInterPageBreak($this->layout, false)) {
            $this->forceNextPage();
            $this->grupoOnFreshPage = true;
            $classes[]  = 'report-pdf-grupo-prueba-new-page-start';
            $interBreak = true;
            $grupoStyle = 'page-break-before:avoid;break-before:avoid;margin-top:0;padding-top:0;';
        }

        if ($this->usesIfFitsMode) {
            $estHeight = $this->estimateGrupoHeightFast($items, $hasFirma);
            $remaining = $this->espacioRestanteMm();

            if ($estHeight > $this->maxSliceMm) {
                $this->grupoAllowSplit = true;
                $classes[]             = 'report-pdf-grupo-prueba-allow-split';
            } elseif ($estHeight <= $remaining) {
                $classes[] = 'report-pdf-grupo-prueba-keep-on-page';
            } else {
                $this->grupoAllowSplit = true;
                $classes[]             = 'report-pdf-grupo-prueba-allow-split';
            }
        }

        $this->bumpCursor($this->areaSeparatorMm);

        return [
            'classes'     => trim(implode(' ', array_unique($classes))),
            'inter_break' => $interBreak,
            'grupo_style' => $grupoStyle,
        ];
    }

    /**
     * @return array{class: string, style: string}
     */
    public function segmentWrapAttrs(
        int $visibleRowCount,
        bool $hasSegmentTitle,
        bool $countSubgrupoCabecera,
        bool $isLastBeforeFirma
    ): array {
        if (! $this->usesIfFitsMode
            || (! $this->grupoAllowSplit && ! ($isLastBeforeFirma && $this->grupoHasFirma))) {
            return ['class' => '', 'style' => ''];
        }

        $classes = [];
        $heightMm = $this->estimateSegmentHeightMm($visibleRowCount, $hasSegmentTitle, $countSubgrupoCabecera);

        if ($isLastBeforeFirma) {
            $classes = array_merge($classes, $this->placeWithFirma($heightMm));

            return ['class' => trim(implode(' ', array_unique($classes))), 'style' => ''];
        }

        if (! $this->grupoAllowSplit) {
            $this->bumpCursor($heightMm);

            return ['class' => '', 'style' => ''];
        }

        $remaining = $this->espacioRestanteMm();
        if ($heightMm > $this->maxSliceMm) {
            $classes[] = 'report-segment-allow-split';
            $this->bumpCursor($heightMm);
        } elseif ($heightMm <= $remaining) {
            $this->bumpCursor($heightMm);
        } elseif ($this->shouldPlaceWithoutForceBreak()) {
            $this->bumpCursor($heightMm);
        } else {
            $classes[] = 'report-segment-force-break-before';
            $this->forceNextPage();
            $this->bumpCursor($heightMm);
        }

        return ['class' => trim(implode(' ', array_unique($classes))), 'style' => ''];
    }

    /**
     * Estimación O(n) para decidir keep/split en beginGrupo (sin armar segmentos).
     *
     * @param list<mixed> $items
     */
    private function estimateGrupoHeightFast(array $items, bool $withFirma): float
    {
        $rowCount       = 0;
        $subgrupoCount  = 0;
        $segmentCount   = 0;
        $segmentTitleCount = 0;
        $lastPriaId     = null;
        $hasRowsInSegment = false;

        foreach ($items as $raw) {
            $it = is_array($raw) ? (object) $raw : $raw;
            if ((int) ($it->es_separador ?? 0) === 1) {
                if ($hasRowsInSegment) {
                    $segmentCount++;
                    $hasRowsInSegment = false;
                }
                $segmentTitleCount++;
                continue;
            }

            $priaId = (int) ($it->prianacategoria_id ?? 0);
            if ($lastPriaId !== $priaId) {
                if ($hasRowsInSegment) {
                    $segmentCount++;
                    $hasRowsInSegment = false;
                }
                $subgrupoCount++;
                $lastPriaId = $priaId;
            }

            $val = trim((string) ($it->regvalues ?? ''));
            if (($val !== '' && $val !== '-') || ! empty($it->show_reference)) {
                $rowCount++;
                $hasRowsInSegment = true;
            }
        }

        if ($hasRowsInSegment) {
            $segmentCount++;
        }

        if ($subgrupoCount < 1) {
            $subgrupoCount = $rowCount > 0 ? 1 : 0;
        }
        if ($segmentCount < 1 && $rowCount > 0) {
            $segmentCount = 1;
        }

        $height = $this->areaSeparatorMm
            + max(0, $subgrupoCount - 1) * 5.0
            + ($subgrupoCount * $this->subgrupoCabeceraMm)
            + ($segmentCount * $this->theadHeightMm)
            + ($rowCount * $this->rowHeightMm)
            + ($segmentTitleCount * $this->segmentTitleHeightMm);

        if ($withFirma) {
            $height += $this->firmaHeightMm;
        }

        return $height;
    }

    private function estimateSegmentHeightMm(int $visibleRowCount, bool $hasSegmentTitle, bool $countSubgrupoCabecera): float
    {
        if ($visibleRowCount <= 0) {
            return 0.0;
        }
        $height = 0.0;
        if ($countSubgrupoCabecera) {
            $height += $this->subgrupoCabeceraMm;
        }
        if ($hasSegmentTitle) {
            $height += $this->segmentTitleHeightMm;
        }
        $height += $this->theadHeightMm + ($visibleRowCount * $this->rowHeightMm);

        return $height;
    }

    /**
     * @return list<string>
     */
    private function placeWithFirma(float $unitHeightMm): array
    {
        $classes = [];
        $firmaH  = $this->firmaHeightMm;
        $bloque  = $unitHeightMm + $firmaH;
        $remaining = $this->espacioRestanteMm();

        if ($bloque > $this->maxSliceMm) {
            $this->bumpCursor($unitHeightMm);
        } elseif ($bloque <= $remaining) {
            $this->bumpCursor($bloque);
        } elseif ($this->shouldPlaceWithoutForceBreak()) {
            $this->bumpCursor($bloque);
        } else {
            $classes[] = 'report-segment-force-break-before';
            $this->forceNextPage();
            $this->bumpCursor($bloque);
        }

        return $classes;
    }

    private function espacioRestanteMm(): float
    {
        return max(0.0, $this->pageEndMm($this->cursorPage) - $this->cursorY);
    }

    /**
     * Equivalente a atPageResultsStart() del JS: ya estamos al inicio útil de la hoja;
     * no aplicar break-before (Dompdf generaría una hoja en blanco).
     */
    private function shouldPlaceWithoutForceBreak(): bool
    {
        if ($this->grupoOnFreshPage) {
            $this->grupoOnFreshPage = false;

            return true;
        }

        return $this->atPageStart();
    }

    private function atPageStart(): bool
    {
        $epsilon   = 2.0;
        $pageStart = $this->pageStartMm($this->cursorPage);

        if ($this->cursorPage === 0) {
            return $this->cursorY <= $this->contentStartMm + $this->areaSeparatorMm + $epsilon;
        }

        return $this->cursorY <= $pageStart + $this->areaSeparatorMm + $epsilon;
    }

    private function pageEndMm(int $pageIndex): float
    {
        if ($pageIndex <= 0) {
            return $this->basePageContentMm;
        }

        return $this->pageStartMm($pageIndex) + $this->basePageContentMm;
    }

    private function pageStartMm(int $pageIndex): float
    {
        if ($pageIndex <= 0) {
            return 0.0;
        }

        return $this->basePageContentMm * $pageIndex;
    }

    private function forceNextPage(): void
    {
        $this->cursorPage++;
        $this->cursorY = $this->pageStartMm($this->cursorPage);
    }

    private function bumpCursor(float $heightMm): void
    {
        $left = max(0.0, $heightMm);
        $guard = 0;
        while ($left > 0.01 && $guard < 200) {
            $remaining = $this->espacioRestanteMm();
            if ($remaining <= 0.01) {
                $this->forceNextPage();
                $guard++;
                continue;
            }
            if ($left <= $remaining) {
                $this->cursorY += $left;
                $left = 0.0;
            } else {
                $left -= $remaining;
                $this->forceNextPage();
            }
            $guard++;
        }
    }
}
