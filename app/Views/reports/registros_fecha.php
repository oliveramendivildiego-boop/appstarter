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

<form method="get" action="<?= site_url('reports/registrosFecha') ?>" class="row g-3 mb-4 d-print-none">
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
    'pdf_url' => site_url('reports/registrosFechaPdf?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No.</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total</th>
                <th class="text-end">Cobrado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row):
                $del = (int) ($row['estado_eliminado'] ?? 0) === 1;
                $anul = (int) ($row['estado_anulado'] ?? 0) === 1;
                $rvn = (int) ($row['regvalues_cnt'] ?? 0);
                if ($del) {
                    $estLabel = 'Eliminada';
                    $estClass = 'bg-dark';
                } elseif ($anul) {
                    $estLabel = 'Anulada';
                    $estClass = 'bg-secondary';
                } elseif ($rvn > 0) {
                    $estLabel = 'Completa';
                    $estClass = 'bg-success';
                } else {
                    $estLabel = 'Incompleta';
                    $estClass = 'bg-warning text-dark';
                }
                $facturable = !$del && !$anul;
                $montoTotal = $facturable ? (float) ($row['total'] ?? 0) : 0.0;
                $montoCobrado = $facturable ? (float) ($row['monto_pagar'] ?? 0) : 0.0;
            ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><span class="badge <?= esc($estClass) ?>"><?= esc($estLabel) ?></span></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= number_format($montoTotal, 2) ?> Bs<?= !$facturable ? ' <span class="text-muted small">(—)</span>' : '' ?></td>
                <td class="text-end"><?= number_format($montoCobrado, 2) ?> Bs<?= !$facturable ? ' <span class="text-muted small">(—)</span>' : '' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay registros en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Registros facturables (sin anuladas):</strong> <?= (int)($totales->total_registros ?? 0) ?> |
    <strong>Total facturado:</strong> <?= number_format((float)($totales->total_facturado ?? 0), 2) ?> Bs |
    <strong>Total cobrado:</strong> <?= number_format((float)($totales->total_cobrado ?? 0), 2) ?> Bs
    <span class="d-block small text-muted mt-1">Las órdenes anuladas aparecen en la tabla para consulta; no suman en totales ni en montos (devolución). En columnas Total/Cobrado se muestra 0 Bs (—). Completa = con resultados guardados.</span>
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
