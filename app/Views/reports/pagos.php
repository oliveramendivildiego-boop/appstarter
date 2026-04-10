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
$cierreStart = $startDate ?? date('Y-m-d');
$cierreEnd   = $endDate ?? date('Y-m-d');
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

<?php $tipoPagoMap = $tipoPagoMap ?? []; ?>

<div class="alert alert-info mb-4">
    <strong>Resumen del período:</strong><br>
    Total facturado: <?= number_format((float)($totales->total_facturado ?? 0), 2) ?> Bs |
    Total cobrado: <?= number_format((float)($totales->total_cobrado ?? 0), 2) ?> Bs |
    <span class="text-danger">Total pendiente: <?= number_format((float)($totales->total_pendiente ?? 0), 2) ?> Bs</span> |
    Cantidad órdenes: <?= (int)($totales->total_registros ?? 0) ?>
    <span class="d-block small mt-1">No se incluyen órdenes anuladas en montos ni cantidades (devolución).</span>
</div>

<h5 class="mt-4">Resumen por tipo de pago</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
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
                    <tr>
                        <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                        <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                        <td class="text-end"><?= number_format((float)($row['total_facturado'] ?? 0), 2) ?> Bs</td>
                        <td class="text-end"><?= number_format((float)($row['total_cobrado'] ?? 0), 2) ?> Bs</td>
                        <td class="text-end <?= ((float)($row['total_pendiente'] ?? 0)) > 0 ? 'text-danger fw-bold' : '' ?>">
                            <?= number_format((float)($row['total_pendiente'] ?? 0), 2) ?> Bs
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted text-center">No hay datos en el período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h5 class="mt-4">Pagos pagados (saldo = 0 o menor)</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-success">
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total</th>
                <th class="text-end">Pagado</th>
                <th class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagosPagados ?? [] as $row): ?>
                <tr>
                    <td><?= esc($row['registro_id'] ?? '') ?></td>
                    <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                    <td><?= esc($row['paciente'] ?? '') ?></td>
                    <td><?= esc($row['doctor'] ?? '') ?></td>
                    <td class="text-end"><?= number_format((float)($row['total'] ?? 0), 2) ?> Bs</td>
                    <td class="text-end"><?= number_format((float)($row['monto_pagado'] ?? 0), 2) ?> Bs</td>
                    <td class="text-end"><?= number_format((float)($row['saldo'] ?? 0), 2) ?> Bs</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($pagosPagados)): ?>
    <p class="text-muted">No hay pagos pagados en el período.</p>
<?php endif; ?>

<h5 class="mt-4">Pendientes de pago (<?= count($pendientes ?? []) ?>)</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead class="table-warning">
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total</th>
                <th class="text-end">Pagado</th>
                <th class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pendientes ?? [] as $row): ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float)($row['total'] ?? 0), 2) ?> Bs</td>
                <td class="text-end"><?= number_format((float)($row['monto_pagado'] ?? 0), 2) ?> Bs</td>
                <td class="text-end text-danger fw-bold"><?= number_format((float)($row['saldo'] ?? 0), 2) ?> Bs</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($pendientes)): ?>
<p class="text-muted">No hay pendientes de pago en el período.</p>
<?php endif; ?>

<h5 class="mt-4">Resumen diario</h5>
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
                        <td><?= esc(date('d/m/Y', strtotime($row['fecha'] ?? ''))) ?></td>
                        <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                        <td class="text-end"><?= number_format((float)($row['total_facturado'] ?? 0), 2) ?> Bs</td>
                        <td class="text-end"><?= number_format((float)($row['total_cobrado'] ?? 0), 2) ?> Bs</td>
                        <td class="text-end <?= ((float)($row['total_pendiente'] ?? 0)) > 0 ? 'text-danger fw-bold' : '' ?>">
                            <?= number_format((float)($row['total_pendiente'] ?? 0), 2) ?> Bs
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
                        <td class="text-end"><?= number_format((float)($row['total_facturado'] ?? 0), 2) ?> Bs</td>
                        <td class="text-end"><?= number_format((float)($row['total_cobrado'] ?? 0), 2) ?> Bs</td>
                        <td class="text-end <?= ((float)($row['total_pendiente'] ?? 0)) > 0 ? 'text-danger fw-bold' : '' ?>">
                            <?= number_format((float)($row['total_pendiente'] ?? 0), 2) ?> Bs
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted text-center">No hay datos en el período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h5 class="mt-4">Todos los pagos (<?= count($todos ?? []) ?>)</h5>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total</th>
                <th class="text-end">Pagado</th>
                <th class="text-end">Saldo</th>
                <th>Tipo pago</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($todos ?? [] as $row): ?>
            <tr class="<?= (float)($row['saldo'] ?? 0) > 0 ? 'table-warning' : '' ?>">
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float)($row['total'] ?? 0), 2) ?> Bs</td>
                <td class="text-end"><?= number_format((float)($row['monto_pagado'] ?? 0), 2) ?> Bs</td>
                <td class="text-end"><?= number_format((float)($row['saldo'] ?? 0), 2) ?> Bs</td>
                <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($todos)): ?>
<p class="text-muted">No hay registros en el período seleccionado.</p>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#report_start", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr("#report_end", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
});
</script>
<?= $this->endSection() ?>
