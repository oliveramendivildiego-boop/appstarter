<?php
/**
 * Prueba compuesta: bloques consecutivos. Orden: título separador → cabecera de tabla → filas.
 *
 * @var string $padre
 * @var list<object|array<string,mixed>> $items
 * @var string $variant 'web' (viewreport) o 'pdf' (PDF / impresión)
 */
$variant = $variant ?? 'web';
$isPdf = ($variant === 'pdf');

$hijo = '';
if (! empty($items[0])) {
    $first = $items[0];
    $hijo = is_object($first) ? ($first->hijo ?? '') : ($first['hijo'] ?? '');
}

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
?>
<?php if ($isPdf): ?>
<div class="group-title"><?= esc($padre) ?> - <?= esc($hijo) ?></div>
<?php else: ?>
<h4 class="mt-4"><?= esc($padre) ?> - <?= esc($hijo) ?></h4>
<?php endif; ?>
<?php foreach ($segments as $seg): ?>
    <?php
    $titleObj = $seg['title'];
    $segItems = $seg['items'];
    $conRefEnSeg = false;
    foreach ($segItems as $it) {
        $it = is_array($it) ? (object) $it : $it;
        $valTmp = trim((string) ($it->regvalues ?? ''));
        if ($valTmp !== '' && $valTmp !== '-' && registro_tiene_rango_referencial($it->valor_min ?? '', $it->valor_max ?? '')) {
            $conRefEnSeg = true;
            break;
        }
    }
    $mainTableClass = $isPdf ? 'results' : 'table mb-0';
    $wrapOpen = ! $isPdf ? '<div class="table-responsive mb-3">' : '<div class="report-segment-table-wrap">';
    $wrapClose = '</div>';
    $tieneConResultado = false;
    foreach ($segItems as $itChk) {
        $itChk = is_array($itChk) ? (object) $itChk : $itChk;
        $vChk = trim((string) ($itChk->regvalues ?? ''));
        if ($vChk !== '' && $vChk !== '-') {
            $tieneConResultado = true;
            break;
        }
    }
    ?>
    <?php if ($tieneConResultado): ?>
    <?= $wrapOpen ?>
        <?php if ($titleObj !== null): ?>
            <?php if ($isPdf): ?>
            <div class="report-segment-title"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php else: ?>
            <div class="report-segment-title-web px-2 py-2 mb-2 bg-secondary bg-opacity-10 border-start border-4 border-secondary rounded-end fw-semibold text-uppercase small"><?= esc($titleObj->nombre ?? '') ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <table class="<?= esc($mainTableClass) ?>">
            <thead<?= $isPdf ? '' : ' class="thead-dark"' ?>>
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
                    if ($valTmp === '' || $valTmp === '-') {
                        continue;
                    }
                    $aid = $item->secanacategoria_id ?? uniqid();
                    ?>
                    <?php if (! $isPdf): ?>
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
                            <td class="text-center <?= $class ?><?= $isPdf && $isOutPdf ? ' out-range' : '' ?>"><?= esc($resMostrar) ?></td>
                            <?php elseif ($itemConRef): ?>
                            <td class="text-center <?= $class ?><?= $isPdf && $isOutPdf ? ' out-range' : '' ?>"><?= esc($resMostrar) ?></td>
                            <td class="text-center<?= $isPdf ? ' ref-range' : '' ?>"><?= esc($refMostrar) ?></td>
                            <?php else: ?>
                            <td class="text-center <?= $class ?><?= $isPdf && $isOutPdf ? ' out-range' : '' ?>" colspan="2"><?= esc($resMostrar) ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?= $wrapClose ?>
    <?php endif; ?>
    <?php
    $sinEnSeg = false;
    foreach ($segItems as $it) {
        $it = is_array($it) ? (object) $it : $it;
        $v = trim((string) ($it->regvalues ?? ''));
        if ($v === '' || $v === '-') {
            $sinEnSeg = true;
            break;
        }
    }
    ?>
    <?php if ($sinEnSeg): ?>
        <?php
        $wrapSinOpen = ! $isPdf ? '<div class="table-responsive mt-1 mb-4">' : '<div class="report-segment-table-wrap report-segment-sin-ref">';
        ?>
        <?= $wrapSinOpen ?>
            <?php if ($titleObj !== null): ?>
                <?php if ($isPdf): ?>
                <div class="report-segment-title"><?= esc($titleObj->nombre ?? '') ?></div>
                <?php else: ?>
                <div class="report-segment-title-web px-2 py-2 mb-2 bg-secondary bg-opacity-10 border-start border-4 border-secondary rounded-end fw-semibold text-uppercase small"><?= esc($titleObj->nombre ?? '') ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <table class="<?= esc($mainTableClass) ?>"<?= $isPdf ? ' style="margin-top:0;"' : '' ?>>
                <thead<?= $isPdf ? '' : ' class="thead-dark"' ?>>
                    <tr>
                        <th>ANÁLISIS</th>
                        <th class="text-center">RANGO REFERENCIAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($segItems as $item): ?>
                        <?php
                        $item = is_array($item) ? (object) $item : $item;
                        $valTmp = trim((string) ($item->regvalues ?? ''));
                        if ($valTmp !== '' && $valTmp !== '-') {
                            continue;
                        }
                        $refMostrar = registro_rango_referencial_texto($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
                        if ($refMostrar === '-' && trim((string) ($item->umedida ?? '')) === '' && ! ((bool) ($item->show_reference ?? false))) {
                            continue;
                        }
                        ?>
                            <tr>
                                <td><?= esc($item->nombre ?? '') ?></td>
                                <td class="text-center<?= $isPdf ? ' ref-range' : '' ?>"><?= esc($refMostrar) ?></td>
                            </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?= $wrapClose ?>
    <?php endif; ?>
<?php endforeach; ?>
