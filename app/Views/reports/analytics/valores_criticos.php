<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? '', 'grupo_id' => $grupoId ?? 0, 'prueba_id' => $pruebaId ?? 0];

$estadoBadge = static function (string $estado): string {
    return match ($estado) {
        'critico_bajo', 'critico_alto' => 'bg-danger',
        'bajo', 'alto'                 => 'bg-warning text-dark',
        default                        => 'bg-secondary',
    };
};
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/valoresCriticosPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/valoresCriticosExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/valoresCriticos') ?>" class="row g-3 mb-4 d-print-none">
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
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center border-warning"><div class="card-body py-2">
            <div class="fs-4 fw-bold"><?= (int) ($porEstado['bajo'] ?? 0) ?></div><div class="small text-muted">Bajos</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center border-warning"><div class="card-body py-2">
            <div class="fs-4 fw-bold"><?= (int) ($porEstado['alto'] ?? 0) ?></div><div class="small text-muted">Altos</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center border-danger"><div class="card-body py-2">
            <div class="fs-4 fw-bold text-danger"><?= (int) ($porEstado['critico_bajo'] ?? 0) ?></div><div class="small text-muted">Críticos bajos</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center border-danger"><div class="card-body py-2">
            <div class="fs-4 fw-bold text-danger"><?= (int) ($porEstado['critico_alto'] ?? 0) ?></div><div class="small text-muted">Críticos altos</div>
        </div></div>
    </div>
</div>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-criticos']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-criticos">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Orden</th>
                <th>Paciente</th>
                <th>Grupo</th>
                <th>Prueba</th>
                <th>Parámetro</th>
                <th class="text-end">Resultado</th>
                <th>Unidad</th>
                <th class="text-end">Ref. mín</th>
                <th class="text-end">Ref. máx</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row): ?>
            <tr>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
                <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
                <td><?= esc($row['paciente']) ?></td>
                <td><?= esc($row['grupo']) ?></td>
                <td><?= esc($row['prueba']) ?></td>
                <td><?= esc($row['parametro']) ?></td>
                <td class="text-end fw-bold"><?= esc($row['valor']) ?></td>
                <td><?= esc($row['unidad']) ?></td>
                <td class="text-end"><?= esc($row['valor_min']) ?></td>
                <td class="text-end"><?= esc($row['valor_max']) ?></td>
                <td><span class="badge <?= $estadoBadge((string) $row['estado']) ?>"><?= esc(\App\Controllers\ReportsAnalytics::estadoValorLabel((string) $row['estado'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-criticos']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay valores fuera de rango en el período seleccionado.</p>
<?php else: ?>
<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header"><strong>Cantidad por prueba</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (array_slice($porPrueba ?? [], 0, 10, true) as $prueba => $cant): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= esc($prueba) ?></span>
                        <span><strong><?= (int) $cant ?></strong> <span class="text-muted small">(<?= ($total ?? 0) > 0 ? number_format($cant * 100 / $total, 1) : 0 ?>%)</span></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header"><strong>Cantidad por grupo</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (array_slice($porGrupo ?? [], 0, 10, true) as $grupo => $cant): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= esc($grupo !== '' ? $grupo : 'Sin grupo') ?></span>
                        <span><strong><?= (int) $cant ?></strong> <span class="text-muted small">(<?= ($total ?? 0) > 0 ? number_format($cant * 100 / $total, 1) : 0 ?>%)</span></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<div class="alert alert-secondary">
    <strong>Total de valores fuera de rango:</strong> <?= (int) ($total ?? 0) ?>
    <span class="d-block small text-muted mt-1">Solo se evalúan resultados numéricos con referencia numérica configurada. "Crítico" usa los límites críticos del catálogo cuando existen.</span>
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
