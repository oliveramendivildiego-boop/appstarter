<?php
/**
 * Prueba compuesta: bloques consecutivos. Orden: título separador → cabecera de tabla → filas.
 *
 * @var string $padre
 * @var list<object|array<string,mixed>> $items
 * @var string $variant 'web' | 'pdf' | 'screen_pdf' (misma maquetación que PDF + inputs editables en pantalla)
 * @var array<int,string> $report_pria_tipo_muestra_nombre prianacategoria_id => nombre (config. en análisis clínico)
 * @var array<int,string> $report_pria_metodo_nombre prianacategoria_id => nombre del método (config.)
 * @var array<int,list<array<string,mixed>>> $report_pria_refs_consolidada tabla consolidada de refs. por población (pruebas compuestas)
 */
$variant = $variant ?? 'web';
$usePdfChrome = ($variant === 'pdf' || $variant === 'screen_pdf');

$hijo = '';
$priaIdTitulo = 0;
if (! empty($items[0])) {
    $first = $items[0];
    $firstObj = is_array($first) ? (object) $first : $first;
    $hijo = $firstObj->hijo ?? '';
}
foreach ($items as $rawPria) {
    $op = is_array($rawPria) ? (object) $rawPria : $rawPria;
    $pid = (int) ($op->prianacategoria_id ?? 0);
    if ($pid > 0) {
        $priaIdTitulo = $pid;
        break;
    }
}
$nombresTipoPorPria = $report_pria_tipo_muestra_nombre ?? [];
$tipoMuestraLinea = trim((string) ($nombresTipoPorPria[$priaIdTitulo] ?? ''));
$mostrarTipoMuestra = $tipoMuestraLinea !== '';
$nombresMetodoPorPria = $report_pria_metodo_nombre ?? [];
$metodoLinea = trim((string) ($nombresMetodoPorPria[$priaIdTitulo] ?? ''));
$mostrarMetodo = $metodoLinea !== '';

$segments = [];
$cur = ['title' => null, 'items' => []];
foreach ($items as $raw) {
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
foreach ($segments as $segTmp) {
    foreach ($segTmp['items'] as $rawIt) {
        $itTmp = is_array($rawIt) ? (object) $rawIt : $rawIt;
        $vTmp = trim((string) ($itTmp->regvalues ?? ''));
        if (($vTmp !== '' && $vTmp !== '-') || !empty($itTmp->show_reference)) {
            $groupTieneAlgunResultado = true;
            break 2;
        }
    }
}
?>
<?php if ($groupTieneAlgunResultado): ?>
<?php if ($usePdfChrome): ?>
<div class="group-title"><?= esc($padre) ?> - <?= esc($hijo) ?></div>
<?php if ($mostrarTipoMuestra): ?>
<div class="report-tipo-muestra" style="font-size:9pt;color:#555;margin:0 0 10px 0;line-height:1.3;">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></div>
<?php endif; ?>
<?php if ($mostrarMetodo): ?>
<div class="report-metodo-prueba" style="font-size:9pt;color:#555;margin:0 0 10px 0;line-height:1.3;">Método: <?= esc($metodoLinea) ?></div>
<?php endif; ?>
<?php else: ?>
<h4 class="mt-4 mb-1"><?= esc($padre) ?> - <?= esc($hijo) ?></h4>
<?php if ($mostrarTipoMuestra || $mostrarMetodo): ?>
<div class="small text-muted mb-3">
    <?php if ($mostrarTipoMuestra): ?>
    <p class="mb-0">Tipo de Muestra: <?= esc($tipoMuestraLinea) ?></p>
    <?php endif; ?>
    <?php if ($mostrarMetodo): ?>
    <p class="mb-0<?= $mostrarTipoMuestra ? ' mt-1' : '' ?>">Método: <?= esc($metodoLinea) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php foreach ($segments as $seg): ?>
    <?php
    $titleObj = $seg['title'];
    $segItems = $seg['items'];
    $conRefEnSeg = false;
    foreach ($segItems as $it) {
        $it = is_array($it) ? (object) $it : $it;
        $valTmp = trim((string) ($it->regvalues ?? ''));
        $mustShowRef = !empty($it->show_reference);
        if ((($valTmp !== '' && $valTmp !== '-') || $mustShowRef) && registro_tiene_rango_referencial($it->valor_min ?? '', $it->valor_max ?? '')) {
            $conRefEnSeg = true;
            break;
        }
    }
    $mainTableClass = $usePdfChrome ? 'results' : 'table mb-0';
    $wrapOpen = ! $usePdfChrome ? '<div class="table-responsive mb-3">' : '<div class="report-segment-table-wrap">';
    $wrapClose = '</div>';
    $tieneConResultado = false;
    foreach ($segItems as $itChk) {
        $itChk = is_array($itChk) ? (object) $itChk : $itChk;
        $vChk = trim((string) ($itChk->regvalues ?? ''));
        if (($vChk !== '' && $vChk !== '-') || !empty($itChk->show_reference)) {
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
        <table class="<?= esc($mainTableClass) ?>">
            <thead<?= $usePdfChrome ? '' : ' class="thead-dark"' ?>>
                <tr>
                    <th>ANÁLISIS</th>
                    <th class="text-center">RESULTADO</th>
                    <?php if ($conRefEnSeg): ?>
                    <th class="text-center">RANGO REFERENCIAL</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($segItems as $item): ?>
                    <?php
                    $item = is_array($item) ? (object) $item : $item;
                    $valTmp = trim((string) ($item->regvalues ?? ''));
                    $mustShowRef = !empty($item->show_reference);
                    if (($valTmp === '' || $valTmp === '-') && !$mustShowRef) {
                        continue;
                    }
                    $aid = $item->secanacategoria_id ?? uniqid();
                    ?>
                    <?php if ($variant !== 'pdf'): ?>
                    <input type="hidden" id="analisis_<?= esc($aid) ?>" name="analisis_<?= esc($aid) ?>" class="analisis" padre="<?= esc($padre) ?>" hijo="<?= esc($hijo) ?>" analisis="<?= esc($item->nombre ?? '') ?>" value="<?= esc($item->regvalues ?? '') ?>" unidad="<?= esc($item->umedida ?? '') ?>" min="<?= esc($item->valor_min ?? '') ?>" max="<?= esc($item->valor_max ?? '') ?>">
                    <?php endif; ?>
                    <?php
                    $val = $item->regvalues ?? '';
                    if (! $val) {
                        $item->regvalues = '-';
                        $val = '-';
                    }
                    $valNorm = trim(strtolower((string) $val));
                    if (in_array($valNorm, ['positivo', 'reactivo'], true)) {
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
                    $resMostrar = registro_resultado_con_unidad($item->regvalues ?? '', $item->umedida ?? '');
                    $refMostrar = registro_rango_referencial_texto($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
                    ?>
                    <?php if (is_object($item)): ?>
                        <?php $itemConRef = registro_tiene_rango_referencial($item->valor_min ?? '', $item->valor_max ?? ''); ?>
                        <tr>
                            <td><?= esc($item->nombre ?? '') ?></td>
                            <?php if (! $conRefEnSeg): ?>
                            <td class="text-center <?= $class ?><?= $usePdfChrome && $isOutPdf ? ' out-range' : '' ?>"><?= esc($resMostrar) ?></td>
                            <?php elseif ($itemConRef): ?>
                            <td class="text-center <?= $class ?><?= $usePdfChrome && $isOutPdf ? ' out-range' : '' ?>"><?= esc($resMostrar) ?></td>
                            <td class="text-center<?= $usePdfChrome ? ' ref-range' : '' ?>"><?= esc($refMostrar) ?></td>
                            <?php else: ?>
                            <td class="text-center <?= $class ?><?= $usePdfChrome && $isOutPdf ? ' out-range' : '' ?>" colspan="2"><?= esc($resMostrar) ?></td>
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
$refsMatrixAll = $report_pria_refs_consolidada ?? [];
if ($priaIdTitulo > 0 && ! empty($refsMatrixAll[$priaIdTitulo]) && $groupTieneAlgunResultado) :
    $matrixRows = $refsMatrixAll[$priaIdTitulo];
    $showSexoCol = false;
    foreach ($matrixRows as $mr) {
        $sx = strtolower(trim((string) ($mr['sexo'] ?? '')));
        if ($sx !== '' && $sx !== 'ambos') {
            $showSexoCol = true;
            break;
        }
    }
    $matrixWrapOpen = ! $usePdfChrome ? '<div class="table-responsive mb-3">' : '<div class="report-segment-table-wrap report-refs-matrix-wrap">';
    $matrixTableClass = $usePdfChrome ? 'results report-refs-matrix' : 'table table-sm table-bordered mb-0';
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
                <th>Grupo poblacional</th>
                <th>Parámetro</th>
                <?php if ($showSexoCol): ?>
                <th class="text-center">Sexo</th>
                <?php endif; ?>
                <th class="text-center">Valor de referencia</th>
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
                $refTxt = registro_rango_referencial_texto($mrow['valor_min'] ?? '', $mrow['valor_max'] ?? '', $mrow['umedida'] ?? '');
                ?>
            <tr>
                <td><?= esc(trim((string) ($mrow['poblacion_nombre'] ?? ''))) ?></td>
                <td><?= esc(trim((string) ($mrow['nombre'] ?? ''))) ?></td>
                <?php if ($showSexoCol): ?>
                <td class="text-center"><?= $sexoTxt ?></td>
                <?php endif; ?>
                <td class="text-center"><?= esc($refTxt) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>
