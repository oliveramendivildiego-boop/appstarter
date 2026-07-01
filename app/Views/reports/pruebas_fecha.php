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

<form method="get" action="<?= site_url('reports/pruebasFecha') ?>" class="row g-3 mb-4 d-print-none">
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
    'pdf_url' => site_url('reports/pruebasFechaPdf?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
<p class="small text-muted">Solo órdenes <strong>completas</strong> (con al menos un resultado guardado), no anuladas ni eliminadas. Los nombres de prueba se resuelven desde el catálogo activo (incluye formato <code>contador_ID</code>).</p>

<div class="row g-2 mb-3 d-print-none">
    <div class="col-md-6 col-lg-4">
        <label for="filtro_listado_reporte" class="form-label small mb-1">Buscar en la tabla</label>
        <input type="search" id="filtro_listado_reporte" class="form-control form-control-sm" placeholder="Ej. PCR, paciente, doctor...">
    </div>
    <div class="col-12">
        <span id="filtro_listado_reporte_info" class="small text-muted"></span>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla_listado_reporte">
        <thead class="table-dark">
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th>Pruebas realizadas</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row): ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(lab_dt_short($row['ingreso'] ?? null)) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td><?= esc($row['pruebas_nombres'] ?? '-') ?></td>
                <td class="text-end"><?= format_currency((float)($row['total'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay órdenes con pruebas en el período seleccionado.</p>
<?php else: ?>
<p class="text-muted small">Total: <?= count($data) ?> orden(es) con pruebas.</p>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/reportes_listado_busqueda.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#report_start", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr("#report_end", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
});
</script>
<?= $this->endSection() ?>
