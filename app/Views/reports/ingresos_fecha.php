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

<form method="get" action="<?= site_url('reports/ingresosFecha') ?>" class="row g-3 mb-4">
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
<p class="small text-muted">Las columnas <strong>facturables</strong> son las que cuentan para ingresos (excluyen anuladas). Las columnas <strong>anuladas</strong> son solo referencia del histórico en el sistema (no se suman arriba).</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th rowspan="2" class="align-middle">Fecha</th>
                <th colspan="3" class="text-center">Facturables</th>
                <th colspan="3" class="text-center text-white-50">Anuladas (referencia)</th>
            </tr>
            <tr>
                <th class="text-end">Cant.</th>
                <th class="text-end">Total fact.</th>
                <th class="text-end">Cobrado</th>
                <th class="text-end text-white-50">Cant.</th>
                <th class="text-end text-white-50">Total (hist.)</th>
                <th class="text-end text-white-50">Cobrado (hist.)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row): ?>
            <?php
                $nAnul = (int) ($row['cantidad_anuladas'] ?? 0);
            ?>
            <tr>
                <td><?= esc(date('d/m/Y', strtotime($row['fecha'] ?? ''))) ?></td>
                <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float)($row['total'] ?? 0), 2) ?> Bs</td>
                <td class="text-end"><?= number_format((float)($row['cobrado'] ?? 0), 2) ?> Bs</td>
                <td class="text-end text-muted"><?= $nAnul > 0 ? $nAnul : '—' ?></td>
                <td class="text-end text-muted"><?= $nAnul > 0 ? number_format((float)($row['total_anulado_ref'] ?? 0), 2) . ' Bs' : '—' ?></td>
                <td class="text-end text-muted"><?= $nAnul > 0 ? number_format((float)($row['cobrado_anulado_ref'] ?? 0), 2) . ' Bs' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay registros en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Registros facturables:</strong> <?= (int)($totales->total_registros ?? 0) ?> |
    <strong>Total facturado:</strong> <?= number_format((float)($totales->total_facturado ?? 0), 2) ?> Bs |
    <strong>Total cobrado:</strong> <?= number_format((float)($totales->total_cobrado ?? 0), 2) ?> Bs
</div>
<?php
    $ta = $totalesAnulados ?? null;
    $nAnulTot = (int) ($ta->total_registros ?? 0);
?>
<?php if ($nAnulTot > 0): ?>
<div class="alert alert-light border text-muted">
    <strong>Órdenes anuladas en el período (no facturables):</strong> <?= $nAnulTot ?> |
    <span title="Valores guardados en pago al momento del reporte; no incluidos en totales facturables">Total histórico facturado:</span> <?= number_format((float) ($ta->total_facturado ?? 0), 2) ?> Bs |
    Cobrado histórico: <?= number_format((float) ($ta->total_cobrado ?? 0), 2) ?> Bs
</div>
<?php endif; ?>
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
