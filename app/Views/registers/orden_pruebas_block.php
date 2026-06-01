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
            <?php if ($showCosts): ?>
                <table class="<?= $forPdf ? '' : 'table table-sm table-borderless mb-0' ?>" style="<?= $forPdf ? 'width:100%;border-collapse:collapse;' : '' ?>">
                    <?php if (!$forPdf): ?>
                    <thead>
                        <tr class="small text-muted">
                            <th style="width:2.5rem;">#</th>
                            <th>Prueba</th>
                            <th class="text-end" style="width:7rem;">Costo</th>
                        </tr>
                    </thead>
                    <?php endif; ?>
                    <tbody>
                        <?php foreach (($items ?? []) as $it): ?>
                            <?php
                            $numPrueba++;
                            $pid = (int) ($it['prianacategoria_id'] ?? 0);
                            $costo = (float) ($costosMap[$pid] ?? 0);
                            ?>
                            <tr>
                                <td class="<?= $forPdf ? '' : 'text-muted' ?>" style="<?= $forPdf ? 'width:1.5rem;vertical-align:top;' : '' ?>"><?= $numPrueba ?>.</td>
                                <td style="<?= $forPdf ? 'padding:2px 4px;' : '' ?>"><?= esc($it['hijo'] ?? '') ?></td>
                                <td class="text-end" style="<?= $forPdf ? 'width:5rem;text-align:right;white-space:nowrap;' : '' ?>"><?= format_currency($costo) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <ol class="mb-0" start="<?= (int) $numPrueba + 1 ?>">
                    <?php foreach (($items ?? []) as $it): ?>
                        <?php $numPrueba++; ?>
                        <li><?= esc($it['hijo'] ?? '') ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
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
        <div class="<?= $forPdf ? 'section' : 'mt-4' ?>">
            <div class="<?= $forPdf ? 'section-title' : 'fw-bold border-bottom pb-1 mb-2' ?>">Costos y pagos</div>
            <table class="<?= $forPdf ? '' : 'table table-sm w-auto mb-3' ?>" style="<?= $forPdf ? 'width:100%;max-width:20rem;' : '' ?>">
                <tbody>
                    <?php if ($totalReco > 0 && abs($totalReco - $totalOrden) > 0.02): ?>
                        <tr>
                            <td>Total recomendado (catálogo)</td>
                            <td class="text-end"><?= format_currency($totalReco) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Total de la orden</strong></td>
                        <td class="text-end"><strong><?= format_currency($totalOrden) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Monto cancelado</td>
                        <td class="text-end"><?= format_currency($montoPagado) ?></td>
                    </tr>
                    <tr>
                        <td>Saldo pendiente</td>
                        <td class="text-end <?= $saldo > 0.02 ? ($forPdf ? '' : 'text-danger fw-semibold') : '' ?>"><?= format_currency($saldo) ?></td>
                    </tr>
                    <tr>
                        <td>Forma de pago</td>
                        <td class="text-end"><?= esc($tipo_pago_nombre ?? '-') ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if ($abonosList !== []): ?>
                <div class="<?= $forPdf ? '' : 'small' ?>">
                    <strong>Detalle de pagos</strong>
                    <table class="<?= $forPdf ? '' : 'table table-sm table-striped mt-1 mb-0' ?>" style="<?= $forPdf ? 'width:100%;margin-top:6px;border-collapse:collapse;' : '' ?>">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abonosList as $ab): ?>
                                <?php
                                $fechaAb = $ab['fecha_abono'] ?? '';
                                if ($fechaAb !== '' && !$forPdf) {
                                    try {
                                        $fechaAb = (new \DateTime($fechaAb))->format('d/m/Y H:i');
                                    } catch (\Throwable $e) {
                                        // mantener valor original
                                    }
                                } elseif ($fechaAb !== '' && $forPdf) {
                                    try {
                                        $fechaAb = (new \DateTime($fechaAb))->format('d/m/Y');
                                    } catch (\Throwable $e) {
                                        // mantener valor original
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= esc($fechaAb !== '' ? $fechaAb : '-') ?></td>
                                    <td><?= esc($ab['tipo_nombre'] ?? '-') ?></td>
                                    <td class="text-end"><?= format_currency((float) ($ab['monto'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
