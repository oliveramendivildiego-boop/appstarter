<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'registers']) ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-eliminar-registro').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            if (!id) return;
            if (!confirm('¿Eliminar el registro #' + id + '? Esta acción no se puede deshacer.')) return;
            var csrf = (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
                ? '&' + CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
            fetch('<?= site_url('registers/delete') ?>/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: csrf ? csrf.substring(1) : ''
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) location.reload();
                else alert(res.message || 'Error al eliminar');
            })
            .catch(function() { alert('Error de conexión'); });
        });
    });
});
</script>

<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Registro', 'url' => null],
    ],
    'right' => '<a href="' . site_url('registers') . '" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Registrar</a> ' .
        '<a href="' . site_url('expediente') . '" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-folder-open me-1"></i> Historial paciente</a>',
]) ?>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="get" action="<?= site_url('registers/lista') ?>" class="d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Buscar por prueba, nombre, apellidos o CI del paciente..." value="<?= esc($search ?? '') ?>">
            <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-search"></i> Buscar</button>
            <?php if (!empty($search)): ?>
            <a href="<?= site_url('registers/lista') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php
        $totalReg = (int)($total ?? 0);
        $pageNum = (int)($page ?? 1);
        $perPage = (int)($perPage ?? 20);
        $desde = $totalReg > 0 ? (($pageNum - 1) * $perPage) + 1 : 0;
        $hasta = min($pageNum * $perPage, $totalReg);
        ?>
        <p class="text-muted small">Mostrando <?= $desde ?>–<?= $hasta ?> de <?= $totalReg ?> registro(s)</p>
        <?= $manage_table ?? '' ?>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php
        $pageNum = (int)($page ?? 1);
        $searchParam = !empty($search) ? 'q=' . urlencode($search) . '&' : '';
        for ($i = 1; $i <= ($totalPages ?? 1); $i++):
        ?>
        <li class="page-item <?= ($i === $pageNum) ? 'active' : '' ?>">
            <a class="page-link" href="<?= site_url('registers/lista') . '?' . $searchParam . 'page=' . $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?= view('partial/footer') ?>
