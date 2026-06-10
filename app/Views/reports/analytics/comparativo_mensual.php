<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? ''];

$fmtVar = static function ($v): string {
    if ($v === null) {
        return '<span class="text-muted">—</span>';
    }
    $v = (float) $v;
    $clase = $v > 0 ? 'text-success' : ($v < 0 ? 'text-danger' : 'text-muted');
    $signo = $v > 0 ? '+' : '';

    return '<span class="' . $clase . '">' . $signo . number_format($v, 2) . '%</span>';
};
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/comparativoMensualPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/comparativoMensualExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/comparativoMensual') ?>" class="row g-3 mb-4 d-print-none">
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

<?php if (! empty($rows)): ?>
<div class="row d-print-none mb-4">
    <div class="col-lg-7 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Tendencia mensual</strong></div>
            <div class="card-body"><canvas id="chartTendenciaMensual" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Crecimiento acumulado (facturación)</strong></div>
            <div class="card-body"><canvas id="chartAcumulado" height="120"></canvas></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-comparativo']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-comparativo">
        <thead class="table-dark">
            <tr>
                <th>Mes</th>
                <th class="text-end">Pacientes</th>
                <th class="text-end">Órdenes</th>
                <th class="text-end">Pruebas</th>
                <th class="text-end">Facturado</th>
                <th class="text-end">Cobrado</th>
                <th class="text-end">Var. vs mes anterior</th>
                <th class="text-end">Var. vs año anterior</th>
                <th class="text-end">Facturado acumulado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row): ?>
            <tr>
                <td><?= esc($row['mes']) ?></td>
                <td class="text-end"><?= (int) $row['pacientes'] ?></td>
                <td class="text-end"><?= (int) $row['ordenes'] ?></td>
                <td class="text-end"><?= (int) $row['pruebas'] ?></td>
                <td class="text-end"><?= format_currency((float) $row['facturado']) ?></td>
                <td class="text-end"><?= format_currency((float) $row['cobrado']) ?></td>
                <td class="text-end"><?= $fmtVar($row['var_mes_facturado']) ?></td>
                <td class="text-end"><?= $fmtVar($row['var_anio_facturado']) ?></td>
                <td class="text-end"><?= format_currency((float) $row['acumulado_facturado']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-comparativo']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay datos en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Meses:</strong> <?= (int) ($totales['meses'] ?? 0) ?> |
    <strong>Pacientes (suma mensual):</strong> <?= (int) ($totales['pacientes'] ?? 0) ?> |
    <strong>Órdenes:</strong> <?= (int) ($totales['ordenes'] ?? 0) ?> |
    <strong>Pruebas:</strong> <?= (int) ($totales['pruebas'] ?? 0) ?> |
    <strong>Facturado:</strong> <?= format_currency((float) ($totales['facturado'] ?? 0)) ?> |
    <strong>Cobrado:</strong> <?= format_currency((float) ($totales['cobrado'] ?? 0)) ?>
    <span class="d-block small text-muted mt-1">Pacientes = pacientes únicos por mes (un paciente atendido en dos meses cuenta en ambos). Órdenes anuladas excluidas. Variaciones calculadas sobre facturación.</span>
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
        'mes'       => (string) $r['mes'],
        'pacientes' => (int) $r['pacientes'],
        'ordenes'   => (int) $r['ordenes'],
        'facturado' => (float) $r['facturado'],
        'acumulado' => (float) $r['acumulado_facturado'],
    ], $rows ?? [])) ?>;
    if (!datos.length || typeof Chart === 'undefined') { return; }

    new Chart(document.getElementById('chartTendenciaMensual'), {
        data: {
            labels: datos.map(function (d) { return d.mes; }),
            datasets: [
                { type: 'bar', label: 'Facturado', data: datos.map(function (d) { return d.facturado; }), backgroundColor: 'rgba(13,110,253,.6)', yAxisID: 'y' },
                { type: 'line', label: 'Órdenes', data: datos.map(function (d) { return d.ordenes; }), borderColor: '#dc3545', tension: .2, yAxisID: 'y1' },
                { type: 'line', label: 'Pacientes', data: datos.map(function (d) { return d.pacientes; }), borderColor: '#198754', tension: .2, yAxisID: 'y1' }
            ]
        },
        options: {
            scales: {
                y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Facturación' } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
            }
        }
    });

    new Chart(document.getElementById('chartAcumulado'), {
        type: 'line',
        data: {
            labels: datos.map(function (d) { return d.mes; }),
            datasets: [{
                label: 'Facturado acumulado',
                data: datos.map(function (d) { return d.acumulado; }),
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,.15)',
                fill: true,
                tension: .2
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
});
</script>
<?= $this->endSection() ?>
