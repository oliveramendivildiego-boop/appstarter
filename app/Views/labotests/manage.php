<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'labotests']) ?>

<?php
$labRight = '<form action="' . site_url('labotests') . '" method="get" class="d-flex" role="search">' .
    '<input type="hidden" id="labotests_page" name="page" value="1">' .
    '<input type="search" id="labotests_search" name="q" class="form-control form-control-sm" placeholder="Buscar examen o grupo..." value="' . esc($search ?? '') . '" style="min-width: 180px;">' .
    '<button type="submit" class="btn btn-outline-primary btn-sm ms-1"><i class="fa-solid fa-search"></i> Buscar</button>' .
    (!empty($search) ? '<a href="' . site_url('labotests') . '" class="btn btn-outline-secondary btn-sm ms-1">Limpiar</a>' : '') .
    '</form>' .
    '<a href="' . site_url('labotests/perfiles') . '" class="btn btn-outline-info btn-sm me-1">Perfiles</a>' .
    '<a href="' . site_url('labotests/manuales') . '" class="btn btn-outline-success btn-sm me-1"><i class="fa-solid fa-book me-1"></i> Manuales</a>' .
    '<a href="' . site_url('labotests/view') . '" class="btn btn-success btn-sm"><i class="fa-solid fa-plus me-1"></i> ' . lang('Labotests.labotests_new_group') . '</a>';
?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
        ['label' => lang('Module.module_labotests_desc'), 'url' => null],
    ],
    'right' => $labRight,
]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="py-3">
    <h4 class="mb-4"><?= lang('Module.module_labotests') ?></h4>

    <div class="row">
        <?php foreach ($categories ?? [] as $cat): ?>
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <a href="<?= site_url('labotests/view/' . $cat['id']) ?>" class="text-white text-decoration-none">
                            <i class="fa-solid fa-flask-vial me-2"></i><?= esc($cat['name']) ?>
                        </a>
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <a href="<?= site_url('labotests/subview/' . $cat['id']) ?>" class="btn btn-light" title="<?= lang('Labotests.labotests_new_analysis') ?>">
                            <i class="fa-solid fa-plus"></i>
                        </a>
                        <a href="<?= site_url('labotests/deletecategory/' . $cat['id']) ?>" class="btn btn-outline-light" title="Eliminar categoría y todos sus análisis" onclick="return confirm('¿Eliminar la categoría y todos sus análisis con configuraciones? Esta acción no se puede deshacer.');">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($cat['items'] ?? [] as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= esc($item['name']) ?></span>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= site_url('labotests/detail/' . $item['id']) ?>" class="btn btn-outline-primary btn-sm" title="<?= lang('Labotests.labotests_config') ?>">
                                    <i class="fa-solid fa-gear"></i>
                                </a>
                                <a href="<?= site_url('labotests/manuales/' . $item['id']) ?>" class="btn btn-outline-info btn-sm" title="Ver manuales y notas">
                                    <i class="fa-solid fa-book"></i>
                                </a>
                                <a href="<?= site_url('labotests/deleteprianacategoria/' . $item['id']) ?>" class="btn btn-outline-danger btn-sm" title="Eliminar análisis y configuraciones" onclick="return confirm('¿Eliminar este análisis y todas sus configuraciones? Esta acción no se puede deshacer.');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($cat['items'])): ?>
                        <li class="list-group-item text-muted"><?= lang('Labotests.labotests_no_items') ?></li>
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
            No se encontraron grupos o exámenes para "<?= esc($search) ?>".
        <?php else: ?>
            <?= lang('Labotests.labotests_no_categories') ?>
            <a href="<?= site_url('labotests/view') ?>" class="alert-link"><?= lang('Labotests.labotests_new_group') ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (($total_pages ?? 1) > 1): ?>
    <?php
    $p = $page ?? 1;
    $tp = $total_pages ?? 1;
    $prevPage = max(1, $p - 1);
    $nextPage = min($tp, $p + 1);
    $params = ['page' => 1];
    if (!empty($search)) {
        $params['q'] = $search;
    }
    ?>
    <nav class="mt-4" aria-label="Paginación">
        <ul class="pagination justify-content-center flex-wrap">
            <li class="page-item <?= $p <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $p <= 1 ? '#' : site_url('labotests?' . http_build_query(array_merge($params, ['page' => $prevPage]))) ?>">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $tp; $i++): ?>
            <li class="page-item <?= ($i === $p) ? 'active' : '' ?>">
                <a class="page-link" href="<?= site_url('labotests?' . http_build_query(array_merge($params, ['page' => $i]))) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $p >= $tp ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $p >= $tp ? '#' : site_url('labotests?' . http_build_query(array_merge($params, ['page' => $nextPage]))) ?>">&raquo;</a>
            </li>
        </ul>
        <p class="text-center text-muted small">Página <?= $p ?> de <?= $tp ?> (<?= $total ?? 0 ?> grupos)</p>
    </nav>
    <?php endif; ?>
</div>
<?= view('partial/footer') ?>
