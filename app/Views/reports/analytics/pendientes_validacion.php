<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? ''];
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/pendientesValidacionPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/pendientesValidacionExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/pendientesValidacion') ?>" class="row g-3 mb-4 d-print-none">
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

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-pendientes']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-pendientes">
        <thead class="table-dark">
            <tr>
                <th>Orden</th>
                <th>Paciente</th>
                <th>Fecha recepción</th>
                <th>Primer resultado</th>
                <th>Usuario que cargó</th>
                <th class="text-end">Tiempo pendiente</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row):
                $horas = (float) ($row['horas_pendiente'] ?? 0);
                $dias  = floor($horas / 24);
                $tiempoLabel = $dias >= 1
                    ? $dias . ' d ' . number_format(fmod($horas, 24), 0) . ' h'
                    : number_format($horas, 1) . ' h';
                $tiempoClass = $horas >= 72 ? 'text-danger fw-bold' : ($horas >= 24 ? 'text-warning fw-bold' : '');
            ?>
            <tr>
                <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
                <td><?= esc($row['paciente']) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
                <td><?= $row['fecha_resultado'] ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_resultado'])) : '—' ?></td>
                <td><?= esc($row['usuario_cargo']) ?></td>
                <td class="text-end <?= esc($tiempoClass) ?>"><?= esc($tiempoLabel) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-pendientes']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay resultados pendientes de validar en el período seleccionado.</p>
<?php else: ?>
<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header"><strong>Pendientes por usuario</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (array_slice($porUsuario ?? [], 0, 10, true) as $usuario => $cant): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= esc($usuario) ?></span>
                        <strong><?= (int) $cant ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header"><strong>Pendientes por área</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (array_slice($porArea ?? [], 0, 10, true) as $area => $cant): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= esc($area) ?></span>
                        <strong><?= (int) $cant ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<div class="alert alert-secondary">
    <strong>Total de órdenes pendientes de validar:</strong> <?= (int) ($total ?? 0) ?>
    <span class="d-block small text-muted mt-1">Órdenes con resultados cargados que aún no tienen validación técnica ni médica registrada. El tiempo pendiente se calcula desde la recepción de la orden.</span>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/analytics_table.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var fpOpts = { dateFormat: "Y-m-d", locale: "es" };
    if (typeof flatpickrPositionArrowTopLeft === 'function') {
        fpOpts.onOpen = function (s, d, i) { flatpickrPositionArrowTopLeft(i); };
    }
    flatpickr("#report_start", fpOpts);
    flatpickr("#report_end", fpOpts);
});
</script>
<?= $this->endSection() ?>
