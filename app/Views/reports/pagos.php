<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>

<form method="get" action="<?= site_url('reports/pagos') ?>" class="row g-3 mb-4">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>

<?php $tipoPagoMap = $tipoPagoMap ?? []; ?>

<div class="alert alert-info mb-4">
    <strong>Resumen del período:</strong><br>
    Total facturado: <?= number_format((float)($totales->total_facturado ?? 0), 2) ?> Bs |
    Total cobrado: <?= number_format((float)($totales->total_cobrado ?? 0), 2) ?> Bs |
    <span class="text-danger">Total pendiente: <?= number_format((float)($totales->total_pendiente ?? 0), 2) ?> Bs</span> |
    Cantidad órdenes: <?= (int)($totales->total_registros ?? 0) ?>
</div>

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
