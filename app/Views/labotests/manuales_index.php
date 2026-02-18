<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'labotests']) ?>

<?php
$searchVal = esc($search ?? '');
$rightNav = '<form action="' . site_url('labotests/manuales') . '" method="get" class="d-flex" role="search">' .
    '<input type="search" name="q" class="form-control form-control-sm" placeholder="Buscar examen o grupo..." value="' . $searchVal . '" style="min-width: 180px;">' .
    '<button type="submit" class="btn btn-outline-primary btn-sm ms-1"><i class="fa-solid fa-search"></i> Buscar</button>' .
    (!empty($search) ? '<a href="' . site_url('labotests/manuales') . '" class="btn btn-outline-secondary btn-sm ms-1">Limpiar</a>' : '') .
    '</form>' .
    '<a href="' . site_url('labotests') . '" class="btn btn-outline-secondary btn-sm">Volver a exámenes</a>';
?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
        ['label' => 'Manuales por proveedor', 'url' => null],
    ],
    'right' => $rightNav,
]) ?>

<div class="py-3">
    <h4 class="mb-4"><i class="fa-solid fa-book-open me-2"></i>Manuales por prueba y proveedor</h4>
    <p class="text-muted mb-4">Seleccione una prueba para ver los manuales de uso (resumen del insert) de cada proveedor de reactivos y el equipo recomendado (ej. Statfax).</p>

    <div class="row">
        <?php foreach ($categories ?? [] as $cat): ?>
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><?= esc($cat['name'] ?? '') ?></h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($cat['items'] ?? [] as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= esc($item['pria_name'] ?? '') ?></span>
                            <a href="<?= site_url('labotests/manuales/' . ($item['prianacategoria_id'] ?? 0)) ?>" class="btn btn-sm <?= ($item['manual_count'] ?? 0) > 0 ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                <i class="fa-solid fa-book me-1"></i>
                                <?= (int)($item['manual_count'] ?? 0) ?> manuales
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($cat['items'])): ?>
                        <li class="list-group-item text-muted">Sin exámenes</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($categories)): ?>
    <div class="alert alert-info">
        <i class="fa-solid fa-info-circle"></i>
        <?php if (!empty($search)): ?>
            No se encontraron exámenes para "<?= esc($search) ?>".
        <?php else: ?>
            No hay exámenes registrados.
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?= view('partial/footer') ?>
