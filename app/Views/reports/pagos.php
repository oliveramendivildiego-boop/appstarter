<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-print-none">
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
$cierreStart = $startDate ?? lab_today_ymd();
$cierreEnd   = $endDate ?? lab_today_ymd();
?>
<div class="row g-3 mb-2 align-items-end flex-wrap">
    <div class="col-auto">
        <form method="get" action="<?= site_url('reports/pagos') ?>" class="d-flex flex-wrap align-items-end gap-3">
            <div>
                <label for="report_start" class="form-label">Desde</label>
                <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
            </div>
            <div>
                <label for="report_end" class="form-label">Hasta</label>
                <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>
    </div>
    <div class="col-auto d-flex align-items-end gap-2 flex-wrap">
        <?= form_open('reports/pagosCierreCrear', ['class' => 'd-inline']) ?>
            <input type="hidden" name="start" value="<?= esc($cierreStart) ?>">
            <input type="hidden" name="end" value="<?= esc($cierreEnd) ?>">
            <button type="submit" class="btn btn-outline-primary">Registrar cierre</button>
        <?= form_close() ?>
        <a href="<?= site_url('reports/pagosCierres') ?>" class="btn btn-outline-secondary">Cierres guardados</a>
    </div>
</div>
<p class="small text-muted mb-0">El cierre guarda el resumen del período mostrado. Luego imprima o exporte a PDF desde <strong>Cierres guardados</strong>.</p>
</div>

<?= view('reports/partials/report_actions', [
    'pdf_url' => site_url('reports/pagosPdf?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
<p class="small text-muted">El reporte lista cada <strong>cobro</strong> según la fecha en que se registró el pago (historial de abonos), no la fecha de ingreso de la orden.</p>

<?php $tipoPagoMap = $tipoPagoMap ?? []; ?>
<?php $totalesEgresos = $totalesEgresos ?? (object) []; ?>
<?php $totalesIngresosCaja = $totalesIngresosCaja ?? (object) []; ?>
<?php $resumenIngresosCajaPorTipo = $resumenIngresosCajaPorTipo ?? []; ?>
<?php $ingresosCajaMov = $ingresosCajaMov ?? []; ?>
<?php $resumenEgresosPorTipo = $resumenEgresosPorTipo ?? []; ?>
<?php $egresos = $egresos ?? []; ?>
<?php $cajaResumen = $cajaResumen ?? ['ingresos' => 0, 'egresos' => 0, 'saldo_neto' => 0, 'estado' => 'positivo']; ?>
<?php $cajaPorTipo = $cajaPorTipo ?? []; ?>
<?php $cajaPorTipoTotales = $cajaPorTipoTotales ?? ['ingresos' => 0, 'egresos' => 0, 'saldo_neto' => 0]; ?>

<div class="alert alert-info mb-4">
    <strong>Resumen del período (por fecha de cobro):</strong><br>
    Total cobrado: <?= format_currency((float)($totales->total_cobrado ?? 0)) ?> |
    Cantidad de cobros: <?= (int)($totales->total_registros ?? 0) ?> |
    Órdenes involucradas: <?= (int)($totales->cantidad_ordenes ?? 0) ?> |
    Total facturado (órdenes con cobro en período): <?= format_currency((float)($totales->total_facturado ?? 0)) ?> |
    <span class="text-danger">Saldo pendiente (esas órdenes): <?= format_currency((float)($totales->total_pendiente ?? 0)) ?></span>
    <span class="d-block small mt-1">Cada fila del detalle es un cobro registrado en el rango de fechas. No se incluyen órdenes anuladas.</span>
</div>

<div class="alert <?= ($cajaResumen['estado'] ?? 'positivo') === 'negativo' ? 'alert-danger' : 'alert-success' ?> mb-4">
    <strong>Cuadre de caja del período:</strong><br>
    Ingresos por ventas (cobrado): <?= format_currency((float) ($cajaResumen['ingresos_ventas'] ?? 0)) ?> |
    Ingresos de caja (módulo): <?= format_currency((float) ($cajaResumen['ingresos_movimientos'] ?? 0)) ?> |
    <strong>Total ingresos: <?= format_currency((float) ($cajaResumen['ingresos'] ?? 0)) ?></strong> |
    Egresos: <?= format_currency((float) ($cajaResumen['egresos'] ?? 0)) ?> |
    <strong>Saldo neto caja: <?= format_currency((float) ($cajaResumen['saldo_neto'] ?? 0)) ?></strong>
    <span class="d-block small mt-1">Los ingresos por ventas usan la <strong>fecha de cada cobro</strong> (historial de abonos), no la fecha de ingreso de la orden. Cálculo: (cobros del período + ingresos de caja) − egresos.</span>
</div>

<h5 class="mt-4">Cuadre de caja por tipo de pago</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-success">
            <tr>
                <th>Tipo de pago</th>
                <th class="text-end">Ingresos</th>
                <th class="text-end">Egresos</th>
                <th class="text-end">Total disponible</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($cajaPorTipo)): ?>
                <?php foreach ($cajaPorTipo as $row): ?>
                    <tr>
                        <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                        <td class="text-end"><?= format_currency((float) ($row['ingresos'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float) ($row['egresos'] ?? 0)) ?></td>
                        <td class="text-end <?= ((float) ($row['saldo_neto'] ?? 0)) < 0 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                            <?= format_currency((float) ($row['saldo_neto'] ?? 0)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-muted text-center">No hay datos por tipo de pago en el período.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="text-end">Total general</th>
                <th class="text-end"><?= format_currency((float) ($cajaPorTipoTotales['ingresos'] ?? 0)) ?></th>
                <th class="text-end"><?= format_currency((float) ($cajaPorTipoTotales['egresos'] ?? 0)) ?></th>
                <th class="text-end <?= ((float) ($cajaPorTipoTotales['saldo_neto'] ?? 0)) < 0 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                    <?= format_currency((float) ($cajaPorTipoTotales['saldo_neto'] ?? 0)) ?>
                </th>
            </tr>
        </tfoot>
    </table>
</div>

<h5 class="mt-4">Resumen por tipo de pago (cobros del período)</h5>
<p class="small text-muted">Haga clic en un tipo de pago para ver el detalle de pacientes y montos pendientes.</p>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped" id="tabla_resumen_pagos_tipo">
        <thead class="table-primary">
            <tr>
                <th>Tipo</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Total facturado</th>
                <th class="text-end">Total cobrado</th>
                <th class="text-end">Total pendiente</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resumenPagosPorTipo ?? [])): ?>
                <?php foreach ($resumenPagosPorTipo as $row): ?>
                    <?php
                    $tipoKey = (string) ($row['tipopago'] ?? '');
                    $tipoLabel = $tipoPagoMap[$tipoKey] ?? $tipoKey ?: '-';
                    $cantidadTipo = (int) ($row['cantidad'] ?? 0);
                    $puedeDetalle = $tipoKey !== '' && (
                        $cantidadTipo > 0
                        || ($tipoKey === '4' && (float) ($row['total_pendiente'] ?? 0) > 0.02)
                    );
                    ?>
                    <tr>
                        <td>
                            <?php if ($puedeDetalle): ?>
                                <a href="#"
                                   class="pagos-tipo-enlace"
                                   role="button"
                                   data-tipopago="<?= esc($tipoKey, 'attr') ?>"
                                   data-tipo-label="<?= esc($tipoLabel, 'attr') ?>"
                                   title="Ver detalle de cobros"><?= esc($tipoLabel) ?></a>
                            <?php else: ?>
                                <?= esc($tipoLabel) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= $cantidadTipo ?></td>
                        <td class="text-end"><?= format_currency((float)($row['total_facturado'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float)($row['total_cobrado'] ?? 0)) ?></td>
                        <td class="text-end <?= ((float)($row['total_pendiente'] ?? 0)) > 0 ? 'text-danger fw-bold' : '' ?>">
                            <?= format_currency((float)($row['total_pendiente'] ?? 0)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted text-center">No hay datos en el período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalPagosPorTipo" tabindex="-1" aria-labelledby="modalPagosPorTipoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagosPorTipoLabel">Detalle de cobros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="modalPagosPorTipoSubtitulo"></p>
                <div id="modalPagosPorTipoCargando" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando…</span></div>
                </div>
                <div id="modalPagosPorTipoError" class="alert alert-danger d-none" role="alert"></div>
                <div class="table-responsive d-none" id="modalPagosPorTipoTablaWrap">
                    <table class="table table-sm table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Fecha cobro</th>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th class="text-end">Monto pendiente</th>
                            </tr>
                        </thead>
                        <tbody id="modalPagosPorTipoBody"></tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th colspan="4" class="text-end">Total</th>
                                <th class="text-end" id="modalPagosPorTipoTotal"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p id="modalPagosPorTipoVacio" class="text-muted text-center mb-0 d-none">No hay registros de este tipo en el período.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<h5 class="mt-4">Resumen de ingresos de caja por tipo de pago</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-success">
            <tr>
                <th>Tipo</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Total ingresos</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resumenIngresosCajaPorTipo)): ?>
                <?php foreach ($resumenIngresosCajaPorTipo as $row): ?>
                    <tr>
                        <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                        <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                        <td class="text-end"><?= format_currency((float) ($row['total_ingresos'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-muted text-center">No hay ingresos de caja en el período.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-end">Total ingresos de caja</th>
                <th class="text-end"><?= format_currency((float) ($totalesIngresosCaja->total_ingresos ?? 0)) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<h5 class="mt-4">Detalle de ingresos de caja (<?= count($ingresosCajaMov) ?>)</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-success">
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Tipo pago</th>
                <th>Desglose</th>
                <th class="text-end">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ingresosCajaMov)): ?>
                <?php foreach ($ingresosCajaMov as $row): ?>
                    <tr>
                        <td><?= (int) ($row['egreso_id'] ?? 0) ?></td>
                        <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta((string) ($row['fecha'] ?? ''))) ?></td>
                        <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                        <td><?= esc((string) ($row['desglose'] ?? '')) ?></td>
                        <td class="text-end"><?= format_currency((float) ($row['monto'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted text-center">No hay ingresos de caja en el período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h5 class="mt-4">Resumen de egresos por tipo de pago</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-danger">
            <tr>
                <th>Tipo</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Total egresos</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resumenEgresosPorTipo)): ?>
                <?php foreach ($resumenEgresosPorTipo as $row): ?>
                    <tr>
                        <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                        <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                        <td class="text-end"><?= format_currency((float) ($row['total_egresos'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-muted text-center">No hay egresos en el período.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-end">Total egresos</th>
                <th class="text-end"><?= format_currency((float) ($totalesEgresos->total_egresos ?? 0)) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<h5 class="mt-4">Detalle de egresos de caja (<?= count($egresos) ?>)</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-danger">
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Tipo pago</th>
                <th>Desglose</th>
                <th class="text-end">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($egresos)): ?>
                <?php foreach ($egresos as $row): ?>
                    <tr>
                        <td><?= (int) ($row['egreso_id'] ?? 0) ?></td>
                        <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta((string) ($row['fecha'] ?? ''))) ?></td>
                        <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                        <td><?= esc((string) ($row['desglose'] ?? '')) ?></td>
                        <td class="text-end"><?= format_currency((float) ($row['monto'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted text-center">No hay egresos en el período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h5 class="mt-4">Cobros en órdenes ya saldadas (<?= count($pagosPagados ?? []) ?>)</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-success">
            <tr>
                <th>Orden</th>
                <th>Fecha cobro</th>
                <th class="text-end">Monto cobrado</th>
                <th>Tipo pago</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total orden</th>
                <th class="text-end">Pagado acum.</th>
                <th class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagosPagados ?? [] as $row): ?>
                <tr>
                    <td><?= esc(registro_orden_display($row)) ?></td>
                    <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_cobro'] ?? $row['ingreso'] ?? '')) ?></td>
                    <td class="text-end fw-bold"><?= format_currency((float)($row['monto_cobro'] ?? $row['monto_pagado'] ?? 0)) ?></td>
                    <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                    <td><?= esc($row['paciente'] ?? '') ?></td>
                    <td><?= esc($row['doctor'] ?? '') ?></td>
                    <td class="text-end"><?= format_currency((float)($row['total'] ?? 0)) ?></td>
                    <td class="text-end"><?= format_currency((float)($row['monto_pagado'] ?? 0)) ?></td>
                    <td class="text-end"><?= format_currency((float)($row['saldo'] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($pagosPagados)): ?>
    <p class="text-muted">No hay cobros en órdenes saldadas en el período.</p>
<?php endif; ?>

<h5 class="mt-4">Cobros parciales (aún con saldo) (<?= count($pendientes ?? []) ?>)</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-warning">
            <tr>
                <th>Orden</th>
                <th>Fecha cobro</th>
                <th class="text-end">Monto cobrado</th>
                <th>Tipo pago</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total orden</th>
                <th class="text-end">Pagado acum.</th>
                <th class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pendientes ?? [] as $row): ?>
            <tr>
                <td><?= esc(registro_orden_display($row)) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_cobro'] ?? $row['ingreso'] ?? '')) ?></td>
                <td class="text-end fw-bold"><?= format_currency((float)($row['monto_cobro'] ?? 0)) ?></td>
                <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= format_currency((float)($row['total'] ?? 0)) ?></td>
                <td class="text-end"><?= format_currency((float)($row['monto_pagado'] ?? 0)) ?></td>
                <td class="text-end text-danger fw-bold"><?= format_currency((float)($row['saldo'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($pendientes)): ?>
<p class="text-muted">No hay cobros parciales en el período.</p>
<?php endif; ?>

<h5 class="mt-4">Resumen diario de cobros</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-secondary">
            <tr>
                <th>Fecha</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Total facturado</th>
                <th class="text-end">Total cobrado</th>
                <th class="text-end">Total pendiente</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resumenPagosPorDia ?? [])): ?>
                <?php foreach ($resumenPagosPorDia as $row): ?>
                    <tr>
                        <td><?= esc(\App\Services\RegisterService::formatReportDate($row['fecha'] ?? '')) ?></td>
                        <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                        <td class="text-end"><?= format_currency((float)($row['total_facturado'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float)($row['total_cobrado'] ?? 0)) ?></td>
                        <td class="text-end <?= ((float)($row['total_pendiente'] ?? 0)) > 0 ? 'text-danger fw-bold' : '' ?>">
                            <?= format_currency((float)($row['total_pendiente'] ?? 0)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted text-center">No hay datos en el período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h5 class="mt-4">Resumen por doctor</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Doctor</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Total facturado</th>
                <th class="text-end">Total cobrado</th>
                <th class="text-end">Total pendiente</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resumenPagosPorDoctor ?? [])): ?>
                <?php foreach ($resumenPagosPorDoctor as $row): ?>
                    <tr>
                        <td><?= esc($row['doctor'] ?? '') ?></td>
                        <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                        <td class="text-end"><?= format_currency((float)($row['total_facturado'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float)($row['total_cobrado'] ?? 0)) ?></td>
                        <td class="text-end <?= ((float)($row['total_pendiente'] ?? 0)) > 0 ? 'text-danger fw-bold' : '' ?>">
                            <?= format_currency((float)($row['total_pendiente'] ?? 0)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted text-center">No hay datos en el período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$todosLista = $todos ?? [];
$totTodosTotalOrden = 0.0;
$totTodosPagadoAcum = 0.0;
$totTodosSaldoOrden = 0.0;
$totTodosOrdenesVistas = [];
foreach ($todosLista as $rowTodos) {
    $ridTodos = (string) ($rowTodos['registro_id'] ?? '');
    if ($ridTodos === '' || isset($totTodosOrdenesVistas[$ridTodos])) {
        continue;
    }
    $totTodosOrdenesVistas[$ridTodos] = true;
    $totTodosTotalOrden += (float) ($rowTodos['total'] ?? 0);
    $totTodosPagadoAcum += (float) ($rowTodos['monto_pagado'] ?? 0);
    $totTodosSaldoOrden += (float) ($rowTodos['saldo'] ?? 0);
}
$totTodosTieneSaldo = $totTodosSaldoOrden > 0.02;
?>
<h5 class="mt-4">Todos los cobros del período (<?= count($todosLista) ?>)</h5>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Orden</th>
                <th>Fecha cobro</th>
                <th class="text-end">Monto cobrado</th>
                <th>Tipo pago</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total orden</th>
                <th class="text-end">Pagado acum.</th>
                <th class="text-end">Saldo orden</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($todosLista as $row): ?>
            <?php $saldoFila = (float) ($row['saldo'] ?? 0); ?>
            <tr class="<?= $saldoFila > 0.02 ? 'table-warning' : '' ?>">
                <td><?= esc(registro_orden_display($row)) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_cobro'] ?? $row['ingreso'] ?? '')) ?></td>
                <td class="text-end fw-bold"><?= format_currency((float)($row['monto_cobro'] ?? $row['monto_pagado'] ?? 0)) ?></td>
                <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= format_currency((float)($row['total'] ?? 0)) ?></td>
                <td class="text-end"><?= format_currency((float)($row['monto_pagado'] ?? 0)) ?></td>
                <td class="text-end <?= $saldoFila > 0.02 ? 'text-danger fw-bold' : '' ?>"><?= format_currency($saldoFila) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if ($todosLista !== []): ?>
        <tfoot class="table-secondary">
            <tr>
                <th colspan="6" class="text-end">Totales</th>
                <th class="text-end"><?= format_currency($totTodosTotalOrden) ?></th>
                <th class="text-end"><?= format_currency($totTodosPagadoAcum) ?></th>
                <th class="text-end <?= $totTodosTieneSaldo ? 'text-danger fw-bold' : '' ?>"><?= format_currency($totTodosSaldoOrden) ?></th>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
<?php if ($todosLista === []): ?>
<p class="text-muted">No hay cobros en el período seleccionado.</p>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
/* Mismo aspecto que «Enlaces en contenido y pie» (body.ynex-theme main a + --ui-link-color) */
#tabla_resumen_pagos_tipo .pagos-tipo-enlace {
    color: var(--ui-link-color, #0d6efd);
    font-weight: var(--ui-link-font-weight, 400);
    font-style: var(--ui-link-font-style, normal);
    text-decoration: underline;
    text-underline-offset: 0.15em;
    cursor: pointer;
}
#tabla_resumen_pagos_tipo tr:has(.pagos-tipo-enlace:hover) {
    background-color: color-mix(in srgb, var(--ui-link-color, #0d6efd) 8%, transparent);
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#report_start", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr("#report_end", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });

    const reportStart = <?= json_encode($startDate ?? '') ?>;
    const reportEnd = <?= json_encode($endDate ?? '') ?>;
    const detalleUrl = <?= json_encode(site_url('reports/pagosDetallePorTipo')) ?>;
    const modalEl = document.getElementById('modalPagosPorTipo');

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function resetModalEstado() {
        document.getElementById('modalPagosPorTipoCargando').classList.add('d-none');
        document.getElementById('modalPagosPorTipoError').classList.add('d-none');
        document.getElementById('modalPagosPorTipoTablaWrap').classList.add('d-none');
        document.getElementById('modalPagosPorTipoVacio').classList.add('d-none');
        document.getElementById('modalPagosPorTipoBody').innerHTML = '';
        document.getElementById('modalPagosPorTipoTotal').textContent = '';
    }

    async function abrirDetalleTipo(tipopago, tipoLabel) {
        if (!modalEl || typeof bootstrap === 'undefined') return;

        resetModalEstado();
        const esPendiente = tipopago === '4';
        document.getElementById('modalPagosPorTipoLabel').textContent =
            (esPendiente ? 'Órdenes pendientes: ' : 'Cobros: ') + tipoLabel;
        document.getElementById('modalPagosPorTipoSubtitulo').textContent = 'Período del reporte · cargando…';
        document.getElementById('modalPagosPorTipoCargando').classList.remove('d-none');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        const params = new URLSearchParams({ start: reportStart, end: reportEnd, tipopago: tipopago });
        try {
            const res = await fetch(detalleUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            document.getElementById('modalPagosPorTipoCargando').classList.add('d-none');

            if (!res.ok || !data.success) {
                const err = document.getElementById('modalPagosPorTipoError');
                err.textContent = data.message || 'No se pudo cargar el detalle.';
                err.classList.remove('d-none');
                return;
            }

            document.getElementById('modalPagosPorTipoSubtitulo').textContent =
                (data.periodo || '') + ' · ' + (data.count || 0) + ' cobro(s)';

            const items = data.items || [];
            if (items.length === 0) {
                const vacio = document.getElementById('modalPagosPorTipoVacio');
                vacio.textContent = esPendiente
                    ? 'No hay órdenes pendientes de pago en el período.'
                    : 'No hay cobros de este tipo en el período.';
                vacio.classList.remove('d-none');
                return;
            }

            const tbody = document.getElementById('modalPagosPorTipoBody');
            tbody.innerHTML = items.map(function(row) {
                const pendiente = parseFloat(row.monto_pendiente) || 0;
                const pendienteClass = pendiente > 0.02 ? 'text-danger fw-bold' : '';
                return '<tr>'
                    + '<td>' + escHtml(row.orden) + '</td>'
                    + '<td>' + escHtml(row.fecha_cobro) + '</td>'
                    + '<td>' + escHtml(row.paciente || '—') + '</td>'
                    + '<td>' + escHtml(row.doctor || '—') + '</td>'
                    + '<td class="text-end fw-semibold ' + pendienteClass + '">' + escHtml(row.monto_pendiente_fmt) + '</td>'
                    + '</tr>';
            }).join('');

            document.getElementById('modalPagosPorTipoTotal').textContent = data.total_fmt || '';
            document.getElementById('modalPagosPorTipoTablaWrap').classList.remove('d-none');
        } catch (e) {
            document.getElementById('modalPagosPorTipoCargando').classList.add('d-none');
            const err = document.getElementById('modalPagosPorTipoError');
            err.textContent = 'Error de conexión al cargar el detalle.';
            err.classList.remove('d-none');
        }
    }

    document.querySelectorAll('#tabla_resumen_pagos_tipo .pagos-tipo-enlace').forEach(function(link) {
        link.addEventListener('click', function(ev) {
            ev.preventDefault();
            abrirDetalleTipo(link.getAttribute('data-tipopago'), link.getAttribute('data-tipo-label') || '');
        });
    });
});
</script>
<?= $this->endSection() ?>
