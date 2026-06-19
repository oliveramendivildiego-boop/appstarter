<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Paginación de grupos para descarga PDF (Dompdf no ejecuta JS).
 * Réplica de applyGrupoInterPageBreaks + applyBrowserPrintGrupoIntactPageBreaks
 * + applyAnalysisUnitPageBreaks del script de impresión.
 */
class ReportPdfDompdfGrupoPageBreakService
{
    private const PAGE_HEIGHT_MM = 279.4;

    /** Dompdf: factor sobre altura teórica (cabecera + tabla + márgenes). */
    private const DOMPDF_PLACEMENT_HEIGHT_RATIO = 0.72;

    /** Margen de seguridad al decidir si un subgrupo pequeño cabe (evita huecos por subestimar). */
    private const SUBGRUPO_KEEP_INTACT_PLACEMENT_RATIO = 1.12;

    private float $dompdfSubgrupoGapMm = 0.0;

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

    private bool $grupoHasFirma = false;

    private bool $usesIfFitsMode = false;

    private bool $usesGrupoIntactMode = false;

    private bool $usesCompactMode = false;

    private bool $applyCompactActive = false;

    private float $compactScaleRatio = 1.0;

    private int $placementIdx = 0;

    /** @var list<array{segment_class: string, cabecera_class: string, subgrupo_class: string}> */
    private array $placementPlan = [];

    /** @var array<int, list<array<string, mixed>>> */
    private array $refsConsolidada = [];

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
        $this->usesGrupoIntactMode = ReportPdfLayoutService::grupoPruebaPageBreakUsesGrupoIntactCss($this->gpb);
        $this->usesCompactMode     = ReportPdfLayoutService::grupoPruebaUsesCompactMode($this->layout);
        $this->compactScaleRatio   = max(75, min(100, (int) ($this->gpb['compact_min_scale_percent'] ?? 85))) / 100;

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

        $fontPt     = max(6.0, (float) ($rs['font_size_pt'] ?? 9.0));
        $lineHeight = max(1.0, (float) ($rs['line_height'] ?? 1.35));
        $cellPadPx  = max(0, (int) ($rs['cell_padding_v_px'] ?? 6));
        $cellPadMm  = $cellPadPx * 25.4 / 96;

        $this->rowHeightMm          = ($fontPt * $lineHeight * 25.4 / 72) + ($cellPadMm * 2);
        $this->theadHeightMm        = $this->rowHeightMm + 1.5;
        $this->segmentTitleHeightMm = $fontPt * 1.2 * 25.4 / 72 + 3.0;
        $this->subgrupoCabeceraMm   = 14.0;
        $this->areaSeparatorMm      = max(4.0, (int) ($rs['grupo_area_separator_margin_top_px'] ?? 10) * 25.4 / 96)
            + max(4.0, (int) ($rs['grupo_area_separator_margin_bottom_px'] ?? 10) * 25.4 / 96) + 6.0;
        $this->dompdfSubgrupoGapMm  = max(0.0, (int) ($rs['grupo_prueba_gap_px'] ?? 10) * 25.4 / 96);

        $sealMm = (int) ($lf['seal_max_height_px'] ?? 110) * 25.4 / 96;
        $sigMm  = (int) ($lf['signature_max_height_px'] ?? 72) * 25.4 / 96;
        $marginFirmaMm = ((float) ($lf['inline_margin_top_pt'] ?? 8) + (float) ($lf['inline_margin_bottom_pt'] ?? 6)) * 25.4 / 72;
        $this->firmaHeightMm = $marginFirmaMm + max($sealMm, $sigMm) + 18.0;

        $this->contentStartMm = max(0.0, $contentStartMm);
        $this->cursorY        = $this->contentStartMm;
    }

    /** @param array<int, list<array<string, mixed>>> $refs */
    public function setRefsConsolidada(array $refs): void
    {
        $this->refsConsolidada = $refs;
    }

    public function isActive(): bool
    {
        return $this->usesIfFitsMode || $this->usesGrupoIntactMode;
    }

    /**
     * @param list<mixed> $items
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
    public function beginGrupo(bool $isFirstGrupo, array $items, bool $hasFirma): array
    {
        $this->grupoHasFirma      = $hasFirma;
        $this->grupoOnFreshPage   = false;
        $this->applyCompactActive = false;
        $this->placementIdx       = 0;
        $this->placementPlan      = [];

        $classes      = [];
        $grupoStyle   = '';
        $interBreak   = false;
        $applyCompact = false;

        if (! $this->usesGrupoIntactMode && ! $this->usesIfFitsMode) {
            $this->bumpCursor($this->areaSeparatorMm);

            return $this->buildResult($classes, $interBreak, $grupoStyle, false);
        }

        $height = $this->estimateGrupoHeightFast($items, $hasFirma, false);

        if (! $isFirstGrupo && ! $this->usesGrupoIntactMode) {
            $headMin = $this->estimateGrupoHeadMinHeight($items);
            if ($headMin > 0 && $headMin <= $this->maxSliceMm && $headMin > $this->espacioRestanteMm()) {
                $this->forceNextPage();
                $this->grupoOnFreshPage = true;
            }
        }

        if ($this->usesGrupoIntactMode) {
            if (! $isFirstGrupo) {
                $headMin = $this->estimateGrupoHeadMinHeight($items);
                if ($headMin > 0 && $headMin <= $this->maxSliceMm && $headMin > $this->espacioRestanteMm()) {
                    $this->forceNextPage();
                    $this->grupoOnFreshPage = true;
                    $interBreak = true;
                    $grupoStyle = 'page-break-before:avoid;break-before:avoid;margin-top:0;padding-top:0;';
                    $classes[]  = 'report-pdf-grupo-prueba-new-page-start';
                }
            }

            $this->bumpCursor($this->areaSeparatorMm);
            $remaining = $this->espacioRestanteMm();

            if ($height <= $remaining && $height <= $this->maxSliceMm) {
                $classes[] = 'report-pdf-grupo-prueba-keep-on-page';
            } elseif ($height <= $this->maxSliceMm) {
                $classes[] = 'report-pdf-grupo-prueba-keep-on-page';
                $classes[] = 'report-pdf-grupo-prueba-split-segments-only';
            } else {
                $classes[] = 'report-pdf-grupo-prueba-split-segments-only';
            }

            $this->placementPlan = $this->buildPlacementPlan($items);

            return $this->buildResult($classes, $interBreak, $grupoStyle, false);
        }

        $remaining = $this->espacioRestanteMm();
        $effective = $height;
        if ($this->usesCompactMode && $height > $remaining) {
            $applyCompact           = true;
            $this->applyCompactActive = true;
            $effective              = $height * $this->compactScaleRatio;
        }

        if ($effective > $this->maxSliceMm) {
            $classes[] = 'report-pdf-grupo-prueba-allow-split';
        } elseif ($effective <= $remaining) {
            $classes[] = 'report-pdf-grupo-prueba-keep-on-page';
            $this->bumpCursor($effective);
        } else {
            $classes[] = 'report-pdf-grupo-prueba-allow-split';
        }

        if ($interBreak) {
            $classes = array_values(array_filter(
                $classes,
                static fn (string $c): bool => $c !== 'report-pdf-grupo-prueba-allow-split'
            ));
            $fitHeight = $applyCompact ? $height * $this->compactScaleRatio : $height;
            if ($fitHeight > $this->maxSliceMm) {
                $classes[] = 'report-pdf-grupo-prueba-split-segments-only';
            } else {
                $classes[] = 'report-pdf-grupo-prueba-keep-on-page';
                $this->bumpCursor($fitHeight);
            }
        }

        $this->placementPlan = $this->buildPlacementPlan($items);

        return $this->buildResult($classes, $interBreak, $grupoStyle, $applyCompact);
    }

    /**
     * @return array{segment_class: string, cabecera_class: string, subgrupo_class: string}
     */
    public function peekNextPlacementAttrs(): array
    {
        $empty = ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => ''];
        if ($this->placementIdx >= count($this->placementPlan)) {
            return $empty;
        }

        return $this->placementPlan[$this->placementIdx];
    }

    /**
     * @return array{segment_class: string, cabecera_class: string, subgrupo_class: string}
     */
    public function consumePlacementAttrs(): array
    {
        $empty = ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => ''];
        if ($this->placementIdx >= count($this->placementPlan)) {
            return $empty;
        }

        return $this->placementPlan[$this->placementIdx++];
    }

    /**
     * @param list<string> $classes
     *
     * @return array{classes: string, inter_break: bool, area_page_leader: bool, grupo_style: string, separator_new_page: bool, apply_compact: bool}
     */
    private function buildResult(array $classes, bool $interBreak, string $grupoStyle, bool $applyCompact): array
    {
        return [
            'classes'            => trim(implode(' ', array_unique($classes))),
            'inter_break'        => $interBreak,
            'area_page_leader'   => $interBreak,
            'grupo_style'        => $grupoStyle,
            'separator_new_page' => $interBreak,
            'apply_compact'      => $applyCompact,
        ];
    }

    /**
     * @param list<mixed> $items
     *
     * @return list<array{segment_class: string, cabecera_class: string, subgrupo_class: string}>
     */
    private function buildPlacementPlan(array $items): array
    {
        if (! $this->usesGrupoIntactMode && ! $this->usesIfFitsMode) {
            return [];
        }

        $savedFresh       = $this->grupoOnFreshPage;
        $lastSubgrupoKey  = -1;
        $cabeceraCounted  = false;
        $plan             = [];
        $units            = iterator_to_array($this->iterVisiblePlacementUnits($items));
        $total            = count($units);
        $keepIntactHandled = [];

        foreach ($units as $idx => $unit) {
            $subgrupoKey = (int) ($unit['subgrupo_key'] ?? -1);
            $keepIntact  = ReportPdfLayoutService::subgrupoShouldKeepIntact($units, $subgrupoKey);

            if ($keepIntact) {
                if (isset($keepIntactHandled[$subgrupoKey])) {
                    $plan[] = $this->emptyPlacementAttrs();
                    continue;
                }
                $keepIntactHandled[$subgrupoKey] = true;
                $subgrupoH = $this->estimateSubgrupoHeightMm($units, $subgrupoKey);
                $lastUnit  = $units[$total - 1] ?? $unit;
                $isLastWithFirma = $this->grupoHasFirma
                    && (int) ($lastUnit['subgrupo_key'] ?? -1) === $subgrupoKey;
                $plan[] = $this->placeSmallSubgrupoUnit($subgrupoH, $isLastWithFirma);
                continue;
            }

            $isLast = ($idx === $total - 1) && $this->grupoHasFirma;
            $countCabecera = false;
            if ($unit['subgrupo_key'] !== $lastSubgrupoKey) {
                $lastSubgrupoKey = $unit['subgrupo_key'];
                $cabeceraCounted = false;
            }
            if (! $cabeceraCounted) {
                $countCabecera   = true;
                $cabeceraCounted = true;
            }

            $unitH = $this->estimateUnitHeightMm(
                $unit['rows'],
                $unit['has_title'],
                $countCabecera,
                $unit['is_matrix']
            );
            $plan[] = $isLast
                ? $this->placeLastUnitWithFirma($unitH, $countCabecera)
                : $this->placeSegmentUnit($unitH, $countCabecera);
        }

        $this->grupoOnFreshPage = false;

        return $this->consolidateTailBreaks($plan, $units);
    }

    /**
     * Consolida saltos del bloque final del grupo en un único ancla (p. ej. GGT + FOSFATASA + firma).
     *
     * @param list<array{segment_class: string, cabecera_class: string, subgrupo_class: string}> $plan
     * @param list<array{rows: int, has_title: bool, subgrupo_key: int, is_matrix: bool}>       $units
     *
     * @return list<array{segment_class: string, cabecera_class: string, subgrupo_class: string}>
     */
    private function consolidateTailBreaks(array $plan, array $units): array
    {
        if ($plan === [] || $units === []) {
            return $plan;
        }

        $firstIdxByKey = [];
        $orderedKeys   = [];
        $seenKeys      = [];
        foreach ($units as $idx => $unit) {
            $sk = (int) ($unit['subgrupo_key'] ?? -1);
            if (! isset($firstIdxByKey[$sk])) {
                $firstIdxByKey[$sk] = $idx;
            }
            if (! isset($seenKeys[$sk])) {
                $orderedKeys[] = $sk;
                $seenKeys[$sk]  = true;
            }
        }

        $keyOrder = array_flip($orderedKeys);
        $anchorSk = $this->tailBreakAnchorSubgrupoKey($units, $orderedKeys);

        if ($anchorSk === null) {
            return $this->pullKeepIntactBreakOneStep($plan, $units, $firstIdxByKey, $orderedKeys, $keyOrder);
        }

        $anchorIdx    = $firstIdxByKey[$anchorSk];
        $anchorOrder  = $keyOrder[$anchorSk];
        $needsAnchor  = false;

        foreach ($units as $idx => $unit) {
            if (($plan[$idx]['subgrupo_class'] ?? '') !== 'report-subgrupo-force-break-before') {
                continue;
            }

            $sk  = (int) ($unit['subgrupo_key'] ?? -1);
            $pos = $keyOrder[$sk] ?? -1;
            if ($pos >= $anchorOrder) {
                $needsAnchor = true;
                $plan[$idx]['subgrupo_class'] = '';
                $plan[$idx]['cabecera_class'] = '';
            }
        }

        if ($needsAnchor && ($plan[$anchorIdx]['subgrupo_class'] ?? '') !== 'report-subgrupo-force-break-before') {
            $plan[$anchorIdx]['subgrupo_class'] = 'report-subgrupo-force-break-before';
            $plan[$anchorIdx]['cabecera_class'] = 'report-cabecera-force-break-before';
        }

        return $plan;
    }

    /**
     * @param list<int> $orderedKeys
     * @param array<int, int> $keyOrder
     * @param array<int, int> $firstIdxByKey
     *
     * @param list<array{segment_class: string, cabecera_class: string, subgrupo_class: string}> $plan
     * @param list<array{rows: int, has_title: bool, subgrupo_key: int, is_matrix: bool}>       $units
     *
     * @return list<array{segment_class: string, cabecera_class: string, subgrupo_class: string}>
     */
    private function pullKeepIntactBreakOneStep(
        array $plan,
        array $units,
        array $firstIdxByKey,
        array $orderedKeys,
        array $keyOrder
    ): array {
        foreach ($units as $idx => $unit) {
            if (($plan[$idx]['subgrupo_class'] ?? '') !== 'report-subgrupo-force-break-before') {
                continue;
            }

            $sk  = (int) ($unit['subgrupo_key'] ?? -1);
            $pos = $keyOrder[$sk] ?? -1;
            if ($pos <= 0) {
                continue;
            }

            $prevSk = $orderedKeys[$pos - 1];
            if (! ReportPdfLayoutService::subgrupoShouldKeepIntact($units, $prevSk)) {
                continue;
            }

            $prevIdx = $firstIdxByKey[$prevSk];
            if (($plan[$prevIdx]['subgrupo_class'] ?? '') !== 'report-subgrupo-force-break-before') {
                $plan[$prevIdx]['subgrupo_class'] = 'report-subgrupo-force-break-before';
                $plan[$prevIdx]['cabecera_class'] = 'report-cabecera-force-break-before';
            }

            $plan[$idx]['subgrupo_class'] = '';
            $plan[$idx]['cabecera_class'] = '';
        }

        return $plan;
    }

    /**
     * @param list<array{rows: int, has_title: bool, subgrupo_key: int, is_matrix: bool}> $units
     * @param list<int>                                                                     $orderedKeys
     */
    private function tailBreakAnchorSubgrupoKey(array $units, array $orderedKeys): ?int
    {
        $keepIntactKeys = [];
        foreach ($orderedKeys as $sk) {
            if (ReportPdfLayoutService::subgrupoShouldKeepIntact($units, $sk)) {
                $keepIntactKeys[] = $sk;
            }
        }

        if ($keepIntactKeys === []) {
            return null;
        }

        $lastSk = $orderedKeys[count($orderedKeys) - 1] ?? null;
        if ($lastSk === null) {
            return $keepIntactKeys[count($keepIntactKeys) - 1];
        }

        if (! $this->grupoHasFirma || ! ReportPdfLayoutService::subgrupoShouldKeepIntact($units, $lastSk)) {
            return $keepIntactKeys[count($keepIntactKeys) - 1];
        }

        $idx = array_search($lastSk, $keepIntactKeys, true);
        if ($idx === false) {
            return $keepIntactKeys[count($keepIntactKeys) - 1];
        }
        if ($idx === 0) {
            return $keepIntactKeys[0];
        }

        return $keepIntactKeys[$idx - 1];
    }

    /**
     * @param list<mixed> $items
     *
     * @return \Generator<int, array{rows: int, has_title: bool, subgrupo_key: int, is_matrix: bool}>
     */
    private function iterVisiblePlacementUnits(array $items): \Generator
    {
        $subgrupos = [];
        $orden     = [];
        foreach ($items as $raw) {
            $it  = is_array($raw) ? (object) $raw : $raw;
            $pid = (int) ($it->prianacategoria_id ?? 0);
            if (! isset($subgrupos[$pid])) {
                $subgrupos[$pid] = [];
                $orden[]         = $pid;
            }
            $subgrupos[$pid][] = $raw;
        }

        foreach ($orden as $subIdx => $priaKey) {
            $subItems = $subgrupos[$priaKey];
            $segments = [];
            $cur      = ['title' => null, 'items' => []];
            foreach ($subItems as $raw) {
                $it = is_array($raw) ? (object) $raw : $raw;
                if ((int) ($it->es_separador ?? 0) === 1) {
                    $segments[] = $cur;
                    $cur        = ['title' => $it, 'items' => []];
                    continue;
                }
                $cur['items'][] = $it;
            }
            $segments[] = $cur;
            $segments   = array_values(array_filter($segments, static function ($s) {
                return $s['title'] !== null || $s['items'] !== [];
            }));

            foreach ($segments as $seg) {
                $rows = 0;
                foreach ($seg['items'] as $rawIt) {
                    $it = is_array($rawIt) ? (object) $rawIt : $rawIt;
                    $v  = trim((string) ($it->regvalues ?? ''));
                    if (($v !== '' && $v !== '-') || ! empty($it->show_reference)) {
                        $rows++;
                    }
                }
                if ($rows > 0) {
                    yield [
                        'rows'         => $rows,
                        'has_title'    => $seg['title'] !== null,
                        'subgrupo_key' => $subIdx,
                        'is_matrix'    => false,
                    ];
                }
            }

            $priaId = (int) $priaKey;
            if ($priaId > 0 && ! empty($this->refsConsolidada[$priaId])) {
                $matrixRows = 0;
                foreach ($this->refsConsolidada[$priaId] as $mrow) {
                    $mrow = is_array($mrow) ? $mrow : [];
                    if (function_exists('registro_tiene_rango_referencial')
                        && registro_tiene_rango_referencial($mrow['valor_min'] ?? '', $mrow['valor_max'] ?? '')) {
                        $matrixRows++;
                    }
                }
                if ($matrixRows > 0) {
                    yield [
                        'rows'         => $matrixRows,
                        'has_title'    => true,
                        'subgrupo_key' => $subIdx,
                        'is_matrix'    => true,
                    ];
                }
            }
        }
    }

    /**
     * @return array{segment_class: string, cabecera_class: string, subgrupo_class: string}
     */
    private function emptyPlacementAttrs(): array
    {
        return ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => ''];
    }

    /**
     * @param list<array{rows: int, has_title: bool, subgrupo_key: int, is_matrix: bool}> $units
     */
    private function estimateSubgrupoHeightMm(array $units, int $subgrupoKey): float
    {
        $height          = 0.0;
        $cabeceraCounted = false;
        foreach ($units as $unit) {
            if ((int) ($unit['subgrupo_key'] ?? -1) !== $subgrupoKey) {
                continue;
            }
            $countCabecera = ! $cabeceraCounted;
            if ($countCabecera) {
                $cabeceraCounted = true;
            }
            $height += $this->estimateUnitHeightMm(
                (int) ($unit['rows'] ?? 0),
                ! empty($unit['has_title']),
                $countCabecera,
                ! empty($unit['is_matrix']),
                false
            );
        }

        return round($height * self::SUBGRUPO_KEEP_INTACT_PLACEMENT_RATIO, 2);
    }

    /**
     * Subgrupo pequeño (≤4 filas): no partir; mover bloque completo a la página siguiente si no cabe.
     *
     * @return array{segment_class: string, cabecera_class: string, subgrupo_class: string}
     */
    private function placeSmallSubgrupoUnit(float $subgrupoH, bool $isLastWithFirma): array
    {
        $classes = $this->emptyPlacementAttrs();
        if ($subgrupoH <= 0) {
            return $classes;
        }

        $blockH    = $isLastWithFirma ? $subgrupoH + $this->firmaHeightMm : $subgrupoH;
        $remaining = $this->espacioRestanteMm();

        if ($blockH > $this->maxSliceMm) {
            $classes['segment_class'] = 'report-segment-allow-split';
            $this->bumpCursor($subgrupoH);

            return $classes;
        }
        if ($blockH <= $remaining) {
            $this->bumpCursor($blockH);

            return $classes;
        }
        if ($this->atPageStart()) {
            $this->bumpCursor($blockH);

            return $classes;
        }

        $classes['subgrupo_class'] = 'report-subgrupo-force-break-before';
        $this->forceNextPage();
        $this->bumpCursor($blockH);

        return $classes;
    }

    /**
     * @return array{segment_class: string, cabecera_class: string, subgrupo_class: string}
     */
    private function placeSegmentUnit(float $unitH, bool $countCabecera): array
    {
        $classes = ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => ''];
        if ($unitH <= 0) {
            return $classes;
        }

        $remaining = $this->espacioRestanteMm();
        if ($unitH > $this->maxSliceMm) {
            $classes['segment_class'] = 'report-segment-allow-split';
            $this->bumpCursor($unitH);
        } elseif ($unitH <= $remaining) {
            $this->bumpCursor($unitH);
        } elseif ($this->atPageStart()) {
            $classes['segment_class'] = 'report-segment-allow-split';
            $this->bumpCursor($unitH);
        } else {
            if ($countCabecera) {
                $classes['subgrupo_class'] = 'report-subgrupo-force-break-before';
            } else {
                $classes['segment_class'] = 'report-segment-force-break-before';
            }
            $this->forceNextPage();
            $this->bumpCursor($unitH);
            if ($unitH > $this->espacioRestanteMm() || $unitH > $this->maxSliceMm) {
                $classes['segment_class'] = trim($classes['segment_class'] . ' report-segment-allow-split');
            }
        }

        return $classes;
    }

    /**
     * @return array{segment_class: string, cabecera_class: string, subgrupo_class: string}
     */
    private function placeLastUnitWithFirma(float $unitH, bool $countCabecera): array
    {
        $classes   = ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => ''];
        $bloque    = $unitH + $this->firmaHeightMm;
        // Dompdf dibuja la firma algo más alta que la estimación: solo para decidir el salto.
        $bloqueCheck = $unitH + ($this->firmaHeightMm * 1.08);
        $remaining = $this->espacioRestanteMm();

        if ($bloque > $this->maxSliceMm) {
            $this->bumpCursor($unitH);

            return $classes;
        }
        if ($bloqueCheck <= $remaining) {
            $this->bumpCursor($bloque);

            return $classes;
        }
        if ($countCabecera) {
            $classes['subgrupo_class'] = 'report-subgrupo-force-break-before';
        } else {
            $classes['segment_class'] = 'report-segment-force-break-before';
        }
        $this->forceNextPage();
        $this->bumpCursor($bloque);

        return $classes;
    }

    private function estimateUnitHeightMm(
        int $visibleRowCount,
        bool $hasSegmentTitle,
        bool $countCabecera,
        bool $isRefsMatrix,
        bool $applyDompdfRatio = true
    ): float {
        if ($visibleRowCount <= 0 && ! $hasSegmentTitle && ! $countCabecera) {
            return 0.0;
        }

        $scale = ($this->applyCompactActive || $this->usesCompactMode) ? $this->compactScaleRatio : 1.0;
        $rowH  = $this->rowHeightMm * $scale;
        $thead = $this->theadHeightMm * $scale;
        $title = $this->segmentTitleHeightMm * $scale;
        $cab   = $this->subgrupoCabeceraMm * $scale;

        $height = 0.0;
        if ($countCabecera) {
            $height += $cab + ($this->dompdfSubgrupoGapMm * $scale);
        }
        if ($hasSegmentTitle) {
            $height += $title;
        }
        if ($isRefsMatrix) {
            $height += $thead + ($visibleRowCount * $rowH) + (4.0 * $scale);
        } else {
            $height += $thead + ($visibleRowCount * $rowH);
        }

        if (! $applyDompdfRatio) {
            return round($height, 2);
        }

        return round($height * self::DOMPDF_PLACEMENT_HEIGHT_RATIO, 2);
    }

    /**
     * @param list<mixed> $items
     */
    private function estimateGrupoHeadMinHeight(array $items): float
    {
        $height = $this->areaSeparatorMm;
        foreach ($this->iterVisiblePlacementUnits($items) as $unit) {
            $height += $this->estimateUnitHeightMm($unit['rows'], $unit['has_title'], true, $unit['is_matrix']);
            break;
        }

        return $height;
    }

    /**
     * @param list<mixed> $items
     */
    private function estimateGrupoHeightFast(array $items, bool $withFirma, bool $withCompact): float
    {
        $scale  = ($withCompact && $this->usesCompactMode) ? $this->compactScaleRatio : 1.0;
        $rowH   = $this->rowHeightMm * $scale;
        $thead  = $this->theadHeightMm * $scale;
        $titleH = $this->segmentTitleHeightMm * $scale;
        $cabH   = $this->subgrupoCabeceraMm * $scale;

        $rowCount = $segmentCount = $segmentTitleCount = $subgrupoCount = $matrixCount = 0;
        $lastPriaId = null;
        $hasRowsInSegment = false;
        $priaWithMatrix = [];

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
                if ($priaId > 0 && ! empty($this->refsConsolidada[$priaId])) {
                    $priaWithMatrix[$priaId] = true;
                }
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
        if ($subgrupoCount < 1 && $rowCount > 0) {
            $subgrupoCount = 1;
        }
        if ($segmentCount < 1 && $rowCount > 0) {
            $segmentCount = 1;
        }
        foreach ($priaWithMatrix as $priaId => $_) {
            $matrixRows = 0;
            foreach ($this->refsConsolidada[$priaId] as $mrow) {
                $mrow = is_array($mrow) ? $mrow : [];
                if (function_exists('registro_tiene_rango_referencial')
                    && registro_tiene_rango_referencial($mrow['valor_min'] ?? '', $mrow['valor_max'] ?? '')) {
                    $matrixRows++;
                }
            }
            if ($matrixRows > 0) {
                $matrixCount++;
            }
        }

        $height = $this->areaSeparatorMm
            + max(0, $subgrupoCount - 1) * (5.0 * $scale)
            + ($subgrupoCount * $cabH)
            + ($segmentCount * $thead)
            + ($rowCount * $rowH)
            + ($segmentTitleCount * $titleH);

        foreach ($priaWithMatrix as $priaId => $_) {
            $matrixRows = 0;
            foreach ($this->refsConsolidada[$priaId] as $mrow) {
                $mrow = is_array($mrow) ? $mrow : [];
                if (function_exists('registro_tiene_rango_referencial')
                    && registro_tiene_rango_referencial($mrow['valor_min'] ?? '', $mrow['valor_max'] ?? '')) {
                    $matrixRows++;
                }
            }
            if ($matrixRows > 0) {
                $height += $thead + ($matrixRows * $rowH) + (4.0 * $scale);
            }
        }

        if ($withFirma) {
            $height += $this->firmaHeightMm;
        }

        return $height;
    }

    private function espacioRestanteMm(): float
    {
        return max(0.0, $this->pageEndMm($this->cursorPage) - $this->cursorY);
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
        $left  = max(0.0, $heightMm);
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
