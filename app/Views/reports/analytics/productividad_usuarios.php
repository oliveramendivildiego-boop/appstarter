<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? '', 'person_id' => $personId ?? 0];
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/productividadUsuariosPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/productividadUsuariosExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/productividadUsuarios') ?>" class="row g-3 mb-4 d-print-none">
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

<?php if (! empty($rows)): ?>
<div class="card mb-4 d-print-none">
    <div class="card-header"><strong>Ranking de usuarios más productivos</strong></div>
    <div class="card-body"><canvas id="chartProductividad" height="110"></canvas></div>
</div>
<?php endif; ?>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-productividad']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-productividad">
        <thead class="table-dark">
            <tr>
                <th>Ranking</th>
                <th>Usuario</th>
                <th class="text-end">Recepciones</th>
                <th class="text-end">Resultados cargados</th>
                <th class="text-end">Modificaciones</th>
                <th class="text-end">Validaciones</th>
                <th class="text-end">Impresiones</th>
                <th class="text-end">Envíos WhatsApp</th>
                <th class="text-end">Total actividad</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row): ?>
            <tr>
                <td><?= (int) $row['ranking'] ?></td>
                <td><?= esc($row['usuario']) ?></td>
                <td class="text-end"><?= (int) $row['recepciones'] ?></td>
                <td class="text-end"><?= (int) $row['resultados_cargados'] ?></td>
                <td class="text-end"><?= (int) $row['modificaciones'] ?></td>
                <td class="text-end"><?= (int) $row['validaciones'] ?></td>
                <td class="text-end"><?= (int) $row['impresiones'] ?></td>
                <td class="text-end"><?= (int) $row['envios_whatsapp'] ?></td>
                <td class="text-end fw-bold"><?= (int) $row['total_actividad'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-productividad']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">Sin actividad registrada en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Usuarios:</strong> <?= (int) ($totales['usuarios'] ?? 0) ?> |
    <strong>Recepciones:</strong> <?= (int) ($totales['recepciones'] ?? 0) ?> |
    <strong>Resultados cargados:</strong> <?= (int) ($totales['resultados_cargados'] ?? 0) ?> |
    <strong>Modificaciones:</strong> <?= (int) ($totales['modificaciones'] ?? 0) ?> |
    <strong>Validaciones:</strong> <?= (int) ($totales['validaciones'] ?? 0) ?> |
    <strong>Envíos WhatsApp:</strong> <?= (int) ($totales['envios_whatsapp'] ?? 0) ?>
    <span class="d-block small text-muted mt-1">Recepciones = órdenes registradas (sin anuladas). El resto de métricas proviene de la bitácora de auditoría. Las impresiones directas del navegador no quedan registradas por el sistema actual.</span>
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
        'usuario'        => (string) $r['usuario'],
        'recepciones'    => (int) $r['recepciones'],
        'cargados'       => (int) $r['resultados_cargados'],
        'modificaciones' => (int) $r['modificaciones'],
        'validaciones'   => (int) $r['validaciones'],
    ], array_slice($rows ?? [], 0, 15)), JSON_UNESCAPED_UNICODE) ?>;
    var canvas = document.getElementById('chartProductividad');
    if (!canvas || !datos.length || typeof Chart === 'undefined') { return; }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: datos.map(function (d) { return d.usuario; }),
            datasets: [
                { label: 'Recepciones', data: datos.map(function (d) { return d.recepciones; }), backgroundColor: '#0d6efd' },
                { label: 'Resultados cargados', data: datos.map(function (d) { return d.cargados; }), backgroundColor: '#198754' },
                { label: 'Modificaciones', data: datos.map(function (d) { return d.modificaciones; }), backgroundColor: '#ffc107' },
                { label: 'Validaciones', data: datos.map(function (d) { return d.validaciones; }), backgroundColor: '#6f42c1' }
            ]
        },
        options: {
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>
<?= $this->endSection() ?>
