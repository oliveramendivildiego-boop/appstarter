<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-print-none">
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>
</div>

<?= view('reports/partials/report_actions', [
    'pdf_url' => site_url('reports/insumosVencimientoPdf'),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?> — Configure en <a href="<?= site_url('config') ?>">Configuración</a> el valor "Días de alerta para vencimiento".</p>

<div class="alert alert-info mb-4 d-print-none">
    <strong>Leyenda:</strong>
    <span class="badge bg-danger me-2">Vencido</span> Lote ya vencido
    <span class="badge bg-warning text-dark me-2">Por vencer</span> Vence en los próximos <?= (int)($dias_alerta ?? 40) ?> días
    <span class="badge bg-secondary me-2">Sin fecha</span> Sin fecha de vencimiento
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Insumo</th>
                <th>Lote</th>
                <th>Fecha ingreso</th>
                <th>Fecha vencimiento</th>
                <th class="text-end">Cantidad</th>
                <th>Unidad</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row): ?>
            <?php
            $estado = $row['estado'] ?? 'ok';
            $rowClass = $estado === 'vencido' ? 'table-danger' : ($estado === 'por_vencer' ? 'table-warning' : '');
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?= esc($row['reactivo_nombre'] ?? '-') ?></td>
                <td><?= esc($row['codigo_lote'] ?? '-') ?></td>
                <td><?= !empty($row['fecha_ingreso']) ? date('d/m/Y', strtotime($row['fecha_ingreso'])) : '-' ?></td>
                <td><?= !empty($row['fecha_vencimiento']) ? date('d/m/Y', strtotime($row['fecha_vencimiento'])) : '-' ?></td>
                <td class="text-end"><?= (int)($row['cantidad'] ?? 0) ?></td>
                <td><?= esc($row['unidad_base'] ?? $row['unidad'] ?? '-') ?></td>
                <td>
                    <?php if ($estado === 'vencido'): ?>
                        <span class="badge bg-danger">Vencido</span>
                    <?php elseif ($estado === 'por_vencer'): ?>
                        <span class="badge bg-warning text-dark">Por vencer (<?= (int)($row['dias_restantes'] ?? 0) ?> días)</span>
                    <?php elseif ($estado === 'sin_fecha'): ?>
                        <span class="badge bg-secondary">Sin fecha</span>
                    <?php else: ?>
                        <span class="badge bg-success"><?= (int)($row['dias_restantes'] ?? 0) ?> días</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay lotes registrados.</p>
<?php else: ?>
<p class="text-muted small">Total: <?= count($data) ?> lote(s).</p>
<?php endif; ?>

<p class="mt-3 d-print-none"><a href="<?= site_url('reports') ?>" class="btn btn-secondary">Volver</a></p>
<?= $this->endSection() ?>
