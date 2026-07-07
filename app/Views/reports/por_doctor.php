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

<form method="get" action="<?= site_url('reports/porDoctor') ?>" class="row g-3 mb-4 d-print-none">
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

<?= view('reports/partials/report_actions', [
    'pdf_url' => site_url('reports/porDoctorPdf?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
<p class="small text-muted">Totales por doctor según <strong>fecha de ingreso</strong> de la orden. Incluye fila «<?= esc($labelSinDoctor ?? 'Sin doctor') ?>» cuando no hay médico asignado. Excluye anuladas.</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Doctor</th>
                <th class="text-end">Cantidad de registros</th>
                <th class="text-end">Total facturado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row): ?>
            <?php $esSinDoctor = (int) ($row['doctor_id'] ?? -1) === 0; ?>
            <tr class="<?= $esSinDoctor ? 'table-warning' : '' ?>">
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= format_currency((float)($row['total'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($sinDoctorDetalle ?? [])): ?>
<?php
$totalSinDoctor = 0.0;
foreach ($sinDoctorDetalle as $rowSd) {
    $totalSinDoctor += (float) ($rowSd['total'] ?? 0);
}
?>
<h5 class="mt-4">Detalle — <?= esc($labelSinDoctor ?? 'Sin doctor') ?> (<?= count($sinDoctorDetalle) ?> orden/es)</h5>
<p class="small text-muted">Órdenes ingresadas en el período sin médico asignado.</p>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm">
        <thead class="table-warning">
            <tr>
                <th>Orden</th>
                <th>Fecha ingreso</th>
                <th>Paciente</th>
                <th class="text-end">Total facturado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sinDoctorDetalle as $rowSd): ?>
            <tr>
                <td><?= esc(registro_orden_display($rowSd)) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($rowSd['ingreso'] ?? '')) ?></td>
                <td><?= esc(trim((string) ($rowSd['paciente'] ?? ''))) ?: '—' ?></td>
                <td class="text-end"><?= format_currency((float) ($rowSd['total'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-warning">
                <th colspan="3" class="text-end">Total <?= esc($labelSinDoctor ?? 'Sin doctor') ?></th>
                <th class="text-end"><?= format_currency($totalSinDoctor) ?></th>
            </tr>
        </tfoot>
    </table>
</div>
<?php endif; ?>

<?php if (empty($data)): ?>
<p class="text-muted">No hay registros en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Registros facturables:</strong> <?= (int)($totales->total_registros ?? 0) ?> |
    <strong>Total facturado:</strong> <?= format_currency((float)($totales->total_facturado ?? 0)) ?>
</div>
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
