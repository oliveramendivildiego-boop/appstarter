<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? '', 'sla' => $slaHoras ?? 24, 'grupo_id' => $grupoId ?? 0, 'prueba_id' => $pruebaId ?? 0];
$fmtHoras = static fn ($h) => $h === null ? '—' : number_format((float) $h, 1) . ' h';
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => ($subtitle ?? '') . ' · SLA: ' . (int) ($slaHoras ?? 24) . ' horas',
    'pdf_url'   => site_url('reports/tiempoEntregaPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/tiempoEntregaExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/tiempoEntrega') ?>" class="row g-3 mb-4 d-print-none">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="grupo_id" class="form-label">Grupo</label>
        <select id="grupo_id" name="grupo_id" class="form-select">
            <option value="0">Todos</option>
            <?php foreach ($grupos ?? [] as $g): ?>
                <option value="<?= (int) $g['anacategoria_id'] ?>" <?= (int) ($grupoId ?? 0) === (int) $g['anacategoria_id'] ? 'selected' : '' ?>><?= esc($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label for="prueba_id" class="form-label">Prueba</label>
        <select id="prueba_id" name="prueba_id" class="form-select">
            <option value="0">Todas</option>
            <?php foreach ($pruebas ?? [] as $p): ?>
                <option value="<?= (int) $p['prianacategoria_id'] ?>" <?= (int) ($pruebaId ?? 0) === (int) $p['prianacategoria_id'] ? 'selected' : '' ?>>
                    <?= esc($p['grupo'] !== '' ? $p['grupo'] . ' — ' . $p['name'] : $p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label for="sla" class="form-label">SLA (horas)</label>
        <input type="number" id="sla" name="sla" min="1" max="720" class="form-control" style="max-width:110px" value="<?= (int) ($slaHoras ?? 24) ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<div class="row mb-3">
    <div class="col-6 col-md-2 mb-2"><div class="card text-center"><div class="card-body py-2">
        <div class="fs-5 fw-bold"><?= (int) ($totales['ordenes'] ?? 0) ?></div><div class="small text-muted">Órdenes</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center"><div class="card-body py-2">
        <div class="fs-5 fw-bold"><?= esc($fmtHoras($totales['promedio_resultado'] ?? null)) ?></div><div class="small text-muted">TAT promedio</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center"><div class="card-body py-2">
        <div class="fs-5 fw-bold"><?= esc($fmtHoras($totales['min_resultado'] ?? null)) ?></div><div class="small text-muted">TAT mínimo</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center"><div class="card-body py-2">
        <div class="fs-5 fw-bold"><?= esc($fmtHoras($totales['max_resultado'] ?? null)) ?></div><div class="small text-muted">TAT máximo</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center border-success"><div class="card-body py-2">
        <div class="fs-5 fw-bold text-success"><?= ($totales['cumplimiento_sla'] ?? null) !== null ? number_format((float) $totales['cumplimiento_sla'], 1) . '%' : '—' ?></div>
        <div class="small text-muted">Cumplimiento SLA</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center border-danger"><div class="card-body py-2">
        <div class="fs-5 fw-bold text-danger"><?= (int) ($totales['fuera_sla'] ?? 0) ?></div><div class="small text-muted">Retrasos</div>
    </div></div></div>
</div>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-tat']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-tat">
        <thead class="table-dark">
            <tr>
                <th>Orden</th>
                <th>Paciente</th>
                <th>Recepción</th>
                <th>Primer resultado</th>
                <th>Primera validación</th>
                <th class="text-end">Horas a resultado</th>
                <th class="text-end">Horas a validación</th>
                <th>SLA</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row):
                $hr = $row['horas_resultado'];
                if ($hr === null) {
                    $slaLabel = 'Sin resultado';
                    $slaClass = 'bg-secondary';
                } elseif ((float) $hr <= (float) ($slaHoras ?? 24)) {
                    $slaLabel = 'Dentro de SLA';
                    $slaClass = 'bg-success';
                } else {
                    $slaLabel = 'Retraso';
                    $slaClass = 'bg-danger';
                }
            ?>
            <tr>
                <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
                <td><?= esc($row['paciente']) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
                <td><?= $row['fecha_resultado'] ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_resultado'])) : '—' ?></td>
                <td><?= $row['fecha_validacion'] ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_validacion'])) : '—' ?></td>
                <td class="text-end"><?= esc($fmtHoras($row['horas_resultado'])) ?></td>
                <td class="text-end"><?= esc($fmtHoras($row['horas_validacion'])) ?></td>
                <td><span class="badge <?= esc($slaClass) ?>"><?= esc($slaLabel) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-tat']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay órdenes en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Órdenes:</strong> <?= (int) ($totales['ordenes'] ?? 0) ?> |
    <strong>Con resultado:</strong> <?= (int) ($totales['con_resultado'] ?? 0) ?> |
    <strong>Sin resultado:</strong> <?= (int) ($totales['sin_resultado'] ?? 0) ?> |
    <strong>Validación — promedio:</strong> <?= esc($fmtHoras($totales['promedio_validacion'] ?? null)) ?>,
    <strong>mín:</strong> <?= esc($fmtHoras($totales['min_validacion'] ?? null)) ?>,
    <strong>máx:</strong> <?= esc($fmtHoras($totales['max_validacion'] ?? null)) ?>
    <span class="d-block small text-muted mt-1">Recepción = ingreso de la orden. Resultado y validación se obtienen de la bitácora de auditoría (primer evento registrado). Las órdenes anuladas no se incluyen.</span>
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
