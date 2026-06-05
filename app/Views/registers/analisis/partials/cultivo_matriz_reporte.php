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
$usePdfChrome = (($variant ?? 'web') === 'pdf' || ($variant ?? 'web') === 'screen_pdf');
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
$segmentWrapStyle = '';
if ($usePdfChrome) {
    $segmentWrapStyle = \App\Services\ReportPdfLayoutService::grupoPruebaSegmentIntactStyleAttr(
        is_array($pdf_layout ?? null) ? $pdf_layout : []
    );
}
$segmentWrapStyleAttr = $segmentWrapStyle !== '' ? ' style="' . esc($segmentWrapStyle, 'attr') . '"' : '';
$mainTableClass = $usePdfChrome ? 'results' : 'table table-bordered table-sm mb-0';
$webTitleMt = ($sub_idx ?? 0) > 0 ? 'mt-5' : 'mt-4';
$renderCultivoCelda = static function (string $cellHtml, string $tdClass = 'text-center'): void {
    $isBordesHtml = str_contains($cellHtml, 'cultivo-celda-bordes');
    $isHtml = $isBordesHtml || ($cellHtml !== '' && $cellHtml !== strip_tags($cellHtml));
    echo '<td class="' . esc($tdClass, 'attr') . ' align-middle' . ($isHtml ? ' cultivo-celda-html' : '') . '">';
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
<div class="report-pdf-subgrupo-block">
<div class="report-pdf-grupo-cabecera">
<div class="group-title"><?= esc($padreTitulo) ?><?= ($padreTitulo !== '' && $hijoTitulo !== '') ? ' - ' : '' ?><?= esc($hijoTitulo) ?></div>
<?php if ($tipoMuestraLinea !== ''): ?>
<div class="report-tipo-muestra" style="font-size:9pt;color:#555;margin:0 0 10px 0;line-height:1.3;">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></div>
<?php endif; ?>
<?php if ($metodoLinea !== ''): ?>
<div class="report-metodo-prueba" style="font-size:9pt;color:#555;margin:0 0 10px 0;line-height:1.3;">Método: <?= esc($metodoLinea) ?></div>
<?php endif; ?>
</div>
<?php else: ?>
<h4 class="<?= esc($webTitleMt, 'attr') ?> mb-1"><?= esc($padreTitulo) ?><?= ($padreTitulo !== '' && $hijoTitulo !== '') ? ' - ' : '' ?><?= esc($hijoTitulo) ?></h4>
<?php if ($tipoMuestraLinea !== '' || $metodoLinea !== ''): ?>
<div class="small text-muted mb-3">
    <?php if ($tipoMuestraLinea !== ''): ?>
    <p class="mb-0">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></p>
    <?php endif; ?>
    <?php if ($metodoLinea !== ''): ?>
    <p class="mb-0<?= $tipoMuestraLinea !== '' ? ' mt-1' : '' ?>">Método: <?= esc($metodoLinea) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>
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
    $colsWrapStyle = $usePdfChrome
        ? ' style="display:flex;width:100%;flex-wrap:nowrap;align-items:stretch;margin-top:0;"'
        : '';
    $colStyleAttr = $usePdfChrome
        ? ' style="flex:1 1 0;min-width:0;width:' . esc((string) $colWidthPct, 'attr') . '%;"'
        : '';
    $tableFixedStyle = $usePdfChrome
        ? ' style="width:100%;table-layout:fixed;margin-top:0;margin-bottom:0;"'
        : '';
    $tableColStyle = $usePdfChrome
        ? ' style="width:100%;table-layout:fixed;margin-top:0;margin-bottom:0;' . ($tieneBanda ? 'border-top:none;' : '') . '"'
        : '';
    $thJoinStyle = ($usePdfChrome && $tieneBanda) ? ' style="border-top:none;"' : '';
    $secIdReport = (string) ($sec['seccion'] ?? '');
    $alineacionFilas = (string) ($sec['alineacion_filas'] ?? 'centro');
    if ($alineacionFilas === 'cuerpo') {
        $alineacionFilas = 'centro';
    }
    if (! in_array($alineacionFilas, ['centro', 'bordes'], true)) {
        $alineacionFilas = 'centro';
    }
    $secClassAlineacion = ($secIdReport === 'cuerpo')
        ? ' report-cultivo-alineacion-' . esc($alineacionFilas, 'attr')
        : '';
    $tdClassCelda = ($secIdReport === 'cuerpo' && $alineacionFilas === 'bordes') ? 'text-start' : 'text-center';
?>
<div class="report-cultivo-seccion mb-3<?= $secClassAlineacion ?>">
    <div class="report-segment-table-wrap"<?= $segmentWrapStyleAttr ?>>
        <?php if ($tieneBanda): ?>
        <div class="report-cultivo-banda w-100">
            <table class="<?= esc($mainTableClass, 'attr') ?>"<?= $tableFixedStyle ?>>
                <thead>
                    <?php foreach ($titulosBanda as $filaTitulos): ?>
                    <tr>
                        <?php foreach ($filaTitulos as $thCell):
                            $thTexto = (string) ($thCell['texto'] ?? '');
                            $thColspan = max(1, (int) ($thCell['colspan'] ?? 1));
                            if ($thColspan < $bandaColspan) {
                                $thColspan = $bandaColspan;
                            }
                        ?>
                        <th class="text-center" colspan="<?= (int) $thColspan ?>"><?= esc($thTexto) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </thead>
            </table>
        </div>
        <?php endif; ?>

        <div class="report-cultivo-columnas w-100"<?= $colsWrapStyle ?>>
            <?php foreach ($columnasDetalle as $colDet):
                $titulosFilasCol = is_array($colDet['titulos_filas'] ?? null) ? $colDet['titulos_filas'] : [];
                $valoresCol = is_array($colDet['valores'] ?? null) ? $colDet['valores'] : [];
                if ($titulosFilasCol === [] && $valoresCol === []) {
                    continue;
                }
            ?>
            <div class="report-cultivo-columna flex-fill"<?= $colStyleAttr ?>>
                <table class="<?= esc($mainTableClass, 'attr') ?>"<?= $tableColStyle ?>>
                    <?php if ($titulosFilasCol !== []): ?>
                    <thead>
                        <?php foreach ($titulosFilasCol as $filaTitulos): ?>
                        <tr>
                            <?php foreach ($filaTitulos as $thCell):
                                $thTexto = (string) ($thCell['texto'] ?? '');
                            ?>
                            <th class="text-center"<?= $thJoinStyle ?>><?= esc($thTexto) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </thead>
                    <?php endif; ?>
                    <?php if ($valoresCol !== []): ?>
                    <tbody>
                        <?php foreach ($valoresCol as $cellHtml): ?>
                        <tr>
                            <?php $renderCultivoCelda((string) $cellHtml, $tdClassCelda); ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endif; ?>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if ($usePdfChrome): ?>
</div>
<?php endif; ?>
