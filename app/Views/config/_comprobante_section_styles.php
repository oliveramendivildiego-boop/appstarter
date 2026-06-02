<?php
/**
 * Estilos tipográficos de una sección (junto a la matriz de diseño).
 *
 * @var string               $sectionId
 * @var list<array<string, mixed>> $sectionStyleGroups
 * @var array<string, mixed> $styles
 * @var array<string, array<string, mixed>> $sectionStyles
 * @var array<string, float|int> $dimensions
 * @var array<string, string> $styleLabels
 * @var list<string>         $fontFamilies
 * @var list<string>         $fontWeights
 * @var list<string>         $fontStyles
 * @var array<string, string> $textTransforms
 * @var ComprobanteLayoutService $layoutSvc
 */

use App\Services\ComprobanteLayoutService;
use App\Services\LayoutService;

$sectionStyleGroups = $sectionStyleGroups ?? [];
$layoutSvc = $layoutSvc ?? new ComprobanteLayoutService();
if ($sectionStyleGroups === []) {
    return;
}

$allowedStyleKeys = $layoutSvc->styleKeysForMatrixSection($sectionId);
if ($sectionId === 'header') {
    $allowedStyleKeys[] = 'body';
    $allowedStyleKeys = array_values(array_unique($allowedStyleKeys));
}
?>
<div class="comp-section-styles h-100">
    <h6 class="text-uppercase text-muted small mb-2">
        <i class="fa-solid fa-font me-1"></i>Estilos tipográficos de esta sección
    </h6>
    <div class="comp-section-styles-scroll comp-style-matrix"
         id="comp_style_matrix_<?= esc($sectionId, 'attr') ?>"
         data-section="<?= esc($sectionId, 'attr') ?>">
        <?php foreach ($sectionStyleGroups as $group):
            $groupDims = $group['dimensions'] ?? [];
            $groupHasStyles = false;
            foreach ($group['style_keys'] as $sk) {
                if (in_array($sk, $allowedStyleKeys, true)) {
                    $groupHasStyles = true;
                    break;
                }
            }
            if (! $groupHasStyles && $groupDims === []) {
                continue;
            }
            ?>
        <?php if (count($sectionStyleGroups) > 1): ?>
        <div class="comp-style-group-label small fw-bold text-primary mb-2 mt-1"><?= esc($group['label']) ?></div>
        <?php endif; ?>

        <?php foreach ($group['style_keys'] as $styleKey):
            if (! in_array($styleKey, $allowedStyleKeys, true)) {
                continue;
            }
            $styleLabel = $styleLabels[$styleKey] ?? $styleKey;
            $st = $sectionStyles[$sectionId][$styleKey]
                ?? $styles[$styleKey]
                ?? ComprobanteLayoutService::DEFAULT_STYLES[$styleKey]
                ?? ComprobanteLayoutService::DEFAULT_STYLES['body'];
            $stColor = LayoutService::htmlColorPickerValue($st['color'] ?? '#1e293b', '#1e293b');
            ?>
        <div class="comp-style-block"
             data-style-key="<?= esc($styleKey, 'attr') ?>"
             data-style-group="<?= esc($group['id'], 'attr') ?>"
             data-section="<?= esc($sectionId, 'attr') ?>">
            <div class="comp-style-block__head">
                <span class="comp-style-block__title"><?= esc($styleLabel) ?></span>
                <span class="comp-style-preview-sample comp-style-block__sample" data-style-key="<?= esc($styleKey, 'attr') ?>">Aa 123</span>
            </div>
            <div class="row g-2 comp-style-block__fields">
                <div class="col-12">
                    <label class="form-label small mb-0">Fuente</label>
                    <select class="form-select form-select-sm comp-style-input" data-style-field="font_family">
                        <?php foreach ($fontFamilies as $ff): ?>
                        <option value="<?= esc($ff, 'attr') ?>" <?= ($st['font_family'] ?? '') === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small mb-0">Tamaño (pt)</label>
                    <input type="number" class="form-control form-control-sm comp-style-input" data-style-field="font_size_pt" min="6" max="24" step="0.5" value="<?= esc((string) ($st['font_size_pt'] ?? 10), 'attr') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-0">Color</label>
                    <input type="color" class="form-control form-control-color comp-style-input comp-style-color w-100" data-style-field="color" value="<?= esc($stColor, 'attr') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-0">Grosor</label>
                    <select class="form-select form-select-sm comp-style-input" data-style-field="font_weight">
                        <?php foreach ($fontWeights as $fw): ?>
                        <option value="<?= esc($fw, 'attr') ?>" <?= ($st['font_weight'] ?? '') === $fw ? 'selected' : '' ?>><?= esc($fw) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small mb-0">Estilo</label>
                    <select class="form-select form-select-sm comp-style-input" data-style-field="font_style">
                        <?php foreach ($fontStyles as $fs): ?>
                        <option value="<?= esc($fs, 'attr') ?>" <?= ($st['font_style'] ?? '') === $fs ? 'selected' : '' ?>><?= esc(ucfirst($fs)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small mb-0">Transformación de texto</label>
                    <select class="form-select form-select-sm comp-style-input" data-style-field="text_transform">
                        <?php foreach ($textTransforms as $tk => $tv): ?>
                        <option value="<?= esc($tk, 'attr') ?>" <?= ($st['text_transform'] ?? 'none') === $tk ? 'selected' : '' ?>><?= esc($tv) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($groupDims !== []): ?>
        <div class="comp-style-dimensions-block" data-style-group="<?= esc($group['id'], 'attr') ?>" data-section="<?= esc($sectionId, 'attr') ?>">
            <div class="small fw-semibold mb-2">Medidas de esta sección</div>
            <div class="row g-2">
                <?php foreach ($groupDims as $dimKey => $dimLabel):
                    $dimVal = $dimensions[$dimKey] ?? ComprobanteLayoutService::DEFAULT_DIMENSIONS[$dimKey] ?? 0;
                    $isColorDim = str_ends_with($dimKey, '_color');
                    $allowEmptyColor = $dimKey === 'header_lab_bg_color' || $dimKey === 'items_header_bg_color';
                    ?>
                <div class="col-12">
                    <label class="form-label small mb-0" for="comp_dim_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>"><?= esc($dimLabel) ?></label>
                    <?php if ($isColorDim):
                        $colorVal = LayoutService::htmlColorPickerValue((string) $dimVal, $allowEmptyColor ? '#ffffff' : '#f0fdfa');
                        if ($allowEmptyColor && trim((string) $dimVal) === ''): ?>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="color"
                               class="form-control form-control-color comp-dimension-input comp-dimension-color"
                               id="comp_dim_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>"
                               data-dimension-key="<?= esc($dimKey, 'attr') ?>"
                               value="<?= esc($colorVal, 'attr') ?>"
                               disabled>
                        <div class="form-check mb-0">
                            <input type="checkbox"
                                   class="form-check-input comp-dimension-color-clear"
                                   id="comp_dim_clear_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>"
                                   data-dimension-key="<?= esc($dimKey, 'attr') ?>"
                                   data-color-input="comp_dim_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>"
                                   checked>
                            <label class="form-check-label small" for="comp_dim_clear_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>">Sin fondo</label>
                        </div>
                    </div>
                        <?php else: ?>
                    <input type="color"
                           class="form-control form-control-color comp-dimension-input comp-dimension-color w-100"
                           id="comp_dim_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>"
                           data-dimension-key="<?= esc($dimKey, 'attr') ?>"
                           value="<?= esc($colorVal, 'attr') ?>">
                        <?php if ($allowEmptyColor): ?>
                    <div class="form-check mt-1 mb-0">
                        <input type="checkbox"
                               class="form-check-input comp-dimension-color-clear"
                               id="comp_dim_clear_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>"
                               data-dimension-key="<?= esc($dimKey, 'attr') ?>"
                               data-color-input="comp_dim_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>">
                        <label class="form-check-label small" for="comp_dim_clear_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>">Sin fondo</label>
                    </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    <?php else:
                        $dimMin = str_contains($dimKey, '_pct') ? 20 : (str_contains($dimKey, 'padding') || str_contains($dimKey, 'border') ? 0 : 160);
                        $dimMax = str_contains($dimKey, '_pct') ? 80 : (str_contains($dimKey, 'padding') || str_contains($dimKey, 'border_width') ? 20 : (str_contains($dimKey, 'radius') ? 16 : 420));
                        $dimStep = str_contains($dimKey, 'width_pt') && ! str_contains($dimKey, 'border') ? 1 : 0.5;
                        ?>
                    <input type="number"
                           class="form-control form-control-sm comp-dimension-input"
                           id="comp_dim_<?= esc($sectionId, 'attr') ?>_<?= esc($dimKey, 'attr') ?>"
                           data-dimension-key="<?= esc($dimKey, 'attr') ?>"
                           min="<?= $dimMin ?>"
                           max="<?= $dimMax ?>"
                           step="<?= $dimStep ?>"
                           value="<?= esc((string) $dimVal, 'attr') ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
