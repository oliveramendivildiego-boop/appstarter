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

<form method="get" action="<?= site_url('reports/pagosPendientes') ?>" class="row g-3 mb-4">
    <div class="col-auto">
        <label for="pend_start" class="form-label">Desde</label>
        <input type="text" id="pend_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="pend_end" class="form-label">Hasta</label>
        <input type="text" id="pend_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-auto d-flex align-items-end">
        <a href="<?= site_url('reports/pagos?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])) ?>" class="btn btn-outline-secondary">Ver reporte de pagos completo</a>
    </div>
</form>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
<p class="small text-muted">Solo órdenes con <strong>saldo pendiente (mayor a cero)</strong> según la fecha de ingreso. No se incluyen anuladas.</p>

<div class="alert alert-warning mb-4">
    <strong><?= count($pendientes ?? []) ?></strong> orden(es) con saldo pendiente en el período.
    <span class="d-block mt-1"><strong>Saldo total pendiente:</strong> <span class="text-danger"><?= number_format((float) ($total_saldo ?? 0), 2) ?> Bs</span></span>
</div>

<div class="table-responsive">
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
                    <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?> Bs</td>
                    <td class="text-end"><?= number_format((float) ($row['monto_pagado'] ?? 0), 2) ?> Bs</td>
                    <td class="text-end text-danger fw-bold"><?= number_format((float) ($row['saldo'] ?? 0), 2) ?> Bs</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($pendientes)): ?>
    <p class="text-muted">No hay pendientes de pago en el período seleccionado.</p>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr('#pend_start', { dateFormat: 'Y-m-d', locale: 'es', onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr('#pend_end', { dateFormat: 'Y-m-d', locale: 'es', onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
});
</script>
<?= $this->endSection() ?>
