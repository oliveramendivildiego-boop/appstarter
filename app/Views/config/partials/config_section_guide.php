<?php
declare(strict_types=1);

/**
 * Mini manual por sección de configuración.
 *
 * @var string $guide_key   clave única para localStorage dismiss
 * @var string $title
 * @var string $body
 * @var list<string>|null $steps
 */
$guideKey = (string) ($guide_key ?? 'default');
$steps = $steps ?? null;
?>
<div class="config-section-guide" data-guide-key="<?= esc($guideKey, 'attr') ?>">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="flex-grow-1">
            <div class="config-section-guide-title"><i class="fa-solid fa-circle-info text-info me-1"></i><?= esc($title ?? '') ?></div>
            <p class="config-section-guide-body"><?= $body ?? '' ?></p>
            <?php if (is_array($steps) && $steps !== []): ?>
            <ol class="config-section-guide-steps mb-0">
                <?php foreach ($steps as $step): ?>
                <li><?= $step ?></li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-sm btn-link text-muted p-0 flex-shrink-0" data-config-guide-dismiss="<?= esc($guideKey, 'attr') ?>" title="Ocultar ayuda de esta sección">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
