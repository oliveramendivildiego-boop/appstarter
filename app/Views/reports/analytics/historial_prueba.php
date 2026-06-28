<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['start' => $startDate ?? '', 'end' => $endDate ?? '', 'prueba_id' => $pruebaId ?? 0];

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
    'subtitle'  => ($prueba['name'] ?? null)
        ? (($prueba['grupo'] ?? '') !== '' ? $prueba['grupo'] . ' — ' . $prueba['name'] . ' · ' : $prueba['name'] . ' · ') . ($subtitle ?? '')
        : ($subtitle ?? ''),
    'pdf_url'   => ($pruebaId ?? 0) > 0 ? site_url('reports/historialPruebaPdf?' . http_build_query($qs)) : null,
    'excel_url' => ($pruebaId ?? 0) > 0 ? site_url('reports/historialPruebaExcel?' . http_build_query($qs)) : null,
]) ?>

<form method="get" action="<?= site_url('reports/historialPrueba') ?>" class="row g-3 mb-3 d-print-none">
    <?php if (($pruebaId ?? 0) > 0): ?>
        <input type="hidden" name="prueba_id" value="<?= (int) $pruebaId ?>">
    <?php endif; ?>
    <div class="col-auto">
        <label for="busqueda_prueba" class="form-label">Prueba (nombre o grupo)</label>
        <input type="text" id="busqueda_prueba" name="q" class="form-control" placeholder="Ej. triglicéridos, glucosa, hemograma..."
               value="<?= esc(($prueba['name'] ?? null) ? (($prueba['grupo'] ?? '') !== '' ? $prueba['grupo'] . ' — ' . $prueba['name'] : $prueba['name']) : ($busqueda ?? '')) ?>"
               <?= ($pruebaId ?? 0) > 0 ? 'readonly' : '' ?>>
    </div>
    <?php if (($pruebaId ?? 0) > 0): ?>
    <div class="col-auto d-flex align-items-end">
        <a class="btn btn-outline-secondary" href="<?= site_url('reports/historialPrueba') ?>">Cambiar prueba</a>
    </div>
    <?php endif; ?>
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary"><?= ($pruebaId ?? 0) > 0 ? 'Filtrar' : 'Buscar' ?></button>
    </div>
</form>

<?php if (($pruebaId ?? 0) === 0 && ! empty($candidatos)): ?>
<div class="card mb-4 d-print-none">
    <div class="card-header">Seleccione una prueba</div>
    <div class="list-group list-group-flush">
        <?php foreach ($candidatos as $c): ?>
            <a class="list-group-item list-group-item-action"
               href="<?= site_url('reports/historialPrueba?' . http_build_query([
                   'prueba_id' => (int) $c['prianacategoria_id'],
                   'start'     => $startDate ?? '',
                   'end'       => $endDate ?? '',
               ])) ?>">
                <strong><?= esc($c['name']) ?></strong>
                <?php if (($c['grupo'] ?? '') !== ''): ?>
                    <span class="text-muted">· <?= esc($c['grupo']) ?></span>
                <?php endif; ?>
                <span class="text-muted small">· Cód. <?= (int) $c['prianacategoria_id'] ?> · Órdenes históricas: <?= (int) ($c['total_ordenes'] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif (($pruebaId ?? 0) === 0 && ($busqueda ?? '') !== ''): ?>
<div class="alert alert-warning d-print-none">No se encontraron pruebas para "<?= esc($busqueda) ?>".</div>
<?php elseif (($pruebaId ?? 0) === 0): ?>
<div class="alert alert-info d-print-none">Busque una prueba por nombre o grupo para ver todos los análisis realizados en el período. Ejemplos: triglicéridos, colesterol, hemoglobina, TSH, creatinina.</div>
<?php endif; ?>

<?php if (($pruebaId ?? 0) > 0): ?>

<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center border-primary"><div class="card-body py-2">
            <div class="fs-4 fw-bold"><?= (int) ($total_ordenes ?? 0) ?></div><div class="small text-muted">Órdenes con resultados</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center border-secondary"><div class="card-body py-2">
            <div class="fs-4 fw-bold"><?= (int) ($total_resultados ?? 0) ?></div><div class="small text-muted">Resultados listados</div>
        </div></div>
    </div>
</div>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-historial-prueba']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-historial-prueba">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Orden</th>
                <th>Paciente</th>
                <th>CI</th>
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
                <td>
                    <a href="<?= site_url('registers/view/' . (int) ($row['registro_id'] ?? 0)) ?>" target="_blank" class="text-decoration-none">
                        <?= esc($row['numero_orden'] ?: $row['registro_id']) ?>
                    </a>
                </td>
                <td><?= esc($row['paciente']) ?></td>
                <td><?= esc($row['paciente_ci'] ?: '—') ?></td>
                <td><?= esc($row['parametro']) ?></td>
                <td class="text-end fw-bold"><?= esc($row['valor']) ?></td>
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
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-historial-prueba']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay resultados registrados para esta prueba en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Prueba:</strong> <?= esc($prueba['name'] ?? '') ?>
    <?php if (($prueba['grupo'] ?? '') !== ''): ?> · <strong>Grupo:</strong> <?= esc($prueba['grupo']) ?><?php endif; ?>
    · <strong>Órdenes:</strong> <?= (int) ($total_ordenes ?? 0) ?>
    · <strong>Resultados:</strong> <?= (int) ($total_resultados ?? 0) ?>
    <span class="d-block small text-muted mt-1">Los estados se calculan con los valores de referencia del catálogo. Haga clic en el número de orden para abrir el registro.</span>
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
});
</script>
<?= $this->endSection() ?>
