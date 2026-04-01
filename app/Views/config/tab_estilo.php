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
$headerBgSaved   = trim((string) ($config['ui_header_bg'] ?? ''));
$headerIsCustom  = $headerBgSaved !== '';
$headerPick      = $headerIsCustom ? $headerBgSaved : (string) ($config['theme_color'] ?? '#FF7218');
$linkSaved       = trim((string) ($config['ui_sidebar_link_color'] ?? ''));
$linkIsCustom    = $linkSaved !== '';
$linkPick        = $linkIsCustom ? $linkSaved : (string) ($config['theme_color'] ?? '#0d6efd');
$linkUi          = trim((string) ($config['ui_link_color'] ?? ''));
$linkUiVal       = $linkUi !== '' ? $linkUi : '#0d6efd';
$hoverBgVal      = trim((string) ($config['ui_sidebar_hover_bg'] ?? ''));
$activeBgVal     = trim((string) ($config['ui_sidebar_active_bg'] ?? ''));

$pvHeaderText         = esc($config['ui_header_text_color'] ?? '#ffffff', 'attr');
$pvSidebarBg          = esc($config['ui_sidebar_bg'] ?? '#f8f9fa', 'attr');
$pvSidebarLink        = esc($linkPick, 'attr');
$pvSidebarHoverBox    = esc($hoverBgVal !== '' ? $hoverBgVal : '#e9ecef', 'attr');
$pvSidebarActiveBox   = esc($activeBgVal !== '' ? $activeBgVal : '#dee2e6', 'attr');
$pvBodyText           = esc($config['ui_body_text_color'] ?? '#212529', 'attr');
$pvMainBg             = esc($config['ui_main_bg'] ?? '#ffffff', 'attr');
$pvLinkPreview        = esc($linkUi !== '' ? $linkUi : '#0d6efd', 'attr');
$pvFooterBg           = esc($config['ui_footer_bg'] ?? '#f8f9fa', 'attr');
$pvFooterText         = esc($config['ui_footer_text_color'] ?? '#6c757d', 'attr');
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
$pvBarStyle           = $headerIsCustom
    ? 'background-color:' . esc($headerPick, 'attr') . ';color:' . $pvHeaderText . ';'
    : 'background:linear-gradient(90deg,' . $previewTheme . ',' . $previewGrad . ');color:' . $pvHeaderText . ';';

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
?>
    <div class="tab-pane fade <?= $activeTab === 'estilo' ? 'show active' : '' ?>" id="tab-estilo" role="tabpanel">
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
                        <div class="config-layout-map__side border-end p-2 small" style="width: 32%; min-width: 7.5rem; background: <?= $pvSidebarBg ?>; color: <?= $pvSidebarLink ?>;">
                            <div class="fw-semibold mb-1"><?= lang('Config.config_style_section_menu') ?></div>
                            <div class="opacity-90">• <?= lang('Config.config_style_preview_menu_left') ?></div>
                            <div class="opacity-75 small">• …</div>
                        </div>
                        <div class="config-layout-map__main flex-grow-1 p-3 small" style="background: <?= $pvMainBg ?>; color: <?= $pvBodyText ?>;">
                            <div class="fw-semibold mb-1"><?= lang('Config.config_style_section_page') ?></div>
                            <p class="mb-1 small"><?= lang('Config.config_style_font_size_main_preview') ?></p>
                            <a href="#" class="small" style="color: <?= $pvLinkPreview ?>; pointer-events: none; text-decoration: underline;"><?= lang('Config.config_style_link_color') ?></a>
                        </div>
                    </div>
                    <div class="config-layout-map__footer px-3 py-2 small border-top d-flex flex-wrap align-items-center" style="background: <?= $pvFooterBg ?>; color: <?= $pvFooterText ?>; justify-content: <?= esc($pvFooterJustifyPreview, 'attr') ?>;">
                        <span>© <?= date('Y') ?> · <?= lang('Config.config_style_section_footer') ?></span>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-3"><?= lang('Config.config_style_layout_map_help') ?></p>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-palette me-2"></i><?= lang('Config.config_style_theme_section') ?></h5>
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
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <?= form_label(lang('Config.config_theme_color'), 'theme_color', ['class' => 'form-label fw-semibold']) ?>
                        <?= form_dropdown('theme_palette_select', $selectThemePal, $selectedThemeHex, 'id="theme_palette_select" class="form-select mb-2" autocomplete="off"') ?>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="color" name="theme_color" id="theme_color" value="<?= esc($config['theme_color'] ?? '#FF7218') ?>" class="form-control form-control-color config-color-picker" autocomplete="off">
                            <input type="text" id="theme_color_hex" value="<?= esc($config['theme_color'] ?? '#FF7218') ?>" class="form-control config-color-hex" readonly autocomplete="off">
                        </div>
                        <small class="text-muted"><?= lang('Config.config_theme_color_custom_hint') ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <?= form_label(lang('Config.config_theme_gradient_end'), 'theme_gradient_end', ['class' => 'form-label fw-semibold']) ?>
                        <?= form_dropdown('theme_gradient_palette_select', $selectThemePal, $selectedGradHex, 'id="theme_gradient_palette_select" class="form-select mb-2" autocomplete="off"') ?>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="color" name="theme_gradient_end" id="theme_gradient_end" value="<?= esc($config['theme_gradient_end'] ?? '#4f46e5') ?>" class="form-control form-control-color config-color-picker" autocomplete="off">
                            <input type="text" id="theme_gradient_end_hex" value="<?= esc($config['theme_gradient_end'] ?? '#4f46e5') ?>" class="form-control config-color-hex" readonly autocomplete="off">
                        </div>
                        <small class="text-muted d-block"><?= lang('Config.config_theme_gradient_help') ?></small>
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
                            <div class="border-end text-center small py-2 px-1" style="width: 26%; background: <?= $pvSidebarBg ?>; color: <?= $pvSidebarLink ?>;"><?= lang('Config.config_style_section_menu') ?></div>
                            <div class="flex-grow-1 p-2 small text-center" style="background-color: <?= $pvMainBg ?>; color: <?= $pvBodyText ?>;">
                                <div class="fw-bold small"><?= lang('Config.config_style_font_size_heading') ?></div>
                                <?= lang('Config.config_style_section_page') ?>
                            </div>
                        </div>
                        <div class="text-center small py-1 border-top text-muted" style="background: <?= $pvFooterBg ?>; color: <?= $pvFooterText ?>;"><?= lang('Config.config_style_section_footer') ?></div>
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
                            <div class="config-zone-preview__side border-end" style="font-size: 0.8125rem; color: <?= $pvSidebarLink ?>; background-color: <?= $pvSidebarBg ?>;">≡ <?= lang('Config.config_style_section_menu') ?></div>
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
                            <input class="form-check-input" type="radio" name="ui_header_mode" id="ui_hdr_theme" value="theme" <?= !$headerIsCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_hdr_theme"><?= lang('Config.config_style_use_theme_primary') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_header_mode" id="ui_hdr_custom" value="custom" <?= $headerIsCustom ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_hdr_custom"><?= lang('Config.config_style_custom_color') ?></label>
                        </div>
                        <input type="color" name="ui_header_bg_custom" id="ui_header_bg_custom" value="<?= esc($headerPick) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_header_bg') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_header_text_color"><?= lang('Config.config_style_header_text') ?></label>
                        <input type="color" name="ui_header_text_color" id="ui_header_text_color" value="<?= esc($config['ui_header_text_color'] ?? '#ffffff') ?>" class="form-control form-control-color">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-list me-2"></i><?= lang('Config.config_style_section_menu') ?></h5>
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="d-flex rounded-3 border overflow-hidden shadow-sm mb-3" style="min-height: 6rem;">
                        <div class="p-2 small" style="width: 38%; background: <?= $pvSidebarBg ?>; color: <?= $pvSidebarLink ?>;">
                            <div class="fw-semibold"><?= lang('Config.config_style_section_menu') ?></div>
                            <div>• Link</div>
                        </div>
                        <div class="flex-grow-1 bg-white p-2 small text-muted"><?= lang('Config.config_style_section_page') ?></div>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-sm-4">
                            <div class="rounded border p-2 text-center" style="background: <?= $pvSidebarHoverBox ?>;"><?= lang('Config.config_style_sidebar_hover') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="rounded border p-2 text-center" style="background: <?= $pvSidebarActiveBox ?>;"><?= lang('Config.config_style_sidebar_active') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="rounded border p-2 text-center bg-white small fw-semibold" style="color: <?= $pvSidebarLink ?>;"><?= lang('Config.config_style_sidebar_link') ?></div>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><?= lang('Config.config_style_preview_sidebar_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_sidebar_bg"><?= lang('Config.config_style_sidebar_bg') ?></label>
                        <input type="color" name="ui_sidebar_bg" id="ui_sidebar_bg" value="<?= esc($config['ui_sidebar_bg'] ?? '#f8f9fa') ?>" class="form-control form-control-color">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_sidebar_bg_help') ?></small>
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
            </div>
            <div class="card-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 border p-3 shadow-sm" style="background: <?= $pvMainBg ?>; color: <?= $pvBodyText ?>;">
                        <p class="mb-2 small"><?= lang('Config.config_style_body_text') ?> — <?= lang('Config.config_style_main_bg') ?></p>
                        <a href="#" class="small" style="color: <?= $pvLinkPreview ?>; pointer-events: none;"><?= lang('Config.config_style_link_color') ?></a>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><?= lang('Config.config_style_preview_page_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_body_text_color"><?= lang('Config.config_style_body_text') ?></label>
                        <input type="color" name="ui_body_text_color" id="ui_body_text_color" value="<?= esc($config['ui_body_text_color'] ?? '#212529') ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_main_bg"><?= lang('Config.config_style_main_bg') ?></label>
                        <input type="color" name="ui_main_bg" id="ui_main_bg" value="<?= esc($config['ui_main_bg'] ?? '#ffffff') ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_link_color') ?></label>
                        <input type="hidden" name="ui_link_default" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_link_default" id="ui_link_default" value="1" autocomplete="off" <?= $linkUi === '' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_link_default"><?= lang('Config.config_style_link_bootstrap') ?></label>
                        </div>
                        <input type="color" name="ui_link_color" id="ui_link_color" value="<?= esc($linkUiVal) ?>" class="form-control form-control-color">
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
                    <div class="rounded-3 border py-3 px-3 small shadow-sm d-flex flex-wrap align-items-center" style="background: <?= $pvFooterBg ?>; color: <?= $pvFooterText ?>; justify-content: <?= esc($pvFooterJustifyPreview, 'attr') ?>;">
                        <span>© <?= date('Y') ?> <?= lang('Config.config_company') ?> — <?= lang('Config.config_style_section_footer') ?></span>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><?= lang('Config.config_style_preview_footer_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_footer_bg"><?= lang('Config.config_style_footer_bg') ?></label>
                        <input type="color" name="ui_footer_bg" id="ui_footer_bg" value="<?= esc($config['ui_footer_bg'] ?? '#f8f9fa') ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_footer_text_color"><?= lang('Config.config_style_footer_text') ?></label>
                        <input type="color" name="ui_footer_text_color" id="ui_footer_text_color" value="<?= esc($config['ui_footer_text_color'] ?? '#6c757d') ?>" class="form-control form-control-color">
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
        document.getElementById('ui_header_bg_custom').disabled = !custom;
    }
    function toggleSl() {
        var custom = document.getElementById('ui_sl_custom').checked;
        document.getElementById('ui_sidebar_link_custom').disabled = !custom;
    }
    var h1 = document.getElementById('ui_hdr_theme');
    var h2 = document.getElementById('ui_hdr_custom');
    if (h1) h1.addEventListener('change', toggleHdr);
    if (h2) h2.addEventListener('change', toggleHdr);
    var s1 = document.getElementById('ui_sl_theme');
    var s2 = document.getElementById('ui_sl_custom');
    if (s1) s1.addEventListener('change', toggleSl);
    if (s2) s2.addEventListener('change', toggleSl);
    toggleHdr();
    toggleSl();

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
})();
</script>
