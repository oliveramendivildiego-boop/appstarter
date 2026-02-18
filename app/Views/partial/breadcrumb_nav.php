<?php
/**
 * Breadcrumb navbar con estilo Bootstrap (navbar, bg-body-tertiary, rounded).
 * Uso: <?= view('partial/breadcrumb_nav', ['items' => [...], 'right' => '...']) ?>
 * items: [['label'=>'X','url'=>'/x'], ['label'=>'Y','url'=>null]]  // url null = activo
 * right: HTML opcional para la derecha (form, botones)
 */
$items = $items ?? [];
$right = $right ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark breadcrumb-nav-theme rounded mt-3 mb-3" aria-label="breadcrumb">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#breadcrumbNav" aria-controls="breadcrumbNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="breadcrumbNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($items as $i => $item): ?>
                <li class="nav-item">
                    <?php if (!empty($item['url'])): ?>
                        <a class="nav-link" href="<?= esc($item['url']) ?>"><?= esc($item['label']) ?></a>
                    <?php else: ?>
                        <span class="nav-link active" aria-current="page"><?= esc($item['label']) ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (!empty($right)): ?>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?= $right ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
