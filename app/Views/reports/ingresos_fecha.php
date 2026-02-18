<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'reports']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>

<form method="get" action="<?= site_url('reports/ingresosFecha') ?>" class="row g-3 mb-4">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="date" id="report_start" name="start" class="form-control" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="date" id="report_end" name="end" class="form-control" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Total facturado</th>
                <th class="text-end">Total cobrado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row): ?>
            <tr>
                <td><?= esc(date('d/m/Y', strtotime($row['fecha'] ?? ''))) ?></td>
                <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float)($row['total'] ?? 0), 2) ?> Bs</td>
                <td class="text-end"><?= number_format((float)($row['cobrado'] ?? 0), 2) ?> Bs</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay registros en el período seleccionado.</p>
<?php else: ?>
<div class="alert alert-secondary">
    <strong>Total registros:</strong> <?= (int)($totales->total_registros ?? 0) ?> |
    <strong>Total facturado:</strong> <?= number_format((float)($totales->total_facturado ?? 0), 2) ?> Bs |
    <strong>Total cobrado:</strong> <?= number_format((float)($totales->total_cobrado ?? 0), 2) ?> Bs
</div>
<?php endif; ?>

<?= view('partial/footer') ?>
