<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = ['ventana' => $ventana ?? 30];

$estadoInfo = static function (string $estado): array {
    return match ($estado) {
        'critico'     => ['Crítico', 'bg-danger'],
        'advertencia' => ['Advertencia', 'bg-warning text-dark'],
        'normal'      => ['Normal', 'bg-success'],
        'sin_stock'   => ['Sin stock', 'bg-dark'],
        default       => ['Sin consumo', 'bg-secondary'],
    };
};
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/proyeccionInsumosPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/proyeccionInsumosExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/proyeccionInsumos') ?>" class="row g-3 mb-4 d-print-none">
    <div class="col-auto">
        <label for="ventana" class="form-label">Ventana de consumo (días)</label>
        <input type="number" id="ventana" name="ventana" min="7" max="365" class="form-control" style="max-width:140px" value="<?= (int) ($ventana ?? 30) ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Recalcular</button>
    </div>
</form>

<div class="row mb-3">
    <div class="col-6 col-md-2 mb-2"><div class="card text-center border-danger"><div class="card-body py-2">
        <div class="fs-4 fw-bold text-danger"><?= (int) ($porEstado['critico'] ?? 0) ?></div><div class="small text-muted">Críticos</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center border-warning"><div class="card-body py-2">
        <div class="fs-4 fw-bold"><?= (int) ($porEstado['advertencia'] ?? 0) ?></div><div class="small text-muted">Advertencia</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center border-success"><div class="card-body py-2">
        <div class="fs-4 fw-bold text-success"><?= (int) ($porEstado['normal'] ?? 0) ?></div><div class="small text-muted">Normales</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center"><div class="card-body py-2">
        <div class="fs-4 fw-bold"><?= (int) ($porEstado['sin_stock'] ?? 0) ?></div><div class="small text-muted">Sin stock</div>
    </div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card text-center"><div class="card-body py-2">
        <div class="fs-4 fw-bold"><?= (int) ($porEstado['sin_consumo'] ?? 0) ?></div><div class="small text-muted">Sin consumo</div>
    </div></div></div>
</div>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-proyeccion']) ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-proyeccion">
        <thead class="table-dark">
            <tr>
                <th>Reactivo</th>
                <th>Unidad</th>
                <th class="text-end">Stock actual</th>
                <th class="text-end">Stock mínimo</th>
                <th class="text-end">Consumo promedio/día</th>
                <th class="text-end">Días restantes</th>
                <th>Fecha estimada de agotamiento</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows ?? [] as $row):
                [$estadoLabel, $estadoClass] = $estadoInfo((string) $row['estado']);
            ?>
            <tr>
                <td><?= esc($row['nombre']) ?></td>
                <td><?= esc($row['unidad']) ?></td>
                <td class="text-end"><?= number_format((float) $row['stock_actual'], 0) ?></td>
                <td class="text-end"><?= number_format((float) $row['stock_minimo'], 0) ?></td>
                <td class="text-end"><?= number_format((float) $row['consumo_promedio_dia'], 2) ?></td>
                <td class="text-end"><?= $row['dias_restantes'] !== null ? (int) $row['dias_restantes'] : '—' ?></td>
                <td><?= esc($row['fecha_agotamiento'] ?? '—') ?></td>
                <td><span class="badge <?= esc($estadoClass) ?>"><?= esc($estadoLabel) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-proyeccion']) ?>

<?php if (empty($rows)): ?>
<p class="text-muted">No hay reactivos registrados en el módulo de insumos.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Reactivos evaluados:</strong> <?= (int) ($total ?? 0) ?>
    <span class="d-block small text-muted mt-1">
        Stock actual = suma de lotes activos. Consumo promedio = salidas de los últimos <?= (int) ($ventana ?? 30) ?> días ÷ <?= (int) ($ventana ?? 30) ?>.
        Crítico: ≤ 7 días restantes o stock bajo el mínimo. Advertencia: ≤ 30 días.
    </span>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/analytics_table.js') ?>"></script>
<?= $this->endSection() ?>
