<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? '', 'person_id' => $personId ?? 0];
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/resultadosCorregidosPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/resultadosCorregidosExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/resultadosCorregidos') ?>" class="row g-3 mb-4 d-print-none">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="person_id" class="form-label">Usuario</label>
        <select id="person_id" name="person_id" class="form-select">
            <option value="0">Todos</option>
            <?php foreach ($usuarios ?? [] as $u): ?>
                <option value="<?= (int) $u['person_id'] ?>" <?= (int) ($personId ?? 0) === (int) $u['person_id'] ? 'selected' : '' ?>>
                    <?= esc($u['nombre'] . ' (' . $u['username'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-corregidos']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-corregidos">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Orden</th>
                <th>Paciente</th>
                <th>Prueba</th>
                <th>Campo</th>
                <th>Resultado original</th>
                <th>Resultado nuevo</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row): ?>
            <tr>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha'] ?? '')) ?></td>
                <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
                <td><?= esc($row['paciente'] ?: '—') ?></td>
                <td><?= esc($row['prueba'] ?: '—') ?></td>
                <td><?= esc($row['campo']) ?></td>
                <td class="text-danger"><s><?= esc($row['valor_anterior']) ?></s></td>
                <td class="text-success fw-bold"><?= esc($row['valor_nuevo']) ?></td>
                <td><?= esc($row['usuario'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-corregidos']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay correcciones de resultados registradas en el período seleccionado.</p>
<?php else: ?>
<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header"><strong>Correcciones por usuario</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (array_slice($porUsuario ?? [], 0, 10, true) as $usuario => $cant): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= esc($usuario !== '' ? $usuario : 'Desconocido') ?></span>
                        <strong><?= (int) $cant ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<div class="alert alert-secondary">
    <strong>Total de correcciones:</strong> <?= (int) ($total ?? 0) ?>
    <span class="d-block small text-muted mt-1">Historial tomado de la bitácora de auditoría existente (comparación antes/después por campo). El sistema no solicita motivo al corregir; las observaciones quedan en el detalle del evento de auditoría.</span>
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
