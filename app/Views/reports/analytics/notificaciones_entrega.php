<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = [
    'start'       => $startDate ?? '',
    'end'         => $endDate ?? '',
    'person_id'   => $personId ?? 0,
    'registro_id' => $registroId ?? 0,
];
$fmtHoras = static fn ($h) => $h === null ? '—' : number_format((float) $h, 1) . ' h';
$calidad  = $totales['calidad'] ?? [];
$chartUsuario = array_slice($totales['por_usuario'] ?? [], 0, 15, true);
$chartDia     = array_slice($totales['por_dia'] ?? [], 0, 15, true);
$chartRecep   = array_slice($totales['por_recepcion'] ?? [], 0, 15, true);
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/notificacionesEntregaPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/notificacionesEntregaExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/notificacionesEntrega') ?>" class="row g-3 mb-4 d-print-none">
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
    <div class="col-auto">
        <label for="registro_id" class="form-label">Registro ID</label>
        <input type="number" min="0" id="registro_id" name="registro_id" class="form-control" style="max-width:120px" value="<?= (int) ($registroId ?? 0) ?: '' ?>" placeholder="Todos">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="card text-center h-100"><div class="card-body py-2">
        <div class="fs-5 fw-bold"><?= (int) ($totales['total_notificaciones'] ?? 0) ?></div>
        <div class="small text-muted">Notificaciones realizadas</div>
    </div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="card text-center h-100"><div class="card-body py-2">
        <div class="fs-5 fw-bold"><?= (int) ($totales['total_analisis'] ?? 0) ?></div>
        <div class="small text-muted">Análisis entregados</div>
    </div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="card text-center h-100"><div class="card-body py-2">
        <div class="fs-5 fw-bold"><?= esc($fmtHoras($totales['promedio_horas'] ?? null)) ?></div>
        <div class="small text-muted">Promedio validación → entrega</div>
    </div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="card text-center h-100 border-success"><div class="card-body py-2">
        <div class="fs-5 fw-bold text-success"><?= ($calidad['pct_dentro_12'] ?? null) !== null ? number_format((float) $calidad['pct_dentro_12'], 1) . '%' : '—' ?></div>
        <div class="small text-muted">Entregas ≤ 12 h</div>
    </div></div></div>
</div>

<div class="row mb-3 d-print-none">
    <div class="col-md-4 mb-2">
        <div class="card h-100"><div class="card-header py-2"><strong>Calidad de entrega</strong></div><div class="card-body py-2 small">
            <div class="d-flex justify-content-between"><span>≤ 12 horas</span><span><strong><?= (int) ($calidad['dentro_12'] ?? 0) ?></strong><?= ($calidad['pct_dentro_12'] ?? null) !== null ? ' (' . number_format((float) $calidad['pct_dentro_12'], 1) . '%)' : '' ?></span></div>
            <div class="d-flex justify-content-between"><span>12 – 24 horas</span><span><strong><?= (int) ($calidad['entre_12_24'] ?? 0) ?></strong><?= ($calidad['pct_entre_12_24'] ?? null) !== null ? ' (' . number_format((float) $calidad['pct_entre_12_24'], 1) . '%)' : '' ?></span></div>
            <div class="d-flex justify-content-between"><span>&gt; 24 horas</span><span><strong><?= (int) ($calidad['mayor_24'] ?? 0) ?></strong><?= ($calidad['pct_mayor_24'] ?? null) !== null ? ' (' . number_format((float) $calidad['pct_mayor_24'], 1) . '%)' : '' ?></span></div>
            <?php if ((int) ($calidad['sin_validacion'] ?? 0) > 0): ?>
            <div class="d-flex justify-content-between text-muted"><span>Sin fecha de validación</span><span><?= (int) $calidad['sin_validacion'] ?></span></div>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card h-100"><div class="card-header py-2"><strong>Entregas por mes</strong></div><div class="card-body py-2 small">
            <?php if (empty($totales['por_mes'])): ?>
                <span class="text-muted">Sin datos</span>
            <?php else: ?>
                <?php foreach (array_slice($totales['por_mes'], 0, 6, true) as $mes => $cnt): ?>
                    <div class="d-flex justify-content-between"><span><?= esc($mes) ?></span><strong><?= (int) $cnt ?></strong></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card h-100"><div class="card-header py-2"><strong>Top recepciones</strong></div><div class="card-body py-2 small">
            <?php if (empty($chartRecep)): ?>
                <span class="text-muted">Sin datos</span>
            <?php else: ?>
                <?php foreach (array_slice($chartRecep, 0, 6, true) as $cod => $cnt): ?>
                    <div class="d-flex justify-content-between"><span><?= esc($cod) ?></span><strong><?= (int) $cnt ?></strong></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<?php if (! empty($rows)): ?>
<div class="row mb-4 d-print-none">
    <div class="col-lg-4 mb-3">
        <div class="card h-100"><div class="card-header"><strong>Entregas por usuario</strong></div>
        <div class="card-body"><canvas id="chartNotifUsuario" height="180"></canvas></div></div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card h-100"><div class="card-header"><strong>Entregas por día</strong></div>
        <div class="card-body"><canvas id="chartNotifDia" height="180"></canvas></div></div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card h-100"><div class="card-header"><strong>Entregas por recepción</strong></div>
        <div class="card-body"><canvas id="chartNotifRecepcion" height="180"></canvas></div></div>
    </div>
</div>
<?php endif; ?>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-notif-entrega']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-notif-entrega">
        <thead class="table-dark">
            <tr>
                <th>Fecha validación</th>
                <th>Fecha entrega</th>
                <th>Tiempo transcurrido</th>
                <th>Análisis</th>
                <th>Paciente</th>
                <th>Registro</th>
                <th>Usuario</th>
                <th>Estado</th>
                <th class="d-print-none"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row): ?>
            <?php $rid = (int) ($row['registro_id'] ?? 0); ?>
            <tr>
                <td><?= ! empty($row['validated_at']) ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['validated_at'])) : '—' ?></td>
                <td><?= ! empty($row['fecha_entrega']) ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_entrega'])) : '—' ?></td>
                <td><?= esc($fmtHoras($row['horas_transcurridas'] ?? null)) ?></td>
                <td><?= esc($row['analisis_codigo'] ?? '') ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['registro_codigo'] ?? '') ?></td>
                <td><?= esc($row['usuario'] ?? '') ?></td>
                <td><span class="badge bg-success"><?= esc($row['estado'] ?? 'Entregado') ?></span></td>
                <td class="text-end d-print-none">
                    <?php if ($rid > 0): ?>
                    <a href="<?= site_url('registers/viewreport/' . $rid) ?>" class="btn btn-sm btn-outline-primary">Ver reporte</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-notif-entrega']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay entregas confirmadas en el período seleccionado.</p>
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

    if (typeof Chart === 'undefined') { return; }

    function barChart(canvasId, labels, data, color) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !labels.length) { return; }
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ label: 'Entregas', data: data, backgroundColor: color }]
            },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    var porUsuario = <?= json_encode($chartUsuario, JSON_UNESCAPED_UNICODE) ?>;
    var porDia = <?= json_encode($chartDia, JSON_UNESCAPED_UNICODE) ?>;
    var porRecep = <?= json_encode($chartRecep, JSON_UNESCAPED_UNICODE) ?>;

    barChart('chartNotifUsuario', Object.keys(porUsuario), Object.values(porUsuario), '#FF7218');
    barChart('chartNotifDia', Object.keys(porDia), Object.values(porDia), '#0d6efd');
    barChart('chartNotifRecepcion', Object.keys(porRecep), Object.values(porRecep), '#198754');
});
</script>
<?= $this->endSection() ?>
