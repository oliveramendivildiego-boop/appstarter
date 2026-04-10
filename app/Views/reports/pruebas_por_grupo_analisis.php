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

<form method="get" action="<?= site_url('reports/pruebasPorGrupoAnalisis') ?>" class="row g-3 mb-4 align-items-end d-print-none">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-md-4 col-lg-3">
        <label for="anacategoria_id" class="form-label">Grupo de análisis (padre)</label>
        <select name="anacategoria_id" id="anacategoria_id" class="form-select">
            <option value="0"<?= (int)($anacategoria_id ?? 0) === 0 ? ' selected' : '' ?>>Todos los grupos</option>
            <?php foreach ($grupos_opts ?? [] as $g): ?>
                <?php $gid = (int) ($g['anacategoria_id'] ?? 0); ?>
                <option value="<?= $gid ?>"<?= (int)($anacategoria_id ?? 0) === $gid ? ' selected' : '' ?>>
                    <?= esc($g['name'] ?? '') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<?= view('reports/partials/report_actions', [
    'pdf_url' => site_url('reports/pruebasPorGrupoAnalisisPdf?' . http_build_query([
        'start' => $startDate ?? '',
        'end' => $endDate ?? '',
        'anacategoria_id' => (int) ($anacategoria_id ?? 0),
    ])),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
<p class="small text-muted mb-0 d-print-none">
    Mismo criterio que <strong>Pruebas realizadas por fecha</strong>: órdenes con al menos un resultado guardado, no anuladas ni eliminadas.
    Cada fila del detalle cuenta cuántas órdenes incluyeron ese análisis (según el campo de pruebas de la orden).
</p>
<p class="small text-muted">Órdenes en el período: <strong><?= (int) ($ordenes_en_periodo ?? 0) ?></strong></p>

<?php if (empty($secciones)): ?>
    <p class="text-muted mt-3">No hay pruebas del grupo seleccionado en el período (o no hay órdenes completas en esas fechas).</p>
<?php else: ?>
    <?php foreach ($secciones as $sec): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0"><?= esc($sec['nombre'] ?? '') ?></h5>
                <span class="badge bg-light text-dark">Total de pruebas (suma en órdenes): <?= (int) ($sec['total_pruebas'] ?? 0) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Análisis</th>
                            <th class="text-end">Veces solicitado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sec['detalle'] ?? [] as $fila): ?>
                            <tr>
                                <td><?= esc($fila['prueba'] ?? '') ?></td>
                                <td class="text-end"><?= (int) ($fila['cantidad'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
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
