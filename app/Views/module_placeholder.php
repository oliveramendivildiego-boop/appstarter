<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null]) ?>
<div class="container py-4">
    <div class="alert alert-info">
        <h4><i class="fa-solid fa-wrench"></i> Módulo en desarrollo</h4>
        <p class="mb-0">El módulo <strong><?= esc(lang('Module.module_' . ($module_id ?? ''))) ?></strong> estará disponible próximamente.</p>
    </div>
    <a href="<?= site_url('home') ?>" class="btn btn-primary">Volver al inicio</a>
</div>
<?= view('partial/footer') ?>
