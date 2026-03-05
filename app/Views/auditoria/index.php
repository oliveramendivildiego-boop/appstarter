<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [['label' => 'Auditoría', 'url' => site_url('auditoria')]]]) ?>

<?php
$totalReg = (int) ($total ?? 0);
$pageNum  = (int) ($page ?? 1);
$perPage  = (int) ($perPage ?? 25);
$desde    = $totalReg > 0 ? (($pageNum - 1) * $perPage) + 1 : 0;
$hasta    = min($pageNum * $perPage, $totalReg);
?>
<p class="text-muted small">Mostrando <?= $desde ?>–<?= $hasta ?> de <?= $totalReg ?> registro(s)</p>

<div class="card">
    <div class="card-header"><strong><i class="fa-solid fa-clipboard-list me-2"></i>Registro de acciones</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Registro</th><th>IP</th></tr></thead>
                <tbody>
                    <?php foreach ($registros ?? [] as $r): ?>
                    <tr>
                        <td><?= esc($r['fecha'] ?? '') ?></td>
                        <td><?= esc(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name_fa'] ?? '')) ?: '-') ?></td>
                        <td><?= esc($r['modulo'] ?? '') ?></td>
                        <td><?= esc($r['accion'] ?? '') ?></td>
                        <td><?= esc($r['registro_id'] ?? '-') ?></td>
                        <td><?= esc($r['ip'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($registros)): ?>
        <p class="text-muted p-3 mb-0">No hay registros de auditoría.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= ($totalPages ?? 1); $i++): ?>
        <li class="page-item <?= ($i === $pageNum) ? 'active' : '' ?>">
            <a class="page-link" href="<?= site_url('auditoria') . '?page=' . $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?= $this->endSection() ?>
