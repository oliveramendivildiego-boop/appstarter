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
 * @var bool $report_forzar_col_ref Si el reporte tiene refs., alinear 3 columnas en todas las tablas PDF
 */
$variant = $variant ?? 'web';
$usePdfChrome = in_array($variant, ['pdf', 'screen_pdf', 'browser_print'], true);
$reportForzarColRef = ! empty($report_forzar_col_ref) && $usePdfChrome;
$pdfGrupoPbService = ($variant === 'pdf' && ($pdf_grupo_pb_service ?? null) instanceof \App\Services\ReportPdfDompdfGrupoPageBreakService)
    ? $pdf_grupo_pb_service
    : null;
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
$ocultarTheadResults = \App\Services\ReportPdfLayoutService::grupoCabeceraOcultarTheadResultsTabla($pdfLayout);
$labConfigLocal = is_array($lab_config ?? null) ? $lab_config : [];
$showInterpretacionCol = in_array($variant, ['screen_pdf', 'pdf', 'browser_print'], true)
    && (($labConfigLocal['interpretacion_enabled'] ?? '0') === '1');

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
        echo view('registers/analisis/partials/cultivo_matriz_reporte', [
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
        ]);
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
        && $subgrupoTotalFilas <= \App\Services\ReportPdfLayoutService::subgrupoKeepIntactMaxRows();

    $subgrupoWrapClass = $subIdx > 0 ? ' report-pdf-subgrupo-prueba' : '';
    $webTitleMt = $subIdx > 0 ? 'mt-5' : 'mt-4';
    $subgrupoGapStyle = $subIdx > 0
        ? \App\Services\ReportPdfLayoutService::subgrupoPruebaGapStyleAttr(
            is_array($pdf_layout ?? null) ? $pdf_layout : [],
            true
        )
        : '';
    $subgrupoPbPeek = $pdfGrupoPbService ? $pdfGrupoPbService->peekNextPlacementAttrs() : ['segment_class' => '', 'cabecera_class' => '', 'subgrupo_class' => ''];
    $subgrupoPbClass = trim(
        'report-pdf-subgrupo-block'
        . $subgrupoWrapClass
        . ($subgrupoKeepIntact ? ' report-subgrupo-keep-intact' : '')
        . ($subgrupoPbPeek['subgrupo_class'] !== '' ? ' ' . $subgrupoPbPeek['subgrupo_class'] : '')
    );
    $cabeceraPbClass = trim('report-pdf-grupo-cabecera' . ($subgrupoPbPeek['cabecera_class'] !== '' ? ' ' . $subgrupoPbPeek['cabecera_class'] : ''));
?>
<?php if ($usePdfChrome): ?>
<div class="<?= esc($subgrupoPbClass, 'attr') ?>"<?= $subgrupoGapStyle !== '' ? ' style="' . esc($subgrupoGapStyle, 'attr') . '"' : '' ?>>
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
<?php foreach ($segments as $segIdx => $seg): ?>
    <?php
    $titleObj = $seg['title'];
    $segItems = $seg['items'];
    $hasMatrixAfter = $priaIdTitulo > 0 && ! empty($refsMatrixAll[$priaIdTitulo]);
    $segmentWrapClass = '';
    if ($pdfGrupoPbService) {
        $segmentPb = $pdfGrupoPbService->consumePlacementAttrs();
        $segmentWrapClass = trim($segmentPb['segment_class']);
    }
    $conRefEnSeg = false;
    foreach ($segItems as $it) {
        $it = is_array($it) ? (object) $it : $it;
        $valTmp = trim((string) ($it->regvalues ?? ''));
        $mustShowRef = ! empty($it->show_reference);
        if ((($valTmp !== '' && $valTmp !== '-') || $mustShowRef) && registro_tiene_rango_referencial($it->valor_min ?? '', $it->valor_max ?? '')) {
            $conRefEnSeg = true;
            break;
        }
    }
    $mostrarColInterpretacion = $showInterpretacionCol && $conRefEnSeg;
    $mostrarColRef = $conRefEnSeg || $reportForzarColRef;
    $resultsColCount = 2 + ($mostrarColRef ? 1 : 0) + ($mostrarColInterpretacion ? 1 : 0);
    $resultsColClass = $usePdfChrome && $resultsColCount >= 3
        ? ' results-cols-' . $resultsColCount
        : '';
    $mainTableClass = ($usePdfChrome ? 'results' : 'table mb-0') . $resultsColClass;
    $resultsTableLayoutAttrs = ($usePdfChrome && $resultsColCount >= 3)
        ? \App\Services\ReportPdfLayoutService::resultsTableFixedLayoutAttrs($resultsColCount)
        : '';
    $resultsColgroupHtml = ($usePdfChrome && $resultsColCount >= 3)
        ? \App\Services\ReportPdfLayoutService::resultsTableColgroupHtml($resultsColCount)
        : '';
    $thWidth = static function (int $colIdx) use ($usePdfChrome, $resultsColCount): string {
        return \App\Services\ReportPdfLayoutService::resultsTableThWidthStyleAttr($colIdx, $resultsColCount, $usePdfChrome);
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
    ?>
    <?php if ($tieneConResultado): ?>
    <?= $wrapOpen ?>
        <?php if ($titleObj !== null): ?>
            <?php if ($usePdfChrome): ?>
            <div class="report-segment-title pdf-card-header"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php else: ?>
            <div class="report-segment-title-web px-2 py-2 mb-2 bg-secondary bg-opacity-10 border-start border-4 border-secondary rounded-end fw-semibold text-uppercase small"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <table class="<?= esc($mainTableClass) ?>"<?= $resultsTableLayoutAttrs ?>>
            <?= $resultsColgroupHtml ?>
            <?php if (! $ocultarTheadResults): ?>
            <thead<?= $usePdfChrome ? '' : ' class="thead-dark"' ?>>
                <tr>
                    <th<?= $thWidth(0) ?>>ANÁLISIS</th>
                    <th class="text-center"<?= $thWidth(1) ?>>RESULTADO</th>
                    <?php if ($mostrarColRef): ?>
                    <th class="text-center"<?= $thWidth(2) ?>>RANGO REFERENCIAL</th>
                    <?php endif; ?>
                    <?php if ($mostrarColInterpretacion): ?>
                    <th class="text-center"<?= $thWidth($mostrarColRef ? 3 : 2) ?>>INTERPRETACIÓN</th>
                    <?php endif; ?>
                </tr>
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
                    $itemConRef = registro_tiene_rango_referencial($item->valor_min ?? '', $item->valor_max ?? '');
                    $interpretacionRef = ($mostrarColInterpretacion && $itemConRef)
                        ? registro_interpretacion_referencial_etiqueta_viewreport(
                            $val,
                            $item->valor_min ?? '',
                            $item->valor_max ?? '',
                            $item->umedida ?? '',
                            $opcionIdItem
                        )
                        : null;
                    if ($mostrarColInterpretacion && $interpretacionRef !== null) {
                        $class = registro_interpretacion_referencial_clase_resultado($interpretacionRef);
                        $isOutPdf = $interpretacionRef['nivel'] !== 'normal';
                    } elseif (in_array($valNorm, ['positivo', 'reactivo'], true)) {
                        $class = 'text-danger font-weight-bold';
                        $isOutPdf = true;
                    } elseif (is_numeric($val) && ($item->valor_min ?? '') !== '' && ($item->valor_max ?? '') !== '') {
                        $inRange = ($val >= $item->valor_min && $val <= $item->valor_max);
                        $class = $inRange ? 'normal' : 'text-danger font-weight-bold';
                        $isOutPdf = ! $inRange;
                    } else {
                        $class = 'normal';
                        $isOutPdf = false;
                    }
                    $mostrarMedidaSoloRef = registro_mostrar_medida_solo_en_referencia($item);
                    $resMostrarHtml = registro_resultado_celda_html($item->regvalues ?? '', $item->umedida ?? '', $opcionIdItem, $mostrarMedidaSoloRef);
                    $refMostrar = registro_rango_referencial_html($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
                    $celdaRicoClass = (registro_opcion_es_texto_rico($opcionIdItem) || registro_opcion_es_texto_fijo($opcionIdItem) || registro_valor_contiene_html_rico((string) ($item->regvalues ?? ''))) ? ' resultado-texto-rico-cell' : '';
                    ?>
                    <?php if (is_object($item)): ?>
                        <tr>
                            <td><?= esc($item->nombre ?? '') ?></td>
                            <?php if (! $mostrarColRef): ?>
                            <td class="text-center<?= $celdaRicoClass ?> <?= $class ?><?= $usePdfChrome && $isOutPdf ? ' out-range' : '' ?>"><?= $resMostrarHtml ?></td>
                            <?php else: ?>
                            <td class="text-center<?= $celdaRicoClass ?> <?= $class ?><?= $usePdfChrome && $isOutPdf ? ' out-range' : '' ?>"><?= $resMostrarHtml ?></td>
                            <td class="text-center<?= $usePdfChrome ? ' ref-range' : '' ?>"><?= $itemConRef ? $refMostrar : '' ?></td>
                            <?php endif; ?>
                            <?php if ($mostrarColInterpretacion): ?>
                            <td class="text-center<?= $interpretacionRef !== null ? ' ' . esc(registro_interpretacion_referencial_clase_resultado($interpretacionRef), 'attr') : '' ?>"><?= $interpretacionRef !== null ? esc($interpretacionRef['label']) : '' ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?= $wrapClose ?>
    <?php endif; ?>
<?php endforeach; ?>
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
    if ($pdfGrupoPbService) {
        $matrixPb = $pdfGrupoPbService->consumePlacementAttrs();
        $matrixWrapClass = trim($matrixPb['segment_class']);
    }
    $matrixTableClass = $usePdfChrome ? 'results report-refs-matrix' : 'table table-sm table-bordered mb-0';
    $matrixWrapOpen = ! $usePdfChrome ? '<div class="table-responsive mb-3">' : '<div class="report-segment-table-wrap report-refs-matrix-wrap' . ($matrixWrapClass !== '' ? ' ' . esc($matrixWrapClass, 'attr') : '') . '"' . $segmentWrapStyleAttr . '>';
    ?>
<?= $matrixWrapOpen ?>
    <?php if ($usePdfChrome): ?>
    <div class="report-refs-matrix-title pdf-card-header" style="margin-top:10px;">Valores de referencia por grupo poblacional</div>
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
<?php if ($usePdfChrome): ?>
</div>
<?php endif; ?>
<?php endforeach; ?>
