<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'registers']) ?>

<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers')],
        ['label' => 'Lista', 'url' => null],
    ],
    'right' => '<a href="' . site_url('expediente') . '" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-folder-open me-1"></i> Historial por paciente</a>',
]) ?>

<div class="row">
    <?= $manage_table ?? '' ?>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= ($totalPages ?? 1); $i++): ?>
        <li class="page-item <?= ($i === ($page ?? 1)) ? 'active' : '' ?>">
            <a class="page-link" href="<?= site_url('registers/lista?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?= view('partial/footer') ?>
