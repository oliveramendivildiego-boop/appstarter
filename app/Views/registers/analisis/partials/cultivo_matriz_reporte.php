<?php
/**
 * Reporte de prueba tipo cultivo (matriz encabezado / cuerpo / pie).
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
$esPersonalizadoMatriz = ! empty($cultivoItem->es_personalizado_matriz);
$segmentWrapStyle = '';
if ($usePdfChrome) {
    $segmentWrapStyle = \App\Services\ReportPdfLayoutService::grupoPruebaSegmentIntactStyleAttr(
        is_array($pdf_layout ?? null) ? $pdf_layout : []
    );
}
$segmentWrapStyleAttr = $segmentWrapStyle !== '' ? ' style="' . esc($segmentWrapStyle, 'attr') . '"' : '';
$mainTableClass = $usePdfChrome ? 'results' : 'table table-bordered table-sm mb-0';
$webTitleMt = ($sub_idx ?? 0) > 0 ? 'mt-5' : 'mt-4';
$renderCultivoCelda = static function (string $cellHtml, string $tdClass = 'text-center', string $tdStyleExtra = ''): void {
    $isBordesHtml = str_contains($cellHtml, 'cultivo-celda-bordes');
    $isHtml = $isBordesHtml || str_contains($cellHtml, 'pers-celda-reporte')
        || ($cellHtml !== '' && $cellHtml !== strip_tags($cellHtml));
    $classes = trim($tdClass . ' align-middle' . ($isHtml ? ' cultivo-celda-html' : ''));
    $styleAttr = $tdStyleExtra !== '' ? ' style="' . esc($tdStyleExtra, 'attr') . '"' : '';
    echo '<td class="' . esc($classes, 'attr') . '"' . $styleAttr . '>';
    if ($isHtml) {
        echo $cellHtml;
    } else {
        echo esc($cellHtml);
    }
    echo '</td>';
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
.report-cultivo-seccion.report-cultivo-personalizado .report-cultivo-columna td {
    text-align: inherit;
}
.report-cultivo-seccion.report-cultivo-personalizado .pers-celda-reporte {
    width: 100%;
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
$subIdxCultivo = (int) ($sub_idx ?? 0);
$subgrupoCultivoClass = 'report-pdf-subgrupo-block' . ($subIdxCultivo > 0 ? ' report-pdf-subgrupo-prueba' : '');
$subgrupoCultivoStyle = $subIdxCultivo > 0
    ? \App\Services\ReportPdfLayoutService::subgrupoPruebaGapStyleAttr(
        is_array($pdf_layout ?? null) ? $pdf_layout : [],
        true
    )
    : '';
?>
<div class="<?= esc($subgrupoCultivoClass, 'attr') ?>"<?= $subgrupoCultivoStyle !== '' ? ' style="' . esc($subgrupoCultivoStyle, 'attr') . '"' : '' ?>>
<div class="report-pdf-grupo-cabecera">
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
    $columnasDetalle = is_array($sec['columnas_detalle'] ?? null) ? $sec['columnas_detalle'] : [];
    $titulosBanda = is_array($sec['titulos_banda'] ?? null) ? $sec['titulos_banda'] : [];
    if ($columnasDetalle === []) {
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
    $tdBorderPersonalizado = $reporteEstilo !== null
        ? \App\Models\LabotestModel::buildPersonalizadoReporteTdBorderStyleAttr($reporteEstilo)
        : '';
    $mergeStyleAttr = static function (string $base, string $extra): string {
        $merged = trim($base . ($base !== '' && $extra !== '' ? ';' : '') . $extra);

        return $merged !== '' ? ' style="' . esc($merged, 'attr') . '"' : '';
    };
    $secClassPersonalizado = $esPersonalizadoMatriz ? ' report-cultivo-personalizado' : '';
?>
<div class="report-cultivo-seccion mb-3<?= $secClassAlineacion ?><?= $secClassPersonalizado ?>">
    <div class="report-segment-table-wrap"<?= $segmentWrapStyleAttr ?>>
        <?php if ($tieneBanda): ?>
        <div class="report-cultivo-banda w-100">
            <table class="<?= esc($mainTableClass, 'attr') ?>"<?= $mergeStyleAttr($tableFixedStyle !== '' ? trim(str_replace([' style="', '"'], '', $tableFixedStyle)) : '', $tableStylePersonalizado) ?>>
                <thead>
                    <?php foreach ($titulosBanda as $filaTitulos): ?>
                    <tr>
                        <?php foreach ($filaTitulos as $thCell):
                            $thTexto = (string) ($thCell['texto'] ?? '');
                            $thColspan = max(1, (int) ($thCell['colspan'] ?? 1));
                            if ($thColspan < $bandaColspan) {
                                $thColspan = $bandaColspan;
                            }
                            $thJoinMerged = trim(
                                ($thJoinStyle !== '' ? trim(str_replace([' style="', '"'], '', $thJoinStyle)) : '')
                                . ($thStylePersonalizado !== '' ? ($thJoinStyle !== '' ? ';' : '') . $thStylePersonalizado : '')
                            );
                            $thClass = $esPersonalizadoMatriz ? '' : 'text-center';
                        ?>
                        <th class="<?= esc($thClass, 'attr') ?>" colspan="<?= (int) $thColspan ?>"<?= $thJoinMerged !== '' ? ' style="' . esc($thJoinMerged, 'attr') . '"' : '' ?>><?= esc($thTexto) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </thead>
            </table>
        </div>
        <?php endif; ?>

        <?php
        $renderCultivoColumnaTabla = static function (array $colDet) use (
            $mainTableClass,
            $tableColStyle,
            $thJoinStyle,
            $tdClassCelda,
            $renderCultivoCelda,
            $tableStylePersonalizado,
            $thStylePersonalizado,
            $tdBorderPersonalizado,
            $esPersonalizadoMatriz,
            $mergeStyleAttr
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
                <table class="<?= esc($mainTableClass, 'attr') ?>"<?= $mergeStyleAttr($tableBaseStyle, $tableStylePersonalizado) ?>>
                    <?php if ($titulosFilasCol !== []): ?>
                    <thead>
                        <?php foreach ($titulosFilasCol as $filaTitulos): ?>
                        <tr>
                            <?php foreach ($filaTitulos as $thCell):
                                $thTexto = (string) ($thCell['texto'] ?? '');
                                $thJoinMerged = trim(
                                    ($thJoinStyle !== '' ? trim(str_replace([' style="', '"'], '', $thJoinStyle)) : '')
                                    . ($thStylePersonalizado !== '' ? ($thJoinStyle !== '' ? ';' : '') . $thStylePersonalizado : '')
                                );
                                $thClass = $esPersonalizadoMatriz ? '' : 'text-center';
                            ?>
                            <th class="<?= esc($thClass, 'attr') ?>"<?= $thJoinMerged !== '' ? ' style="' . esc($thJoinMerged, 'attr') . '"' : '' ?>><?= esc($thTexto) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </thead>
                    <?php endif; ?>
                    <?php if ($valoresCol !== []): ?>
                    <tbody>
                        <?php foreach ($valoresCol as $cellHtml): ?>
                        <tr>
                            <?php $renderCultivoCelda((string) $cellHtml, $tdClassCelda, $tdBorderPersonalizado); ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endif; ?>
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
<?php endforeach; ?>

<?php if ($usePdfChrome): ?>
</div>
<?php endif; ?>
