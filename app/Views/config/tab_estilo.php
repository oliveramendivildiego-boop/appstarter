<?php
/**
 * Pestaña Apariencia (fragmento incluido desde config/manage).
 *
 * @var array<string, mixed> $config
 * @var string               $activeTab
 * @var array<string, string> $theme_palette
 */

use App\Services\LayoutService;

$fontOpts        = LayoutService::uiFontOptionsForView();
$fontSizeOpts    = LayoutService::uiFontSizeRemOptionsForView();
$activeTab       = $activeTab ?? 'sistema';
$previewTheme    = esc($config['theme_color'] ?? '#FF7218', 'attr');
$previewGrad     = esc($config['theme_gradient_end'] ?? '#4f46e5', 'attr');
$theme_palette   = $theme_palette ?? [];
$curFont         = strtolower((string) ($config['ui_font_family'] ?? 'poppins'));
$curFontResolved = array_key_exists($curFont, $fontOpts) ? $curFont : 'poppins';
$fontPreviewStackEsc = esc(LayoutService::uiFontFamilyCssStackForKey($curFontResolved), 'attr');
$fontPreviewLabel    = $fontOpts[$curFontResolved];
$headerBgSaved = trim((string) ($config['ui_header_bg'] ?? ''));
$headerMode    = match (true) {
    strtolower($headerBgSaved) === 'transparent' => 'transparent',
    $headerBgSaved === ''                        => 'theme',
    default                                      => 'custom',
};
$headerIsCustom = $headerMode === 'custom';
$headerPick     = (string) ($config['theme_color'] ?? '#FF7218');
if ($headerIsCustom && preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $headerBgSaved)) {
    $headerPick = strtoupper(strlen($headerBgSaved) === 4
        ? '#' . $headerBgSaved[1] . $headerBgSaved[1] . $headerBgSaved[2] . $headerBgSaved[2] . $headerBgSaved[3] . $headerBgSaved[3]
        : $headerBgSaved);
}
$linkSaved       = trim((string) ($config['ui_sidebar_link_color'] ?? ''));
$linkIsCustom    = $linkSaved !== '';
$linkPick        = $linkIsCustom ? $linkSaved : (string) ($config['theme_color'] ?? '#0d6efd');
$linkUi          = trim((string) ($config['ui_link_color'] ?? ''));
$linkUiVal       = $linkUi !== '' ? $linkUi : '#0d6efd';
$hoverBgVal      = trim((string) ($config['ui_sidebar_hover_bg'] ?? ''));
$activeBgVal     = trim((string) ($config['ui_sidebar_active_bg'] ?? ''));

$sidebarBgStored = trim((string) ($config['ui_sidebar_bg'] ?? '#f8f9fa'));
$mainBgStored    = trim((string) ($config['ui_main_bg'] ?? '#ffffff'));
$footerBgStored  = trim((string) ($config['ui_footer_bg'] ?? '#f8f9fa'));
$sidebarBgTransparent = strtolower($sidebarBgStored) === 'transparent';
$mainBgTransparent    = strtolower($mainBgStored) === 'transparent';
$footerBgTransparent  = strtolower($footerBgStored) === 'transparent';
$pvSidebarBg          = $sidebarBgTransparent ? 'transparent' : esc($sidebarBgStored, 'attr');
$pvSidebarHoverBox    = esc($hoverBgVal !== '' ? $hoverBgVal : '#e9ecef', 'attr');
$pvSidebarActiveBox   = esc($activeBgVal !== '' ? $activeBgVal : '#dee2e6', 'attr');
$pvMainBg             = $mainBgTransparent ? 'transparent' : esc($mainBgStored, 'attr');
$pvFooterBg           = $footerBgTransparent ? 'transparent' : esc($footerBgStored, 'attr');
$footerAlignSaved     = strtolower((string) ($config['ui_footer_text_align'] ?? 'left'));
if (! in_array($footerAlignSaved, ['left', 'center', 'right'], true)) {
    $footerAlignSaved = 'left';
}
$pvFooterJustifyPreview = match ($footerAlignSaved) {
    'center' => 'center',
    'right'  => 'flex-end',
    default  => 'flex-start',
};
$pvCardRadius         = max(0, min(24, (int) ($config['ui_card_radius'] ?? 8)));

$sidebarBgRaw    = $sidebarBgTransparent ? '#e9ecef' : $sidebarBgStored;
$mainBgRaw       = $mainBgTransparent ? '#e9ecef' : $mainBgStored;
$footerBgRaw     = $footerBgTransparent ? '#e9ecef' : $footerBgStored;
$bodyTextRaw     = trim((string) ($config['ui_body_text_color'] ?? '#212529'));
$footerTextRaw   = trim((string) ($config['ui_footer_text_color'] ?? '#6c757d'));
$headerTextRaw   = trim((string) ($config['ui_header_text_color'] ?? '#ffffff'));
$labotestsCardTitleColor = trim((string) ($config['ui_labotests_card_header_title_color'] ?? '#ffffff'));
if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $labotestsCardTitleColor)) {
    $labotestsCardTitleColor = '#ffffff';
}
$barBgApprox = match ($headerMode) {
    'transparent' => '#e9ecef',
    'custom'      => $headerPick,
    default       => (string) ($config['theme_gradient_end'] ?? '#4f46e5'),
};
$pvBarFgWire = LayoutService::readableForegroundOnBackground($barBgApprox, $headerTextRaw);
$pvBarStyle  = match ($headerMode) {
    'transparent' => 'background:transparent;border:1px dashed #adb5bd;color:' . esc($pvBarFgWire, 'attr') . ';',
    'custom'      => 'background-color:' . esc($headerPick, 'attr') . ';color:' . esc($pvBarFgWire, 'attr') . ';',
    default       => 'background:linear-gradient(90deg,' . $previewTheme . ',' . $previewGrad . ');color:' . esc($pvBarFgWire, 'attr') . ';',
};
$pvSidebarFgWire = LayoutService::readableForegroundOnBackground($sidebarBgRaw, $linkPick);
$pvMainFgWire    = LayoutService::readableForegroundOnBackground($mainBgRaw, $bodyTextRaw);
$pvMainLinkWire  = LayoutService::readableForegroundOnBackground($mainBgRaw, $linkUiVal, true);
$pvFooterFgWire  = LayoutService::readableForegroundOnBackground($footerBgRaw, $footerTextRaw);
$hoverPreviewBg  = $hoverBgVal !== '' ? $hoverBgVal : '#e9ecef';
$activePreviewBg = $activeBgVal !== '' ? $activeBgVal : '#dee2e6';
$pvHoverLabelFg  = LayoutService::readableForegroundOnBackground($hoverPreviewBg, '#212529');
$pvActiveLabelFg = LayoutService::readableForegroundOnBackground($activePreviewBg, '#212529');
$pvSidebarOnWhiteFg = LayoutService::readableForegroundOnBackground('#ffffff', $linkPick);

$palette          = $theme_palette;
$currentThemeCol  = strtolower((string) ($config['theme_color'] ?? '#FF7218'));
$selectThemePal   = ['' => 'Personalizado'] + $palette;
$selectedThemeHex = '';
foreach (array_keys($palette) as $h) {
    if (strtolower(ltrim($h, '#')) === strtolower(ltrim($currentThemeCol, '#'))) {
        $selectedThemeHex = $h;
        break;
    }
}
$currentGradCol   = strtolower((string) ($config['theme_gradient_end'] ?? '#4f46e5'));
$selectedGradHex  = '';
foreach (array_keys($palette) as $h) {
    if (strtolower(ltrim($h, '#')) === strtolower(ltrim($currentGradCol, '#'))) {
        $selectedGradHex = $h;
        break;
    }
}

$btnPrimaryCustom = trim((string) ($config['ui_btn_primary_bg'] ?? '')) !== '';
$btnPreviewBgHex  = $btnPrimaryCustom ? trim((string) $config['ui_btn_primary_bg']) : (string) ($config['theme_color'] ?? '#FF7218');
$btnHovCustom     = trim((string) ($config['ui_btn_primary_hover_bg'] ?? '')) !== '';
$uiBtnBw          = max(0, min(8, (int) ($config['ui_btn_border_width'] ?? 0)));
$uiCardBw         = max(0, min(8, (int) ($config['ui_card_border_width'] ?? 0)));
$uiBtnSides       = LayoutService::normalizeUiBorderSides((string) ($config['ui_btn_border_sides'] ?? 'all'));
$uiCardSides      = LayoutService::normalizeUiBorderSides((string) ($config['ui_card_border_sides'] ?? 'all'));
$uiBtnSh          = LayoutService::normalizeUiShadowKey((string) ($config['ui_btn_shadow'] ?? 'none'));
$uiCardSh         = LayoutService::normalizeUiShadowKey((string) ($config['ui_card_shadow'] ?? 'none'));
$borderSideOpts   = LayoutService::uiBorderSidesOptionsForView();
$shadowOpts       = LayoutService::uiShadowOptionsForView();

$fwHeader   = LayoutService::normalizeUiFontWeight((string) ($config['ui_header_text_weight'] ?? ''), '500');
$fsHeader   = LayoutService::normalizeUiFontStyle((string) ($config['ui_header_text_style'] ?? ''), 'normal');
$fwSidebarL = LayoutService::normalizeUiFontWeight((string) ($config['ui_sidebar_link_weight'] ?? ''), '500');
$fsSidebarL = LayoutService::normalizeUiFontStyle((string) ($config['ui_sidebar_link_style'] ?? ''), 'normal');
$fwBody     = LayoutService::normalizeUiFontWeight((string) ($config['ui_body_text_weight'] ?? ''), '400');
$fsBody     = LayoutService::normalizeUiFontStyle((string) ($config['ui_body_text_style'] ?? ''), 'normal');
$fwLink     = LayoutService::normalizeUiFontWeight((string) ($config['ui_link_weight'] ?? ''), '400');
$fsLink     = LayoutService::normalizeUiFontStyle((string) ($config['ui_link_style'] ?? ''), 'normal');
$fwFooter   = LayoutService::normalizeUiFontWeight((string) ($config['ui_footer_text_weight'] ?? ''), '400');
$fsFooter   = LayoutService::normalizeUiFontStyle((string) ($config['ui_footer_text_style'] ?? ''), 'normal');
$fwBtnText  = LayoutService::normalizeUiFontWeight((string) ($config['ui_btn_primary_text_weight'] ?? ''), '500');
$fsBtnText  = LayoutService::normalizeUiFontStyle((string) ($config['ui_btn_primary_text_style'] ?? ''), 'normal');
$fwLabCard  = LayoutService::normalizeUiFontWeight((string) ($config['ui_labotests_card_header_title_weight'] ?? ''), '600');
$fsLabCard  = LayoutService::normalizeUiFontStyle((string) ($config['ui_labotests_card_header_title_style'] ?? ''), 'normal');
$pgThemeFallback = (string) ($config['theme_color'] ?? '#FF7218');
if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $pgThemeFallback)) {
    $pgThemeFallback = '#FF7218';
}
$pgLinkColor = trim((string) ($config['ui_pagination_link_color'] ?? ''));
if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $pgLinkColor)) {
    $pgLinkColor = $pgThemeFallback;
}
$fwPagination = LayoutService::normalizeUiFontWeight((string) ($config['ui_pagination_link_weight'] ?? ''), '400');
$fsPagination = LayoutService::normalizeUiFontStyle((string) ($config['ui_pagination_link_style'] ?? ''), 'normal');
$pgActiveBg = trim((string) ($config['ui_pagination_active_bg'] ?? ''));
if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $pgActiveBg)) {
    $pgActiveBg = $pgThemeFallback;
}
$pgActiveColor = trim((string) ($config['ui_pagination_active_color'] ?? ''));
if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $pgActiveColor)) {
    $pgActiveColor = '#ffffff';
}
?>
    <div class="tab-pane fade config-tab-estilo <?= $activeTab === 'estilo' ? 'show active' : '' ?>" id="tab-estilo" role="tabpanel">
        <p class="text-muted small mb-4"><?= lang('Config.config_style_tab_intro') ?></p>

        <?= form_open(site_url('config/saveUiStyle'), ['id' => 'form_ui_style']) ?>
        <?= csrf_field() ?>

        <div id="config-layout-map" class="card shadow-sm mb-4 border-primary border-2 overflow-hidden">
            <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom">
                <h5 class="mb-0 fw-semibold text-primary"><i class="fa-solid fa-map me-2"></i><?= lang('Config.config_style_layout_map_title') ?></h5>
            </div>
            <div class="card-body">
                <div class="config-layout-map__frame rounded border border-2 overflow-hidden bg-white shadow-sm">
                    <div class="config-layout-map__bar px-3 py-2 small fw-semibold" style="<?= $pvBarStyle ?>">
                        <span class="me-2 opacity-75">☰</span><?= lang('Config.config_style_section_bar') ?> · <?= lang('Config.config_company') ?>
                    </div>
                    <div class="d-flex config-layout-map__mid" style="min-height: 9rem;">
                        <div class="config-layout-map__side border-end p-2 small" style="width: 32%; min-width: 7.5rem; background: <?= $pvSidebarBg ?>; color: <?= esc($pvSidebarFgWire, 'attr') ?>;">
                            <div class="fw-semibold mb-1"><?= lang('Config.config_style_section_menu') ?></div>
                            <div class="opacity-90">• <?= lang('Config.config_style_preview_menu_left') ?></div>
                            <div class="opacity-75 small">• …</div>
                        </div>
                        <div class="config-layout-map__main flex-grow-1 p-3 small" style="background: <?= $pvMainBg ?>; color: <?= esc($pvMainFgWire, 'attr') ?>;">
                            <div class="fw-semibold mb-1"><?= lang('Config.config_style_section_page') ?></div>
                            <p class="mb-1 small"><?= lang('Config.config_style_font_size_main_preview') ?></p>
                            <a href="#" class="small" style="color: <?= esc($pvMainLinkWire, 'attr') ?>; pointer-events: none; text-decoration: underline;"><?= lang('Config.config_style_link_color') ?></a>
                        </div>
                    </div>
                    <div class="config-layout-map__footer px-3 py-2 small border-top d-flex flex-wrap align-items-center" style="background: <?= $pvFooterBg ?>; color: <?= esc($pvFooterFgWire, 'attr') ?>; justify-content: <?= esc($pvFooterJustifyPreview, 'attr') ?>;">
                        <span>© <?= date('Y') ?> · <?= lang('Config.config_style_section_footer') ?></span>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-3"><?= lang('Config.config_style_layout_map_help') ?></p>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-palette me-2"></i><?= lang('Config.config_style_theme_section') ?></h5>
                <p class="mb-0 mt-1 small opacity-90"><?= lang('Config.config_style_theme_section_help') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-3"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-6 col-sm-4 col-md-3 text-center">
                            <div class="rounded-3 border shadow-sm mx-auto mb-1 config-swatch-tile" style="width: 3.5rem; height: 3.5rem; background: <?= $previewTheme ?>;"></div>
                            <span class="small d-block"><?= lang('Config.config_style_theme_preview_swatch_theme') ?></span>
                        </div>
                        <div class="col-6 col-sm-4 col-md-3 text-center">
                            <div class="rounded-3 border shadow-sm mx-auto mb-1 config-swatch-tile" style="width: 3.5rem; height: 3.5rem; background: <?= $previewGrad ?>;"></div>
                            <span class="small d-block"><?= lang('Config.config_style_theme_preview_swatch_grad') ?></span>
                        </div>
                    </div>
                    <div class="rounded-3 py-3 px-3 text-white small fw-semibold text-center shadow-sm" style="background: linear-gradient(90deg, <?= $previewTheme ?>, <?= $previewGrad ?>);">
                        <?= lang('Config.config_style_theme_preview_strip') ?>
                    </div>
                    <p class="small text-muted mb-0 mt-3"><?= lang('Config.config_style_nav_theme_note') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <?= form_label(lang('Config.config_theme_color'), 'theme_color', ['class' => 'form-label fw-semibold']) ?>
                        <?= form_dropdown('theme_palette_select', $selectThemePal, $selectedThemeHex, 'id="theme_palette_select" class="form-select mb-2" autocomplete="off"') ?>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="color" name="theme_color" id="theme_color" value="<?= esc($config['theme_color'] ?? '#FF7218') ?>" class="form-control form-control-color config-color-picker" autocomplete="off">
                            <input type="text" id="theme_color_hex" value="<?= esc($config['theme_color'] ?? '#FF7218') ?>" class="form-control config-color-hex" readonly autocomplete="off">
                        </div>
                        <small class="text-muted"><?= lang('Config.config_theme_color_custom_hint') ?></small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <?= form_label(lang('Config.config_theme_gradient_end'), 'theme_gradient_end', ['class' => 'form-label fw-semibold']) ?>
                        <?= form_dropdown('theme_gradient_palette_select', $selectThemePal, $selectedGradHex, 'id="theme_gradient_palette_select" class="form-select mb-2" autocomplete="off"') ?>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="color" name="theme_gradient_end" id="theme_gradient_end" value="<?= esc($config['theme_gradient_end'] ?? '#4f46e5') ?>" class="form-control form-control-color config-color-picker" autocomplete="off">
                            <input type="text" id="theme_gradient_end_hex" value="<?= esc($config['theme_gradient_end'] ?? '#4f46e5') ?>" class="form-control config-color-hex" readonly autocomplete="off">
                        </div>
                        <small class="text-muted d-block"><?= lang('Config.config_theme_gradient_help') ?></small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold" for="ui_header_text_color_theme"><?= lang('Config.config_style_header_text') ?></label>
                        <input type="color" id="ui_header_text_color_theme" value="<?= esc($config['ui_header_text_color'] ?? '#ffffff') ?>" class="form-control form-control-color">
                        <small class="text-muted d-block"><?= lang('Config.config_style_header_text_breadcrumb_hint') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden border-start border-4 border-primary">
            <div class="card-header py-3 bg-primary bg-opacity-10 border-bottom">
                <h5 class="mb-0 fw-semibold text-primary"><i class="fa-solid fa-hand-pointer me-2"></i><?= lang('Config.config_style_btn_section') ?></h5>
                <p class="mb-0 mt-1 small text-muted"><?= lang('Config.config_style_btn_section_help') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-3"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center py-3">
                        <button type="button" class="btn btn-primary" disabled><?= lang('Config.config_save_btn') ?></button>
                        <span class="small text-muted"><?= lang('Config.config_style_btn_section') ?></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label fw-semibold"><?= lang('Config.config_style_btn_bg_mode') ?></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_btn_primary_mode" id="ui_btn_bg_theme" value="theme" <?= ! $btnPrimaryCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_btn_bg_theme"><?= lang('Config.config_style_use_theme_primary') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_btn_primary_mode" id="ui_btn_bg_custom" value="custom" <?= $btnPrimaryCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_btn_bg_custom"><?= lang('Config.config_style_custom_color') ?></label>
                        </div>
                        <input type="color" name="ui_btn_primary_bg_custom" id="ui_btn_primary_bg_custom" value="<?= esc($btnPreviewBgHex) ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label fw-semibold" for="ui_btn_primary_text"><?= lang('Config.config_style_btn_text') ?></label>
                        <input type="color" name="ui_btn_primary_text" id="ui_btn_primary_text" value="<?= esc($config['ui_btn_primary_text'] ?? '#ffffff') ?>" class="form-control form-control-color">
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_btn_primary_text_weight',
                            'styleField'  => 'ui_btn_primary_text_style',
                            'weightId'    => 'ui_btn_primary_text_weight',
                            'styleId'     => 'ui_btn_primary_text_style',
                            'weightVal'   => $fwBtnText,
                            'styleVal'    => $fsBtnText,
                        ]) ?>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label fw-semibold"><?= lang('Config.config_style_btn_hover_mode') ?></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_btn_hover_mode" id="ui_btn_hov_auto" value="auto" <?= ! $btnHovCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_btn_hov_auto"><?= lang('Config.config_style_btn_hover_auto') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_btn_hover_mode" id="ui_btn_hov_custom" value="custom" <?= $btnHovCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_btn_hov_custom"><?= lang('Config.config_style_custom_color') ?></label>
                        </div>
                        <input type="color" name="ui_btn_primary_hover_custom" id="ui_btn_primary_hover_custom" value="<?= esc($btnHovCustom ? (string) $config['ui_btn_primary_hover_bg'] : '#000000') ?>" class="form-control form-control-color">
                    </div>
                </div>
                <hr class="text-muted">
                <h6 class="fw-semibold mb-3"><i class="fa-regular fa-square me-2"></i><?= lang('Config.config_style_btn_border_section') ?></h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_btn_border_width"><?= lang('Config.config_style_btn_border_width') ?></label>
                        <input type="range" name="ui_btn_border_width" id="ui_btn_border_width" class="form-range" min="0" max="8" value="<?= $uiBtnBw ?>">
                        <div class="small text-muted"><span id="ui_btn_border_width_out"><?= $uiBtnBw ?></span> px</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_btn_border_color"><?= lang('Config.config_style_btn_border_color') ?></label>
                        <input type="color" name="ui_btn_border_color" id="ui_btn_border_color" value="<?= esc($config['ui_btn_border_color'] ?? '#212529') ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_btn_border_sides"><?= lang('Config.config_style_btn_border_sides') ?></label>
                        <?= form_dropdown('ui_btn_border_sides', $borderSideOpts, $uiBtnSides, 'class="form-select" id="ui_btn_border_sides" autocomplete="off"') ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_btn_shadow"><?= lang('Config.config_style_btn_shadow') ?></label>
                        <?= form_dropdown('ui_btn_shadow', $shadowOpts, $uiBtnSh, 'class="form-select" id="ui_btn_shadow" autocomplete="off"') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header py-3 bg-info bg-opacity-25 border-bottom border-info border-3">
                <h5 class="mb-0 fw-semibold text-info-emphasis"><i class="fa-solid fa-font me-2"></i><?= lang('Config.config_style_card_font_layout') ?></h5>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-3"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="config-font-preview p-3 rounded-3 border bg-white mb-3 shadow-sm" style="font-family: <?= $fontPreviewStackEsc ?>; font-size: 1.2rem; line-height: 1.4;">
                        Aa Bb Cc 123 — <span class="text-muted" style="font-size: 0.95rem;"><?= esc($fontPreviewLabel) ?></span>
                    </div>
                    <p class="small text-muted mb-3"><?= lang('Config.config_style_preview_font_sample') ?></p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-2 text-center small bg-white h-100 config-preview-layout-thumb">
                                <div class="text-muted mb-1"><?= lang('Config.config_style_preview_menu_left') ?></div>
                                <div class="d-flex gap-1 justify-content-center align-items-stretch mx-auto" style="max-width: 11rem; height: 4rem;">
                                    <div class="rounded-start border bg-secondary bg-opacity-25" style="width: 28%;"></div>
                                    <div class="flex-grow-1 rounded-end border bg-white"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-2 text-center small bg-white h-100 config-preview-layout-thumb">
                                <div class="text-muted mb-1"><?= lang('Config.config_style_preview_menu_right') ?></div>
                                <div class="d-flex gap-1 justify-content-center align-items-stretch mx-auto" style="max-width: 11rem; height: 4rem;">
                                    <div class="flex-grow-1 rounded-start border bg-white"></div>
                                    <div class="rounded-end border bg-secondary bg-opacity-25" style="width: 28%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="ui_font_family"><?= lang('Config.config_style_font') ?></label>
                        <?= form_dropdown(
                            'ui_font_family',
                            $fontOpts,
                            array_key_exists($curFont, $fontOpts) ? $curFont : 'poppins',
                            'class="form-select" id="ui_font_family" autocomplete="off"'
                        ) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold"><?= lang('Config.config_style_menu_side') ?></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ui_sidebar_position" id="ui_side_l" value="left" autocomplete="off" <?= (($config['ui_sidebar_position'] ?? 'left') !== 'right') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ui_side_l"><?= lang('Config.config_style_menu_left') ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ui_sidebar_position" id="ui_side_r" value="right" autocomplete="off" <?= (($config['ui_sidebar_position'] ?? '') === 'right') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ui_side_r"><?= lang('Config.config_style_menu_right') ?></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-text-height me-2"></i><?= lang('Config.config_style_font_sizes_section') ?></h5>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <p class="text-muted small mb-3"><?= lang('Config.config_style_font_sizes_intro') ?></p>
                    <div class="config-zone-preview config-zone-preview--wireframe rounded-3 overflow-hidden border border-2 mb-0">
                        <div class="config-zone-preview__bar text-white text-center small py-2" style="background: linear-gradient(90deg, <?= $previewTheme ?>, <?= $previewGrad ?>);"><?= lang('Config.config_style_section_bar') ?></div>
                        <div class="d-flex" style="min-height: 5rem;">
                            <div class="border-end text-center small py-2 px-1" style="width: 26%; background: <?= $pvSidebarBg ?>; color: <?= esc($pvSidebarFgWire, 'attr') ?>;"><?= lang('Config.config_style_section_menu') ?></div>
                            <div class="flex-grow-1 p-2 small text-center" style="background-color: <?= $pvMainBg ?>; color: <?= esc($pvMainFgWire, 'attr') ?>;">
                                <div class="fw-bold small"><?= lang('Config.config_style_font_size_heading') ?></div>
                                <?= lang('Config.config_style_section_page') ?>
                            </div>
                        </div>
                        <div class="text-center small py-1 border-top config-tab-estilo__footer-strip" style="background: <?= $pvFooterBg ?>; color: <?= esc($pvFooterFgWire, 'attr') ?>;"><?= lang('Config.config_style_section_footer') ?></div>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="config-zone-preview config-zone-preview--lg config-zone-preview--base rounded-3 overflow-hidden d-flex flex-column mb-2 border">
                            <div class="config-zone-preview__block flex-grow-1 bg-white border-bottom" style="font-size: 0.875rem;">Aa Bb — <?= lang('Config.config_style_font_size_base') ?></div>
                            <div class="config-zone-preview__caption text-muted px-2 py-2 bg-light"><?= lang('Config.config_style_font_size_base_preview') ?></div>
                        </div>
                        <label class="form-label fw-semibold" for="ui_font_size_base"><?= lang('Config.config_style_font_size_base') ?></label>
                        <?= form_dropdown(
                            'ui_font_size_base',
                            $fontSizeOpts,
                            LayoutService::normalizeUiFontSizeRemInput((string) ($config['ui_font_size_base'] ?? ''), '1'),
                            'class="form-select" id="ui_font_size_base" autocomplete="off"'
                        ) ?>
                    </div>
                    <div class="col-lg-6">
                        <div class="config-zone-preview config-zone-preview--lg config-zone-preview--main rounded-3 overflow-hidden mb-2 border">
                            <div class="config-zone-preview__block bg-white border" style="font-size: 0.875rem;">
                                <span class="d-block fw-semibold mb-1"><?= lang('Config.config_style_section_page') ?></span>
                                <?= lang('Config.config_style_font_size_main_preview') ?>
                            </div>
                        </div>
                        <label class="form-label fw-semibold" for="ui_font_size_main"><?= lang('Config.config_style_font_size_main') ?></label>
                        <?= form_dropdown(
                            'ui_font_size_main',
                            $fontSizeOpts,
                            LayoutService::normalizeUiFontSizeRemInput((string) ($config['ui_font_size_main'] ?? ''), '1'),
                            'class="form-select" id="ui_font_size_main" autocomplete="off"'
                        ) ?>
                    </div>
                    <div class="col-lg-6">
                        <div class="config-zone-preview config-zone-preview--lg config-zone-preview--header rounded-3 overflow-hidden mb-2 border">
                            <div class="config-zone-preview__bar text-white" style="background: linear-gradient(90deg, <?= $previewTheme ?>, <?= $previewGrad ?>); font-size: 0.8125rem;"><?= lang('Config.config_style_section_bar') ?> · <?= lang('Config.config_company') ?></div>
                            <div class="config-zone-preview__caption text-muted px-2 py-1 bg-white"><?= lang('Config.config_style_font_size_header_preview') ?></div>
                        </div>
                        <label class="form-label fw-semibold" for="ui_font_size_header"><?= lang('Config.config_style_font_size_header') ?></label>
                        <?= form_dropdown(
                            'ui_font_size_header',
                            $fontSizeOpts,
                            LayoutService::normalizeUiFontSizeRemInput((string) ($config['ui_font_size_header'] ?? ''), '1'),
                            'class="form-select" id="ui_font_size_header" autocomplete="off"'
                        ) ?>
                    </div>
                    <div class="col-lg-6">
                        <div class="config-zone-preview config-zone-preview--lg config-zone-preview--sidebar rounded-3 overflow-hidden mb-2 d-flex border">
                            <div class="config-zone-preview__side border-end" style="font-size: 0.8125rem; color: <?= esc($pvSidebarFgWire, 'attr') ?>; background-color: <?= $pvSidebarBg ?>;">≡ <?= lang('Config.config_style_section_menu') ?></div>
                            <div class="config-zone-preview__main bg-white"><?= lang('Config.config_style_font_size_sidebar_preview') ?></div>
                        </div>
                        <label class="form-label fw-semibold" for="ui_font_size_sidebar"><?= lang('Config.config_style_font_size_sidebar') ?></label>
                        <?= form_dropdown(
                            'ui_font_size_sidebar',
                            $fontSizeOpts,
                            LayoutService::normalizeUiFontSizeRemInput((string) ($config['ui_font_size_sidebar'] ?? ''), '1'),
                            'class="form-select" id="ui_font_size_sidebar" autocomplete="off"'
                        ) ?>
                    </div>
                    <div class="col-lg-6">
                        <div class="config-zone-preview config-zone-preview--lg config-zone-preview--footer rounded-3 overflow-hidden mb-2 border">
                            <div class="config-zone-preview__foot bg-secondary bg-opacity-10 border text-muted" style="font-size: 0.8125rem;">© <?= lang('Config.config_style_section_footer') ?></div>
                            <div class="config-zone-preview__caption px-2 py-1 bg-light"><?= lang('Config.config_style_font_size_footer_preview') ?></div>
                        </div>
                        <label class="form-label fw-semibold" for="ui_font_size_footer"><?= lang('Config.config_style_font_size_footer') ?></label>
                        <?= form_dropdown(
                            'ui_font_size_footer',
                            $fontSizeOpts,
                            LayoutService::normalizeUiFontSizeRemInput((string) ($config['ui_font_size_footer'] ?? ''), '0.875'),
                            'class="form-select" id="ui_font_size_footer" autocomplete="off"'
                        ) ?>
                    </div>
                    <div class="col-lg-6">
                        <div class="config-zone-preview config-zone-preview--lg config-zone-preview--heading rounded-3 overflow-hidden mb-2 border">
                            <div class="config-zone-preview__title bg-light border-bottom" style="font-size: 0.875rem;"><?= lang('Config.config_style_font_size_heading') ?></div>
                            <div class="config-zone-preview__sub"><?= lang('Config.config_style_font_size_heading_preview') ?></div>
                        </div>
                        <label class="form-label fw-semibold" for="ui_font_size_heading"><?= lang('Config.config_style_font_size_heading') ?></label>
                        <?= form_dropdown(
                            'ui_font_size_heading',
                            $fontSizeOpts,
                            LayoutService::normalizeUiFontSizeRemInput((string) ($config['ui_font_size_heading'] ?? ''), '1.125'),
                            'class="form-select" id="ui_font_size_heading" autocomplete="off"'
                        ) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-warning py-3">
                <h5 class="mb-0 fw-semibold text-dark"><i class="fa-solid fa-fill-drip me-2"></i><?= lang('Config.config_style_section_bar') ?></h5>
                <p class="mb-0 mt-1 small text-dark text-opacity-75"><?= lang('Config.config_style_bar_section_hint') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 py-3 px-3 text-center shadow-sm mb-2" style="<?= $pvBarStyle ?>">
                        <?= lang('Config.config_style_section_bar') ?> · <?= lang('Config.config_company') ?>
                    </div>
                    <p class="small text-muted mb-0"><?= lang('Config.config_style_preview_bar_hint') ?></p>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_header_bg') ?></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_header_mode" id="ui_hdr_theme" value="theme" <?= $headerMode === 'theme' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_hdr_theme"><?= lang('Config.config_style_use_theme_primary') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_header_mode" id="ui_hdr_custom" value="custom" <?= $headerMode === 'custom' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_hdr_custom"><?= lang('Config.config_style_custom_color') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_header_mode" id="ui_hdr_transparent" value="transparent" <?= $headerMode === 'transparent' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_hdr_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_header_bg_custom" id="ui_header_bg_custom" value="<?= esc($headerPick) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_header_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_header_transparent_help') ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_header_text_color"><?= lang('Config.config_style_header_text') ?></label>
                        <input type="color" name="ui_header_text_color" id="ui_header_text_color" value="<?= esc($config['ui_header_text_color'] ?? '#ffffff') ?>" class="form-control form-control-color">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_header_text_breadcrumb_hint') ?></small>
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_header_text_weight',
                            'styleField'  => 'ui_header_text_style',
                            'weightId'    => 'ui_header_text_weight',
                            'styleId'     => 'ui_header_text_style',
                            'weightVal'   => $fwHeader,
                            'styleVal'    => $fsHeader,
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header py-3 border-bottom" style="background: linear-gradient(90deg, <?= $previewTheme ?>, <?= $previewGrad ?>);">
                <h5 class="mb-0 fw-semibold text-white"><i class="fa-solid fa-flask-vial me-2"></i><?= lang('Config.config_style_section_labotests_cards') ?></h5>
                <p class="mb-0 mt-1 small text-white text-opacity-90"><?= lang('Config.config_style_section_labotests_cards_hint') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 overflow-hidden shadow-sm border mb-2" style="max-width: 22rem;">
                        <div class="card-header bg-primary text-white py-2 px-3 d-flex align-items-center">
                            <h6 class="mb-0 config-labotests-card-title-preview" style="color: <?= esc($labotestsCardTitleColor, 'attr') ?> !important; font-weight: <?= esc($fwLabCard, 'attr') ?> !important; font-style: <?= esc($fsLabCard, 'attr') ?> !important;"><?= lang('Config.config_style_labotests_card_preview_sample') ?></h6>
                        </div>
                    </div>
                    <p class="small text-muted mb-0"><?= lang('Config.config_style_labotests_card_preview_help') ?></p>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label" for="ui_labotests_card_header_title_color"><?= lang('Config.config_style_labotests_card_title_color') ?></label>
                        <input type="color" name="ui_labotests_card_header_title_color" id="ui_labotests_card_header_title_color" value="<?= esc($labotestsCardTitleColor) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_labotests_card_title_color') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_labotests_card_title_color_help') ?></small>
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_labotests_card_header_title_weight',
                            'styleField'  => 'ui_labotests_card_header_title_style',
                            'weightId'    => 'ui_labotests_card_header_title_weight',
                            'styleId'     => 'ui_labotests_card_header_title_style',
                            'weightVal'   => $fwLabCard,
                            'styleVal'    => $fsLabCard,
                        ]) ?>
                    </div>
                </div>
                <script>
                (function () {
                    var input = document.getElementById('ui_labotests_card_header_title_color');
                    var wSel = document.getElementById('ui_labotests_card_header_title_weight');
                    var sSel = document.getElementById('ui_labotests_card_header_title_style');
                    var preview = document.querySelector('.config-labotests-card-title-preview');
                    if (!preview) return;
                    function syncPreview() {
                        if (input) preview.style.setProperty('color', input.value, 'important');
                        if (wSel) preview.style.setProperty('font-weight', wSel.value, 'important');
                        if (sSel) preview.style.setProperty('font-style', sSel.value, 'important');
                    }
                    if (input) input.addEventListener('input', syncPreview);
                    if (wSel) wSel.addEventListener('change', syncPreview);
                    if (sSel) sSel.addEventListener('change', syncPreview);
                })();
                </script>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header py-3 border-bottom bg-light">
                <h5 class="mb-0 fw-semibold text-dark"><i class="fa-solid fa-angles-right me-2"></i><?= lang('Config.config_style_section_pagination') ?></h5>
                <p class="mb-0 mt-1 small text-muted"><?= lang('Config.config_style_section_pagination_hint') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div id="config-pagination-preview-wrap" class="rounded-3 p-3 border bg-white mb-2 d-inline-block" style="--ui-pagination-link-color: <?= esc($pgLinkColor, 'attr') ?>; --ui-pagination-font-weight: <?= esc($fwPagination, 'attr') ?>; --ui-pagination-font-style: <?= esc($fsPagination, 'attr') ?>; --ui-pagination-active-bg: <?= esc($pgActiveBg, 'attr') ?>; --ui-pagination-active-color: <?= esc($pgActiveColor, 'attr') ?>;">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item"><a class="page-link" href="#" onclick="return false;">1</a></li>
                            <li class="page-item active" aria-current="page"><span class="page-link">2</span></li>
                            <li class="page-item"><a class="page-link" href="#" onclick="return false;">3</a></li>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        </ul>
                    </div>
                    <p class="small text-muted mb-0"><?= lang('Config.config_style_pagination_preview_help') ?></p>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label" for="ui_pagination_link_color"><?= lang('Config.config_style_pagination_link_color') ?></label>
                        <input type="color" name="ui_pagination_link_color" id="ui_pagination_link_color" value="<?= esc($pgLinkColor) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_pagination_link_color') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_pagination_link_color_help') ?></small>
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_pagination_link_weight',
                            'styleField'  => 'ui_pagination_link_style',
                            'weightId'    => 'ui_pagination_link_weight',
                            'styleId'     => 'ui_pagination_link_style',
                            'weightVal'   => $fwPagination,
                            'styleVal'    => $fsPagination,
                        ]) ?>
                    </div>
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label" for="ui_pagination_active_bg"><?= lang('Config.config_style_pagination_active_bg') ?></label>
                        <input type="color" name="ui_pagination_active_bg" id="ui_pagination_active_bg" value="<?= esc($pgActiveBg) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_pagination_active_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_pagination_active_bg_help') ?></small>
                        <label class="form-label mt-3" for="ui_pagination_active_color"><?= lang('Config.config_style_pagination_active_color') ?></label>
                        <input type="color" name="ui_pagination_active_color" id="ui_pagination_active_color" value="<?= esc($pgActiveColor) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_pagination_active_color') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_pagination_active_color_help') ?></small>
                    </div>
                </div>
                <script>
                (function () {
                    var wrap = document.getElementById('config-pagination-preview-wrap');
                    var input = document.getElementById('ui_pagination_link_color');
                    var wSel = document.getElementById('ui_pagination_link_weight');
                    var sSel = document.getElementById('ui_pagination_link_style');
                    var activeBg = document.getElementById('ui_pagination_active_bg');
                    var activeFg = document.getElementById('ui_pagination_active_color');
                    if (!wrap) return;
                    function sync() {
                        if (input) wrap.style.setProperty('--ui-pagination-link-color', input.value);
                        if (wSel) wrap.style.setProperty('--ui-pagination-font-weight', wSel.value);
                        if (sSel) wrap.style.setProperty('--ui-pagination-font-style', sSel.value);
                        if (activeBg) wrap.style.setProperty('--ui-pagination-active-bg', activeBg.value);
                        if (activeFg) wrap.style.setProperty('--ui-pagination-active-color', activeFg.value);
                    }
                    if (input) input.addEventListener('input', sync);
                    if (wSel) wSel.addEventListener('change', sync);
                    if (sSel) sSel.addEventListener('change', sync);
                    if (activeBg) activeBg.addEventListener('input', sync);
                    if (activeFg) activeFg.addEventListener('input', sync);
                })();
                </script>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-list me-2"></i><?= lang('Config.config_style_section_menu') ?></h5>
                <p class="mb-0 mt-1 small opacity-90"><?= lang('Config.config_style_menu_nav_hint') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="d-flex rounded-3 border overflow-hidden shadow-sm mb-3" style="min-height: 6rem;">
                        <div class="p-2 small" style="width: 38%; background: <?= $pvSidebarBg ?>; color: <?= esc($pvSidebarFgWire, 'attr') ?>;">
                            <div class="fw-semibold"><?= lang('Config.config_style_section_menu') ?></div>
                            <div>• Link</div>
                        </div>
                        <div class="flex-grow-1 bg-white p-2 small config-tab-estilo__on-white"><?= lang('Config.config_style_section_page') ?></div>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-sm-4">
                            <div class="rounded border p-2 text-center" style="background: <?= $pvSidebarHoverBox ?>; color: <?= esc($pvHoverLabelFg, 'attr') ?>;"><?= lang('Config.config_style_sidebar_hover') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="rounded border p-2 text-center" style="background: <?= $pvSidebarActiveBox ?>; color: <?= esc($pvActiveLabelFg, 'attr') ?>;"><?= lang('Config.config_style_sidebar_active') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="rounded border p-2 text-center bg-white small fw-semibold" style="color: <?= esc($pvSidebarOnWhiteFg, 'attr') ?>;"><?= lang('Config.config_style_sidebar_link') ?></div>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><?= lang('Config.config_style_preview_sidebar_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_sidebar_bg"><?= lang('Config.config_style_sidebar_bg') ?></label>
                        <input type="hidden" name="ui_sidebar_bg_transparent" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_sidebar_bg_transparent" id="ui_sidebar_bg_transparent" value="1" autocomplete="off" <?= $sidebarBgTransparent ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_sidebar_bg_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_sidebar_bg" id="ui_sidebar_bg" value="<?= esc($sidebarBgTransparent ? '#f8f9fa' : $sidebarBgStored) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_sidebar_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_sidebar_bg_help') ?></small>
                        <small class="text-muted d-block"><?= lang('Config.config_style_sidebar_transparent_help') ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_sidebar_link') ?></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_sidebar_link_mode" id="ui_sl_theme" value="theme" <?= !$linkIsCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_sl_theme"><?= lang('Config.config_style_use_theme_primary') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_sidebar_link_mode" id="ui_sl_custom" value="custom" <?= $linkIsCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_sl_custom"><?= lang('Config.config_style_custom_color') ?></label>
                        </div>
                        <input type="color" name="ui_sidebar_link_custom" id="ui_sidebar_link_custom" value="<?= esc($linkPick) ?>" class="form-control form-control-color">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_sidebar_link_help') ?></small>
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_sidebar_link_weight',
                            'styleField'  => 'ui_sidebar_link_style',
                            'weightId'    => 'ui_sidebar_link_weight',
                            'styleId'     => 'ui_sidebar_link_style',
                            'weightVal'   => $fwSidebarL,
                            'styleVal'    => $fsSidebarL,
                        ]) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_sidebar_hover') ?></label>
                        <input type="hidden" name="ui_sidebar_hover_default" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_sidebar_hover_default" id="ui_sidebar_hover_default" value="1" autocomplete="off" <?= $hoverBgVal === '' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_sidebar_hover_default"><?= lang('Config.config_style_hover_auto') ?></label>
                        </div>
                        <input type="color" name="ui_sidebar_hover_bg" id="ui_sidebar_hover_bg" value="<?= esc($hoverBgVal !== '' ? $hoverBgVal : '#dee2e6') ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_sidebar_active') ?></label>
                        <input type="hidden" name="ui_sidebar_active_default" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_sidebar_active_default" id="ui_sidebar_active_default" value="1" autocomplete="off" <?= $activeBgVal === '' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_sidebar_active_default"><?= lang('Config.config_style_active_auto') ?></label>
                        </div>
                        <input type="color" name="ui_sidebar_active_bg" id="ui_sidebar_active_bg" value="<?= esc($activeBgVal !== '' ? $activeBgVal : '#ced4da') ?>" class="form-control form-control-color">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header py-3 bg-body-secondary border-bottom border-primary border-3">
                <h5 class="mb-0 fw-semibold text-primary"><i class="fa-solid fa-table-columns me-2"></i><?= lang('Config.config_style_section_page') ?></h5>
                <p class="mb-0 mt-1 small text-muted"><?= lang('Config.config_style_page_section_hint') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 border p-3 shadow-sm" style="background: <?= $pvMainBg ?>; color: <?= esc($pvMainFgWire, 'attr') ?>;">
                        <p class="mb-2 small"><?= lang('Config.config_style_body_text') ?> — <?= lang('Config.config_style_main_bg') ?></p>
                        <a href="#" class="small" style="color: <?= esc($pvMainLinkWire, 'attr') ?>; pointer-events: none;"><?= lang('Config.config_style_link_color') ?></a>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><?= lang('Config.config_style_preview_page_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_body_text_color"><?= lang('Config.config_style_body_text') ?></label>
                        <input type="color" name="ui_body_text_color" id="ui_body_text_color" value="<?= esc($config['ui_body_text_color'] ?? '#212529') ?>" class="form-control form-control-color">
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_body_text_weight',
                            'styleField'  => 'ui_body_text_style',
                            'weightId'    => 'ui_body_text_weight',
                            'styleId'     => 'ui_body_text_style',
                            'weightVal'   => $fwBody,
                            'styleVal'    => $fsBody,
                        ]) ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_main_bg"><?= lang('Config.config_style_main_bg') ?></label>
                        <input type="hidden" name="ui_main_bg_transparent" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_main_bg_transparent" id="ui_main_bg_transparent" value="1" autocomplete="off" <?= $mainBgTransparent ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_main_bg_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_main_bg" id="ui_main_bg" value="<?= esc($mainBgTransparent ? '#ffffff' : $mainBgStored) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_main_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_main_bg_transparent_help') ?></small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_link_color') ?></label>
                        <input type="hidden" name="ui_link_default" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_link_default" id="ui_link_default" value="1" autocomplete="off" <?= $linkUi === '' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_link_default"><?= lang('Config.config_style_link_bootstrap') ?></label>
                        </div>
                        <input type="color" name="ui_link_color" id="ui_link_color" value="<?= esc($linkUiVal) ?>" class="form-control form-control-color">
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_link_weight',
                            'styleField'  => 'ui_link_style',
                            'weightId'    => 'ui_link_weight',
                            'styleId'     => 'ui_link_style',
                            'weightVal'   => $fwLink,
                            'styleVal'    => $fsLink,
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-secondary text-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="fa-regular fa-copyright me-2"></i><?= lang('Config.config_style_section_footer') ?></h5>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 border py-3 px-3 small shadow-sm d-flex flex-wrap align-items-center" style="background: <?= $pvFooterBg ?>; color: <?= esc($pvFooterFgWire, 'attr') ?>; justify-content: <?= esc($pvFooterJustifyPreview, 'attr') ?>;">
                        <span>© <?= date('Y') ?> <?= lang('Config.config_company') ?> — <?= lang('Config.config_style_section_footer') ?></span>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><?= lang('Config.config_style_preview_footer_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_footer_bg"><?= lang('Config.config_style_footer_bg') ?></label>
                        <input type="hidden" name="ui_footer_bg_transparent" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_footer_bg_transparent" id="ui_footer_bg_transparent" value="1" autocomplete="off" <?= $footerBgTransparent ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_footer_bg_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_footer_bg" id="ui_footer_bg" value="<?= esc($footerBgTransparent ? '#f8f9fa' : $footerBgStored) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_footer_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_footer_transparent_help') ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_footer_text_color"><?= lang('Config.config_style_footer_text') ?></label>
                        <input type="color" name="ui_footer_text_color" id="ui_footer_text_color" value="<?= esc($config['ui_footer_text_color'] ?? '#6c757d') ?>" class="form-control form-control-color">
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_footer_text_weight',
                            'styleField'  => 'ui_footer_text_style',
                            'weightId'    => 'ui_footer_text_weight',
                            'styleId'     => 'ui_footer_text_style',
                            'weightVal'   => $fwFooter,
                            'styleVal'    => $fsFooter,
                        ]) ?>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label d-block"><?= lang('Config.config_style_footer_align') ?></label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="ui_footer_text_align" id="ui_footer_align_l" value="left" autocomplete="off" <?= $footerAlignSaved === 'left' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ui_footer_align_l"><?= lang('Config.config_style_footer_align_left') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="ui_footer_text_align" id="ui_footer_align_c" value="center" autocomplete="off" <?= $footerAlignSaved === 'center' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ui_footer_align_c"><?= lang('Config.config_style_footer_align_center') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="ui_footer_text_align" id="ui_footer_align_r" value="right" autocomplete="off" <?= $footerAlignSaved === 'right' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ui_footer_align_r"><?= lang('Config.config_style_footer_align_right') ?></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header py-3 border-start border-5 border-danger bg-danger bg-opacity-10">
                <h5 class="mb-0 fw-semibold text-danger"><i class="fa-regular fa-square me-2"></i><?= lang('Config.config_style_card_components') ?></h5>
                <p class="mb-0 mt-1 small text-muted"><?= lang('Config.config_style_card_section_hint') ?></p>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="d-flex flex-wrap gap-3 align-items-end justify-content-center">
                        <div class="text-center">
                            <div id="config-card-radius-demo-0" class="bg-white border shadow-sm mx-auto mb-1" style="width: 5rem; height: 3.25rem; border-radius: <?= (int) $pvCardRadius ?>px;"></div>
                            <span id="ui_card_radius_demo_label" class="small text-muted"><?= (int) $pvCardRadius ?> px</span>
                        </div>
                        <div class="text-center">
                            <div id="config-card-radius-demo-1" class="bg-primary bg-opacity-10 border border-primary mx-auto mb-1" style="width: 5rem; height: 3.25rem; border-radius: <?= max(0, (int) $pvCardRadius - 2) ?>px;"></div>
                            <span class="small text-muted"><?= lang('Config.config_style_card_components') ?></span>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-3 text-center"><?= lang('Config.config_style_preview_cards_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_card_radius"><?= lang('Config.config_style_card_radius') ?></label>
                        <input type="range" name="ui_card_radius" id="ui_card_radius" class="form-range" min="0" max="24" value="<?= (int) ($config['ui_card_radius'] ?? 8) ?>" oninput="(function(v){v=parseInt(v,10)||0;document.getElementById('ui_card_radius_out').textContent=v;var a=document.getElementById('config-card-radius-demo-0'),b=document.getElementById('config-card-radius-demo-1'),l=document.getElementById('ui_card_radius_demo_label');if(a)a.style.borderRadius=v+'px';if(b)b.style.borderRadius=Math.max(0,v-2)+'px';if(l)l.textContent=v+' px';})(this.value)">
                        <div class="small text-muted"><?= lang('Config.config_style_card_radius_val') ?> <strong id="ui_card_radius_out"><?= (int) ($config['ui_card_radius'] ?? 8) ?></strong> px</div>
                    </div>
                </div>
                <hr class="text-muted">
                <h6 class="fw-semibold mb-3"><?= lang('Config.config_style_card_border_section') ?></h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_card_border_width"><?= lang('Config.config_style_card_border_width') ?></label>
                        <input type="range" name="ui_card_border_width" id="ui_card_border_width" class="form-range" min="0" max="8" value="<?= $uiCardBw ?>">
                        <div class="small text-muted"><span id="ui_card_border_width_out"><?= $uiCardBw ?></span> px</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_card_border_color"><?= lang('Config.config_style_card_border_color') ?></label>
                        <input type="color" name="ui_card_border_color" id="ui_card_border_color" value="<?= esc($config['ui_card_border_color'] ?? '#dee2e6') ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_card_border_sides"><?= lang('Config.config_style_card_border_sides') ?></label>
                        <?= form_dropdown('ui_card_border_sides', $borderSideOpts, $uiCardSides, 'class="form-select" id="ui_card_border_sides" autocomplete="off"') ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_card_shadow"><?= lang('Config.config_style_card_shadow') ?></label>
                        <?= form_dropdown('ui_card_shadow', $shadowOpts, $uiCardSh, 'class="form-select" id="ui_card_shadow" autocomplete="off"') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fa-solid fa-floppy-disk me-2"></i><?= lang('Config.config_save_btn') ?></button>
        </div>
        <?= form_close() ?>
    </div>

<script>
(function() {
    function toggleHdr() {
        var custom = document.getElementById('ui_hdr_custom').checked;
        var el = document.getElementById('ui_header_bg_custom');
        if (el) el.disabled = !custom;
    }
    function toggleSl() {
        var custom = document.getElementById('ui_sl_custom').checked;
        document.getElementById('ui_sidebar_link_custom').disabled = !custom;
    }
    var h1 = document.getElementById('ui_hdr_theme');
    var h2 = document.getElementById('ui_hdr_custom');
    var h3 = document.getElementById('ui_hdr_transparent');
    if (h1) h1.addEventListener('change', toggleHdr);
    if (h2) h2.addEventListener('change', toggleHdr);
    if (h3) h3.addEventListener('change', toggleHdr);
    var s1 = document.getElementById('ui_sl_theme');
    var s2 = document.getElementById('ui_sl_custom');
    if (s1) s1.addEventListener('change', toggleSl);
    if (s2) s2.addEventListener('change', toggleSl);
    toggleHdr();
    toggleSl();

    function toggleBgTransparent(checkId, colorId) {
        var c = document.getElementById(checkId);
        var p = document.getElementById(colorId);
        if (p) p.disabled = c && c.checked;
    }
    var sb = document.getElementById('ui_sidebar_bg_transparent');
    var mb = document.getElementById('ui_main_bg_transparent');
    var fb = document.getElementById('ui_footer_bg_transparent');
    if (sb) sb.addEventListener('change', function() { toggleBgTransparent('ui_sidebar_bg_transparent', 'ui_sidebar_bg'); });
    if (mb) mb.addEventListener('change', function() { toggleBgTransparent('ui_main_bg_transparent', 'ui_main_bg'); });
    if (fb) fb.addEventListener('change', function() { toggleBgTransparent('ui_footer_bg_transparent', 'ui_footer_bg'); });
    toggleBgTransparent('ui_sidebar_bg_transparent', 'ui_sidebar_bg');
    toggleBgTransparent('ui_main_bg_transparent', 'ui_main_bg');
    toggleBgTransparent('ui_footer_bg_transparent', 'ui_footer_bg');

    function toggleHover() {
        var d = document.getElementById('ui_sidebar_hover_default').checked;
        document.getElementById('ui_sidebar_hover_bg').disabled = d;
    }
    function toggleActive() {
        var d = document.getElementById('ui_sidebar_active_default').checked;
        document.getElementById('ui_sidebar_active_bg').disabled = d;
    }
    function toggleLink() {
        var d = document.getElementById('ui_link_default').checked;
        document.getElementById('ui_link_color').disabled = d;
    }
    var hd = document.getElementById('ui_sidebar_hover_default');
    var ad = document.getElementById('ui_sidebar_active_default');
    var ld = document.getElementById('ui_link_default');
    if (hd) hd.addEventListener('change', toggleHover);
    if (ad) ad.addEventListener('change', toggleActive);
    if (ld) ld.addEventListener('change', toggleLink);
    toggleHover();
    toggleActive();
    toggleLink();

    function toggleBtnBg() {
        var custom = document.getElementById('ui_btn_bg_custom') && document.getElementById('ui_btn_bg_custom').checked;
        var el = document.getElementById('ui_btn_primary_bg_custom');
        if (el) el.disabled = !custom;
    }
    function toggleBtnHov() {
        var custom = document.getElementById('ui_btn_hov_custom') && document.getElementById('ui_btn_hov_custom').checked;
        var el = document.getElementById('ui_btn_primary_hover_custom');
        if (el) el.disabled = !custom;
    }
    function toggleBtnBorderDeps() {
        var r = document.getElementById('ui_btn_border_width');
        var w = r ? (parseInt(r.value, 10) || 0) : 0;
        var c = document.getElementById('ui_btn_border_color');
        var s = document.getElementById('ui_btn_border_sides');
        if (c) c.disabled = w < 1;
        if (s) s.disabled = w < 1;
    }
    function toggleCardBorderDeps() {
        var r = document.getElementById('ui_card_border_width');
        var w = r ? (parseInt(r.value, 10) || 0) : 0;
        var c = document.getElementById('ui_card_border_color');
        var s = document.getElementById('ui_card_border_sides');
        if (c) c.disabled = w < 1;
        if (s) s.disabled = w < 1;
        var o = document.getElementById('ui_card_border_width_out');
        if (o && r) o.textContent = String(w);
    }
    var b1 = document.getElementById('ui_btn_bg_theme');
    var b2 = document.getElementById('ui_btn_bg_custom');
    if (b1) b1.addEventListener('change', toggleBtnBg);
    if (b2) b2.addEventListener('change', toggleBtnBg);
    var bh1 = document.getElementById('ui_btn_hov_auto');
    var bh2 = document.getElementById('ui_btn_hov_custom');
    if (bh1) bh1.addEventListener('change', toggleBtnHov);
    if (bh2) bh2.addEventListener('change', toggleBtnHov);
    var bw = document.getElementById('ui_btn_border_width');
    if (bw) {
        bw.addEventListener('input', function() {
            var o = document.getElementById('ui_btn_border_width_out');
            if (o) o.textContent = this.value;
            toggleBtnBorderDeps();
        });
    }
    var cw = document.getElementById('ui_card_border_width');
    if (cw) {
        cw.addEventListener('input', toggleCardBorderDeps);
    }
    toggleBtnBg();
    toggleBtnHov();
    toggleBtnBorderDeps();
    toggleCardBorderDeps();

    // Duplicar control de color de texto de barra superior en sección "Marca y degradado".
    var hdrTextMain = document.getElementById('ui_header_text_color');
    var hdrTextTheme = document.getElementById('ui_header_text_color_theme');
    if (hdrTextMain && hdrTextTheme) {
        hdrTextTheme.value = hdrTextMain.value || hdrTextTheme.value;
        var syncHdrMain = function() { hdrTextMain.value = hdrTextTheme.value; };
        var syncHdrTheme = function() { hdrTextTheme.value = hdrTextMain.value; };
        hdrTextTheme.addEventListener('input', syncHdrMain);
        hdrTextMain.addEventListener('input', syncHdrTheme);
    }
})();
</script>
