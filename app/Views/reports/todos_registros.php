<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>

<?php
$desde = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
$hasta = min($page * $perPage, $total);
?>
<p class="small text-muted">Mostrando <?= $desde ?>–<?= $hasta ?> de <?= (int) $total ?> registro(s)</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th class="text-end">Total</th>
                <th class="text-end">Cobrado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row): ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float)($row['total'] ?? 0), 2) ?> Bs</td>
                <td class="text-end"><?= number_format((float)($row['monto_pagar'] ?? 0), 2) ?> Bs</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay registros guardados.</p>
<?php else: ?>
<div class="d-flex flex-wrap align-items-center gap-3 mt-3">
    <div class="alert alert-secondary mb-0 py-2">
        <strong>Total registros:</strong> <?= (int)($totales->total_registros ?? 0) ?> |
        <strong>Total facturado:</strong> <?= number_format((float)($totales->total_facturado ?? 0), 2) ?> Bs |
        <strong>Total cobrado:</strong> <?= number_format((float)($totales->total_cobrado ?? 0), 2) ?> Bs
    </div>
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Paginación">
        <ul class="pagination pagination-sm mb-0">
            <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= site_url('reports/todosRegistros?page=' . ($page - 1)) ?>">Anterior</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= site_url('reports/todosRegistros?page=' . $i) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="<?= site_url('reports/todosRegistros?page=' . ($page + 1)) ?>">Siguiente</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
