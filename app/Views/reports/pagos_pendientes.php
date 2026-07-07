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
</div>

<form method="get" action="<?= site_url('reports/pagosPendientes') ?>" class="row g-3 mb-4 d-print-none">
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

<?= view('reports/partials/report_actions', [
    'pdf_url' => site_url('reports/pagosPendientesPdf?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
<p class="small text-muted">Órdenes con <strong>saldo pendiente hoy</strong>, filtradas por <strong>fecha de ingreso</strong> de la orden (no por fecha de cobro). No se incluyen anuladas ni eliminadas. Por defecto se muestran los últimos 2 años.</p>

<div class="alert alert-warning mb-4">
    <strong><?= count($pendientes ?? []) ?></strong> orden(es) con saldo pendiente ingresadas en el período.
    <span class="d-block mt-1"><strong>Saldo total pendiente:</strong> <span class="text-danger"><?= format_currency((float) ($total_saldo ?? 0)) ?></span></span>
    <span class="d-block small mt-1 text-muted">
        En el <a href="<?= site_url('reports/pagos?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])) ?>">reporte de pagos</a>,
        el saldo pendiente del resumen solo incluye órdenes que <strong>tuvieron un cobro en ese mismo rango</strong>.
        Si filtra aquí el mismo mes (p. ej. junio 2026), ambos totales deben coincidir cuando todas las órdenes pendientes se ingresaron en ese mes.
    </span>
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
                    <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
                    <td><?= esc($row['paciente'] ?? '') ?></td>
                    <td><?= esc($row['doctor'] ?? '') ?></td>
                    <td class="text-end"><?= format_currency((float) ($row['total'] ?? 0)) ?></td>
                    <td class="text-end"><?= format_currency((float) ($row['monto_pagado'] ?? 0)) ?></td>
                    <td class="text-end text-danger fw-bold"><?= format_currency((float) ($row['saldo'] ?? 0)) ?></td>
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
