<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? '', 'grupo_id' => $grupoId ?? 0, 'doctor_id' => $doctorId ?? 0];
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/pruebasMasSolicitadasPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/pruebasMasSolicitadasExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/pruebasMasSolicitadas') ?>" class="row g-3 mb-4 d-print-none">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="grupo_id" class="form-label">Grupo de análisis</label>
        <select id="grupo_id" name="grupo_id" class="form-select">
            <option value="0">Todos</option>
            <?php foreach ($grupos ?? [] as $g): ?>
                <option value="<?= (int) $g['anacategoria_id'] ?>" <?= (int) ($grupoId ?? 0) === (int) $g['anacategoria_id'] ? 'selected' : '' ?>><?= esc($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label for="doctor_id" class="form-label">Médico</label>
        <select id="doctor_id" name="doctor_id" class="form-select">
            <option value="0">Todos</option>
            <?php foreach ($doctores ?? [] as $d): ?>
                <option value="<?= (int) $d['doctor_id'] ?>" <?= (int) ($doctorId ?? 0) === (int) $d['doctor_id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<?php if (! empty($rows)): ?>
<div class="row d-print-none mb-4">
    <div class="col-lg-7 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Pruebas más solicitadas</strong>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="btnTop10">Top 10</button>
                    <button type="button" class="btn btn-outline-primary" id="btnTop20">Top 20</button>
                </div>
            </div>
            <div class="card-body"><canvas id="chartTop" height="130"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Distribución porcentual (Top 10 + otras)</strong></div>
            <div class="card-body"><canvas id="chartPie" height="130"></canvas></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-solicitadas']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-solicitadas">
        <thead class="table-dark">
            <tr>
                <th>Ranking</th>
                <th>Código</th>
                <th>Prueba</th>
                <th>Grupo</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">% del total</th>
                <th class="text-end">Ingreso estimado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row): ?>
            <tr>
                <td><?= (int) $row['ranking'] ?></td>
                <td><?= (int) $row['codigo'] ?></td>
                <td><?= esc($row['prueba']) ?></td>
                <td><?= esc($row['grupo']) ?></td>
                <td class="text-end"><?= (int) $row['cantidad'] ?></td>
                <td class="text-end"><?= number_format((float) $row['porcentaje'], 2) ?>%</td>
                <td class="text-end"><?= format_currency((float) $row['ingreso']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-solicitadas']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay pruebas solicitadas en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Órdenes del período:</strong> <?= (int) ($totales['total_ordenes'] ?? 0) ?> |
    <strong>Total pruebas solicitadas:</strong> <?= (int) ($totales['total_pruebas'] ?? 0) ?> |
    <strong>Ingreso estimado total:</strong> <?= format_currency((float) ($totales['total_ingresos'] ?? 0)) ?>
    <span class="d-block small text-muted mt-1">El ingreso es estimado (cantidad × precio de catálogo vigente). Las órdenes anuladas no se incluyen.</span>
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

    var datos = <?= json_encode(array_map(static fn ($r) => [
        'prueba'   => (string) $r['prueba'],
        'cantidad' => (int) $r['cantidad'],
    ], array_slice($rows ?? [], 0, 20))) ?>;
    var totalPruebas = <?= (int) ($totales['total_pruebas'] ?? 0) ?>;
    if (!datos.length || typeof Chart === 'undefined') { return; }

    var ctxTop = document.getElementById('chartTop');
    var chartTop = new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: datos.slice(0, 10).map(function (d) { return d.prueba; }),
            datasets: [{ label: 'Cantidad solicitada', data: datos.slice(0, 10).map(function (d) { return d.cantidad; }), backgroundColor: '#0d6efd' }]
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    function setTop(n, btnOn, btnOff) {
        chartTop.data.labels = datos.slice(0, n).map(function (d) { return d.prueba; });
        chartTop.data.datasets[0].data = datos.slice(0, n).map(function (d) { return d.cantidad; });
        chartTop.update();
        btnOn.classList.add('active');
        btnOff.classList.remove('active');
    }
    var b10 = document.getElementById('btnTop10'), b20 = document.getElementById('btnTop20');
    if (b10 && b20) {
        b10.addEventListener('click', function () { setTop(10, b10, b20); });
        b20.addEventListener('click', function () { setTop(20, b20, b10); });
    }

    var top10 = datos.slice(0, 10);
    var resto = totalPruebas - top10.reduce(function (s, d) { return s + d.cantidad; }, 0);
    var pieLabels = top10.map(function (d) { return d.prueba; });
    var pieData = top10.map(function (d) { return d.cantidad; });
    if (resto > 0) { pieLabels.push('Otras'); pieData.push(resto); }
    new Chart(document.getElementById('chartPie'), {
        type: 'doughnut',
        data: { labels: pieLabels, datasets: [{ data: pieData }] },
        options: { plugins: { legend: { position: 'right' } } }
    });
});
</script>
<?= $this->endSection() ?>
