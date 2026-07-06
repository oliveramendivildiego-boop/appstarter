<?php
/**
 * Reporte de prueba tipo personalizado (matriz encabezado / cuerpo / pie).
 *
 * @var string $padre
 * @var string $hijo
 * @var int $pria_id_titulo
 * @var object $cultivo_item
 * @var string $variant
 * @var array<string, mixed>|null $pdf_layout
 * @var array<int, string> $report_pria_tipo_muestra_nombre
 * @var array<int, string> $report_pria_metodo_nombre
 */
$usePdfChrome = in_array((string) ($variant ?? 'web'), ['pdf', 'screen_pdf', 'browser_print'], true);
$cultivoItem = $cultivo_item ?? null;
$priaIdTitulo = (int) ($cultivoItem->prianacategoria_id ?? ($pria_id_titulo ?? 0));
$padreTitulo = trim((string) ($cultivoItem->padre ?? ($padre ?? '')));
$hijoTitulo = trim((string) ($cultivoItem->hijo ?? ($hijo ?? '')));
$tipoMuestraLinea = trim((string) ($cultivoItem->tipo_muestra_nombre ?? ''));
$metodoLinea = trim((string) ($cultivoItem->metodo_nombre ?? ''));
if ($tipoMuestraLinea === '' && $priaIdTitulo > 0) {
    $tipoMuestraLinea = trim((string) (($report_pria_tipo_muestra_nombre ?? [])[$priaIdTitulo] ?? ''));
}
if ($metodoLinea === '' && $priaIdTitulo > 0) {
    $metodoLinea = trim((string) (($report_pria_metodo_nombre ?? [])[$priaIdTitulo] ?? ''));
}
$secciones = is_array($cultivoItem->cultivo_display ?? null) ? $cultivoItem->cultivo_display : [];
$secciones = array_values(array_filter(
    $secciones,
    static fn ($sec): bool => is_array($sec) && \App\Models\LabotestModel::cultivoDisplaySeccionTieneValorUsuario($sec)
));
if ($secciones === []) {
    return;
}
$esPersonalizadoMatriz = ! empty($cultivoItem->es_personalizado_matriz);
$segmentWrapStyle = '';
if ($usePdfChrome) {
    $segmentWrapStyle = \App\Services\ReportPdfLayoutService::grupoPruebaSegmentIntactStyleAttr(
        is_array($pdf_layout ?? null) ? $pdf_layout : []
    );
}
$segmentWrapStyleAttr = $segmentWrapStyle !== '' ? ' style="' . esc($segmentWrapStyle, 'attr') . '"' : '';
/** @var \App\Services\ReportLayout\LayoutPlanApplier|null $layoutPlanApplier */
$layoutPlanApplier = ($layout_plan_applier ?? null) instanceof \App\Services\ReportLayout\LayoutPlanApplier
    ? $layout_plan_applier
    : null;
$layoutAreaIndexCultivo = (int) ($layout_area_index ?? 0);
$layoutBlockIndexCultivo = (int) ($layout_block_index ?? 0);
$areaFirmaBundle = is_array($area_firma_bundle ?? null) ? $area_firma_bundle : null;
$layoutSectionIndexCultivo = 0;
$isLastSubgrupoCultivo = ! empty($is_last_subgrupo);
$cultivoFirmaEnTailBundle = $areaFirmaBundle !== null && ($areaFirmaBundle['firma'] ?? []) !== [];
$cultivoFirmaRendered = false;
$renderCultivoFirmaDentroDelBundle = static function () use (
    &$cultivoFirmaRendered,
    $usePdfChrome,
    $isLastSubgrupoCultivo,
    $layoutPlanApplier,
    $areaFirmaBundle,
): void {
    if ($cultivoFirmaRendered || ! $usePdfChrome || ! $isLastSubgrupoCultivo) {
        return;
    }
    if ($areaFirmaBundle === null || ($areaFirmaBundle['firma'] ?? []) === []) {
        return;
    }
    if ($layoutPlanApplier !== null && ! $layoutPlanApplier->isSignatureTailBundleOpen()) {
        echo $layoutPlanApplier->beginSignatureTailBundleMarkup();
    }
    echo view('registers/partials/report_lab_firma_grupo_inline', $areaFirmaBundle);
    $cultivoFirmaRendered = true;
};
$cerrarCultivoFirmaBundle = static function () use ($layoutPlanApplier): void {
    if ($layoutPlanApplier !== null && $layoutPlanApplier->isSignatureTailBundleOpen()) {
        echo $layoutPlanApplier->endSignatureTailBundleMarkup();
    }
};
$subgrupoTailBundleAtStart = $usePdfChrome
    && $layoutPlanApplier !== null
    && $isLastSubgrupoCultivo
    && $cultivoFirmaEnTailBundle
    && $layoutPlanApplier->shouldOpenSignatureTailBundleBeforeSubgrupo($layoutAreaIndexCultivo, $layoutBlockIndexCultivo);
$mainTableClass = $usePdfChrome ? 'results' : 'table table-bordered table-sm mb-0';
$subIdxCultivo = (int) ($sub_idx ?? 0);
$webTitleMt = $subIdxCultivo > 0 ? 'mt-5' : 'mt-4';
$renderCultivoTdCelda = static function (
    array|string $celda,
    string $tdClass = '',
    int $colspan = 1,
    int $rowspan = 1,
    string $styleExtra = '',
    ?array $reporteEstiloBloque = null,
) use ($usePdfChrome): void {
    if (is_array($celda)) {
        $cellHtml = (string) ($celda['html'] ?? $celda['texto'] ?? '');
        $estilo = trim((string) ($celda['estilo'] ?? ''));
    } else {
        $cellHtml = (string) $celda;
        $estilo = '';
    }
    $tdStyle = trim($estilo . ($estilo !== '' && $styleExtra !== '' ? ';' : '') . $styleExtra);
    if (is_array($reporteEstiloBloque) && $reporteEstiloBloque !== []) {
        $tdStyle = \App\Models\LabotestModel::mergePersonalizadoReporteTdEstiloConBloque($tdStyle, $reporteEstiloBloque);
    }
    $isBordesHtml = str_contains($cellHtml, 'cultivo-celda-bordes');
    $isHtml = $isBordesHtml || str_contains($cellHtml, 'pers-celda-reporte')
        || ($cellHtml !== '' && $cellHtml !== strip_tags($cellHtml));
    $classes = trim($tdClass . ' align-middle' . ($isHtml ? ' cultivo-celda-html' : ''));
    $attrs = ' class="' . esc($classes, 'attr') . '"';
    if ($colspan > 1) {
        $attrs .= ' colspan="' . (int) $colspan . '"';
    }
    if ($rowspan > 1) {
        $attrs .= ' rowspan="' . (int) $rowspan . '"';
    }
    $alignAttr = \App\Models\LabotestModel::extractPersonalizadoTdAlignAttr($tdStyle);
    if ($alignAttr !== '') {
        $attrs .= ' align="' . esc($alignAttr, 'attr') . '"';
    }
    if ($tdStyle !== '') {
        $attrs .= ' style="' . esc($tdStyle, 'attr') . '"';
    }
    if ($isHtml && $alignAttr !== '') {
        $celdaNegrita = \App\Models\LabotestModel::personalizadoCeldaEstiloEsNegrita($tdStyle);
        $cellHtml = \App\Models\LabotestModel::wrapPersonalizadoCeldaHtmlParaMpdf($cellHtml, $alignAttr, $celdaNegrita);
    }
    echo '<td' . $attrs . '>';
    if ($isHtml) {
        echo $cellHtml;
    } else {
        echo esc($cellHtml);
    }
    echo '</td>';
};
$renderCultivoCelda = static function (
    array|string $cellHtml,
    string $tdClass = 'text-center',
    string $tdStyleExtra = '',
    ?array $reporteEstiloBloque = null,
) use ($renderCultivoTdCelda): void {
    if (is_array($cellHtml)) {
        $renderCultivoTdCelda($cellHtml, $tdClass, 1, 1, $tdStyleExtra, $reporteEstiloBloque);

        return;
    }
    $renderCultivoTdCelda($cellHtml, $tdClass, 1, 1, $tdStyleExtra, $reporteEstiloBloque);
};
$renderCultivoCeldaGrilla = static function (array $celda, ?array $reporteEstiloBloque = null) use ($renderCultivoTdCelda): void {
    $renderCultivoTdCelda(
        $celda,
        '',
        max(1, (int) ($celda['colspan'] ?? 1)),
        max(1, (int) ($celda['rowspan'] ?? 1)),
        '',
        $reporteEstiloBloque
    );
};
?>
<?php if (! $usePdfChrome): ?>
<style>
.report-cultivo-seccion .report-cultivo-banda,
.report-cultivo-seccion .report-cultivo-columnas {
    width: 100%;
}
.report-cultivo-seccion .report-cultivo-banda .table {
    width: 100%;
    table-layout: fixed;
    margin-bottom: 0;
}
.report-cultivo-seccion .report-cultivo-columnas {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    margin-top: 0;
}
.report-cultivo-seccion .report-cultivo-columna {
    flex: 1 1 0;
    min-width: 0;
}
.report-cultivo-seccion .report-cultivo-columna .table {
    width: 100%;
    table-layout: fixed;
    margin-top: 0;
    margin-bottom: 0;
}
.report-cultivo-seccion .report-cultivo-banda + .report-cultivo-columnas .report-cultivo-columna .table {
    border-top: none;
}
.report-cultivo-seccion .report-cultivo-banda + .report-cultivo-columnas .report-cultivo-columna .table thead tr:first-child th {
    border-top: none;
}
.report-cultivo-seccion.report-cultivo-alineacion-centro .report-cultivo-columna td {
    text-align: center;
}
.report-cultivo-seccion.report-cultivo-alineacion-bordes .report-cultivo-columna td {
    text-align: left;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}
.report-cultivo-seccion.report-cultivo-personalizado .report-cultivo-columna td.pers-celda-reporte {
    width: 100%;
}
.report-cultivo-grilla-personalizado {
    width: 100%;
    table-layout: fixed;
}
.report-cultivo-grilla-personalizado td {
    vertical-align: middle;
}
.report-cultivo-grilla-personalizado.report-cultivo-estilo-reporte {
    border-collapse: collapse;
}
.report-cultivo-grilla-personalizado.report-cultivo-estilo-reporte td,
.report-cultivo-grilla-personalizado.report-cultivo-estilo-reporte th {
    vertical-align: middle;
}
table.results.report-cultivo-personalizado-tabla.report-cultivo-borde-default > tbody > tr > td,
table.results.report-cultivo-grilla-personalizado.report-cultivo-borde-default > tbody > tr > td {
    border-width: 1px !important;
    border-style: solid !important;
    border-color: #dee2e6 !important;
}
table.results.report-cultivo-personalizado-tabla.report-cultivo-borde-none > tbody > tr > td,
table.results.report-cultivo-grilla-personalizado.report-cultivo-borde-none > tbody > tr > td {
    border: none !important;
}
table.results.report-cultivo-personalizado-tabla,
table.results.report-cultivo-grilla-personalizado {
    border-collapse: collapse !important;
}
<?= \App\Models\LabotestModel::buildPersonalizadoReporteCeldaContenidoCss('table') ?>
.report-cultivo-grilla-personalizado td.cultivo-celda-html p,
.report-cultivo-grilla-personalizado td.cultivo-celda-html div,
.report-cultivo-grilla-personalizado td.cultivo-celda-html span,
.report-cultivo-grilla-personalizado td.cultivo-celda-html em,
.report-cultivo-grilla-personalizado td.cultivo-celda-html i,
.report-cultivo-grilla-personalizado td.cultivo-celda-html u {
    text-align: inherit !important;
}
.report-cultivo-grilla-personalizado .pers-celda-reporte {
    width: 100%;
    box-sizing: border-box;
}
.cultivo-celda-bordes {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    text-align: left;
}
.cultivo-celda-bordes .cultivo-celda-izq {
    flex: 1 1 auto;
    min-width: 0;
}
.cultivo-celda-bordes .cultivo-celda-der {
    flex: 0 0 auto;
    text-align: right;
    white-space: nowrap;
}
</style>
<?php endif; ?>
<?php if ($usePdfChrome): ?>
<?php
$cultivoPb = $layoutPlanApplier
    ? $layoutPlanApplier->blockMarkers($layoutAreaIndexCultivo, $layoutBlockIndexCultivo)
    : ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => ''];
$subgrupoCultivoClass = trim(
    'report-pdf-subgrupo-block'
    . ($subIdxCultivo > 0 ? ' report-pdf-subgrupo-prueba' : '')
    . ($cultivoPb['subgrupo_class'] !== '' ? ' ' . $cultivoPb['subgrupo_class'] : '')
);
$cabeceraCultivoClass = trim('report-pdf-grupo-cabecera' . ($cultivoPb['cabecera_class'] !== '' ? ' ' . $cultivoPb['cabecera_class'] : ''));
$subgrupoCultivoStyle = $subIdxCultivo > 0
    ? \App\Services\ReportPdfLayoutService::subgrupoPruebaGapStyleAttr(
        is_array($pdf_layout ?? null) ? $pdf_layout : [],
        true
    )
    : '';
$blockIdCultivo = \App\Services\ReportLayout\ReportTreeBuilder::analysisBlockId($layoutAreaIndexCultivo, $layoutBlockIndexCultivo);
$cultivoTotalFilas = 0;
foreach ($secciones as $secRowCount) {
    $grillaCount = is_array($secRowCount['grilla_reporte']['filas'] ?? null)
        ? count($secRowCount['grilla_reporte']['filas'])
        : 0;
    if ($grillaCount > 0) {
        $cultivoTotalFilas += $grillaCount;

        continue;
    }
    $columnasDetalleCount = is_array($secRowCount['columnas_detalle'] ?? null) ? $secRowCount['columnas_detalle'] : [];
    foreach ($columnasDetalleCount as $colDetCount) {
        $valoresCount = is_array($colDetCount['valores'] ?? null) ? count($colDetCount['valores']) : 0;
        $cultivoTotalFilas += $valoresCount;
    }
}
$cultivoKeepIntact = $usePdfChrome
    && $cultivoTotalFilas > 0
    && $cultivoTotalFilas <= \App\Services\ReportPdfLayoutService::subgrupoKeepIntactMaxRows()
    && (
        $layoutPlanApplier === null
        || \App\Services\ReportLayout\ReportPaginationMode::usesSubgrupoKeepIntactInHtml(
            $layoutPlanApplier->mode()
        )
    );
if ($cultivoKeepIntact) {
    $subgrupoCultivoClass .= ' report-subgrupo-keep-intact';
}
?>
<?php if ($variant === 'browser_print' && $layoutPlanApplier !== null && $layoutPlanApplier->browserPrintPageBreakBeforeAnalysisBlock($layoutAreaIndexCultivo, $layoutBlockIndexCultivo)): ?>
<?= view('registers/partials/report_browser_print_plan_page_break', [
    'plan_page_index' => $layoutPlanApplier->analysisBlockPlanPageIndex($layoutAreaIndexCultivo, $layoutBlockIndexCultivo),
]) ?>
<?php endif; ?>
<div class="<?= esc($subgrupoCultivoClass, 'attr') ?>" data-layout-block-id="<?= esc($blockIdCultivo, 'attr') ?>"<?= $subgrupoCultivoStyle !== '' ? ' style="' . esc($subgrupoCultivoStyle, 'attr') . '"' : '' ?>>
<?php if ($subgrupoTailBundleAtStart): ?>
<?= $layoutPlanApplier->beginSignatureTailBundleMarkup() ?>
<?php endif; ?>
<div class="<?= esc($cabeceraCultivoClass, 'attr') ?>">
<?= view('registers/analisis/partials/report_grupo_cabecera_content', [
    'padre'              => $padreTitulo,
    'hijo'               => $hijoTitulo,
    'tipo_muestra_linea' => $tipoMuestraLinea,
    'metodo_linea'       => $metodoLinea,
    'variant'            => $variant ?? 'web',
    'pdf_layout'         => $pdf_layout ?? [],
    'web_title_mt'       => $webTitleMt,
    'sub_idx'            => $subIdxCultivo,
    'grupo_es_primero'   => ! empty($grupo_es_primero),
]) ?>
</div>
<?php else: ?>
<?= view('registers/analisis/partials/report_grupo_cabecera_content', [
    'padre'              => $padreTitulo,
    'hijo'               => $hijoTitulo,
    'tipo_muestra_linea' => $tipoMuestraLinea,
    'metodo_linea'       => $metodoLinea,
    'variant'            => $variant ?? 'web',
    'pdf_layout'         => $pdf_layout ?? [],
    'web_title_mt'       => $webTitleMt,
    'sub_idx'            => $subIdxCultivo,
    'grupo_es_primero'   => ! empty($grupo_es_primero),
]) ?>
<?php endif; ?>

<?php foreach ($secciones as $sec):
    $tailSplitSec = ($layoutPlanApplier && $cultivoFirmaEnTailBundle)
        ? $layoutPlanApplier->sectionSignatureTailSplit($layoutAreaIndexCultivo, $layoutBlockIndexCultivo, $layoutSectionIndexCultivo)
        : null;
    $tailSplitAtSec = is_array($tailSplitSec) ? (int) ($tailSplitSec['splitAt'] ?? 0) : null;
    $tailFullInTailSec = is_array($tailSplitSec) && ! empty($tailSplitSec['fullInTail']);
    $segmentWrapClassSec = '';
    if ($usePdfChrome && $layoutPlanApplier) {
        $segmentWrapClassSec = trim($layoutPlanApplier->segmentClass($layoutAreaIndexCultivo, $layoutBlockIndexCultivo, $layoutSectionIndexCultivo));
    }
    if ($tailFullInTailSec) {
        $segmentWrapClassSec = trim(preg_replace('/\breport-segment-allow-split\b/', '', $segmentWrapClassSec));
        $segmentWrapClassSec = trim($segmentWrapClassSec . ' report-segment-tail-with-signature');
    }
    $segmentWrapDivClass = trim('report-segment-table-wrap' . ($segmentWrapClassSec !== '' ? ' ' . $segmentWrapClassSec : ''));
    $segmentWrapOpenAttrSec = ' class="' . esc($segmentWrapDivClass, 'attr') . '"' . $segmentWrapStyleAttr;

    $grillaReporte = ($esPersonalizadoMatriz && is_array($sec['grilla_reporte'] ?? null))
        ? $sec['grilla_reporte']
        : null;
    if ($grillaReporte !== null) {
        $titulosFilasGrilla = is_array($grillaReporte['titulos_filas'] ?? null) ? $grillaReporte['titulos_filas'] : [];
        $filasGrilla = is_array($grillaReporte['filas'] ?? null) ? $grillaReporte['filas'] : [];
        if ($titulosFilasGrilla === [] && $filasGrilla === []) {
            $layoutSectionIndexCultivo++;

            continue;
        }
        $reporteEstiloGrilla = is_array($sec['reporte_estilo'] ?? null) ? $sec['reporte_estilo'] : null;
        $secScopeClass = \App\Models\LabotestModel::personalizadoReporteBloqueScopeClass((string) ($sec['seccion'] ?? 'grilla'));
        $bordeScopedStyleGrilla = $reporteEstiloGrilla !== null
            ? \App\Models\LabotestModel::buildPersonalizadoReporteBordeScopedStyleBlock($secScopeClass, $reporteEstiloGrilla)
            : '';
        $tableStyleGrilla = $reporteEstiloGrilla !== null
            ? \App\Models\LabotestModel::buildPersonalizadoReporteTableStyleAttr($reporteEstiloGrilla)
            : '';
        $thStyleGrilla = $reporteEstiloGrilla !== null
            ? \App\Models\LabotestModel::buildPersonalizadoReporteThStyleAttr($reporteEstiloGrilla)
            : '';
        $tableGrillaClass = \App\Models\LabotestModel::buildPersonalizadoReporteTableClassAttr(
            $reporteEstiloGrilla ?? [],
            $mainTableClass . ' report-cultivo-grilla-personalizado w-100'
        );
        $tableGrillaStyleAttr = $tableStyleGrilla !== '' ? ' style="' . esc($tableStyleGrilla, 'attr') . '"' : '';
        $signatureTailBundleOpenSec = '';
        if ($usePdfChrome && $layoutPlanApplier !== null && $cultivoFirmaEnTailBundle && $tailFullInTailSec && ! $subgrupoTailBundleAtStart) {
            $signatureTailBundleOpenSec = $layoutPlanApplier->beginSignatureTailBundleMarkup();
        }
        $needsTailSplitSec = $tailSplitAtSec !== null && $tailSplitAtSec > 0 && count($filasGrilla) > $tailSplitAtSec;
        $renderGrillaBodyRows = static function (array $filas, int $startAt = 0, ?int $stopBefore = null) use ($renderCultivoCeldaGrilla, $reporteEstiloGrilla): void {
            $rowIdx = 0;
            foreach ($filas as $filaCeldas) {
                if ($rowIdx < $startAt) {
                    $rowIdx++;

                    continue;
                }
                if ($stopBefore !== null && $rowIdx >= $stopBefore) {
                    break;
                }
                echo '<tr>';
                foreach ($filaCeldas as $celdaGrilla) {
                    if (! is_array($celdaGrilla)) {
                        continue;
                    }
                    $renderCultivoCeldaGrilla($celdaGrilla, $reporteEstiloGrilla);
                }
                echo '</tr>';
                $rowIdx++;
            }
        };
        ?>
<div class="report-cultivo-seccion mb-3 report-cultivo-personalizado <?= esc($secScopeClass, 'attr') ?>">
    <?= $bordeScopedStyleGrilla ?>
    <?= $signatureTailBundleOpenSec ?>
    <div<?= $segmentWrapOpenAttrSec ?>>
        <table class="<?= esc($tableGrillaClass, 'attr') ?>"<?= $tableGrillaStyleAttr ?>>
            <?php if ($titulosFilasGrilla !== [] || $filasGrilla !== []): ?>
            <tbody>
                <?php foreach ($titulosFilasGrilla as $filaTitulos): ?>
                <tr>
                    <?php foreach ($filaTitulos as $tituloCell):
                        $tituloTexto = (string) ($tituloCell['texto'] ?? '');
                        if (trim($tituloTexto) === '') {
                            continue;
                        }
                        $renderCultivoTdCelda(
                            $tituloCell,
                            '',
                            max(1, (int) ($tituloCell['colspan'] ?? 1)),
                            1,
                            '',
                            $reporteEstiloGrilla
                        );
                    endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php $renderGrillaBodyRows($filasGrilla, 0, $needsTailSplitSec ? $tailSplitAtSec : null); ?>
            </tbody>
            <?php endif; ?>
        </table>
    </div>
    <?php if ($needsTailSplitSec): ?>
    <?php
        if ($cultivoFirmaEnTailBundle && $usePdfChrome && $layoutPlanApplier !== null && $signatureTailBundleOpenSec === '' && ! $layoutPlanApplier->isSignatureTailBundleOpen()) {
            echo $layoutPlanApplier->beginSignatureTailBundleMarkup();
        }
        $tailWrapClassSec = trim('report-segment-table-wrap report-segment-tail-with-signature report-signature-tail-continuation' . ($segmentWrapClassSec !== '' ? ' ' . preg_replace('/\breport-segment-allow-split\b/', '', $segmentWrapClassSec) : ''));
    ?>
    <div class="<?= esc($tailWrapClassSec, 'attr') ?>"<?= $segmentWrapStyleAttr ?>>
        <table class="<?= esc($tableGrillaClass . ' report-segment-thead-continuation', 'attr') ?>"<?= $tableGrillaStyleAttr ?>>
            <tbody>
                <?php $renderGrillaBodyRows($filasGrilla, $tailSplitAtSec); ?>
            </tbody>
        </table>
    </div>
    <?php
        $renderCultivoFirmaDentroDelBundle();
        $cerrarCultivoFirmaBundle();
    ?>
    <?php else: ?>
    <?php
        $renderCultivoFirmaDentroDelBundle();
        $cerrarCultivoFirmaBundle();
    ?>
    <?php endif; ?>
</div>
        <?php
        $layoutSectionIndexCultivo++;

        continue;
    }

    $columnasDetalle = is_array($sec['columnas_detalle'] ?? null) ? $sec['columnas_detalle'] : [];
    $titulosBanda = is_array($sec['titulos_banda'] ?? null) ? $sec['titulos_banda'] : [];
    if ($columnasDetalle === []) {
        $layoutSectionIndexCultivo++;

        continue;
    }
    $numColsVisible = count($columnasDetalle);
    $colWidthPct = $numColsVisible > 0 ? (100 / $numColsVisible) : 100;
    $bandaColspan = max(1, $numColsVisible);
    $tieneBanda = $titulosBanda !== [];
    $colTdStyleAttr = $usePdfChrome
        ? ' style="width:' . esc((string) $colWidthPct, 'attr') . '%;vertical-align:top;padding:0;border:none;"'
        : '';
    $layoutTableStyle = $usePdfChrome
        ? ' style="width:100%;table-layout:fixed;border-collapse:collapse;border-spacing:0;margin-top:0;"'
        : '';
    $tableFixedStyle = $usePdfChrome
        ? ' style="width:100%;table-layout:fixed;margin-top:0;margin-bottom:0;"'
        : '';
    $tableColStyle = $usePdfChrome
        ? ' style="width:100%;table-layout:fixed;margin-top:0;margin-bottom:0;' . ($tieneBanda ? 'border-top:none;' : '') . '"'
        : '';
    $thJoinStyle = ($usePdfChrome && $tieneBanda) ? ' style="border-top:none;"' : '';
    $secIdReport = (string) ($sec['seccion'] ?? '');
    $secTipoReport = (string) ($sec['tipo'] ?? $secIdReport);
    $alineacionFilas = (string) ($sec['alineacion_filas'] ?? 'centro');
    if ($alineacionFilas === 'cuerpo') {
        $alineacionFilas = 'centro';
    }
    if (! in_array($alineacionFilas, ['centro', 'bordes'], true)) {
        $alineacionFilas = 'centro';
    }
    $secClassAlineacion = ($secTipoReport === 'cuerpo' && ! $esPersonalizadoMatriz)
        ? ' report-cultivo-alineacion-' . esc($alineacionFilas, 'attr')
        : '';
    $tdClassCelda = ($esPersonalizadoMatriz || ($secTipoReport === 'cuerpo' && $alineacionFilas === 'bordes'))
        ? ''
        : 'text-center';
    $reporteEstilo = ($esPersonalizadoMatriz && is_array($sec['reporte_estilo'] ?? null))
        ? $sec['reporte_estilo']
        : null;
    $tableStylePersonalizado = $reporteEstilo !== null
        ? \App\Models\LabotestModel::buildPersonalizadoReporteTableStyleAttr($reporteEstilo)
        : '';
    $thStylePersonalizado = $reporteEstilo !== null
        ? \App\Models\LabotestModel::buildPersonalizadoReporteThStyleAttr($reporteEstilo)
        : '';
    $secScopeClass = \App\Models\LabotestModel::personalizadoReporteBloqueScopeClass((string) ($sec['seccion'] ?? 'columnas'));
    $bordeScopedStyle = $reporteEstilo !== null
        ? \App\Models\LabotestModel::buildPersonalizadoReporteBordeScopedStyleBlock($secScopeClass, $reporteEstilo)
        : '';
    $mergeStyleAttr = static function (string $base, string $extra): string {
        $merged = trim($base . ($base !== '' && $extra !== '' ? ';' : '') . $extra);

        return $merged !== '' ? ' style="' . esc($merged, 'attr') . '"' : '';
    };
    $secClassPersonalizado = $esPersonalizadoMatriz ? ' report-cultivo-personalizado' : '';
    $tableClassPersonalizado = ($reporteEstilo !== null)
        ? \App\Models\LabotestModel::buildPersonalizadoReporteTableClassAttr($reporteEstilo, $mainTableClass)
        : $mainTableClass;
?>
<div class="report-cultivo-seccion mb-3<?= $secClassAlineacion ?><?= $secClassPersonalizado ?> <?= esc($secScopeClass, 'attr') ?>">
    <?= $bordeScopedStyle ?>
    <div<?= $segmentWrapOpenAttrSec ?>>
        <?php if ($tieneBanda): ?>
        <div class="report-cultivo-banda w-100">
            <table class="<?= esc($tableClassPersonalizado, 'attr') ?>"<?= $mergeStyleAttr($tableFixedStyle !== '' ? trim(str_replace([' style="', '"'], '', $tableFixedStyle)) : '', $tableStylePersonalizado) ?>>
                <tbody>
                    <?php foreach ($titulosBanda as $filaTitulos): ?>
                    <tr>
                        <?php foreach ($filaTitulos as $tituloCell):
                            $tituloTexto = (string) ($tituloCell['texto'] ?? '');
                            $tituloColspan = max(1, (int) ($tituloCell['colspan'] ?? 1));
                            if ($tituloColspan < $bandaColspan) {
                                $tituloColspan = $bandaColspan;
                            }
                            $renderCultivoTdCelda(
                                $tituloCell,
                                '',
                                $tituloColspan,
                                1,
                                '',
                                $reporteEstilo
                            );
                        endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php
        $renderCultivoColumnaTabla = static function (array $colDet) use (
            $tableClassPersonalizado,
            $tableColStyle,
            $tdClassCelda,
            $renderCultivoTdCelda,
            $tableStylePersonalizado,
            $mergeStyleAttr,
            $reporteEstilo
        ): void {
            $titulosFilasCol = is_array($colDet['titulos_filas'] ?? null) ? $colDet['titulos_filas'] : [];
            $valoresCol = is_array($colDet['valores'] ?? null) ? $colDet['valores'] : [];
            if ($titulosFilasCol === [] && $valoresCol === []) {
                return;
            }
            $tableBaseStyle = $tableColStyle !== ''
                ? trim(str_replace([' style="', '"'], '', $tableColStyle))
                : '';
            ?>
                <table class="<?= esc($tableClassPersonalizado, 'attr') ?>"<?= $mergeStyleAttr($tableBaseStyle, $tableStylePersonalizado) ?>>
                    <tbody>
                        <?php foreach ($titulosFilasCol as $filaTitulos): ?>
                        <tr>
                            <?php foreach ($filaTitulos as $tituloCell):
                                $renderCultivoTdCelda(
                                    $tituloCell,
                                    '',
                                    1,
                                    1,
                                    '',
                                    $reporteEstilo
                                );
                            endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($valoresCol as $celdaVal): ?>
                        <tr>
                            <?php
                            $renderCultivoTdCelda(
                                $celdaVal,
                                $tdClassCelda,
                                1,
                                1,
                                '',
                                $reporteEstilo
                            );
                            ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php
        };
        ?>
        <?php if ($usePdfChrome): ?>
        <div class="report-cultivo-columnas w-100">
            <table class="report-cultivo-layout-table"<?= $layoutTableStyle ?>>
                <tr>
                    <?php foreach ($columnasDetalle as $colDet):
                        $titulosFilasCol = is_array($colDet['titulos_filas'] ?? null) ? $colDet['titulos_filas'] : [];
                        $valoresCol = is_array($colDet['valores'] ?? null) ? $colDet['valores'] : [];
                        if ($titulosFilasCol === [] && $valoresCol === []) {
                            continue;
                        }
                    ?>
                    <td class="report-cultivo-columna-td"<?= $colTdStyleAttr ?>>
                        <?php $renderCultivoColumnaTabla($colDet); ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </table>
        </div>
        <?php else: ?>
        <div class="report-cultivo-columnas w-100">
            <?php foreach ($columnasDetalle as $colDet):
                $titulosFilasCol = is_array($colDet['titulos_filas'] ?? null) ? $colDet['titulos_filas'] : [];
                $valoresCol = is_array($colDet['valores'] ?? null) ? $colDet['valores'] : [];
                if ($titulosFilasCol === [] && $valoresCol === []) {
                    continue;
                }
            ?>
            <div class="report-cultivo-columna flex-fill">
                <?php $renderCultivoColumnaTabla($colDet); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $layoutSectionIndexCultivo++; ?>
<?php endforeach; ?>

<?php if ($usePdfChrome && $isLastSubgrupoCultivo): ?>
    <?php
    $renderCultivoFirmaDentroDelBundle();
    $cerrarCultivoFirmaBundle();
    ?>
<?php endif; ?>

<?php if ($usePdfChrome): ?>
</div>
<?php endif; ?>
