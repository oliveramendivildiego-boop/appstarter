<?php
/**
 * Peso y estilo de fuente (junto a un color de texto en Apariencia).
 *
 * @var string $weightField nombre del campo POST (ej. ui_header_text_weight)
 * @var string $styleField
 * @var string $weightId    id del select peso
 * @var string $styleId
 * @var string $weightVal   valor guardado
 * @var string $styleVal
 */
use App\Services\LayoutService;

$weightField = $weightField ?? '';
$styleField  = $styleField ?? '';
$weightId    = $weightId ?? '';
$styleId     = $styleId ?? '';
$weightVal   = (string) ($weightVal ?? '400');
$styleVal    = (string) ($styleVal ?? 'normal');

$weightOpts = LayoutService::uiFontWeightOptionsForView();
$styleOpts  = LayoutService::uiFontStyleOptionsForView();
?>
<div class="row g-2 mt-1">
    <div class="col-md-6">
        <label class="form-label small mb-1" for="<?= esc($weightId) ?>"><?= lang('Config.config_style_font_weight') ?></label>
        <?= form_dropdown($weightField, $weightOpts, $weightVal, 'class="form-select form-select-sm" id="' . esc($weightId, 'attr') . '" autocomplete="off"') ?>
    </div>
    <div class="col-md-6">
        <label class="form-label small mb-1" for="<?= esc($styleId) ?>"><?= lang('Config.config_style_font_style') ?></label>
        <?= form_dropdown($styleField, $styleOpts, $styleVal, 'class="form-select form-select-sm" id="' . esc($styleId, 'attr') . '" autocomplete="off"') ?>
    </div>
</div>
