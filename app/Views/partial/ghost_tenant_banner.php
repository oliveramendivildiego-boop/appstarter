<?php
if (empty(session()->get('suppress_tenant_audit'))) {
    return;
}
$ghostKey = (string) (session()->get('ghost_target_tenant_key') ?? '');
?>
<div class="alert alert-warning border-0 rounded-0 mb-0 py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm" role="status" style="border-bottom: 3px solid #856404 !important;">
    <div class="small">
        <strong><i class="fa-solid fa-user-secret me-1"></i>Modo superusuario (sin auditoría en este tenant).</strong>
        <?php if ($ghostKey !== ''): ?>
            <span class="text-muted">Tenant: <code><?= esc($ghostKey) ?></code></span>
        <?php endif; ?>
        <span class="text-muted d-block d-md-inline ms-md-2">Las acciones no se registran en la tabla de auditoría del cliente.</span>
    </div>
    <a href="<?= site_url('config/ghostExitTenant') ?>" class="btn btn-sm btn-dark shrink-0">Salir de este modo</a>
</div>
