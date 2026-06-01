<?php
/**
 * Listado numerado de pruebas, resumen de cantidad y bloque de costos/pagos (hoja de trabajo).
 *
 * @var array<string, list<array<string, mixed>>> $grupos_pruebas
 * @var int $total_pruebas
 * @var bool $show_order_costs
 * @var array<int, float> $costos_por_id
 * @var object|null $pago
 * @var list<array<string, mixed>> $abonos
 * @var string $tipo_pago_nombre
 * @var bool $for_pdf
 */
helper('layout');
$forPdf = !empty($for_pdf);
$showCosts = !empty($show_order_costs);
$costosMap = is_array($costos_por_id ?? null) ? $costos_por_id : [];
$numPrueba = 0;
?>
<?php if (empty($grupos_pruebas ?? [])): ?>
    <?php if ($forPdf): ?>
        <p class="muted">No hay pruebas para esta orden.</p>
    <?php else: ?>
        <div class="alert alert-warning mb-0">No hay pruebas para esta orden.</div>
    <?php endif; ?>
<?php else: ?>
    <?php foreach (($grupos_pruebas ?? []) as $padre => $items): ?>
        <div class="<?= $forPdf ? 'section' : 'mb-3' ?>">
            <div class="<?= $forPdf ? 'section-title' : 'fw-bold text-uppercase border-bottom pb-1 mb-2' ?>"><?= esc($padre) ?></div>
            <ul class="orden-pruebas-items<?= $forPdf ? '' : ' mb-0' ?>"<?= $forPdf ? ' style="list-style:none;padding:0;margin:0;text-align:left;"' : '' ?>>
                <?php foreach (($items ?? []) as $it): ?>
                    <?php
                    $numPrueba++;
                    $pid = (int) ($it['prianacategoria_id'] ?? 0);
                    $costo = $showCosts ? (float) ($costosMap[$pid] ?? 0) : 0.0;
                    ?>
                    <li class="orden-prueba-item"<?= $forPdf ? ' style="display:flex;align-items:baseline;margin:0 0 2px;padding:0;text-align:left;"' : '' ?>>
                        <span class="orden-prueba-num<?= !$forPdf && !$showCosts ? ' text-muted' : '' ?>"><?= $numPrueba ?>.</span>
                        <span class="orden-prueba-nombre"><?= esc($it['hijo'] ?? '') ?></span>
                        <?php if ($showCosts): ?>
                            <span class="orden-prueba-costo"<?= $forPdf ? ' style="margin-left:auto;text-align:right;white-space:nowrap;"' : '' ?>><?= format_currency($costo) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <div class="<?= $forPdf ? 'section' : 'mt-3 p-2 bg-light rounded border' ?>">
        <strong>Resumen:</strong>
        <?= (int) ($total_pruebas ?? $numPrueba) ?> prueba<?= ((int) ($total_pruebas ?? $numPrueba)) === 1 ? '' : 's' ?> a realizar
    </div>

    <?php if ($showCosts): ?>
        <?php
        $pagoRow = $pago ?? null;
        $totalOrden = (float) ($pagoRow->total ?? 0);
        $totalReco = (float) ($pagoRow->total_reco ?? 0);
        $montoPagado = (float) ($pagoRow->monto_pagar ?? 0);
        $saldo = (float) ($pagoRow->saldo ?? 0);
        if ($saldo <= 0 && $totalOrden > 0) {
            $saldo = max(0, $totalOrden - $montoPagado);
        }
        $abonosList = is_array($abonos ?? null) ? $abonos : [];
        ?>
        <?php
        $saldoClass = $saldo > 0.02 && !$forPdf ? 'text-danger fw-semibold' : '';
        $partesResumen = [];
        if ($totalReco > 0 && abs($totalReco - $totalOrden) > 0.02) {
            $partesResumen[] = '<span class="text-muted">Reco.</span> ' . format_currency($totalReco);
        }
        $partesResumen[] = '<strong>Total</strong> ' . format_currency($totalOrden);
        $partesResumen[] = '<span class="text-muted">Cancelado</span> ' . format_currency($montoPagado);
        $partesResumen[] = '<span class="text-muted">Saldo</span> <span class="' . esc($saldoClass, 'attr') . '">' . format_currency($saldo) . '</span>';
        $partesResumen[] = '<span class="text-muted">Pago</span> ' . esc($tipo_pago_nombre ?? '-');

        $partesAbonos = [];
        foreach ($abonosList as $ab) {
            $fechaAb = $ab['fecha_abono'] ?? '';
            if ($fechaAb !== '') {
                $fechaAb = $forPdf
                    ? \App\Services\RegisterService::formatReportDate(substr($fechaAb, 0, 10))
                    : \App\Services\RegisterService::formatStoredReporteFechaCorta($fechaAb);
            }
            $partesAbonos[] = esc($fechaAb !== '' ? $fechaAb : '-')
                . ' ' . esc($ab['tipo_nombre'] ?? '-')
                . ' ' . format_currency((float) ($ab['monto'] ?? 0));
        }
        ?>
        <div class="<?= $forPdf ? 'section' : 'mt-3' ?>" style="<?= $forPdf ? 'font-size:11px;line-height:1.4;' : '' ?>">
            <div class="<?= $forPdf ? '' : 'small' ?>" style="<?= $forPdf ? '' : 'line-height:1.45;' ?>">
                <strong>Costos y pagos:</strong>
                <?= implode(' <span class="text-muted">·</span> ', $partesResumen) ?>
                <?php if ($partesAbonos !== []): ?>
                    <span class="text-muted"> · </span>
                    <span class="text-muted">Pagos:</span> <?= implode('<span class="text-muted">; </span>', $partesAbonos) ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
