<?php
/**
 * Prueba compuesta: bloques consecutivos. Orden: título separador → cabecera de tabla → filas.
 * Si hay varias pruebas (prianacategoria) bajo el mismo área (padre), cada una lleva su propio
 * título, tipo de muestra, método y tablas de resultados.
 *
 * @var string $padre
 * @var list<object|array<string,mixed>> $items
 * @var string $variant 'web' | 'pdf' | 'screen_pdf' | 'browser_print' (PDF chrome en pdf/screen_pdf/browser_print)
 * @var array<int,string> $report_pria_tipo_muestra_nombre prianacategoria_id => nombre (config. en análisis clínico)
 * @var array<int,string> $report_pria_metodo_nombre prianacategoria_id => nombre del método (config.)
 * @var array<int,list<array<string,mixed>>> $report_pria_refs_consolidada tabla consolidada de refs. por población (pruebas compuestas)
 */
$variant = $variant ?? 'web';
$usePdfChrome = in_array($variant, ['pdf', 'screen_pdf', 'browser_print'], true);
/** @var \App\Services\ReportLayout\LayoutPlanApplier|null $layoutPlanApplier */
$layoutPlanApplier = ($layout_plan_applier ?? null) instanceof \App\Services\ReportLayout\LayoutPlanApplier
    ? $layout_plan_applier
    : null;
$layoutAreaIndex = (int) ($layout_area_index ?? 0);
$layoutBlockIndex = 0;
$segmentWrapStyle = '';
if ($usePdfChrome && $variant === 'browser_print') {
    $segmentWrapStyle = \App\Services\ReportPdfLayoutService::grupoPruebaSegmentIntactStyleAttr(
        is_array($pdf_layout ?? null) ? $pdf_layout : []
    );
}
$segmentWrapStyleAttr = $segmentWrapStyle !== '' ? ' style="' . esc($segmentWrapStyle, 'attr') . '"' : '';
$nombresTipoPorPria = $report_pria_tipo_muestra_nombre ?? [];
$nombresMetodoPorPria = $report_pria_metodo_nombre ?? [];
$refsMatrixAll = $report_pria_refs_consolidada ?? [];
$pdfLayout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$resultsCellMarkupAttrs = static function (string $column, bool $isHeader, string $widthStyle = '', string $extraColorCss = '') use ($pdfLayout, $usePdfChrome): string {
    if (! $usePdfChrome) {
        return $widthStyle;
    }
    $extra = '';
    if ($widthStyle !== '' && preg_match('/style="([^"]*)"/', $widthStyle, $m)) {
        $extra = rtrim((string) ($m[1] ?? ''), ';');
    }
    if ($extraColorCss !== '') {
        $extra = trim($extra . ';' . $extraColorCss, ';');
    }

    return \App\Services\ReportPdfLayoutService::resultsColumnCellMarkupAttrs($pdfLayout, $column, $isHeader, $extra);
};
$resultsColAlignClass = static function (string $column, bool $isHeader) use ($pdfLayout, $usePdfChrome): string {
    if (! $usePdfChrome) {
        return '';
    }
    $cls = \App\Services\ReportPdfLayoutService::resultsColumnAlignClass($pdfLayout, $column, $isHeader);

    return $cls !== '' ? ' ' . esc($cls, 'attr') : '';
};
$ocultarTheadResults = \App\Services\ReportPdfLayoutService::grupoCabeceraOcultarTheadResultsTabla($pdfLayout);
$labConfigLocal = is_array($lab_config ?? null) ? $lab_config : [];
if (! array_key_exists('sin_doctor_show_interpretation', $labConfigLocal)) {
    try {
        $labConfigLocal = array_merge((new \App\Services\ConfigService())->getAllAsArray(), $labConfigLocal);
    } catch (\Throwable $e) {
        // Mantener lab_config parcial si falla la carga.
    }
}
$showInterpretacionCol = false;
if (in_array($variant, ['screen_pdf', 'pdf', 'browser_print'], true)) {
    $showInterpretacionCol = registro_doctor_mostrar_interpretacion_col($doctor ?? null, $labConfigLocal);
}

$subgruposPorPria = [];
$ordenPriaKeys = [];
foreach ($items as $raw) {
    $it = is_array($raw) ? (object) $raw : $raw;
    $pid = (int) ($it->prianacategoria_id ?? 0);
    if (! isset($subgruposPorPria[$pid])) {
        $subgruposPorPria[$pid] = [];
        $ordenPriaKeys[] = $pid;
    }
    $subgruposPorPria[$pid][] = $raw;
}

foreach ($ordenPriaKeys as $subIdx => $priaKey) :
    $isLastSubgrupo = ($subIdx === count($ordenPriaKeys) - 1);
    $subItems = $subgruposPorPria[$priaKey];

    $cultivoItem = null;
    foreach ($subItems as $rawCultivo) {
        $itCultivo = is_array($rawCultivo) ? (object) $rawCultivo : $rawCultivo;
        if (! empty($itCultivo->es_cultivo_matriz)) {
            $cultivoItem = $itCultivo;
            break;
        }
    }
    if ($cultivoItem !== null) {
        $matrizReporteView = ! empty($cultivoItem->es_personalizado_matriz)
            ? 'registers/analisis/partials/personalizado_matriz_reporte'
            : 'registers/analisis/partials/cultivo_matriz_reporte';
        echo view($matrizReporteView, [
            'padre'                           => trim((string) ($cultivoItem->padre ?? $padre)),
            'hijo'                            => trim((string) ($cultivoItem->hijo ?? '')),
            'pria_id_titulo'                  => (int) ($cultivoItem->prianacategoria_id ?? 0),
            'cultivo_item'                    => $cultivoItem,
            'variant'                         => $variant,
            'pdf_layout'                      => $pdf_layout ?? [],
            'report_pria_tipo_muestra_nombre' => $nombresTipoPorPria,
            'report_pria_metodo_nombre'       => $nombresMetodoPorPria,
            'sub_idx'                         => $subIdx,
            'grupo_es_primero'                => ! empty($grupo_es_primero),
            'layout_plan_applier'             => $layoutPlanApplier,
            'layout_area_index'               => $layoutAreaIndex,
            'layout_block_index'              => $layoutBlockIndex,
            'is_last_subgrupo'                => $isLastSubgrupo,
            'area_firma_bundle'               => $isLastSubgrupo ? ($area_firma_bundle ?? null) : null,
            'grupo_area_inter_break'          => ! empty($grupo_area_inter_break) && $layoutBlockIndex === 0,
        ]);
        $layoutBlockIndex++;
        continue;
    }

    $hijo = '';
    foreach ($subItems as $rawHijo) {
        $itHijo = is_array($rawHijo) ? (object) $rawHijo : $rawHijo;
        if ((int) ($itHijo->es_separador ?? 0) === 1) {
            continue;
        }
        $h = trim((string) ($itHijo->hijo ?? ''));
        if ($h !== '') {
            $hijo = $h;
            break;
        }
    }
    if ($hijo === '' && ! empty($subItems[0])) {
        $first = $subItems[0];
        $firstObj = is_array($first) ? (object) $first : $first;
        $hijo = trim((string) ($firstObj->hijo ?? ''));
    }

    $priaIdTitulo = $priaKey > 0 ? $priaKey : 0;
    if ($priaIdTitulo < 1) {
        foreach ($subItems as $rawPria) {
            $op = is_array($rawPria) ? (object) $rawPria : $rawPria;
            $pid = (int) ($op->prianacategoria_id ?? 0);
            if ($pid > 0) {
                $priaIdTitulo = $pid;
                break;
            }
        }
    }

    $tipoMuestraLinea = trim((string) ($nombresTipoPorPria[$priaIdTitulo] ?? ''));
    $metodoLinea = trim((string) ($nombresMetodoPorPria[$priaIdTitulo] ?? ''));

    $segments = [];
    $cur = ['title' => null, 'items' => []];
    foreach ($subItems as $raw) {
        $it = is_array($raw) ? (object) $raw : $raw;
        if ((int) ($it->es_separador ?? 0) === 1) {
            $segments[] = $cur;
            $cur = ['title' => $it, 'items' => []];

            continue;
        }
        $cur['items'][] = $it;
    }
    $segments[] = $cur;
    $segments = array_values(array_filter($segments, static function ($s) {
        return $s['title'] !== null || $s['items'] !== [];
    }));

    $groupTieneAlgunResultado = false;
    $subgrupoTotalFilas = 0;
    foreach ($segments as $segTmp) {
        foreach ($segTmp['items'] as $rawIt) {
            $itTmp = is_array($rawIt) ? (object) $rawIt : $rawIt;
            $vTmp = trim((string) ($itTmp->regvalues ?? ''));
            if (($vTmp !== '' && $vTmp !== '-') || ! empty($itTmp->show_reference)) {
                $groupTieneAlgunResultado = true;
                $subgrupoTotalFilas++;
            }
        }
    }
    if ($priaIdTitulo > 0 && ! empty($refsMatrixAll[$priaIdTitulo])) {
        foreach ($refsMatrixAll[$priaIdTitulo] as $mrowTmp) {
            $mrowTmp = is_array($mrowTmp) ? $mrowTmp : [];
            if (registro_tiene_rango_referencial($mrowTmp['valor_min'] ?? '', $mrowTmp['valor_max'] ?? '')) {
                $subgrupoTotalFilas++;
            }
        }
    }

    if (! $groupTieneAlgunResultado) {
        continue;
    }

    $subgrupoKeepIntact = $usePdfChrome
        && $subgrupoTotalFilas > 0
        && $subgrupoTotalFilas <= \App\Services\ReportPdfLayoutService::subgrupoKeepIntactMaxRows()
        && (
            $layoutPlanApplier === null
            || \App\Services\ReportLayout\ReportPaginationMode::usesSubgrupoKeepIntactInHtml(
                $layoutPlanApplier->mode()
            )
        );

    $subgrupoWrapClass = $subIdx > 0 ? ' report-pdf-subgrupo-prueba' : '';
    $webTitleMt = $subIdx > 0 ? 'mt-5' : 'mt-4';
    $subgrupoGapStyle = '';
    if ($subIdx > 0) {
        $flowMode = $layoutPlanApplier !== null
            && \App\Services\ReportLayout\ReportPaginationMode::usesFlowContinuousPagination(
                $layoutPlanApplier->mode()
            );
        if (! $flowMode) {
            $subgrupoGapStyle = \App\Services\ReportPdfLayoutService::subgrupoPruebaGapStyleAttr(
                is_array($pdf_layout ?? null) ? $pdf_layout : [],
                true
            );
        }
    }
    $subgrupoPbPeek = $layoutPlanApplier
        ? $layoutPlanApplier->blockMarkers($layoutAreaIndex, $layoutBlockIndex)
        : ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => '', 'segment_classes' => []];
    $subgrupoPbClass = trim(
        'report-pdf-subgrupo-block'
        . $subgrupoWrapClass
        . ($subgrupoKeepIntact ? ' report-subgrupo-keep-intact' : '')
        . ($subgrupoPbPeek['subgrupo_class'] !== '' ? ' ' . $subgrupoPbPeek['subgrupo_class'] : '')
    );
    $cabeceraPbClass = trim('report-pdf-grupo-cabecera' . ($subgrupoPbPeek['cabecera_class'] !== '' ? ' ' . $subgrupoPbPeek['cabecera_class'] : ''));
    $subgrupoTailBundleAtStart = $usePdfChrome
        && $layoutPlanApplier !== null
        && $isLastSubgrupo
        && $layoutPlanApplier->shouldOpenSignatureTailBundleBeforeSubgrupo($layoutAreaIndex, $layoutBlockIndex);
?>
<?php if ($usePdfChrome): ?>
<?php if ($variant === 'browser_print' && $layoutPlanApplier !== null && $layoutPlanApplier->browserPrintPageBreakBeforeAnalysisBlock($layoutAreaIndex, $layoutBlockIndex)): ?>
<?= view('registers/partials/report_browser_print_plan_page_break', [
    'plan_page_index' => $layoutPlanApplier->analysisBlockPlanPageIndex($layoutAreaIndex, $layoutBlockIndex),
]) ?>
<?php endif; ?>
<div class="<?= esc($subgrupoPbClass, 'attr') ?>" data-layout-block-id="<?= esc(\App\Services\ReportLayout\ReportTreeBuilder::analysisBlockId($layoutAreaIndex, $layoutBlockIndex), 'attr') ?>"<?= $subgrupoGapStyle !== '' ? ' style="' . esc($subgrupoGapStyle, 'attr') . '"' : '' ?>>
<?php if ($subgrupoTailBundleAtStart): ?>
<?= $layoutPlanApplier->beginSignatureTailBundleMarkup() ?>
<?php endif; ?>
<div class="<?= esc($cabeceraPbClass, 'attr') ?>">
<?= view('registers/analisis/partials/report_grupo_cabecera_content', [
    'padre'              => $padre,
    'hijo'               => $hijo,
    'tipo_muestra_linea' => $tipoMuestraLinea,
    'metodo_linea'       => $metodoLinea,
    'variant'            => $variant,
    'pdf_layout'         => $pdf_layout ?? [],
    'web_title_mt'       => $webTitleMt,
    'sub_idx'            => $subIdx,
    'grupo_es_primero'   => ! empty($grupo_es_primero),
]) ?>
</div>
<?php else: ?>
<?= view('registers/analisis/partials/report_grupo_cabecera_content', [
    'padre'              => $padre,
    'hijo'               => $hijo,
    'tipo_muestra_linea' => $tipoMuestraLinea,
    'metodo_linea'       => $metodoLinea,
    'variant'            => $variant,
    'pdf_layout'         => $pdf_layout ?? [],
    'web_title_mt'       => $webTitleMt,
    'sub_idx'            => $subIdx,
    'grupo_es_primero'   => ! empty($grupo_es_primero),
]) ?>
<?php endif; ?>
<?php
$cmpService = null;
$precomputedHeatmaps = is_array($report_categorical_heatmap ?? null) ? $report_categorical_heatmap : [];
$precomputedModos    = is_array($report_graficar_modo ?? null) ? $report_graficar_modo : [];
$graficarModo = $priaIdTitulo > 0
    ? (int) ($precomputedModos[$priaIdTitulo] ?? \App\Models\LabotestModel::GRAFICAR_NO)
    : \App\Models\LabotestModel::GRAFICAR_NO;
if ($priaIdTitulo > 0 && $graficarModo === \App\Models\LabotestModel::GRAFICAR_NO && $precomputedModos === []) {
    $cmpService = new \App\Services\CategoricalSerialComparisonService();
    $graficarModo = $cmpService->getGraficarModo($priaIdTitulo);
}
$heatmapData = null;
if ($priaIdTitulo > 0) {
    if (array_key_exists($priaIdTitulo, $precomputedHeatmaps)) {
        $heatmapData = $precomputedHeatmaps[$priaIdTitulo];
    } else {
        $cmpService ??= new \App\Services\CategoricalSerialComparisonService();
        $heatmapData = $cmpService->buildFromReportItemsWithModo($subItems, $priaIdTitulo, $graficarModo);
    }
}
$tieneHeatmap = is_array($heatmapData) && ! empty($heatmapData['sections']);
$precomputedTolerance = is_array($report_tolerance_chart ?? null) ? $report_tolerance_chart : [];
$toleranceData = null;
if ($priaIdTitulo > 0) {
    if (array_key_exists($priaIdTitulo, $precomputedTolerance)) {
        $toleranceData = $precomputedTolerance[$priaIdTitulo];
    } else {
        $toleranceSvc = new \App\Services\ToleranceCurveChartService();
        $toleranceData = $toleranceSvc->buildFromReportItemsWithModo($subItems, $priaIdTitulo, $graficarModo);
    }
}
$tieneTolerance = is_array($toleranceData) && ! empty($toleranceData['points']);
$tieneGrafica = $tieneHeatmap || $tieneTolerance;
$mostrarTablaSeriada = ! ($graficarModo === \App\Models\LabotestModel::GRAFICAR_SI && $tieneGrafica);
$mostrarHeatmap = $tieneHeatmap && in_array(
    $graficarModo,
    [\App\Models\LabotestModel::GRAFICAR_SI, \App\Models\LabotestModel::GRAFICAR_AMBOS],
    true
);
$mostrarTolerance = $tieneTolerance && ! $tieneHeatmap && in_array(
    $graficarModo,
    [\App\Models\LabotestModel::GRAFICAR_SI, \App\Models\LabotestModel::GRAFICAR_AMBOS],
    true
);
?>
<?php $layoutSectionIndex = 0; ?>
<?php $firstTableAfterCabecera = true; ?>
<?php if ($mostrarTablaSeriada): ?>
<?php foreach ($segments as $segIdx => $seg): ?>
    <?php
    $titleObj = $seg['title'];
    $segItems = $seg['items'];
    $hasMatrixAfter = $priaIdTitulo > 0 && ! empty($refsMatrixAll[$priaIdTitulo]);
    $segmentWrapClass = '';
    if ($layoutPlanApplier) {
        $segmentWrapClass = trim($layoutPlanApplier->segmentClass($layoutAreaIndex, $layoutBlockIndex, $layoutSectionIndex));
    }
    $conRefEnSeg = false;
    $conInterpEnSeg = false;
    foreach ($segItems as $it) {
        $it = is_array($it) ? (object) $it : $it;
        $valTmp = trim((string) ($it->regvalues ?? ''));
        $mustShowRef = ! empty($it->show_reference);
        $opcionIdTmp = (int) ($it->opcion_id ?? 3);
        if (($valTmp !== '' && $valTmp !== '-') || $mustShowRef) {
            if (registro_tiene_rango_referencial($it->valor_min ?? '', $it->valor_max ?? '')) {
                $conRefEnSeg = true;
            }
            if (registro_mostrar_interpretacion_en_reporte(
                $valTmp,
                $it->valor_min ?? '',
                $it->valor_max ?? '',
                $opcionIdTmp,
                $it->umedida ?? ''
            )) {
                $conInterpEnSeg = true;
            }
        }
        if ($conRefEnSeg && $conInterpEnSeg) {
            break;
        }
    }
    $mostrarColInterpretacion = $showInterpretacionCol && $conInterpEnSeg;
    $mostrarColRef = $conRefEnSeg;
    $resultsColCount = 2 + ($mostrarColRef ? 1 : 0) + ($mostrarColInterpretacion ? 1 : 0);
    $resultsColClass = \App\Services\ReportPdfLayoutService::resultsTableGridClass($usePdfChrome, $resultsColCount);
    $mainTableClass = ($usePdfChrome ? 'results' : 'table mb-0') . $resultsColClass;
    $suppressTableMarginTop = $usePdfChrome && $firstTableAfterCabecera;
    $resultsTableLayoutAttrs = ($usePdfChrome && $resultsColCount >= 2)
        ? \App\Services\ReportPdfLayoutService::resultsTablePdfLayoutAttrs($resultsColCount, $pdfLayout, $usePdfChrome, $suppressTableMarginTop)
        : ($usePdfChrome
            ? \App\Services\ReportPdfLayoutService::resultsTablePdfLayoutAttrs($resultsColCount, $pdfLayout, true, $suppressTableMarginTop)
            : '');
    $resultsColgroupHtml = ($usePdfChrome && $resultsColCount >= 2)
        ? \App\Services\ReportPdfLayoutService::resultsTableColgroupHtml($resultsColCount, $usePdfChrome)
        : '';
    $thWidth = static function (int $colIdx) use ($usePdfChrome, $resultsColCount): string {
        return \App\Services\ReportPdfLayoutService::resultsTableThWidthStyleAttr($colIdx, $resultsColCount, $usePdfChrome);
    };
    $rangoHeaderColspanAttr = ($mostrarColRef && ! $mostrarColInterpretacion) ? ' colspan="2"' : '';
    $renderResultsTheadRow = static function () use (
        $usePdfChrome,
        $resultsColAlignClass,
        $resultsCellMarkupAttrs,
        $thWidth,
        $mostrarColRef,
        $mostrarColInterpretacion,
        $rangoHeaderColspanAttr
    ): void {
        ?>
                <tr>
                    <th class="results-col-analisis<?= $resultsColAlignClass('analisis', true) ?>"<?= $resultsCellMarkupAttrs('analisis', true, $thWidth(0)) ?>>ANÁLISIS</th>
                    <?php if ($mostrarColRef || $mostrarColInterpretacion): ?>
                    <th class="results-col-resultado<?= $resultsColAlignClass('resultado', true) ?>"<?= $resultsCellMarkupAttrs('resultado', true, $thWidth(1)) ?>>RESULTADO</th>
                    <?php if ($mostrarColRef): ?>
                    <th class="results-col-rango<?= $resultsColAlignClass('rango', true) ?>"<?= $rangoHeaderColspanAttr ?><?= $resultsCellMarkupAttrs('rango', true, $thWidth(2)) ?>>RANGO REFERENCIAL</th>
                    <?php endif; ?>
                    <?php if ($mostrarColInterpretacion): ?>
                    <th class="results-col-interpretacion<?= $resultsColAlignClass('interpretacion', true) ?>"<?= $resultsCellMarkupAttrs('interpretacion', true, $thWidth(3)) ?>>INTERPRETACIÓN</th>
                    <?php endif; ?>
                    <?php else: ?>
                    <th class="results-col-resultado<?= $resultsColAlignClass('resultado', true) ?>" colspan="3"<?= $resultsCellMarkupAttrs('resultado', true) ?>>RESULTADO</th>
                    <?php endif; ?>
                </tr>
        <?php
    };
    $wrapOpen = ! $usePdfChrome ? '<div class="table-responsive mb-3">' : '<div class="report-segment-table-wrap' . ($segmentWrapClass !== '' ? ' ' . esc($segmentWrapClass, 'attr') : '') . '"' . $segmentWrapStyleAttr . '>';
    $wrapClose = '</div>';
    $tieneConResultado = false;
    foreach ($segItems as $itChk) {
        $itChk = is_array($itChk) ? (object) $itChk : $itChk;
        $vChk = trim((string) ($itChk->regvalues ?? ''));
        if (($vChk !== '' && $vChk !== '-') || ! empty($itChk->show_reference)) {
            $tieneConResultado = true;
            break;
        }
    }
    $tailSplit = $layoutPlanApplier
        ? $layoutPlanApplier->sectionSignatureTailSplit($layoutAreaIndex, $layoutBlockIndex, $layoutSectionIndex)
        : null;
    $tailSplitAt = is_array($tailSplit) ? (int) ($tailSplit['splitAt'] ?? 0) : null;
    $tailFullInTail = is_array($tailSplit) && ! empty($tailSplit['fullInTail']);
    $visibleRowIdx = 0;
    $tailTableOpened = false;
    if ($tailFullInTail) {
        $segmentWrapClass = trim(preg_replace('/\breport-segment-allow-split\b/', '', $segmentWrapClass));
        $segmentWrapClass = trim($segmentWrapClass . ' report-segment-tail-with-signature');
        $wrapOpen = ! $usePdfChrome
            ? '<div class="table-responsive mb-3">'
            : '<div class="report-segment-table-wrap' . ($segmentWrapClass !== '' ? ' ' . esc($segmentWrapClass, 'attr') : '') . '"' . $segmentWrapStyleAttr . '>';
    }
    $signatureTailBundleOpen = '';
    if ($usePdfChrome && $layoutPlanApplier !== null && $tailFullInTail && empty($subgrupoTailBundleAtStart)) {
        $signatureTailBundleOpen = $layoutPlanApplier->beginSignatureTailBundleMarkup();
    }
    $titleInTailOnly = $tailSplitAt !== null && $tailSplitAt > 0 && $titleObj !== null;
    ?>
    <?php if ($tieneConResultado): ?>
    <?php if ($firstTableAfterCabecera): ?>
    <?php $firstTableAfterCabecera = false; ?>
    <?php endif; ?>
    <?php if ($tailFullInTail): ?>
    <?php $tailTableOpened = true; ?>
    <?php endif; ?>
    <?= $signatureTailBundleOpen ?>
    <?= $wrapOpen ?>
        <?php if ($titleObj !== null && ! $titleInTailOnly): ?>
            <?php if ($usePdfChrome): ?>
            <div class="report-segment-title pdf-card-header report-pdf-section-row-title"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php else: ?>
            <div class="report-segment-title-web px-2 py-2 mb-2 bg-secondary bg-opacity-10 border-start border-4 border-secondary rounded-end fw-semibold text-uppercase small"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <table class="<?= esc($mainTableClass) ?>"<?= $resultsTableLayoutAttrs ?>>
            <?= $resultsColgroupHtml ?>
            <?php if (! $ocultarTheadResults): ?>
            <thead<?= $usePdfChrome ? '' : ' class="thead-dark"' ?>>
                <?php $renderResultsTheadRow(); ?>
            </thead>
            <?php endif; ?>
            <tbody>
                <?php foreach ($segItems as $item): ?>
                    <?php
                    $item = is_array($item) ? (object) $item : $item;
                    $valTmp = trim((string) ($item->regvalues ?? ''));
                    $mustShowRef = ! empty($item->show_reference);
                    if (($valTmp === '' || $valTmp === '-') && ! $mustShowRef) {
                        continue;
                    }
                    if ($tailSplitAt !== null && $tailSplitAt > 0 && $visibleRowIdx === $tailSplitAt && ! $tailTableOpened): ?>
            </tbody>
        </table>
    <?= $wrapClose ?>
    <?php
                    if ($usePdfChrome && $layoutPlanApplier !== null && $signatureTailBundleOpen === '') {
                        echo $layoutPlanApplier->beginSignatureTailBundleMarkup();
                    }
                    $tailWrapClass = trim('report-segment-table-wrap report-segment-tail-with-signature report-signature-tail-continuation' . ($segmentWrapClass !== '' ? ' ' . preg_replace('/\breport-segment-allow-split\b/', '', $segmentWrapClass) : ''));
                    $tailWrapOpen = ! $usePdfChrome
                        ? '<div class="table-responsive mb-3">'
                        : '<div class="' . esc($tailWrapClass, 'attr') . '"' . $segmentWrapStyleAttr . '>';
                    echo $tailWrapOpen;
                    if ($titleInTailOnly): ?>
            <?php if ($usePdfChrome): ?>
            <div class="report-segment-title pdf-card-header report-pdf-section-row-title"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php else: ?>
            <div class="report-segment-title-web px-2 py-2 mb-2 bg-secondary bg-opacity-10 border-start border-4 border-secondary rounded-end fw-semibold text-uppercase small"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php endif; ?>
                    <?php endif; ?>
        <table class="<?= esc($mainTableClass) ?> report-segment-thead-continuation"<?= $resultsTableLayoutAttrs ?>>
            <?= $resultsColgroupHtml ?>
            <?php if (! $ocultarTheadResults): ?>
            <thead<?= $usePdfChrome ? '' : ' class="thead-dark"' ?>>
                <?php $renderResultsTheadRow(); ?>
            </thead>
            <?php endif; ?>
            <tbody>
                    <?php
                        $tailTableOpened = true;
                    endif;
                    $aid = $item->secanacategoria_id ?? uniqid();
                    ?>
                    <?php if ($variant === 'screen_pdf' || $variant === 'web'): ?>
                    <input type="hidden" id="analisis_<?= esc($aid) ?>" name="analisis_<?= esc($aid) ?>" class="analisis" padre="<?= esc($padre) ?>" hijo="<?= esc($hijo) ?>" analisis="<?= esc($item->nombre ?? '') ?>" value="<?= esc($item->regvalues ?? '') ?>" unidad="<?= esc($item->umedida ?? '') ?>" min="<?= esc($item->valor_min ?? '') ?>" max="<?= esc($item->valor_max ?? '') ?>">
                    <?php endif; ?>
                    <?php
                    $val = $item->regvalues ?? '';
                    if (! $val) {
                        $item->regvalues = '-';
                        $val = '-';
                    }
                    $valNorm = trim(strtolower((string) $val));
                    $opcionIdItem = (int) ($item->opcion_id ?? 3);
                    $itemConRef = registro_mostrar_rango_referencial_en_reporte(
                        $val,
                        $item->valor_min ?? '',
                        $item->valor_max ?? '',
                        $opcionIdItem,
                        $item->umedida ?? ''
                    );
                    $itemConInterp = registro_mostrar_interpretacion_en_reporte(
                        $val,
                        $item->valor_min ?? '',
                        $item->valor_max ?? '',
                        $opcionIdItem,
                        $item->umedida ?? ''
                    );
                    $interpretacionRef = ($mostrarColInterpretacion && $itemConInterp)
                        ? registro_interpretacion_referencial_etiqueta_viewreport(
                            $val,
                            $item->valor_min ?? '',
                            $item->valor_max ?? '',
                            $item->umedida ?? '',
                            $opcionIdItem
                        )
                        : null;

                    // Modo de visualización por doctor (si está presente en el contexto)
                    $displayMode = 'clinico';
                    $doctorIsSynthetic = false;
                    if (! empty($doctor)) {
                        if (is_object($doctor)) {
                            $displayMode = trim((string) ($doctor->display_mode ?? $displayMode));
                            if (property_exists($doctor, 'report_sin_prefijo_medico') && $doctor->report_sin_prefijo_medico) {
                                $doctorIsSynthetic = true;
                            }
                        } elseif (is_array($doctor)) {
                            $displayMode = trim((string) ($doctor['display_mode'] ?? $displayMode));
                            if (! empty($doctor['report_sin_prefijo_medico'])) {
                                $doctorIsSynthetic = true;
                            }
                        }
                    }
                    // Si el doctor es el objeto sintético creado para "Sin doctor", usar la config de laboratorio
                    if ($doctorIsSynthetic) {
                        $cfgMode = is_array($labConfigLocal) ? trim((string) ($labConfigLocal['sin_doctor_report_mode'] ?? '')) : '';
                        if ($cfgMode !== '' && in_array($cfgMode, ['clinico', 'neutral', 'semaforo'], true)) {
                            $displayMode = $cfgMode;
                        }
                    }
                    if ($displayMode === '') $displayMode = 'clinico';

                    if ($showInterpretacionCol && $mostrarColInterpretacion && $interpretacionRef !== null) {
                        if ($displayMode === 'neutral') {
                            $class = 'normal';
                            $isOutPdf = false;
                        } elseif ($displayMode === 'clinico') {
                            $nivel = $interpretacionRef['nivel'] ?? 'normal';
                            if ($nivel === 'alto') {
                                $class = 'report-interpretacion-alto';
                            } elseif ($nivel === 'bajo') {
                                $class = 'report-interpretacion-bajo';
                            } else {
                                $class = 'normal';
                            }
                            $isOutPdf = $nivel !== 'normal';
                        } else {
                            // semaforo u otros modos: mantener comportamiento previo
                            $class = registro_interpretacion_referencial_clase_resultado($interpretacionRef);
                            $isOutPdf = $interpretacionRef['nivel'] !== 'normal';
                        }
                        if ($displayMode === 'semaforo') {
                            log_message('debug', 'Render semaforo fila ' . ($item->secanacategoria_id ?? 'n/a') . ' interp=' . ($interpretacionRef['nivel'] ?? 'n/a'));
                        }
                    } elseif ($showInterpretacionCol && in_array($valNorm, ['positivo', 'reactivo'], true)) {
                        if ($displayMode === 'neutral') {
                            $class = 'normal';
                            $isOutPdf = false;
                        } else {
                            if ($displayMode === 'clinico') {
                                $class = 'report-interpretacion-alto';
                            } else {
                                $class = 'text-danger font-weight-bold';
                            }
                            $isOutPdf = true;
                        }
                    } elseif ($showInterpretacionCol && is_numeric($val) && ($item->valor_min ?? '') !== '' && ($item->valor_max ?? '') !== '') {
                        $inRange = ($val >= $item->valor_min && $val <= $item->valor_max);
                        if ($displayMode === 'neutral') {
                            $class = 'normal';
                            $isOutPdf = false;
                        } else {
                            if ($inRange) {
                                $class = 'normal';
                                $isOutPdf = false;
                            } else {
                                if ($displayMode === 'clinico') {
                                    // distinguir bajo vs alto para clinico
                                    if ($val < $item->valor_min) {
                                        $class = 'report-interpretacion-bajo';
                                    } else {
                                        $class = 'report-interpretacion-alto';
                                    }
                                } else {
                                    $class = 'text-danger font-weight-bold';
                                }
                                $isOutPdf = true;
                            }
                        }
                    } else {
                        $class = 'normal';
                        $isOutPdf = false;
                    }
                    $mostrarMedidaSoloRef = registro_mostrar_medida_solo_en_referencia($item);
                    $resMostrarHtml = registro_resultado_celda_html($item->regvalues ?? '', $item->umedida ?? '', $opcionIdItem, $mostrarMedidaSoloRef);
                    $refMostrar = registro_rango_referencial_html($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
                    $celdaRicoClass = (registro_opcion_es_texto_rico($opcionIdItem) || registro_opcion_es_texto_fijo($opcionIdItem) || registro_valor_contiene_html_rico((string) ($item->regvalues ?? ''))) ? ' resultado-texto-rico-cell' : '';
                    $itemSinRefNiInterp = ! $itemConRef && $interpretacionRef === null;
                    $resultadoColspan = $itemSinRefNiInterp ? 3 : 1;
                    $resultadoColspanAttr = $resultadoColspan > 1 ? ' colspan="' . (int) $resultadoColspan . '"' : '';
                    $resultadoColspanClass = $resultadoColspan > 1 ? ' resultado-colspan-rest' : '';
                    $rangoColspanAttr = ($mostrarColRef && ! $mostrarColInterpretacion) ? ' colspan="2"' : '';
                    ?>
                    <?php if (is_object($item)): ?>
                        <tr>
                            <td class="results-col-analisis<?= $resultsColAlignClass('analisis', false) ?>"<?= $resultsCellMarkupAttrs('analisis', false) ?>><?= esc($item->nombre ?? '') ?></td>
                            <?php
                                // Semáforo junto al resultado solo si la interpretación está habilitada
                                // (/config sin doctor o doctor con interpretacion_enabled) y no hay columna aparte.
                                if ($showInterpretacionCol && $displayMode === 'semaforo' && empty($mostrarColInterpretacion)) {
                                    // Asegurar que tenemos la interpretación calculada si hay rango referencial
                                    if ($itemConInterp && $interpretacionRef === null) {
                                        $interpretacionRef = registro_interpretacion_referencial_etiqueta_viewreport(
                                            $val,
                                            $item->valor_min ?? '',
                                            $item->valor_max ?? '',
                                            $item->umedida ?? '',
                                            $opcionIdItem
                                        );
                                    }
                                    $iconHtml = '';
                                    $iconClass = '';
                                    $lvl = $interpretacionRef['nivel'] ?? null;
                                    if ($lvl === null) {
                                        // Fallback heuristics: positivos/reactivos o rango numérico
                                        if (in_array($valNorm, ['positivo', 'reactivo'], true)) {
                                            $lvl = 'alto';
                                        } elseif (is_numeric($val) && ($item->valor_min ?? '') !== '' && ($item->valor_max ?? '') !== '') {
                                            $lvl = ($val >= $item->valor_min && $val <= $item->valor_max) ? 'normal' : 'alto';
                                        } else {
                                            $lvl = 'normal';
                                        }
                                    }
                                    if ($lvl === 'alto') {
                                        $iconHtml = registro_interpretacion_semaforo_icon_html('alto', $usePdfChrome);
                                        $class = 'report-interpretacion-alto';
                                    } elseif ($lvl === 'bajo') {
                                        $iconHtml = registro_interpretacion_semaforo_icon_html('bajo', $usePdfChrome);
                                        $class = 'report-interpretacion-bajo';
                                    } else {
                                        $iconHtml = '';
                                        $class = 'normal';
                                    }
                                    if ($iconHtml !== '') {
                                        // colocar el icono a la derecha del resultado
                                        $resMostrarHtml = $resMostrarHtml . ' ' . $iconHtml;
                                    }
                                }
                            ?>
                            <?php
                                $resultadoClassList = trim($class . ($usePdfChrome && $isOutPdf ? ' out-range' : ''));
                                $pdfInterpColorCss = $usePdfChrome
                                    ? registro_interpretacion_pdf_td_style_fragment($resultadoClassList)
                                    : '';
                            ?>
                            <td class="results-col-resultado<?= $celdaRicoClass ?><?= $resultadoColspanClass ?> <?= $class ?><?= $usePdfChrome && $isOutPdf ? ' out-range' : '' ?><?= $resultsColAlignClass('resultado', false) ?>"<?= $resultadoColspanAttr ?><?= $resultsCellMarkupAttrs('resultado', false, '', $pdfInterpColorCss) ?>><?= $resMostrarHtml ?></td>
                            <?php if ($resultadoColspan === 1 && $mostrarColRef): ?>
                            <td class="results-col-rango<?= $usePdfChrome ? ' ref-range' : '' ?><?= $resultsColAlignClass('rango', false) ?>"<?= $rangoColspanAttr ?><?= $resultsCellMarkupAttrs('rango', false) ?>><?= $itemConRef ? $refMostrar : '' ?></td>
                            <?php endif; ?>
                            <?php if ($resultadoColspan === 1 && $mostrarColInterpretacion): ?>
                            <?php
                                $interpHtml = $interpretacionRef !== null ? esc($interpretacionRef['label']) : '';
                                if ($displayMode === 'semaforo' && $interpretacionRef !== null) {
                                    $ico = registro_interpretacion_semaforo_icon_html(
                                        (string) ($interpretacionRef['nivel'] ?? 'normal'),
                                        $usePdfChrome,
                                        true
                                    );
                                    if ($ico !== '') {
                                        $interpHtml = $ico . ' ' . $interpHtml;
                                    }
                                }
                            ?>
                            <?php
                                $interpClassList = $interpretacionRef !== null ? trim($class) : '';
                                $pdfInterpColColorCss = ($usePdfChrome && $interpClassList !== '')
                                    ? registro_interpretacion_pdf_td_style_fragment($interpClassList)
                                    : '';
                            ?>
                            <td class="results-col-interpretacion<?= $interpretacionRef !== null ? ' ' . esc($class, 'attr') : '' ?><?= $resultsColAlignClass('interpretacion', false) ?>"<?= $resultsCellMarkupAttrs('interpretacion', false, '', $pdfInterpColColorCss) ?>><?= $interpHtml !== '' ? $interpHtml : '' ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                    <?php $visibleRowIdx++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?= $wrapClose ?>
    <?php $layoutSectionIndex++; ?>
    <?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
<?php if ($mostrarHeatmap): ?>
    <?= view('registers/analisis/partials/comparacion_seriada_heatmap', [
        'heatmap'        => $heatmapData,
        'variant'        => $variant,
        'use_pdf_chrome' => $usePdfChrome,
    ]) ?>
<?php endif; ?>
<?php if ($mostrarTolerance): ?>
    <?= view('registers/analisis/partials/tolerancia_curva_chart', [
        'chart'          => $toleranceData,
        'variant'        => $variant,
        'use_pdf_chrome' => $usePdfChrome,
    ]) ?>
<?php endif; ?>
<?php
if ($priaIdTitulo > 0 && ! empty($refsMatrixAll[$priaIdTitulo])) :
    $matrixRows = $refsMatrixAll[$priaIdTitulo];
    $showSexoCol = false;
    foreach ($matrixRows as $mr) {
        $sx = strtolower(trim((string) ($mr['sexo'] ?? '')));
        if ($sx !== '' && $sx !== 'ambos') {
            $showSexoCol = true;
            break;
        }
    }
    $matrixWrapClass = '';
    if ($layoutPlanApplier) {
        $matrixWrapClass = trim($layoutPlanApplier->segmentClass($layoutAreaIndex, $layoutBlockIndex, $layoutSectionIndex));
    }
    $matrixTableClass = $usePdfChrome ? 'results report-refs-matrix' : 'table table-sm table-bordered mb-0';
    $matrixWrapOpen = ! $usePdfChrome ? '<div class="table-responsive mb-3">' : '<div class="report-segment-table-wrap report-refs-matrix-wrap' . ($matrixWrapClass !== '' ? ' ' . esc($matrixWrapClass, 'attr') : '') . '"' . $segmentWrapStyleAttr . '>';
    ?>
<?= $matrixWrapOpen ?>
    <?php if ($usePdfChrome): ?>
    <div class="report-refs-matrix-title pdf-card-header"<?php
        $matrixTitleStyle = \App\Services\ReportPdfLayoutService::refsMatrixTitleSpacingStyleAttr($pdfLayout);
        echo $matrixTitleStyle !== '' ? ' style="' . esc($matrixTitleStyle, 'attr') . '"' : '';
    ?>>Valores de referencia por grupo poblacional</div>
    <?php else: ?>
    <div class="report-refs-matrix-title-web px-2 py-2 mt-3 mb-2 bg-light border-start border-4 border-secondary rounded-end small fw-semibold text-uppercase">Valores de referencia por grupo poblacional</div>
    <?php endif; ?>
    <table class="<?= esc($matrixTableClass, 'attr') ?>"<?= $usePdfChrome ? ' style="font-size:8pt;width:100%;"' : '' ?>>
        <thead<?= $usePdfChrome ? '' : ' class="table-light"' ?>>
            <tr>
                <th class="matrix-col-population">Grupo poblacional</th>
                <th class="matrix-col-parameter">Parámetro</th>
                <?php if ($showSexoCol): ?>
                <th class="text-center matrix-col-sex">Sexo</th>
                <?php endif; ?>
                <th class="text-center matrix-col-reference">Valor de referencia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($matrixRows as $mrow) : ?>
                <?php
                $mrow = is_array($mrow) ? $mrow : [];
                if (! registro_tiene_rango_referencial($mrow['valor_min'] ?? '', $mrow['valor_max'] ?? '')) {
                    continue;
                }
                $sexoTxt = trim((string) ($mrow['sexo'] ?? ''));
                $sexoLower = strtolower($sexoTxt);
                if ($sexoLower === 'ambos' || $sexoLower === '') {
                    $sexoTxt = '—';
                } else {
                    $sexoTxt = esc($sexoTxt);
                }
                $refTxt = registro_rango_referencial_html($mrow['valor_min'] ?? '', $mrow['valor_max'] ?? '', $mrow['umedida'] ?? '');
                ?>
            <tr>
                <td class="matrix-col-population"><?= esc(trim((string) ($mrow['poblacion_nombre'] ?? ''))) ?></td>
                <td class="matrix-col-parameter"><?= esc(trim((string) ($mrow['nombre'] ?? ''))) ?></td>
                <?php if ($showSexoCol): ?>
                <td class="text-center matrix-col-sex"><?= $sexoTxt ?></td>
                <?php endif; ?>
                <td class="text-center matrix-col-reference"><?= $refTxt ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php if ($usePdfChrome && ! empty($isLastSubgrupo) && $layoutPlanApplier !== null && $layoutPlanApplier->isSignatureTailBundleOpen()): ?>
    <?php
    $firmaBundle = is_array($area_firma_bundle ?? null) ? $area_firma_bundle : null;
    if ($firmaBundle !== null && ($firmaBundle['firma'] ?? []) !== []):
        echo view('registers/partials/report_lab_firma_grupo_inline', $firmaBundle);
    endif;
    echo $layoutPlanApplier->endSignatureTailBundleMarkup();
    ?>
<?php endif; ?>
<?php if ($usePdfChrome): ?>
</div>
<?php endif; ?>
<?php $layoutBlockIndex++; ?>
<?php endforeach; ?>
