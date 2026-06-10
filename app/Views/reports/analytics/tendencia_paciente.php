<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? '', 'paciente_id' => $personId ?? 0, 'prueba_id' => $pruebaId ?? 0];

$estadoBadge = static function (string $estado): string {
    return match ($estado) {
        'critico_bajo', 'critico_alto' => 'bg-danger',
        'bajo', 'alto'                 => 'bg-warning text-dark',
        'normal'                       => 'bg-success',
        default                        => 'bg-secondary',
    };
};
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => ($paciente['paciente'] ?? null) ? ($paciente['paciente'] . ' · ' . ($subtitle ?? '')) : ($subtitle ?? ''),
    'pdf_url'   => ($personId ?? 0) > 0 ? site_url('reports/tendenciaPacientePdf?' . http_build_query($qs)) : null,
    'excel_url' => ($personId ?? 0) > 0 ? site_url('reports/tendenciaPacienteExcel?' . http_build_query($qs)) : null,
]) ?>

<form method="get" action="<?= site_url('reports/tendenciaPaciente') ?>" class="row g-3 mb-3 d-print-none">
    <?php if (($personId ?? 0) > 0): ?>
        <input type="hidden" name="paciente_id" value="<?= (int) $personId ?>">
    <?php endif; ?>
    <div class="col-auto">
        <label for="busqueda_pac" class="form-label">Paciente (nombre o CI)</label>
        <input type="text" id="busqueda_pac" name="q" class="form-control" placeholder="Buscar paciente..."
               value="<?= esc($paciente['paciente'] ?? ($busqueda ?? '')) ?>" <?= ($personId ?? 0) > 0 ? 'readonly' : '' ?>>
    </div>
    <?php if (($personId ?? 0) > 0): ?>
    <div class="col-auto d-flex align-items-end">
        <a class="btn btn-outline-secondary" href="<?= site_url('reports/tendenciaPaciente') ?>">Cambiar paciente</a>
    </div>
    <?php endif; ?>
    <div class="col-auto">
        <label for="prueba_id" class="form-label">Tipo de prueba</label>
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
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary"><?= ($personId ?? 0) > 0 ? 'Filtrar' : 'Buscar' ?></button>
    </div>
</form>

<?php if (($personId ?? 0) === 0 && ! empty($candidatos)): ?>
<div class="card mb-4 d-print-none">
    <div class="card-header">Seleccione un paciente</div>
    <div class="list-group list-group-flush">
        <?php foreach ($candidatos as $c): ?>
            <a class="list-group-item list-group-item-action"
               href="<?= site_url('reports/tendenciaPaciente?' . http_build_query(['paciente_id' => (int) $c['person_id'], 'start' => $startDate ?? '', 'end' => $endDate ?? ''])) ?>">
                <strong><?= esc($c['paciente']) ?></strong>
                <span class="text-muted small">CI: <?= esc($c['ci'] ?: '—') ?> · Nac.: <?= esc($c['birthday'] ?: '—') ?> · Órdenes: <?= (int) $c['total_ordenes'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif (($personId ?? 0) === 0 && ($busqueda ?? '') !== ''): ?>
<div class="alert alert-warning d-print-none">No se encontraron pacientes para "<?= esc($busqueda) ?>".</div>
<?php elseif (($personId ?? 0) === 0): ?>
<div class="alert alert-info d-print-none">Busque y seleccione un paciente para ver su historial de resultados. Útil para seguimiento de glucosa, HbA1c, perfil lipídico, PSA, TSH, hemograma, etc.</div>
<?php endif; ?>

<?php if (($personId ?? 0) > 0): ?>

<?php
// Series numéricas por parámetro para el gráfico histórico
$series = [];
foreach ($rows ?? [] as $row) {
    $valorNum = str_replace(',', '.', trim((string) $row['valor']));
    if (! preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $valorNum)) {
        continue;
    }
    $clave = trim($row['prueba'] . ($row['parametro'] !== $row['prueba'] ? ' — ' . $row['parametro'] : ''));
    $series[$clave][] = ['x' => substr((string) $row['ingreso'], 0, 10), 'y' => (float) $valorNum];
}
$series = array_slice($series, 0, 12, true);
?>

<?php if ($series !== []): ?>
<div class="card mb-4 d-print-none">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <strong>Gráfico histórico</strong>
        <select id="serieSelect" class="form-select form-select-sm w-auto">
            <?php $i = 0; foreach (array_keys($series) as $nombre): ?>
                <option value="<?= $i ?>"><?= esc($nombre) ?></option>
            <?php $i++; endforeach; ?>
        </select>
    </div>
    <div class="card-body"><canvas id="chartTendencia" height="110"></canvas></div>
</div>
<?php endif; ?>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-tendencia']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-tendencia">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Orden</th>
                <th>Prueba</th>
                <th>Parámetro</th>
                <th class="text-end">Resultado</th>
                <th>Unidad</th>
                <th class="text-end">Ref. mín</th>
                <th class="text-end">Ref. máx</th>
                <th>Estado</th>
                <th>Médico solicitante</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row): ?>
            <tr>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
                <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
                <td><?= esc($row['prueba']) ?></td>
                <td><?= esc($row['parametro']) ?></td>
                <td class="text-end"><?= esc($row['valor']) ?></td>
                <td><?= esc($row['unidad']) ?></td>
                <td class="text-end"><?= esc($row['valor_min']) ?></td>
                <td class="text-end"><?= esc($row['valor_max']) ?></td>
                <td><span class="badge <?= $estadoBadge((string) $row['estado']) ?>"><?= esc(\App\Controllers\ReportsAnalytics::estadoValorLabel((string) $row['estado'])) ?></span></td>
                <td><?= esc($row['doctor'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-tendencia']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">El paciente no tiene resultados registrados en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Resultados listados:</strong> <?= count($rows) ?> |
    <strong>Paciente:</strong> <?= esc($paciente['paciente'] ?? '') ?> (CI: <?= esc($paciente['ci'] ?? '—') ?>)
    <span class="d-block small text-muted mt-1">La comparación cronológica usa la fecha de ingreso de cada orden. Los estados se calculan con los valores de referencia configurados en el catálogo.</span>
</div>
<?php endif; ?>

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

    var series = <?= json_encode(array_map(static fn ($pts) => $pts, $series ?? []), JSON_UNESCAPED_UNICODE) ?>;
    var nombres = Object.keys(series);
    var canvas = document.getElementById('chartTendencia');
    if (!canvas || !nombres.length || typeof Chart === 'undefined') { return; }

    var chart = new Chart(canvas, {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: { scales: { y: { beginAtZero: false } }, plugins: { legend: { display: true } } }
    });

    function pinta(idx) {
        var nombre = nombres[idx];
        var pts = series[nombre] || [];
        chart.data.labels = pts.map(function (p) { return p.x; });
        chart.data.datasets = [{
            label: nombre,
            data: pts.map(function (p) { return p.y; }),
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,.15)',
            tension: .2,
            fill: true
        }];
        chart.update();
    }
    pinta(0);
    var sel = document.getElementById('serieSelect');
    if (sel) { sel.addEventListener('change', function () { pinta(parseInt(sel.value, 10) || 0); }); }
});
</script>
<?= $this->endSection() ?>
