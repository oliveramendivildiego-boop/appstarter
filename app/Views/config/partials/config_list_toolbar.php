<?php
declare(strict_types=1);

/** @var string $search_placeholder */
/** @var string|null $toolbar_extra_html HTML adicional (ordenar, botones…) */
?>
<div class="config-list-toolbar">
    <div class="input-group input-group-sm config-list-search-wrap">
        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="search" class="form-control config-list-search" placeholder="<?= esc($search_placeholder ?? 'Buscar en la lista…', 'attr') ?>" autocomplete="off">
    </div>
    <?php if (! empty($toolbar_extra_html)): ?>
    <div class="config-list-toolbar-extra d-flex flex-wrap align-items-center gap-2">
        <?= $toolbar_extra_html ?>
    </div>
    <?php endif; ?>
    <span class="config-list-meta ms-auto"></span>
</div>
