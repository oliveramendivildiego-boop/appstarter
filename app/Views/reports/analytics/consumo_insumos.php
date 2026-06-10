<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? ''];
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/consumoInsumosPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/consumoInsumosExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/consumoInsumos') ?>" class="row g-3 mb-4 d-print-none">
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

<?php if (! empty($porMes)): ?>
<div class="card mb-4 d-print-none">
    <div class="card-header"><strong>Consumo mensual por reactivo</strong></div>
    <div class="card-body"><canvas id="chartConsumo" height="100"></canvas></div>
</div>
<?php endif; ?>

<h5 class="mt-3">Consumo por prueba y reactivo</h5>
<p class="text-muted small">Consumo automático aplicado al cargar resultados (pruebas que consumen más recursos primero).</p>
<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-consumo-prueba']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-consumo-prueba">
        <thead class="table-dark">
            <tr>
                <th>Prueba</th>
                <th>Reactivo</th>
                <th>Unidad</th>
                <th class="text-end">Eventos</th>
                <th class="text-end">Cantidad consumida</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($porPrueba ?? [] as $row): ?>
            <tr>
                <td><?= esc($row['prueba']) ?></td>
                <td><?= esc($row['reactivo']) ?></td>
                <td><?= esc($row['unidad']) ?></td>
                <td class="text-end"><?= (int) $row['eventos'] ?></td>
                <td class="text-end"><?= number_format((float) $row['cantidad_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-consumo-prueba']) ?>
<?php if (empty($porPrueba)): ?>
<p class="text-muted">Sin consumo automático por prueba en el período (requiere configuración prueba → reactivo en el módulo de insumos).</p>
<?php endif; ?>

<h5 class="mt-4">Consumo por reactivo (todas las salidas de inventario)</h5>
<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-consumo-reactivo']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-consumo-reactivo">
        <thead class="table-dark">
            <tr>
                <th>Reactivo</th>
                <th>Unidad</th>
                <th class="text-end">Movimientos</th>
                <th class="text-end">Cantidad consumida</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($porReactivo ?? [] as $row): ?>
            <tr>
                <td><?= esc($row['reactivo']) ?></td>
                <td><?= esc($row['unidad']) ?></td>
                <td class="text-end"><?= (int) $row['movimientos'] ?></td>
                <td class="text-end"><?= number_format((float) $row['cantidad_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-consumo-reactivo']) ?>

<div class="row">
    <div class="col-md-6 mb-3">
        <h6>Consumo diario</h6>
        <div class="table-responsive" style="max-height: 320px; overflow-y:auto">
            <table class="table table-sm table-bordered">
                <thead class="table-light"><tr><th>Día</th><th>Reactivo</th><th class="text-end">Cantidad</th></tr></thead>
                <tbody>
                    <?php foreach ($porDia ?? [] as $row): ?>
                    <tr>
                        <td><?= esc($row['periodo']) ?></td>
                        <td><?= esc($row['reactivo']) ?></td>
                        <td class="text-end"><?= number_format((float) $row['cantidad_total'], 2) ?> <?= esc($row['unidad']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <h6>Consumo mensual</h6>
        <div class="table-responsive" style="max-height: 320px; overflow-y:auto">
            <table class="table table-sm table-bordered">
                <thead class="table-light"><tr><th>Mes</th><th>Reactivo</th><th class="text-end">Cantidad</th></tr></thead>
                <tbody>
                    <?php foreach ($porMes ?? [] as $row): ?>
                    <tr>
                        <td><?= esc($row['periodo']) ?></td>
                        <td><?= esc($row['reactivo']) ?></td>
                        <td class="text-end"><?= number_format((float) $row['cantidad_total'], 2) ?> <?= esc($row['unidad']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-secondary">
    <strong>Reactivos con consumo:</strong> <?= (int) ($totales['reactivos'] ?? 0) ?> |
    <strong>Combinaciones prueba-reactivo:</strong> <?= (int) ($totales['combinaciones'] ?? 0) ?> |
    <strong>Total unidades consumidas:</strong> <?= number_format((float) ($totales['total_consumido'] ?? 0), 2) ?>
    <span class="d-block small text-muted mt-1">El costo estimado no está disponible: el módulo de insumos actual no registra costos de compra de reactivos.</span>
</div>
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

    var porMes = <?= json_encode(array_map(static fn ($r) => [
        'periodo'  => (string) $r['periodo'],
        'reactivo' => (string) $r['reactivo'],
        'cantidad' => (float) $r['cantidad_total'],
    ], $porMes ?? []), JSON_UNESCAPED_UNICODE) ?>;
    var canvas = document.getElementById('chartConsumo');
    if (!canvas || !porMes.length || typeof Chart === 'undefined') { return; }

    var meses = [];
    var reactivos = {};
    porMes.forEach(function (r) {
        if (meses.indexOf(r.periodo) === -1) { meses.push(r.periodo); }
        reactivos[r.reactivo] = true;
    });
    meses.sort();
    var colores = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6f42c1', '#20c997', '#fd7e14', '#0dcaf0'];
    var datasets = Object.keys(reactivos).slice(0, 8).map(function (nombre, i) {
        return {
            label: nombre,
            backgroundColor: colores[i % colores.length],
            data: meses.map(function (mes) {
                var fila = porMes.filter(function (r) { return r.periodo === mes && r.reactivo === nombre; })[0];
                return fila ? fila.cantidad : 0;
            })
        };
    });
    new Chart(canvas, {
        type: 'bar',
        data: { labels: meses, datasets: datasets },
        options: { scales: { y: { beginAtZero: true } } }
    });
});
</script>
<?= $this->endSection() ?>
