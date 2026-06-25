<?php
/**
 * Pestaña Apariencia (fragmento incluido desde config/manage).
 *
 * @var array<string, mixed> $config
 * @var string               $activeTab
 * @var array<string, string> $theme_palette
 */

helper('layout');

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
$breadcrumbBgSaved = trim((string) ($config['ui_breadcrumb_bg'] ?? $config['ui_header_bg'] ?? ''));
$breadcrumbMode    = match (true) {
    strtolower($breadcrumbBgSaved) === 'transparent' => 'transparent',
    $breadcrumbBgSaved === ''                        => 'theme',
    default                                          => 'custom',
};
$breadcrumbIsCustom = $breadcrumbMode === 'custom';
$breadcrumbPick     = (string) ($config['theme_color'] ?? '#FF7218');
if ($breadcrumbIsCustom && preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $breadcrumbBgSaved)) {
    $breadcrumbPick = strtoupper(strlen($breadcrumbBgSaved) === 4
        ? '#' . $breadcrumbBgSaved[1] . $breadcrumbBgSaved[1] . $breadcrumbBgSaved[2] . $breadcrumbBgSaved[2] . $breadcrumbBgSaved[3] . $breadcrumbBgSaved[3]
        : $breadcrumbBgSaved);
}
$breadcrumbPick = LayoutService::htmlColorPickerValue($breadcrumbPick, '#FF7218');
$linkSaved       = trim((string) ($config['ui_sidebar_link_color'] ?? ''));
$linkIsCustom    = $linkSaved !== '';
$linkPick        = $linkIsCustom ? $linkSaved : (string) ($config['theme_color'] ?? '#0d6efd');
$linkPick        = LayoutService::htmlColorPickerValue($linkPick, '#0d6efd');
$linkUi          = trim((string) ($config['ui_link_color'] ?? ''));
$linkUiVal       = $linkUi !== '' ? $linkUi : '#0d6efd';
$linkUiVal       = LayoutService::htmlColorPickerValue($linkUiVal, '#0d6efd');
$hoverBgVal      = trim((string) ($config['ui_sidebar_hover_bg'] ?? ''));
$activeBgVal     = trim((string) ($config['ui_sidebar_active_bg'] ?? ''));

$sidebarBgStored = trim((string) ($config['ui_sidebar_bg'] ?? '#f8f9fa'));
$mainBgStored    = trim((string) ($config['ui_main_bg'] ?? '#ffffff'));
$bodyBgStored    = trim((string) ($config['ui_body_bg'] ?? ''));
if ($bodyBgStored === '') {
    $bodyBgStored = $mainBgStored;
}
$footerBgStored  = trim((string) ($config['ui_footer_bg'] ?? '#f8f9fa'));
$sidebarBgTransparent = strtolower($sidebarBgStored) === 'transparent';
$bodyBgTransparent    = strtolower($bodyBgStored) === 'transparent';
$mainBgTransparent    = strtolower($mainBgStored) === 'transparent';
$footerBgTransparent  = strtolower($footerBgStored) === 'transparent';
$pvSidebarBg          = $sidebarBgTransparent ? 'transparent' : esc($sidebarBgStored, 'attr');
$pvSidebarHoverBox    = esc($hoverBgVal !== '' ? $hoverBgVal : '#e9ecef', 'attr');
$pvSidebarActiveBox   = esc($activeBgVal !== '' ? $activeBgVal : '#dee2e6', 'attr');
$pvBodyBg             = $bodyBgTransparent ? 'transparent' : esc($bodyBgStored, 'attr');
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
$bodyBgRaw       = $bodyBgTransparent ? '#e9ecef' : $bodyBgStored;
$mainBgRaw       = $mainBgTransparent ? ($bodyBgTransparent ? '#e9ecef' : $bodyBgStored) : $mainBgStored;
$footerBgRaw     = $footerBgTransparent ? '#e9ecef' : $footerBgStored;
$bodyTextRaw     = trim((string) ($config['ui_body_text_color'] ?? '#212529'));
$footerTextRaw   = trim((string) ($config['ui_footer_text_color'] ?? '#6c757d'));
$navbarTextRaw   = trim((string) ($config['ui_navbar_text_color'] ?? $config['ui_header_text_color'] ?? '#ffffff'));
$breadcrumbTextRaw = trim((string) ($config['ui_breadcrumb_text_color'] ?? $config['ui_header_text_color'] ?? '#ffffff'));
$headerDatetimeRaw = trim((string) ($config['ui_header_datetime_color'] ?? ''));
$headerDatetimeUseDefault = $headerDatetimeRaw === '';
$headerDatetimePick = $headerDatetimeUseDefault ? $navbarTextRaw : $headerDatetimeRaw;
$headerDatetimePick = LayoutService::htmlColorPickerValue($headerDatetimePick, '#ffffff');
$headerDtFormatKey = LayoutService::normalizeHeaderDatetimeFormat((string) ($config['ui_header_datetime_format'] ?? ''));
$headerDtFormatOpts = LayoutService::headerDatetimeFormatOptionsForView();
$headerDtFormatPresets = LayoutService::headerDatetimeFormatPresets();
try {
    $headerDtPreviewNow = new \DateTimeImmutable('now', new \DateTimeZone(\App\Services\RegisterService::reportDisplayTimezone()));
} catch (\Throwable $e) {
    $headerDtPreviewNow = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
}
$headerDtPreviewText = LayoutService::formatHeaderDatetime($headerDtPreviewNow, $headerDtFormatKey);
$labotestsCardBgSaved = trim((string) ($config['ui_labotests_card_header_bg'] ?? ''));
$labotestsCardMode    = match (true) {
    strtolower($labotestsCardBgSaved) === 'transparent' => 'transparent',
    $labotestsCardBgSaved === ''                        => 'theme',
    default                                             => 'custom',
};
$labotestsCardIsCustom = $labotestsCardMode === 'custom';
$labotestsCardPick     = (string) ($config['theme_color'] ?? '#FF7218');
if ($labotestsCardIsCustom && preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $labotestsCardBgSaved)) {
    $labotestsCardPick = strtoupper(strlen($labotestsCardBgSaved) === 4
        ? '#' . $labotestsCardBgSaved[1] . $labotestsCardBgSaved[1] . $labotestsCardBgSaved[2] . $labotestsCardBgSaved[2] . $labotestsCardBgSaved[3] . $labotestsCardBgSaved[3]
        : $labotestsCardBgSaved);
}
$labotestsCardPick = LayoutService::htmlColorPickerValue($labotestsCardPick, '#FF7218');
$labotestsCardTitleColor = trim((string) ($config['ui_labotests_card_header_title_color'] ?? '#ffffff'));
if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $labotestsCardTitleColor)) {
    $labotestsCardTitleColor = '#ffffff';
}
$labotestsCardTitleColor = LayoutService::htmlColorPickerValue($labotestsCardTitleColor, '#ffffff');
$pvLabCardHeaderStyle = match ($labotestsCardMode) {
    'transparent' => 'background:transparent;border:1px dashed #adb5bd;',
    'custom'      => 'background-color:' . esc($labotestsCardPick, 'attr') . ';',
    default       => 'background:linear-gradient(90deg,' . $previewTheme . ',' . $previewGrad . ');',
};
$navbarBgApprox = (string) ($config['theme_gradient_end'] ?? '#4f46e5');
$pvNavbarFgWire = LayoutService::readableForegroundOnBackground($navbarBgApprox, $navbarTextRaw);
$pvNavbarClockFg = LayoutService::readableForegroundOnBackground($navbarBgApprox, $headerDatetimePick);
$pvNavbarStyle = 'background:linear-gradient(90deg,' . $previewTheme . ',' . $previewGrad . ');color:' . esc($pvNavbarFgWire, 'attr') . ';';

$breadcrumbBgApprox = match ($breadcrumbMode) {
    'transparent' => '#e9ecef',
    'custom'      => $breadcrumbPick,
    default       => (string) ($config['theme_gradient_end'] ?? '#4f46e5'),
};
$pvBreadcrumbFgWire = LayoutService::readableForegroundOnBackground($breadcrumbBgApprox, $breadcrumbTextRaw);
$pvBreadcrumbStyle  = match ($breadcrumbMode) {
    'transparent' => 'background:transparent;border:1px dashed #adb5bd;color:' . esc($pvBreadcrumbFgWire, 'attr') . ';',
    'custom'      => 'background-color:' . esc($breadcrumbPick, 'attr') . ';color:' . esc($pvBreadcrumbFgWire, 'attr') . ';',
    default       => 'background:linear-gradient(90deg,' . $previewTheme . ',' . $previewGrad . ');color:' . esc($pvBreadcrumbFgWire, 'attr') . ';',
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
$btnPreviewBgHex  = LayoutService::htmlColorPickerValue($btnPreviewBgHex, '#FF7218');
$btnHovCustom     = trim((string) ($config['ui_btn_primary_hover_bg'] ?? '')) !== '';
$uiBtnBw          = max(0, min(8, (int) ($config['ui_btn_border_width'] ?? 0)));
$uiCardBw         = max(0, min(8, (int) ($config['ui_card_border_width'] ?? 0)));
$uiBtnSides       = LayoutService::normalizeUiBorderSides((string) ($config['ui_btn_border_sides'] ?? 'all'));
$uiCardSides      = LayoutService::normalizeUiBorderSides((string) ($config['ui_card_border_sides'] ?? 'all'));
$uiBtnSh          = LayoutService::normalizeUiShadowKey((string) ($config['ui_btn_shadow'] ?? 'none'));
$uiCardSh         = LayoutService::normalizeUiShadowKey((string) ($config['ui_card_shadow'] ?? 'none'));
$borderSideOpts   = LayoutService::uiBorderSidesOptionsForView();
$shadowOpts       = LayoutService::uiShadowOptionsForView();

$fwNavbar      = LayoutService::normalizeUiFontWeight((string) ($config['ui_navbar_text_weight'] ?? $config['ui_header_text_weight'] ?? ''), '500');
$fsNavbar      = LayoutService::normalizeUiFontStyle((string) ($config['ui_navbar_text_style'] ?? $config['ui_header_text_style'] ?? ''), 'normal');
$fwBreadcrumb  = LayoutService::normalizeUiFontWeight((string) ($config['ui_breadcrumb_text_weight'] ?? $config['ui_header_text_weight'] ?? ''), '500');
$fsBreadcrumb  = LayoutService::normalizeUiFontStyle((string) ($config['ui_breadcrumb_text_style'] ?? $config['ui_header_text_style'] ?? ''), 'normal');
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
$pgLinkColor   = LayoutService::htmlColorPickerValue($pgLinkColor, $pgThemeFallback);
$pgActiveBg    = LayoutService::htmlColorPickerValue($pgActiveBg, $pgThemeFallback);
$pgActiveColor = LayoutService::htmlColorPickerValue($pgActiveColor, '#ffffff');
?>
    <div class="tab-pane fade config-tab-estilo <?= $activeTab === 'estilo' ? 'show active' : '' ?>" id="tab-estilo" role="tabpanel">
        <?= view('config/partials/config_section_guide', [
            'guide_key' => 'estilo',
            'title' => 'Apariencia del sistema web',
            'body' => 'Colores del tema, menú lateral, tipografía, botones y pie de página que ven los usuarios al trabajar en el laboratorio.',
            'steps' => [
                'Use el buscador para encontrar la sección del acordeón que necesita.',
                'Observe la vista previa mientras cambia colores y fuentes.',
                'Guarde con el botón al final de la pestaña.',
            ],
        ]) ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <p class="text-muted small mb-0 flex-grow-1" style="min-width: 16rem;"><?= lang('Config.config_style_tab_intro') ?></p>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm config-sistema-search-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" id="config_estilo_search" class="form-control" placeholder="Buscar sección (botones, menú, pie...)" autocomplete="off">
                </div>
                <span id="config_estilo_search_count" class="small text-muted text-nowrap"></span>
            </div>
        </div>

        <?= form_open(site_url('config/saveUiStyle'), ['id' => 'form_ui_style']) ?>
        <?= csrf_field() ?>

        <div class="accordion config-accordion" id="configEstiloAccordion">

        <!-- Sección: Vista general del diseño -->
        <div class="accordion-item" id="config-layout-map">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-mapa" aria-expanded="true" aria-controls="estsec-mapa">
                    <i class="fa-solid fa-map me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_layout_map_title') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Esquema de la interfaz con los colores actuales</span>
                </button>
            </h2>
            <div id="estsec-mapa" class="accordion-collapse collapse show" data-cfg-default-open="1">
            <div class="accordion-body">
                <div class="config-layout-map__frame rounded border border-2 overflow-hidden bg-white shadow-sm">
                    <div class="config-layout-map__bar px-3 py-2 small fw-semibold d-flex justify-content-between align-items-center gap-2" style="<?= $pvNavbarStyle ?>">
                        <span><span class="me-2 opacity-75">☰</span><?= lang('Config.config_style_theme_section') ?> · <?= lang('Config.config_company') ?></span>
                        <span class="config-header-datetime-preview d-inline-flex align-items-center text-nowrap" style="color: <?= esc($pvNavbarClockFg, 'attr') ?>; font-weight: normal;">
                            <?= header_datetime_icon() ?><span class="config-header-datetime-preview-text"><?= esc($headerDtPreviewText) ?></span>
                        </span>
                    </div>
                    <div class="d-flex config-layout-map__mid" style="min-height: 9rem; background: <?= $pvBodyBg ?>;">
                        <div class="config-layout-map__side border-end p-2 small" style="width: 32%; min-width: 7.5rem; background: <?= $pvSidebarBg ?>; color: <?= esc($pvSidebarFgWire, 'attr') ?>;">
                            <div class="fw-semibold mb-1"><?= lang('Config.config_style_section_menu') ?></div>
                            <div class="opacity-90">• <?= lang('Config.config_style_preview_menu_left') ?></div>
                            <div class="opacity-75 small">• …</div>
                        </div>
                        <div class="config-layout-map__main flex-grow-1 p-3 small" style="background: <?= $pvMainBg ?>; color: <?= esc($pvMainFgWire, 'attr') ?>;">
                            <div class="rounded-2 px-2 py-1 small mb-2" style="<?= $pvBreadcrumbStyle ?>"><?= lang('Config.config_style_section_breadcrumb') ?> · Módulo</div>
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
        </div>

        <!-- Sección: Marca y degradado -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-marca" aria-expanded="false" aria-controls="estsec-marca">
                    <i class="fa-solid fa-palette me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_theme_section') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Encabezado fijo: marca, degradado, texto y reloj</span>
                </button>
            </h2>
            <div id="estsec-marca" class="accordion-collapse collapse">
            <div class="accordion-body">
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
                            <input type="color" name="theme_color" id="theme_color" value="<?= esc(LayoutService::htmlColorPickerValue($config['theme_color'] ?? '', '#FF7218')) ?>" class="form-control form-control-color config-color-picker" autocomplete="off">
                            <input type="text" id="theme_color_hex" value="<?= esc(LayoutService::htmlColorPickerValue($config['theme_color'] ?? '', '#FF7218')) ?>" class="form-control config-color-hex" readonly autocomplete="off">
                        </div>
                        <small class="text-muted"><?= lang('Config.config_theme_color_custom_hint') ?></small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <?= form_label(lang('Config.config_theme_gradient_end'), 'theme_gradient_end', ['class' => 'form-label fw-semibold']) ?>
                        <?= form_dropdown('theme_gradient_palette_select', $selectThemePal, $selectedGradHex, 'id="theme_gradient_palette_select" class="form-select mb-2" autocomplete="off"') ?>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="color" name="theme_gradient_end" id="theme_gradient_end" value="<?= esc(LayoutService::htmlColorPickerValue($config['theme_gradient_end'] ?? '', '#4f46e5')) ?>" class="form-control form-control-color config-color-picker" autocomplete="off">
                            <input type="text" id="theme_gradient_end_hex" value="<?= esc(LayoutService::htmlColorPickerValue($config['theme_gradient_end'] ?? '', '#4f46e5')) ?>" class="form-control config-color-hex" readonly autocomplete="off">
                        </div>
                        <small class="text-muted d-block"><?= lang('Config.config_theme_gradient_help') ?></small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold" for="ui_navbar_text_color"><?= lang('Config.config_style_navbar_text') ?></label>
                        <input type="color" name="ui_navbar_text_color" id="ui_navbar_text_color" value="<?= esc(LayoutService::htmlColorPickerValue($config['ui_navbar_text_color'] ?? $config['ui_header_text_color'] ?? '', '#ffffff')) ?>" class="form-control form-control-color">
                        <small class="text-muted d-block"><?= lang('Config.config_style_navbar_text_hint') ?></small>
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_navbar_text_weight',
                            'styleField'  => 'ui_navbar_text_style',
                            'weightId'    => 'ui_navbar_text_weight',
                            'styleId'     => 'ui_navbar_text_style',
                            'weightVal'   => $fwNavbar,
                            'styleVal'    => $fsNavbar,
                        ]) ?>
                    </div>
                </div>
                <hr class="text-muted my-2">
                <div class="row align-items-end">
                    <div class="col-lg-4 mb-3">
                        <label class="form-label" for="ui_header_datetime_color"><?= lang('Config.config_style_header_datetime_color') ?></label>
                        <input type="hidden" name="ui_header_datetime_default" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_header_datetime_default" id="ui_header_datetime_default" value="1" autocomplete="off" <?= $headerDatetimeUseDefault ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_header_datetime_default"><?= lang('Config.config_style_header_datetime_same_as_bar') ?></label>
                        </div>
                        <input type="color" name="ui_header_datetime_color" id="ui_header_datetime_color" value="<?= esc($headerDatetimePick) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_header_datetime_color') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_header_datetime_color_help') ?></small>
                    </div>
                    <div class="col-lg-8 mb-3">
                        <label class="form-label fw-semibold" for="ui_header_datetime_format"><?= lang('Config.config_style_header_datetime_format') ?></label>
                        <select name="ui_header_datetime_format" id="ui_header_datetime_format" class="form-select" autocomplete="off">
                            <?php foreach ($headerDtFormatOpts as $fmtVal => $fmtLabel): ?>
                                <?php $fmtSample = $headerDtFormatPresets[$fmtVal]['preview'] ?? ''; ?>
                                <option value="<?= esc($fmtVal, 'attr') ?>" data-sample="<?= esc($fmtSample, 'attr') ?>" <?= $fmtVal === $headerDtFormatKey ? 'selected' : '' ?>><?= esc($fmtLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_header_datetime_format_help') ?></small>
                        <div class="small mt-2">
                            <?= lang('Config.config_style_header_datetime_format_example') ?>:
                            <strong id="ui_header_datetime_format_live" class="font-monospace"><?= esc($headerDtPreviewText) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Sección: Tipografía y disposición -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-tipografia" aria-expanded="false" aria-controls="estsec-tipografia">
                    <i class="fa-solid fa-font me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_card_font_layout') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Fuente, tamaño por defecto, móviles y lado del menú</span>
                </button>
            </h2>
            <div id="estsec-tipografia" class="accordion-collapse collapse">
            <div class="accordion-body">
                <?php $fontPreviewGoogleHref = LayoutService::uiFontPreviewGoogleHref(); ?>
                <?php if ($fontPreviewGoogleHref): ?>
                <link rel="stylesheet" href="<?= esc($fontPreviewGoogleHref, 'attr') ?>">
                <?php endif; ?>
                <link rel="stylesheet" href="<?= base_url('css/vendor/inter-font.css') ?>">
                <style>
                .config-font-tile {
                    transition: border-color .12s ease-in-out, box-shadow .12s ease-in-out;
                    cursor: pointer;
                }
                .config-font-tile:hover {
                    border-color: #9db6d8 !important;
                    box-shadow: 0 .15rem .45rem rgba(31, 111, 215, .15);
                }
                .config-font-tile.active {
                    border-color: #1f6fd7 !important;
                    border-width: 2px !important;
                    box-shadow: 0 .15rem .5rem rgba(31, 111, 215, .25);
                }
                .config-font-tile.active .config-font-tile-check {
                    visibility: visible;
                }
                .config-font-tile-check {
                    visibility: hidden;
                }
                </style>
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-3"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div id="config-font-preview-live" class="config-font-preview p-3 rounded-3 border bg-white mb-3 shadow-sm" style="font-family: <?= $fontPreviewStackEsc ?>; font-size: 1.2rem; line-height: 1.4;">
                        Aa Bb Cc 123 — <span class="text-muted" style="font-size: 0.95rem;" id="config-font-preview-live-label"><?= esc($fontPreviewLabel) ?></span>
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
                        <small class="text-muted d-block mt-1">También puede elegirla haciendo clic en una tarjeta de abajo.</small>
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
                <h6 class="cfg-sub-title text-muted fw-bold mb-2">Tipografías disponibles (vista previa)</h6>
                <p class="small text-muted mb-2">Cada tarjeta se muestra con su propia tipografía. Haga clic para seleccionarla; se aplica al guardar.</p>
                <div class="row g-2 mb-3" id="config-font-gallery">
                    <?php foreach ($fontOpts as $fk => $fLabel): ?>
                    <div class="col-6 col-md-4 col-lg-3 d-flex">
                        <button type="button"
                            class="config-font-tile w-100 border rounded-3 bg-white p-2 text-start<?= $fk === $curFontResolved ? ' active' : '' ?>"
                            data-font-key="<?= esc($fk, 'attr') ?>"
                            title="<?= esc($fLabel, 'attr') ?>"
                            style="font-family: <?= esc(LayoutService::uiFontFamilyCssStackForKey($fk), 'attr') ?>;">
                            <span class="d-flex justify-content-between align-items-start">
                                <span class="d-block" style="font-size: 1.15rem; line-height: 1.3;">Aa Bb Cc 123</span>
                                <i class="fa-solid fa-circle-check text-primary config-font-tile-check" aria-hidden="true"></i>
                            </span>
                            <span class="d-block small text-muted"><?= esc($fLabel) ?></span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <script>
                (function initConfigFontGallery() {
                    var sel = document.getElementById('ui_font_family');
                    var preview = document.getElementById('config-font-preview-live');
                    var previewLabel = document.getElementById('config-font-preview-live-label');
                    var tiles = Array.prototype.slice.call(document.querySelectorAll('#config-font-gallery .config-font-tile'));
                    if (!sel) { return; }
                    var fonts = <?php
                        $fontPreviewMap = [];
                        foreach ($fontOpts as $fk => $fLabel) {
                            $fontPreviewMap[$fk] = [
                                'label'  => $fLabel,
                                'family' => LayoutService::uiFontFamilyCssStackForKey($fk),
                            ];
                        }
                        echo json_encode($fontPreviewMap, JSON_UNESCAPED_UNICODE);
                    ?>;
                    function apply(key) {
                        var f = fonts[key];
                        if (!f) { return; }
                        if (preview) { preview.style.fontFamily = f.family; }
                        if (previewLabel) { previewLabel.textContent = f.label; }
                        tiles.forEach(function (t) {
                            t.classList.toggle('active', t.getAttribute('data-font-key') === key);
                        });
                    }
                    sel.addEventListener('change', function () { apply(sel.value); });
                    tiles.forEach(function (t) {
                        t.addEventListener('click', function () {
                            sel.value = t.getAttribute('data-font-key');
                            apply(sel.value);
                        });
                    });
                })();
                </script>
                <hr class="text-muted">
                <h6 class="cfg-sub-title text-muted fw-bold mb-3"><i class="fa-solid fa-text-height me-2"></i>Tamaño de letra</h6>
                <?php
                $fsBaseSel     = LayoutService::normalizeUiFontSizeRemInput((string) ($config['ui_font_size_base'] ?? ''), '1');
                $fsMobileSaved = trim((string) ($config['ui_font_size_base_mobile'] ?? ''));
                $fsMobileSel   = $fsMobileSaved === '' ? '' : LayoutService::normalizeUiFontSizeRemInput($fsMobileSaved, '1');
                $fontSizeMobileOpts = ['' => lang('Config.config_style_font_size_mobile_same')] + $fontSizeOpts;
                ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="ui_font_size_base"><?= lang('Config.config_style_font_size_default') ?></label>
                        <?= form_dropdown('ui_font_size_base', $fontSizeOpts, $fsBaseSel, 'class="form-select" id="ui_font_size_base" autocomplete="off"') ?>
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_font_size_default_help') ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="ui_font_size_base_mobile"><?= lang('Config.config_style_font_size_mobile') ?></label>
                        <?= form_dropdown('ui_font_size_base_mobile', $fontSizeMobileOpts, $fsMobileSel, 'class="form-select" id="ui_font_size_base_mobile" autocomplete="off"') ?>
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_font_size_mobile_help') ?></small>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Sección: Tamaño del texto por zona -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-tamanos" aria-expanded="false" aria-controls="estsec-tamanos">
                    <i class="fa-solid fa-text-height me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_font_sizes_section') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Ajuste fino por zona: contenido, barra, menú, pie y títulos</span>
                </button>
            </h2>
            <div id="estsec-tamanos" class="accordion-collapse collapse">
            <div class="accordion-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <p class="text-muted small mb-3"><?= lang('Config.config_style_font_sizes_intro') ?></p>
                    <div class="config-zone-preview config-zone-preview--wireframe rounded-3 overflow-hidden border border-2 mb-0">
                        <div class="config-zone-preview__bar text-white text-center small py-2" style="background: linear-gradient(90deg, <?= $previewTheme ?>, <?= $previewGrad ?>);"><?= lang('Config.config_style_theme_section') ?></div>
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
                            <div class="config-zone-preview__bar text-white" style="background: linear-gradient(90deg, <?= $previewTheme ?>, <?= $previewGrad ?>); font-size: 0.8125rem;"><?= lang('Config.config_style_theme_section') ?> · <?= lang('Config.config_company') ?></div>
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
        </div>

        <!-- Sección: Barra de migas de pan -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-barra" aria-expanded="false" aria-controls="estsec-barra">
                    <i class="fa-solid fa-fill-drip me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_section_breadcrumb') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Fondo y texto del nav de ruta (breadcrumb)</span>
                </button>
            </h2>
            <div id="estsec-barra" class="accordion-collapse collapse">
            <div class="accordion-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 py-3 px-3 shadow-sm mb-2" style="<?= $pvBreadcrumbStyle ?>">
                        <span><?= lang('Config.config_style_section_breadcrumb') ?> · Módulo › Página</span>
                    </div>
                    <p class="small text-muted mb-0"><?= lang('Config.config_style_preview_breadcrumb_hint') ?></p>
                </div>
                <div class="row align-items-end">
                    <div class="col-lg-4 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_breadcrumb_bg') ?></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_breadcrumb_mode" id="ui_bc_theme" value="theme" <?= $breadcrumbMode === 'theme' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_bc_theme"><?= lang('Config.config_style_use_theme_primary') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_breadcrumb_mode" id="ui_bc_custom" value="custom" <?= $breadcrumbMode === 'custom' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_bc_custom"><?= lang('Config.config_style_custom_color') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_breadcrumb_mode" id="ui_bc_transparent" value="transparent" <?= $breadcrumbMode === 'transparent' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_bc_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_breadcrumb_bg_custom" id="ui_breadcrumb_bg_custom" value="<?= esc($breadcrumbPick) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_breadcrumb_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_breadcrumb_transparent_help') ?></small>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label class="form-label" for="ui_breadcrumb_text_color"><?= lang('Config.config_style_breadcrumb_text') ?></label>
                        <input type="color" name="ui_breadcrumb_text_color" id="ui_breadcrumb_text_color" value="<?= esc(LayoutService::htmlColorPickerValue($config['ui_breadcrumb_text_color'] ?? $config['ui_header_text_color'] ?? '', '#ffffff')) ?>" class="form-control form-control-color">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_breadcrumb_text_hint') ?></small>
                        <?= view('config/partials/ui_font_variant', [
                            'weightField' => 'ui_breadcrumb_text_weight',
                            'styleField'  => 'ui_breadcrumb_text_style',
                            'weightId'    => 'ui_breadcrumb_text_weight',
                            'styleId'     => 'ui_breadcrumb_text_style',
                            'weightVal'   => $fwBreadcrumb,
                            'styleVal'    => $fsBreadcrumb,
                        ]) ?>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Sección: Menú lateral -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-menu" aria-expanded="false" aria-controls="estsec-menu">
                    <i class="fa-solid fa-list me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_section_menu') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Fondo, enlaces, hover y elemento activo del menú</span>
                </button>
            </h2>
            <div id="estsec-menu" class="accordion-collapse collapse">
            <div class="accordion-body">

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
                        <input type="color" name="ui_sidebar_bg" id="ui_sidebar_bg" value="<?= esc($sidebarBgTransparent ? '#f8f9fa' : LayoutService::htmlColorPickerValue($sidebarBgStored, '#f8f9fa')) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_sidebar_bg') ?>">
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
                        <input type="color" name="ui_sidebar_hover_bg" id="ui_sidebar_hover_bg" value="<?= esc(LayoutService::htmlColorPickerValue($hoverBgVal !== '' ? $hoverBgVal : '#dee2e6', '#dee2e6')) ?>" class="form-control form-control-color">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_sidebar_active') ?></label>
                        <input type="hidden" name="ui_sidebar_active_default" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_sidebar_active_default" id="ui_sidebar_active_default" value="1" autocomplete="off" <?= $activeBgVal === '' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_sidebar_active_default"><?= lang('Config.config_style_active_auto') ?></label>
                        </div>
                        <input type="color" name="ui_sidebar_active_bg" id="ui_sidebar_active_bg" value="<?= esc(LayoutService::htmlColorPickerValue($activeBgVal !== '' ? $activeBgVal : '#ced4da', '#ced4da')) ?>" class="form-control form-control-color">
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Sección: Contenido (página) -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-pagina" aria-expanded="false" aria-controls="estsec-pagina">
                    <i class="fa-solid fa-table-columns me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_section_page') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Fondo del body y del main, texto y enlaces</span>
                </button>
            </h2>
            <div id="estsec-pagina" class="accordion-collapse collapse">
            <div class="accordion-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 border overflow-hidden shadow-sm">
                        <div class="px-3 py-2 small text-muted border-bottom" style="background: <?= $pvBodyBg ?>;"><?= lang('Config.config_style_body_bg') ?></div>
                        <div class="p-3" style="background: <?= $pvMainBg ?>; color: <?= esc($pvMainFgWire, 'attr') ?>;">
                            <p class="mb-2 small"><?= lang('Config.config_style_body_text') ?> — <?= lang('Config.config_style_main_bg') ?></p>
                            <a href="#" class="small" style="color: <?= esc($pvMainLinkWire, 'attr') ?>; pointer-events: none;"><?= lang('Config.config_style_link_color') ?></a>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><?= lang('Config.config_style_preview_page_hint') ?></p>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_body_text_color"><?= lang('Config.config_style_body_text') ?></label>
                        <input type="color" name="ui_body_text_color" id="ui_body_text_color" value="<?= esc(LayoutService::htmlColorPickerValue($config['ui_body_text_color'] ?? '', '#212529')) ?>" class="form-control form-control-color">
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
                        <label class="form-label" for="ui_body_bg"><?= lang('Config.config_style_body_bg') ?></label>
                        <input type="hidden" name="ui_body_bg_transparent" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_body_bg_transparent" id="ui_body_bg_transparent" value="1" autocomplete="off" <?= $bodyBgTransparent ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_body_bg_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_body_bg" id="ui_body_bg" value="<?= esc($bodyBgTransparent ? '#ffffff' : LayoutService::htmlColorPickerValue($bodyBgStored, '#ffffff')) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_body_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_body_bg_transparent_help') ?></small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ui_main_bg"><?= lang('Config.config_style_main_bg') ?></label>
                        <input type="hidden" name="ui_main_bg_transparent" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="ui_main_bg_transparent" id="ui_main_bg_transparent" value="1" autocomplete="off" <?= $mainBgTransparent ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ui_main_bg_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_main_bg" id="ui_main_bg" value="<?= esc($mainBgTransparent ? '#ffffff' : LayoutService::htmlColorPickerValue($mainBgStored, '#ffffff')) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_main_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_main_bg_transparent_help') ?></small>
                    </div>
                </div>
                <div class="row">
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
        </div>

        <!-- Sección: Pie de página -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-pie" aria-expanded="false" aria-controls="estsec-pie">
                    <i class="fa-regular fa-copyright me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_section_footer') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Fondo, texto y alineación del pie</span>
                </button>
            </h2>
            <div id="estsec-pie" class="accordion-collapse collapse">
            <div class="accordion-body">
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
                        <input type="color" name="ui_footer_bg" id="ui_footer_bg" value="<?= esc($footerBgTransparent ? '#f8f9fa' : LayoutService::htmlColorPickerValue($footerBgStored, '#f8f9fa')) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_footer_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_footer_transparent_help') ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ui_footer_text_color"><?= lang('Config.config_style_footer_text') ?></label>
                        <input type="color" name="ui_footer_text_color" id="ui_footer_text_color" value="<?= esc(LayoutService::htmlColorPickerValue($config['ui_footer_text_color'] ?? '', '#6c757d')) ?>" class="form-control form-control-color">
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
        </div>

        <!-- Sección: Botones -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-botones" aria-expanded="false" aria-controls="estsec-botones">
                    <i class="fa-solid fa-hand-pointer me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_btn_section') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Color, texto, hover, bordes y sombra del botón principal</span>
                </button>
            </h2>
            <div id="estsec-botones" class="accordion-collapse collapse">
            <div class="accordion-body">
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
                        <input type="color" name="ui_btn_primary_text" id="ui_btn_primary_text" value="<?= esc(LayoutService::htmlColorPickerValue($config['ui_btn_primary_text'] ?? '', '#ffffff')) ?>" class="form-control form-control-color">
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
                        <input type="color" name="ui_btn_primary_hover_custom" id="ui_btn_primary_hover_custom" value="<?= esc(LayoutService::htmlColorPickerValue($btnHovCustom ? (string) ($config['ui_btn_primary_hover_bg'] ?? '') : '#000000', '#000000')) ?>" class="form-control form-control-color">
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
                        <input type="color" name="ui_btn_border_color" id="ui_btn_border_color" value="<?= esc(LayoutService::htmlColorPickerValue($config['ui_btn_border_color'] ?? '', '#212529')) ?>" class="form-control form-control-color">
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
        </div>

        <!-- Sección: Tarjetas -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-tarjetas" aria-expanded="false" aria-controls="estsec-tarjetas">
                    <i class="fa-regular fa-square me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_card_components') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Redondeo, bordes y sombra de las tarjetas</span>
                </button>
            </h2>
            <div id="estsec-tarjetas" class="accordion-collapse collapse">
            <div class="accordion-body">
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
                        <input type="color" name="ui_card_border_color" id="ui_card_border_color" value="<?= esc(LayoutService::htmlColorPickerValue($config['ui_card_border_color'] ?? '', '#dee2e6')) ?>" class="form-control form-control-color">
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
        </div>

        <!-- Sección: Tarjetas de pruebas -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-labotests" aria-expanded="false" aria-controls="estsec-labotests">
                    <i class="fa-solid fa-flask-vial me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_section_labotests_cards') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Título de las tarjetas en pantallas de pruebas</span>
                </button>
            </h2>
            <div id="estsec-labotests" class="accordion-collapse collapse">
            <div class="accordion-body">
                <div class="config-section-preview mb-4 p-3 rounded-3 border bg-light">
                    <div class="small fw-semibold text-secondary text-uppercase config-style-preview-title mb-2"><?= lang('Config.config_style_preview_caption') ?></div>
                    <div class="rounded-3 overflow-hidden shadow-sm border mb-2" style="max-width: 22rem;">
                        <div class="card-header bg-primary text-white py-2 px-3 d-flex align-items-center config-labotests-card-header-preview" style="<?= $pvLabCardHeaderStyle ?>">
                            <h6 class="mb-0 config-labotests-card-title-preview" style="color: <?= esc($labotestsCardTitleColor, 'attr') ?> !important; font-weight: <?= esc($fwLabCard, 'attr') ?> !important; font-style: <?= esc($fsLabCard, 'attr') ?> !important;"><?= lang('Config.config_style_labotests_card_preview_sample') ?></h6>
                        </div>
                    </div>
                    <p class="small text-muted mb-0"><?= lang('Config.config_style_labotests_card_preview_help') ?></p>
                </div>
                <div class="row align-items-end">
                    <div class="col-lg-4 mb-3">
                        <label class="form-label"><?= lang('Config.config_style_labotests_card_bg') ?></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_labotests_card_mode" id="ui_lab_card_theme" value="theme" <?= $labotestsCardMode === 'theme' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_lab_card_theme"><?= lang('Config.config_style_use_theme_primary') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_labotests_card_mode" id="ui_lab_card_custom" value="custom" <?= $labotestsCardMode === 'custom' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_lab_card_custom"><?= lang('Config.config_style_custom_color') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ui_labotests_card_mode" id="ui_lab_card_transparent" value="transparent" <?= $labotestsCardMode === 'transparent' ? 'checked' : '' ?> autocomplete="off">
                            <label class="form-check-label" for="ui_lab_card_transparent"><?= lang('Config.config_style_bg_transparent') ?></label>
                        </div>
                        <input type="color" name="ui_labotests_card_bg_custom" id="ui_labotests_card_bg_custom" value="<?= esc($labotestsCardPick) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_labotests_card_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_labotests_card_transparent_help') ?></small>
                    </div>
                    <div class="col-lg-4 mb-3 mb-md-0">
                        <label class="form-label" for="ui_labotests_card_header_title_color"><?= lang('Config.config_style_labotests_card_title_color') ?></label>
                        <input type="color" name="ui_labotests_card_header_title_color" id="ui_labotests_card_header_title_color" value="<?= esc(LayoutService::htmlColorPickerValue($labotestsCardTitleColor, '#ffffff')) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_labotests_card_title_color') ?>">
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
                    var themeColor = document.getElementById('theme_color');
                    var themeGrad = document.getElementById('theme_gradient_end');
                    var input = document.getElementById('ui_labotests_card_header_title_color');
                    var wSel = document.getElementById('ui_labotests_card_header_title_weight');
                    var sSel = document.getElementById('ui_labotests_card_header_title_style');
                    var bgCustom = document.getElementById('ui_labotests_card_bg_custom');
                    var headerPreview = document.querySelector('.config-labotests-card-header-preview');
                    var titlePreview = document.querySelector('.config-labotests-card-title-preview');
                    if (!titlePreview) return;

                    function labCardMode() {
                        if (document.getElementById('ui_lab_card_transparent') && document.getElementById('ui_lab_card_transparent').checked) return 'transparent';
                        if (document.getElementById('ui_lab_card_custom') && document.getElementById('ui_lab_card_custom').checked) return 'custom';
                        return 'theme';
                    }

                    function syncBgPreview() {
                        if (!headerPreview) return;
                        var mode = labCardMode();
                        if (mode === 'transparent') {
                            headerPreview.style.background = 'transparent';
                            headerPreview.style.border = '1px dashed #adb5bd';
                        } else if (mode === 'custom' && bgCustom) {
                            headerPreview.style.border = '';
                            headerPreview.style.background = bgCustom.value;
                            headerPreview.style.backgroundImage = 'none';
                        } else {
                            headerPreview.style.border = '';
                            var c1 = themeColor ? themeColor.value : '#FF7218';
                            var c2 = themeGrad ? themeGrad.value : '#4f46e5';
                            headerPreview.style.background = 'linear-gradient(90deg,' + c1 + ',' + c2 + ')';
                            headerPreview.style.backgroundImage = '';
                        }
                        if (bgCustom) bgCustom.disabled = mode !== 'custom';
                    }

                    function syncTitlePreview() {
                        if (input) titlePreview.style.setProperty('color', input.value, 'important');
                        if (wSel) titlePreview.style.setProperty('font-weight', wSel.value, 'important');
                        if (sSel) titlePreview.style.setProperty('font-style', sSel.value, 'important');
                    }

                    function syncPreview() {
                        syncBgPreview();
                        syncTitlePreview();
                    }

                    ['ui_lab_card_theme', 'ui_lab_card_custom', 'ui_lab_card_transparent'].forEach(function (id) {
                        var el = document.getElementById(id);
                        if (el) el.addEventListener('change', syncPreview);
                    });
                    if (bgCustom) bgCustom.addEventListener('input', syncPreview);
                    if (input) input.addEventListener('input', syncTitlePreview);
                    if (wSel) wSel.addEventListener('change', syncTitlePreview);
                    if (sSel) sSel.addEventListener('change', syncTitlePreview);
                    if (themeColor) themeColor.addEventListener('input', syncBgPreview);
                    if (themeGrad) themeGrad.addEventListener('input', syncBgPreview);
                    syncPreview();
                })();
                </script>
            </div>
            </div>
        </div>

        <!-- Sección: Paginación -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#estsec-paginacion" aria-expanded="false" aria-controls="estsec-paginacion">
                    <i class="fa-solid fa-angles-right me-2 text-primary"></i>
                    <span class="fw-semibold"><?= lang('Config.config_style_section_pagination') ?></span>
                    <span class="cfg-sec-hint small text-muted ms-2 d-none d-md-inline">Colores de los listados paginados</span>
                </button>
            </h2>
            <div id="estsec-paginacion" class="accordion-collapse collapse">
            <div class="accordion-body">
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
                        <input type="color" name="ui_pagination_link_color" id="ui_pagination_link_color" value="<?= esc(LayoutService::htmlColorPickerValue($pgLinkColor, $pgThemeFallback)) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_pagination_link_color') ?>">
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
                        <input type="color" name="ui_pagination_active_bg" id="ui_pagination_active_bg" value="<?= esc(LayoutService::htmlColorPickerValue($pgActiveBg, $pgThemeFallback)) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_pagination_active_bg') ?>">
                        <small class="text-muted d-block mt-1"><?= lang('Config.config_style_pagination_active_bg_help') ?></small>
                        <label class="form-label mt-3" for="ui_pagination_active_color"><?= lang('Config.config_style_pagination_active_color') ?></label>
                        <input type="color" name="ui_pagination_active_color" id="ui_pagination_active_color" value="<?= esc(LayoutService::htmlColorPickerValue($pgActiveColor, '#ffffff')) ?>" class="form-control form-control-color" title="<?= lang('Config.config_style_pagination_active_color') ?>">
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
        </div>

        </div><!-- /configEstiloAccordion -->

        <div id="config_estilo_no_results" class="alert alert-light border text-center text-muted my-3" style="display: none;">
            <i class="fa-solid fa-magnifying-glass me-1"></i>No se encontró ninguna sección con ese texto.
        </div>

        <div class="config-sistema-savebar position-sticky bottom-0 bg-white border-top mt-3 py-2 d-flex flex-wrap align-items-center gap-3">
            <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-2"></i><?= lang('Config.config_save_btn') ?></button>
            <span class="small text-muted">Guarda los cambios de todas las secciones de apariencia.</span>
        </div>
        <?= form_close() ?>

        <script>
        (function initConfigEstiloSearch() {
            var input = document.getElementById('config_estilo_search');
            var acc = document.getElementById('configEstiloAccordion');
            if (!input || !acc) { return; }
            var counter = document.getElementById('config_estilo_search_count');
            var noResults = document.getElementById('config_estilo_no_results');
            var sections = Array.prototype.slice.call(acc.querySelectorAll('.accordion-item'));
            function norm(s) {
                s = (s || '').toLowerCase();
                try { s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (e) {}
                return s;
            }
            function setOpen(sec, open) {
                var col = sec.querySelector('.accordion-collapse');
                var btn = sec.querySelector('.accordion-button');
                if (!col || !btn) { return; }
                col.classList.toggle('show', open);
                btn.classList.toggle('collapsed', !open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            function apply() {
                var q = norm(input.value.trim());
                var total = 0;
                sections.forEach(function (sec) {
                    if (!q) {
                        sec.classList.remove('d-none');
                        var col = sec.querySelector('.accordion-collapse');
                        setOpen(sec, !!(col && col.hasAttribute('data-cfg-default-open')));
                        return;
                    }
                    var hit = norm(sec.textContent).indexOf(q) !== -1;
                    sec.classList.toggle('d-none', !hit);
                    if (hit) {
                        total++;
                        setOpen(sec, true);
                    }
                });
                if (counter) {
                    counter.textContent = q ? (total + (total === 1 ? ' sección' : ' secciones')) : '';
                }
                if (noResults) {
                    noResults.style.display = (q && total === 0) ? 'block' : 'none';
                }
            }
            input.addEventListener('input', apply);
            input.addEventListener('search', apply);
        })();
        </script>
    </div>

<script>
(function() {
    function toggleBc() {
        var custom = document.getElementById('ui_bc_custom').checked;
        var el = document.getElementById('ui_breadcrumb_bg_custom');
        if (el) el.disabled = !custom;
    }
    function toggleSl() {
        var custom = document.getElementById('ui_sl_custom').checked;
        document.getElementById('ui_sidebar_link_custom').disabled = !custom;
    }
    var h1 = document.getElementById('ui_bc_theme');
    var h2 = document.getElementById('ui_bc_custom');
    var h3 = document.getElementById('ui_bc_transparent');
    if (h1) h1.addEventListener('change', toggleBc);
    if (h2) h2.addEventListener('change', toggleBc);
    if (h3) h3.addEventListener('change', toggleBc);
    var s1 = document.getElementById('ui_sl_theme');
    var s2 = document.getElementById('ui_sl_custom');
    if (s1) s1.addEventListener('change', toggleSl);
    if (s2) s2.addEventListener('change', toggleSl);
    toggleBc();
    toggleSl();

    function toggleBgTransparent(checkId, colorId) {
        var c = document.getElementById(checkId);
        var p = document.getElementById(colorId);
        if (p) p.disabled = c && c.checked;
    }
    var sb = document.getElementById('ui_sidebar_bg_transparent');
    var bb = document.getElementById('ui_body_bg_transparent');
    var mb = document.getElementById('ui_main_bg_transparent');
    var fb = document.getElementById('ui_footer_bg_transparent');
    if (sb) sb.addEventListener('change', function() { toggleBgTransparent('ui_sidebar_bg_transparent', 'ui_sidebar_bg'); });
    if (bb) bb.addEventListener('change', function() { toggleBgTransparent('ui_body_bg_transparent', 'ui_body_bg'); });
    if (mb) mb.addEventListener('change', function() { toggleBgTransparent('ui_main_bg_transparent', 'ui_main_bg'); });
    if (fb) fb.addEventListener('change', function() { toggleBgTransparent('ui_footer_bg_transparent', 'ui_footer_bg'); });
    toggleBgTransparent('ui_sidebar_bg_transparent', 'ui_sidebar_bg');
    toggleBgTransparent('ui_body_bg_transparent', 'ui_body_bg');
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

    function toggleHeaderDatetimeColor() {
        var useDefault = document.getElementById('ui_header_datetime_default');
        var picker = document.getElementById('ui_header_datetime_color');
        if (picker && useDefault) {
            picker.disabled = useDefault.checked;
        }
        syncHeaderDatetimePreview();
    }

    function syncHeaderDatetimePreview() {
        var useDefault = document.getElementById('ui_header_datetime_default');
        var picker = document.getElementById('ui_header_datetime_color');
        var navbarText = document.getElementById('ui_navbar_text_color');
        var color = (useDefault && useDefault.checked && navbarText)
            ? navbarText.value
            : (picker ? picker.value : '');
        document.querySelectorAll('.config-header-datetime-preview').forEach(function(el) {
            el.style.color = color;
        });
    }

    var hdrDtDefault = document.getElementById('ui_header_datetime_default');
    var hdrDtColor = document.getElementById('ui_header_datetime_color');
    if (hdrDtDefault) hdrDtDefault.addEventListener('change', toggleHeaderDatetimeColor);
    if (hdrDtColor) hdrDtColor.addEventListener('input', syncHeaderDatetimePreview);
    var navbarTextEl = document.getElementById('ui_navbar_text_color');
    if (navbarTextEl) navbarTextEl.addEventListener('input', syncHeaderDatetimePreview);
    toggleHeaderDatetimeColor();

    var hdrDtFormatSel = document.getElementById('ui_header_datetime_format');
    var hdrDtFormatLive = document.getElementById('ui_header_datetime_format_live');
    var hdrDtTz = <?= json_encode(\App\Services\RegisterService::reportDisplayTimezone(), JSON_UNESCAPED_UNICODE) ?>;

    function formatHeaderDatetimePreview(date, tz, formatKey) {
        try {
            var p;
            switch (formatKey) {
                case 'dmY_his':
                    p = {};
                    new Intl.DateTimeFormat('es', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).formatToParts(date).forEach(function (x) { if (x.type !== 'literal') p[x.type] = x.value; });
                    return p.day + '/' + p.month + '/' + p.year + ' ' + p.hour + ':' + p.minute + ':' + (p.second || '00');
                case 'dm_hi':
                    p = {};
                    new Intl.DateTimeFormat('es', { timeZone: tz, month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(date).forEach(function (x) { if (x.type !== 'literal') p[x.type] = x.value; });
                    return p.day + '/' + p.month + ' ' + p.hour + ':' + p.minute;
                case 'ymd_hi':
                    p = {};
                    new Intl.DateTimeFormat('es', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(date).forEach(function (x) { if (x.type !== 'literal') p[x.type] = x.value; });
                    return p.year + '-' + p.month + '-' + p.day + ' ' + p.hour + ':' + p.minute;
                case 'dmY_hi_12':
                    p = {};
                    new Intl.DateTimeFormat('es', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true }).formatToParts(date).forEach(function (x) { if (x.type !== 'literal') p[x.type] = x.value; });
                    return p.day + '/' + p.month + '/' + p.year + ' ' + p.hour + ':' + p.minute + ' ' + (p.dayPeriod || '');
                case 'long_es':
                    return new Intl.DateTimeFormat('es', { timeZone: tz, day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).format(date);
                case 'dmY_hi':
                default:
                    p = {};
                    new Intl.DateTimeFormat('es', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(date).forEach(function (x) { if (x.type !== 'literal') p[x.type] = x.value; });
                    return p.day + '/' + p.month + '/' + p.year + ' ' + p.hour + ':' + p.minute;
            }
        } catch (e) {
            return '';
        }
    }

    function syncHeaderDatetimeFormatPreview() {
        if (!hdrDtFormatSel) return;
        var key = hdrDtFormatSel.value || 'dmY_hi';
        var text = formatHeaderDatetimePreview(new Date(), hdrDtTz, key);
        if (!text && hdrDtFormatSel.options[hdrDtFormatSel.selectedIndex]) {
            text = hdrDtFormatSel.options[hdrDtFormatSel.selectedIndex].getAttribute('data-sample') || '';
        }
        if (hdrDtFormatLive && text) hdrDtFormatLive.textContent = text;
        document.querySelectorAll('.config-header-datetime-preview-text').forEach(function (el) {
            if (text) el.textContent = text;
        });
    }

    if (hdrDtFormatSel) {
        hdrDtFormatSel.addEventListener('change', syncHeaderDatetimeFormatPreview);
        syncHeaderDatetimeFormatPreview();
        window.setInterval(syncHeaderDatetimeFormatPreview, 30000);
    }
})();
</script>
