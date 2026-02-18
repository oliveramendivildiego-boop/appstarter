<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'auditoria']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [['label' => 'Auditoría', 'url' => site_url('auditoria')]]]) ?>

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

<?= view('partial/footer') ?>
