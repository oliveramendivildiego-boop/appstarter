<?php

namespace App\Services;

use App\Models\AppConfigModel;

/**
 * Servicio para datos del layout (logo, tema, empresa, apariencia UI).
 */
class LayoutService
{
    protected AppConfigModel $appConfig;

    /** @var array<string, string> Valor numérico en rem (sin unidad) => etiqueta para selects */
    private const FONT_SIZE_REM_PRESETS = [
        '0.75'    => 'Extra pequeño (~12 px)',
        '0.8125'  => 'Muy pequeño (~13 px)',
        '0.875'   => 'Pequeño (~14 px)',
        '0.9375'  => 'Intermedio (~15 px)',
        '1'       => 'Normal (~16 px)',
        '1.0625'  => 'Ligeramente grande (~17 px)',
        '1.125'   => 'Grande (~18 px)',
        '1.1875'  => 'Muy grande (~19 px)',
        '1.25'    => 'Extra grande (~20 px)',
        '1.375'   => 'Máximo recomendado (~22 px)',
    ];

    /** @var array<string, array{href: ?string, use_inter_css: bool, family: string}> */
    private const FONT_PRESETS = [
        'system' => [
            'href'          => null,
            'use_inter_css' => false,
            'family'        => 'system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif',
        ],
        'poppins' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Poppins\', system-ui, sans-serif',
        ],
        'inter' => [
            'href'          => null,
            'use_inter_css' => true,
            'family'        => '\'Inter\', system-ui, sans-serif',
        ],
        'roboto' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Roboto\', system-ui, sans-serif',
        ],
        'open_sans' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Open Sans\', system-ui, sans-serif',
        ],
        'lato' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Lato\', system-ui, sans-serif',
        ],
        'nunito' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Nunito\', system-ui, sans-serif',
        ],
        'ubuntu' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Ubuntu\', system-ui, sans-serif',
        ],
        'merriweather' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Merriweather\', Georgia, \'Times New Roman\', serif',
        ],
        'montserrat' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Montserrat\', system-ui, sans-serif',
        ],
        'raleway' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Raleway\', system-ui, sans-serif',
        ],
        'work_sans' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Work Sans\', system-ui, sans-serif',
        ],
        'source_sans' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Source Sans 3\', system-ui, sans-serif',
        ],
        'rubik' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Rubik\', system-ui, sans-serif',
        ],
        'manrope' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Manrope\', system-ui, sans-serif',
        ],
        'mulish' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Mulish:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Mulish\', system-ui, sans-serif',
        ],
        'karla' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Karla:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Karla\', system-ui, sans-serif',
        ],
        'quicksand' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Quicksand\', system-ui, sans-serif',
        ],
        'fira_sans' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Fira+Sans:wght@300;400;500;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Fira Sans\', system-ui, sans-serif',
        ],
        'lora' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Lora\', Georgia, \'Times New Roman\', serif',
        ],
        'playfair' => [
            'href'          => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap',
            'use_inter_css' => false,
            'family'        => '\'Playfair Display\', Georgia, \'Times New Roman\', serif',
        ],
    ];

    public function __construct()
    {
        $this->appConfig = model(AppConfigModel::class);
    }

    /**
     * Valor #rrggbb para el atributo value de inputs HTML type="color" (nunca vacío).
     */
    public static function htmlColorPickerValue(?string $raw, string $fallback): string
    {
        foreach ([trim((string) $raw), trim($fallback), '#000000'] as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $v = strtoupper($candidate);
            if (($v[0] ?? '') !== '#') {
                $v = '#' . $v;
            }
            if (preg_match('/^#([0-9A-F]{3})$/', $v, $m)) {
                $h = $m[1];

                return '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
            }
            if (preg_match('/^#([0-9A-F]{6})$/', $v)) {
                return $v;
            }
        }

        return '#000000';
    }

    private function normalizeHex(?string $value): ?string
    {
        $v = strtoupper(trim((string) $value));
        if ($v === '') {
            return null;
        }
        if ($v[0] !== '#') {
            $v = '#' . $v;
        }
        if (preg_match('/^#([0-9A-F]{3})$/', $v, $m)) {
            $h = $m[1];

            return '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        if (preg_match('/^#([0-9A-F]{6})$/', $v)) {
            return $v;
        }

        return null;
    }

    private function hexToRgba(?string $hex, float $alpha): ?string
    {
        $n = $this->normalizeHex($hex);
        if ($n === null) {
            return null;
        }
        $h   = ltrim($n, '#');
        $r   = hexdec(substr($h, 0, 2));
        $g   = hexdec(substr($h, 2, 2));
        $b   = hexdec(substr($h, 4, 2));
        $alpha = max(0.0, min(1.0, $alpha));

        return sprintf('rgba(%d,%d,%d,%.3f)', $r, $g, $b, $alpha);
    }

    /**
     * Color de fondo UI: hex o la palabra clave CSS "transparent".
     */
    private function resolveUiBackground(?string $stored, string $defaultHex): string
    {
        $t = strtolower(trim((string) $stored));
        if ($t === 'transparent') {
            return 'transparent';
        }

        return $this->normalizeHex($stored) ?? $defaultHex;
    }

    /**
     * Obtiene la configuración para el layout/header
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        helper('config');
        $keys = $this->appConfig->getMultiple([
            'company', 'logo', 'header_brand', 'theme_color', 'theme_gradient_end', 'website', 'currency_symbol', 'currency_side',
            'ui_font_family', 'ui_font_size_base', 'ui_font_size_main', 'ui_font_size_header', 'ui_font_size_sidebar',
            'ui_font_size_footer', 'ui_font_size_heading', 'ui_font_size_base_mobile',
            'ui_sidebar_position', 'ui_body_text_color', 'ui_sidebar_bg', 'ui_sidebar_link_color',
            'ui_sidebar_hover_bg', 'ui_sidebar_active_bg',
            'ui_navbar_text_color', 'ui_navbar_text_weight', 'ui_navbar_text_style',
            'ui_breadcrumb_bg', 'ui_breadcrumb_text_color', 'ui_breadcrumb_text_weight', 'ui_breadcrumb_text_style',
            'ui_header_bg', 'ui_header_text_color', 'ui_header_text_weight', 'ui_header_text_style',
            'ui_header_datetime_color', 'ui_header_datetime_format', 'ui_body_bg', 'ui_main_bg',
            'ui_footer_bg', 'ui_footer_text_color', 'ui_footer_text_align', 'ui_card_radius', 'ui_link_color',
            'ui_sidebar_link_weight', 'ui_sidebar_link_style',
            'ui_body_text_weight', 'ui_body_text_style',
            'ui_link_weight', 'ui_link_style',
            'ui_footer_text_weight', 'ui_footer_text_style',
            'ui_btn_primary_text_weight', 'ui_btn_primary_text_style',
            'ui_btn_primary_bg', 'ui_btn_primary_text', 'ui_btn_primary_hover_bg',
            'ui_btn_border_width', 'ui_btn_border_color', 'ui_btn_border_sides', 'ui_btn_shadow',
            'ui_card_border_width', 'ui_card_border_color', 'ui_card_border_sides', 'ui_card_shadow',
            'ui_labotests_card_header_bg', 'ui_labotests_card_header_title_color', 'ui_labotests_card_header_title_weight', 'ui_labotests_card_header_title_style',
            'ui_pagination_link_color', 'ui_pagination_link_weight', 'ui_pagination_link_style',
            'ui_pagination_active_bg', 'ui_pagination_active_color',
            'ui_delivery_notif_text', 'ui_delivery_notif_text_active', 'ui_delivery_notif_text_hover',
            'ui_delivery_notif_hover_bg', 'ui_delivery_notif_hover_opacity',
            'ui_delivery_notif_bg_active', 'ui_delivery_notif_dot',
            'ui_delivery_notif_text_weight', 'ui_delivery_notif_text_style',
        ]);

        $themeColor = '#FF7218';
        if (!empty($keys['theme_color']) && preg_match('/^#[a-fA-F0-9]{3,6}$/', $keys['theme_color'])) {
            $themeColor = $keys['theme_color'];
        }

        $themeHover  = function_exists('darken_hex_color') ? darken_hex_color($themeColor, 12) : $themeColor;
        $themeActive = function_exists('darken_hex_color') ? darken_hex_color($themeColor, 25) : $themeColor;

        $gradientEnd = $this->normalizeHex($keys['theme_gradient_end'] ?? '') ?? '#4f46e5';

        $logoPath = !empty(trim((string) ($keys['logo'] ?? ''))) ? trim($keys['logo']) : 'images/logo-john.png';
        $headerBrand = $keys['header_brand'] ?? 'logo';
        $showLogoInHeader = ($headerBrand === 'logo') && !empty($logoPath) && file_exists(FCPATH . $logoPath);

        $currencySymbol = trim((string) ($keys['currency_symbol'] ?? '$'));
        if ($currencySymbol === '') {
            $currencySymbol = '$';
        }
        $currencySideRaw = trim((string) ($keys['currency_side'] ?? 'left'));
        $currencySideLower = strtolower($currencySideRaw);
        if ($currencySideLower === 'right' || strpos($currencySideLower, 'der') === 0) {
            $currencySide = 'right';
        } else {
            $currencySide = 'left';
        }

        $fontKey = strtolower(trim((string) ($keys['ui_font_family'] ?? 'poppins')));
        if (!isset(self::FONT_PRESETS[$fontKey])) {
            $fontKey = 'poppins';
        }
        $fontPreset = self::FONT_PRESETS[$fontKey];

        $sidebarRight = strtolower(trim((string) ($keys['ui_sidebar_position'] ?? 'left'))) === 'right';

        $bodyColor = $this->normalizeHex($keys['ui_body_text_color'] ?? '') ?? '#212529';
        $sidebarBg = $this->resolveUiBackground($keys['ui_sidebar_bg'] ?? null, '#f8f9fa');
        $sidebarLink = $this->normalizeHex($keys['ui_sidebar_link_color'] ?? '');
        $sidebarHoverBg = $this->normalizeHex($keys['ui_sidebar_hover_bg'] ?? '');
        $sidebarActiveBg = $this->normalizeHex($keys['ui_sidebar_active_bg'] ?? '');

        // Encabezado fijo (<header class="navbar-theme">): degradado del tema (Marca y degradado).
        $navbarBg      = $themeColor;
        $navbarBgImage = 'linear-gradient(135deg,' . $themeColor . ',' . $gradientEnd . ')';
        $navbarText    = $this->normalizeHex($keys['ui_navbar_text_color'] ?? $keys['ui_header_text_color'] ?? '') ?? '#ffffff';
        $navbarFontW   = self::normalizeUiFontWeight((string) ($keys['ui_navbar_text_weight'] ?? $keys['ui_header_text_weight'] ?? ''), '500');
        $navbarFontS   = self::normalizeUiFontStyle((string) ($keys['ui_navbar_text_style'] ?? $keys['ui_header_text_style'] ?? ''), 'normal');

        // Barra de migas (<nav class="breadcrumb-nav-theme">): Config → Barra superior.
        $breadcrumbBgRaw = trim((string) ($keys['ui_breadcrumb_bg'] ?? $keys['ui_header_bg'] ?? ''));
        if (strtolower($breadcrumbBgRaw) === 'transparent') {
            $breadcrumbBg      = 'transparent';
            $breadcrumbBgImage = 'none';
        } elseif ($breadcrumbBgRaw === '') {
            $breadcrumbBg      = $themeColor;
            $breadcrumbBgImage = 'linear-gradient(135deg,' . $themeColor . ',' . $gradientEnd . ')';
        } else {
            $breadcrumbBg      = $this->normalizeHex($breadcrumbBgRaw) ?? $themeColor;
            $breadcrumbBgImage = 'none';
        }
        $breadcrumbText  = $this->normalizeHex($keys['ui_breadcrumb_text_color'] ?? $keys['ui_header_text_color'] ?? '') ?? '#ffffff';
        $breadcrumbFontW = self::normalizeUiFontWeight((string) ($keys['ui_breadcrumb_text_weight'] ?? $keys['ui_header_text_weight'] ?? ''), '500');
        $breadcrumbFontS = self::normalizeUiFontStyle((string) ($keys['ui_breadcrumb_text_style'] ?? $keys['ui_header_text_style'] ?? ''), 'normal');

        $headerDatetimeStored = $this->normalizeHex($keys['ui_header_datetime_color'] ?? '');
        $headerDatetimeColor = ($headerDatetimeStored !== null && $headerDatetimeStored !== '')
            ? $headerDatetimeStored
            : $navbarText;

        $mainBgStored = trim((string) ($keys['ui_main_bg'] ?? ''));
        $bodyBgStored = trim((string) ($keys['ui_body_bg'] ?? ''));
        if ($bodyBgStored === '') {
            $bodyBgStored = $mainBgStored !== '' ? $mainBgStored : '#ffffff';
        }
        $bodyBg = $this->resolveUiBackground($bodyBgStored, '#ffffff');
        $mainBg = $this->resolveUiBackground($mainBgStored !== '' ? $mainBgStored : '#ffffff', '#ffffff');
        $footerBg = $this->resolveUiBackground($keys['ui_footer_bg'] ?? null, '#f8f9fa');
        $footerText = $this->normalizeHex($keys['ui_footer_text_color'] ?? '') ?? '#6c757d';
        $linkColor = $this->normalizeHex($keys['ui_link_color'] ?? '');

        $sidebarLinkW = self::normalizeUiFontWeight((string) ($keys['ui_sidebar_link_weight'] ?? ''), '500');
        $sidebarLinkS = self::normalizeUiFontStyle((string) ($keys['ui_sidebar_link_style'] ?? ''), 'normal');
        $bodyFontW      = self::normalizeUiFontWeight((string) ($keys['ui_body_text_weight'] ?? ''), '400');
        $bodyFontS      = self::normalizeUiFontStyle((string) ($keys['ui_body_text_style'] ?? ''), 'normal');
        $linkFontW      = self::normalizeUiFontWeight((string) ($keys['ui_link_weight'] ?? ''), '400');
        $linkFontS      = self::normalizeUiFontStyle((string) ($keys['ui_link_style'] ?? ''), 'normal');
        $footerFontW    = self::normalizeUiFontWeight((string) ($keys['ui_footer_text_weight'] ?? ''), '400');
        $footerFontS    = self::normalizeUiFontStyle((string) ($keys['ui_footer_text_style'] ?? ''), 'normal');
        $btnFontW       = self::normalizeUiFontWeight((string) ($keys['ui_btn_primary_text_weight'] ?? ''), '500');
        $btnFontS       = self::normalizeUiFontStyle((string) ($keys['ui_btn_primary_text_style'] ?? ''), 'normal');

        $radius = (int) ($keys['ui_card_radius'] ?? 8);
        $radius = max(0, min(24, $radius));

        $footerAlign = strtolower(trim((string) ($keys['ui_footer_text_align'] ?? 'left')));
        if (! in_array($footerAlign, ['left', 'center', 'right'], true)) {
            $footerAlign = 'left';
        }
        $footerJustify = match ($footerAlign) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };

        $fsBase    = self::normalizeUiFontSizeRemInput((string) ($keys['ui_font_size_base'] ?? ''), '1');
        $fsMain    = self::normalizeUiFontSizeRemInput((string) ($keys['ui_font_size_main'] ?? ''), '1');
        $fsHeader  = self::normalizeUiFontSizeRemInput((string) ($keys['ui_font_size_header'] ?? ''), '1');
        $fsSidebar = self::normalizeUiFontSizeRemInput((string) ($keys['ui_font_size_sidebar'] ?? ''), '1');
        $fsFooter  = self::normalizeUiFontSizeRemInput((string) ($keys['ui_font_size_footer'] ?? ''), '0.875');
        $fsHeading = self::normalizeUiFontSizeRemInput((string) ($keys['ui_font_size_heading'] ?? ''), '1.125');
        $fsBaseMobileRaw = trim((string) ($keys['ui_font_size_base_mobile'] ?? ''));
        $fsBaseMobile    = $fsBaseMobileRaw === '' ? '' : self::normalizeUiFontSizeRemInput($fsBaseMobileRaw, '1');

        $sidebarLinkCss = $sidebarLink ?? $themeColor;
        $sidebarHoverCss = $sidebarHoverBg ?? 'rgba(0, 0, 0, 0.07)';
        $sidebarActiveCss = $sidebarActiveBg ?? 'rgba(0, 0, 0, 0.1)';

        $linkExtra = $linkColor ? '--ui-link-color:' . $linkColor . ';' : '';

        $btnBg   = $this->normalizeHex($keys['ui_btn_primary_bg'] ?? '');
        $btnText = $this->normalizeHex($keys['ui_btn_primary_text'] ?? '') ?? '#ffffff';
        $btnHov  = $this->normalizeHex($keys['ui_btn_primary_hover_bg'] ?? '');
        $btnExtra = '--ui-btn-primary-text:' . $btnText . ';';
        if ($btnBg !== null && $btnBg !== '') {
            $btnExtra .= '--ui-btn-primary-bg:' . $btnBg . ';';
        }
        if ($btnHov !== null && $btnHov !== '') {
            $btnExtra .= '--ui-btn-primary-hover-bg:' . $btnHov . ';';
        }

        $btnBorderW = (int) ($keys['ui_btn_border_width'] ?? 0);
        $btnBorderW = max(0, min(8, $btnBorderW));
        if ($btnBorderW > 0) {
            $btnBorderColor = $this->normalizeHex($keys['ui_btn_border_color'] ?? '') ?? '#000000';
            $btnSides       = self::normalizeUiBorderSides((string) ($keys['ui_btn_border_sides'] ?? 'all'));
            $btnExtra .= self::uiBorderSideVars('ui-btn', $btnBorderW, $btnBorderColor, $btnSides);
        }

        $btnShadow = self::normalizeUiShadowKey((string) ($keys['ui_btn_shadow'] ?? 'none'));
        if ($btnShadow !== 'none') {
            $btnExtra .= '--ui-btn-shadow:' . self::uiShadowCssValue($btnShadow) . ';';
        }

        $cardExtra = '';
        $cardBorderW = (int) ($keys['ui_card_border_width'] ?? 0);
        $cardBorderW = max(0, min(8, $cardBorderW));
        if ($cardBorderW > 0) {
            $cbc = trim((string) ($keys['ui_card_border_color'] ?? ''));
            $cardBorderColor = $cbc === '' ? 'var(--bs-border-color)' : ($this->normalizeHex($cbc) ?? '#dee2e6');
            $cardSides       = self::normalizeUiBorderSides((string) ($keys['ui_card_border_sides'] ?? 'all'));
            $cardExtra .= self::uiBorderSideVars('ui-card', $cardBorderW, $cardBorderColor, $cardSides);
        }

        $cardShadow = self::normalizeUiShadowKey((string) ($keys['ui_card_shadow'] ?? 'none'));
        if ($cardShadow !== 'none') {
            $cardExtra .= '--ui-card-shadow:' . self::uiShadowCssValue($cardShadow) . ';';
        }

        $fontSizeCss = sprintf(
            '--ui-font-size-base:%srem;--ui-font-size-main:%srem;--ui-font-size-header:%srem;--ui-font-size-sidebar:%srem;--ui-font-size-footer:%srem;--ui-font-size-heading:%srem;',
            $fsBase,
            $fsMain,
            $fsHeader,
            $fsSidebar,
            $fsFooter,
            $fsHeading
        );
        if ($fsBaseMobile !== '') {
            $fontSizeCss .= '--ui-font-size-base-mobile:' . $fsBaseMobile . 'rem;';
        }
        $typoExtra = sprintf(
            '--ui-navbar-font-weight:%s;--ui-navbar-font-style:%s;--ui-breadcrumb-font-weight:%s;--ui-breadcrumb-font-style:%s;--ui-sidebar-link-font-weight:%s;--ui-sidebar-link-font-style:%s;--ui-body-font-weight:%s;--ui-body-font-style:%s;--ui-link-font-weight:%s;--ui-link-font-style:%s;--ui-footer-font-weight:%s;--ui-footer-font-style:%s;--ui-btn-font-weight:%s;--ui-btn-font-style:%s;',
            $navbarFontW,
            $navbarFontS,
            $breadcrumbFontW,
            $breadcrumbFontS,
            $sidebarLinkW,
            $sidebarLinkS,
            $bodyFontW,
            $bodyFontS,
            $linkFontW,
            $linkFontS,
            $footerFontW,
            $footerFontS,
            $btnFontW,
            $btnFontS
        );

        $labotestsCardBgRaw = trim((string) ($keys['ui_labotests_card_header_bg'] ?? ''));
        if (strtolower($labotestsCardBgRaw) === 'transparent') {
            $labotestsCardBg      = 'transparent';
            $labotestsCardBgImage = 'none';
        } elseif ($labotestsCardBgRaw === '') {
            $labotestsCardBg      = $themeColor;
            $labotestsCardBgImage = 'linear-gradient(135deg,' . $themeColor . ',' . $gradientEnd . ')';
        } else {
            $labotestsCardBg      = $this->normalizeHex($labotestsCardBgRaw) ?? $themeColor;
            $labotestsCardBgImage = 'none';
        }

        $labotestsCardTitle = $this->normalizeHex($keys['ui_labotests_card_header_title_color'] ?? '') ?? '#ffffff';
        $labotestsCardTitleW = self::normalizeUiFontWeight((string) ($keys['ui_labotests_card_header_title_weight'] ?? ''), '600');
        $labotestsCardTitleS = self::normalizeUiFontStyle((string) ($keys['ui_labotests_card_header_title_style'] ?? ''), 'normal');

        $paginationLinkHex = $this->normalizeHex($keys['ui_pagination_link_color'] ?? '') ?? $themeColor;
        $paginationLinkW = self::normalizeUiFontWeight((string) ($keys['ui_pagination_link_weight'] ?? ''), '400');
        $paginationLinkS = self::normalizeUiFontStyle((string) ($keys['ui_pagination_link_style'] ?? ''), 'normal');
        $paginationActiveBg = $this->normalizeHex($keys['ui_pagination_active_bg'] ?? '') ?? $themeColor;
        $paginationActiveColor = $this->normalizeHex($keys['ui_pagination_active_color'] ?? '') ?? '#ffffff';

        $deliveryNotifExtra = '';
        $deliveryText = $this->normalizeHex($keys['ui_delivery_notif_text'] ?? '');
        if ($deliveryText !== null && $deliveryText !== '') {
            $deliveryNotifExtra .= '--ui-delivery-notif-text:' . $deliveryText . ';';
        }
        $deliveryTextActive = $this->normalizeHex($keys['ui_delivery_notif_text_active'] ?? '');
        if ($deliveryTextActive !== null && $deliveryTextActive !== '') {
            $deliveryNotifExtra .= '--ui-delivery-notif-text-active:' . $deliveryTextActive . ';';
        } elseif ($deliveryText !== null && $deliveryText !== '') {
            $deliveryNotifExtra .= '--ui-delivery-notif-text-active:' . $deliveryText . ';';
        }
        $deliveryTextHover = $this->normalizeHex($keys['ui_delivery_notif_text_hover'] ?? '');
        if ($deliveryTextHover !== null && $deliveryTextHover !== '') {
            $deliveryNotifExtra .= '--ui-delivery-notif-text-hover:' . $deliveryTextHover . ';';
        }
        $deliveryHoverOpacity = max(0, min(100, (int) ($keys['ui_delivery_notif_hover_opacity'] ?? 8)));
        $hoverBgHex = trim((string) ($keys['ui_delivery_notif_hover_bg'] ?? ''));
        $deliveryHoverRgba = $this->hexToRgba($hoverBgHex !== '' ? $hoverBgHex : '#ffffff', $deliveryHoverOpacity / 100);
        if ($deliveryHoverRgba !== null) {
            $deliveryNotifExtra .= '--ui-delivery-notif-hover-bg:' . $deliveryHoverRgba . ';';
        }
        $deliveryBgActive = $this->normalizeHex($keys['ui_delivery_notif_bg_active'] ?? '');
        if ($deliveryBgActive !== null && $deliveryBgActive !== '') {
            $deliveryNotifExtra .= '--ui-delivery-notif-bg-active:' . $deliveryBgActive . ';';
        }
        $deliveryDot = $this->normalizeHex($keys['ui_delivery_notif_dot'] ?? '');
        $deliveryNotifExtra .= '--ui-delivery-notif-dot:' . (($deliveryDot !== null && $deliveryDot !== '') ? $deliveryDot : $themeColor) . ';';
        $deliveryFontW = self::normalizeUiFontWeight((string) ($keys['ui_delivery_notif_text_weight'] ?? ''), '500');
        $deliveryFontS = self::normalizeUiFontStyle((string) ($keys['ui_delivery_notif_text_style'] ?? ''), 'normal');
        $deliveryNotifExtra .= sprintf(
            '--ui-delivery-notif-font-weight:%s;--ui-delivery-notif-font-style:%s;',
            $deliveryFontW,
            $deliveryFontS
        );

        $uiInlineStyle = '--theme-gradient-end:' . $gradientEnd . ';--ui-font-family:' . $fontPreset['family'] . ';' . $fontSizeCss
            . '--ui-footer-justify:' . $footerJustify . ';'
            . sprintf(
                '--ui-body-color:%s;--text-main:%s;--ui-sidebar-bg:%s;--ui-sidebar-link:%s;--ui-sidebar-hover-bg:%s;--ui-sidebar-active-bg:%s;--ui-navbar-bg:%s;--ui-navbar-bg-image:%s;--ui-navbar-text:%s;--ui-breadcrumb-bg:%s;--ui-breadcrumb-bg-image:%s;--ui-breadcrumb-text:%s;--ui-header-datetime-color:%s;--ui-body-bg:%s;--ui-main-bg:%s;--bg-main:%s;--ui-footer-bg:%s;--ui-footer-text:%s;--ui-card-radius:%dpx;%s%s%s%s--ui-labotests-card-header-bg:%s;--ui-labotests-card-header-bg-image:%s;--ui-labotests-card-header-title:%s;',
                $bodyColor,
                $bodyColor,
                $sidebarBg,
                $sidebarLinkCss,
                $sidebarHoverCss,
                $sidebarActiveCss,
                $navbarBg,
                $navbarBgImage,
                $navbarText,
                $breadcrumbBg,
                $breadcrumbBgImage,
                $breadcrumbText,
                $headerDatetimeColor,
                $bodyBg,
                $mainBg,
                $bodyBg,
                $footerBg,
                $footerText,
                $radius,
                $linkExtra,
                $btnExtra,
                $cardExtra,
                $typoExtra,
                $labotestsCardBg,
                $labotestsCardBgImage,
                $labotestsCardTitle
            )
            . sprintf(
                '--ui-labotests-card-header-font-weight:%s;--ui-labotests-card-header-font-style:%s;',
                $labotestsCardTitleW,
                $labotestsCardTitleS
            )
            . sprintf(
                '--ui-pagination-link-color:%s;--ui-pagination-font-weight:%s;--ui-pagination-font-style:%s;--ui-pagination-active-bg:%s;--ui-pagination-active-color:%s;',
                $paginationLinkHex,
                $paginationLinkW,
                $paginationLinkS,
                $paginationActiveBg,
                $paginationActiveColor
            )
            . $deliveryNotifExtra;

        return [
            'company'              => $keys['company'] ?? 'Laboratorio',
            'logo'                 => $logoPath,
            'header_brand'         => $headerBrand,
            'show_logo'            => $showLogoInHeader,
            'theme_color'          => $themeColor,
            'theme_gradient_end'   => $gradientEnd,
            'theme_hover'          => $themeHover,
            'theme_active'         => $themeActive,
            'website'              => trim($keys['website'] ?? ''),
            'currency_symbol'      => $currencySymbol,
            'currency_side'        => $currencySide,
            'ui_font_family_key'   => $fontKey,
            'ui_font_family'       => $fontPreset['family'],
            'ui_google_font_href'  => $fontPreset['href'],
            'ui_use_inter_css'     => $fontPreset['use_inter_css'],
            'sidebar_right'          => $sidebarRight,
            'ui_inline_style'        => $uiInlineStyle,
            'header_datetime_format' => self::normalizeHeaderDatetimeFormat((string) ($keys['ui_header_datetime_format'] ?? '')),
        ];
    }

    /**
     * Etiquetas para el select de fuente en Configuración → Apariencia.
     *
     * @return array<string, string>
     */
    public static function uiFontOptionsForView(): array
    {
        return [
            'system'       => 'Sistema (fuente del dispositivo)',
            'poppins'      => 'Poppins',
            'inter'        => 'Inter',
            'roboto'       => 'Roboto',
            'open_sans'    => 'Open Sans',
            'lato'         => 'Lato',
            'nunito'       => 'Nunito',
            'ubuntu'       => 'Ubuntu',
            'montserrat'   => 'Montserrat',
            'raleway'      => 'Raleway',
            'work_sans'    => 'Work Sans',
            'source_sans'  => 'Source Sans 3',
            'rubik'        => 'Rubik',
            'manrope'      => 'Manrope',
            'mulish'       => 'Mulish',
            'karla'        => 'Karla',
            'quicksand'    => 'Quicksand (redondeada)',
            'fira_sans'    => 'Fira Sans',
            'merriweather' => 'Merriweather (serif)',
            'lora'         => 'Lora (serif)',
            'playfair'     => 'Playfair Display (serif)',
        ];
    }

    /**
     * URL combinada de Google Fonts para previsualizar todas las tipografías
     * en Configuración → Apariencia (solo se carga en esa página).
     */
    public static function uiFontPreviewGoogleHref(): ?string
    {
        $params = [];
        foreach (self::FONT_PRESETS as $preset) {
            $href = $preset['href'] ?? null;
            if (! is_string($href) || $href === '') {
                continue;
            }
            $query = (string) parse_url($href, PHP_URL_QUERY);
            foreach (explode('&', $query) as $pair) {
                if (str_starts_with($pair, 'family=')) {
                    $params[] = $pair;
                }
            }
        }
        if ($params === []) {
            return null;
        }

        return 'https://fonts.googleapis.com/css2?' . implode('&', array_unique($params)) . '&display=swap';
    }

    /**
     * Pila font-family para vista previa en Config → Apariencia.
     */
    public static function uiFontFamilyCssStackForKey(string $fontKey): string
    {
        $k = strtolower(trim($fontKey));
        if (! isset(self::FONT_PRESETS[$k])) {
            $k = 'poppins';
        }

        return self::FONT_PRESETS[$k]['family'];
    }

    /**
     * Opciones de tamaño (rem) para Configuración → Apariencia.
     *
     * @return array<string, string>
     */
    public static function uiFontSizeRemOptionsForView(): array
    {
        return self::FONT_SIZE_REM_PRESETS;
    }

    /**
     * Valida y devuelve una clave de tamaño en rem (sin unidad) para guardar o aplicar.
     */
    public static function normalizeUiFontSizeRemInput(string $value, string $default): string
    {
        $v = str_replace(',', '.', trim($value));
        $allowed = array_keys(self::FONT_SIZE_REM_PRESETS);
        if ($v !== '' && in_array($v, $allowed, true)) {
            return $v;
        }

        return in_array($default, $allowed, true) ? $default : '1';
    }

    /** @var list<string> */
    private const UI_FONT_WEIGHTS = ['100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold', 'lighter', 'bolder'];

    /** @var list<string> */
    private const UI_FONT_STYLES = ['normal', 'italic', 'oblique'];

    /**
     * @return array<string, string>
     */
    public static function uiFontWeightOptionsForView(): array
    {
        return [
            '100'     => '100 — Muy fino',
            '200'     => '200 — Extra fino',
            '300'     => '300 — Fino',
            '400'     => '400 — Normal / regular',
            '500'     => '500 — Medio',
            '600'     => '600 — Seminegrita',
            '700'     => '700 — Negrita',
            '800'     => '800 — Extra negrita',
            '900'     => '900 — Negro',
            'normal'  => 'normal (palabra clave CSS)',
            'bold'    => 'bold (palabra clave CSS)',
            'lighter' => 'lighter (más fino que el padre)',
            'bolder'  => 'bolder (más grueso que el padre)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function uiFontStyleOptionsForView(): array
    {
        return [
            'normal'  => 'Normal',
            'italic'  => 'Cursiva',
            'oblique' => 'Oblicua',
        ];
    }

    public static function normalizeUiFontWeight(string $value, string $default): string
    {
        $v = strtolower(trim($value));

        return in_array($v, self::UI_FONT_WEIGHTS, true) ? $v : $default;
    }

    public static function normalizeUiFontStyle(string $value, string $default): string
    {
        $v = strtolower(trim($value));

        return in_array($v, self::UI_FONT_STYLES, true) ? $v : $default;
    }

    /**
     * @return array<string, string>
     */
    public static function uiBorderSidesOptionsForView(): array
    {
        return [
            'all'    => 'Todos los lados',
            'none'   => 'Sin borde visible (solo sombra si aplica)',
            'top'    => 'Solo arriba',
            'right'  => 'Solo derecha',
            'bottom' => 'Solo abajo',
            'left'   => 'Solo izquierda',
            'tb'     => 'Arriba y abajo',
            'lr'     => 'Izquierda y derecha',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function uiShadowOptionsForView(): array
    {
        return [
            'none' => 'Sin sombra',
            'sm'   => 'Sombra suave',
            'md'   => 'Sombra media',
            'lg'   => 'Sombra marcada',
        ];
    }

    public static function normalizeUiBorderSides(string $value): string
    {
        $v = strtolower(trim($value));
        $ok = ['all', 'none', 'top', 'right', 'bottom', 'left', 'tb', 'lr'];

        return in_array($v, $ok, true) ? $v : 'all';
    }

    public static function normalizeUiShadowKey(string $value): string
    {
        $v = strtolower(trim($value));
        $ok = ['none', 'sm', 'md', 'lg'];

        return in_array($v, $ok, true) ? $v : 'none';
    }

    public static function uiShadowCssValue(string $key): string
    {
        return match ($key) {
            'sm' => '0 1px 3px rgba(0,0,0,0.08)',
            'md' => '0 4px 14px rgba(0,0,0,0.12)',
            'lg' => '0 10px 28px rgba(0,0,0,0.16)',
            default => 'none',
        };
    }

    /**
     * Variables CSS por lado: --{prefix}-bt, -br, -bb, -bl.
     */
    public static function uiBorderSideVars(string $prefix, int $width, string $color, string $sides): string
    {
        $w = max(0, min(8, $width));
        $n = 'none';
        if ($w <= 0 || $sides === 'none') {
            return "--{$prefix}-bt:{$n};--{$prefix}-br:{$n};--{$prefix}-bb:{$n};--{$prefix}-bl:{$n};";
        }
        $line = "{$w}px solid {$color}";
        $map  = [
            'all'    => [$line, $line, $line, $line],
            'top'    => [$line, $n, $n, $n],
            'right'  => [$n, $line, $n, $n],
            'bottom' => [$n, $n, $line, $n],
            'left'   => [$n, $n, $n, $line],
            'tb'     => [$line, $n, $line, $n],
            'lr'     => [$n, $line, $n, $line],
        ];
        $s = $map[$sides] ?? $map['all'];

        return "--{$prefix}-bt:{$s[0]};--{$prefix}-br:{$s[1]};--{$prefix}-bb:{$s[2]};--{$prefix}-bl:{$s[3]};";
    }

    /**
     * Color de primer plano legible sobre un fondo (contraste aproximado ~WCAG).
     * Si $preferred contrasta poco, devuelve texto claro u oscuro según luminancia del fondo.
     *
     * @param bool $preferBlueLink Si true, ante bajo contraste usa un azul visible sobre fondo oscuro
     */
    public static function readableForegroundOnBackground(string $bgHex, string $preferredFgHex, bool $preferBlueLink = false): string
    {
        $parse = static function (string $h): ?array {
            $h = strtoupper(ltrim(trim($h), '#'));
            if ($h === '') {
                return null;
            }
            if (preg_match('/^([0-9A-F]{3})$/', $h, $m)) {
                $x = $m[1];

                return [
                    hexdec($x[0] . $x[0]),
                    hexdec($x[1] . $x[1]),
                    hexdec($x[2] . $x[2]),
                ];
            }
            if (preg_match('/^([0-9A-F]{6})$/', $h)) {
                return [
                    hexdec(substr($h, 0, 2)),
                    hexdec(substr($h, 2, 2)),
                    hexdec(substr($h, 4, 2)),
                ];
            }

            return null;
        };

        $relL = static function (array $rgb): float {
            $lin = [];
            foreach ($rgb as $c) {
                $c /= 255;
                $lin[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }

            return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
        };

        $toHex = static function (array $rgb): string {
            return '#' . sprintf('%02X%02X%02X', max(0, min(255, $rgb[0])), max(0, min(255, $rgb[1])), max(0, min(255, $rgb[2])));
        };

        $bg = $parse($bgHex) ?? [248, 249, 250];
        $fg = $parse($preferredFgHex);
        if ($fg === null) {
            return $relL($bg) < 0.45 ? '#F8F9FA' : '#212529';
        }

        $L1 = $relL($bg) + 0.05;
        $L2 = $relL($fg) + 0.05;
        $ratio = $L1 > $L2 ? $L1 / $L2 : $L2 / $L1;
        if ($ratio >= 3.0) {
            return $toHex($fg);
        }

        $darkBg = $relL($bg) < 0.45;
        if ($preferBlueLink) {
            return $darkBg ? '#93C5FD' : '#0A58CA';
        }

        return $darkBg ? '#F8F9FA' : '#212529';
    }

    /**
     * Formatos de fecha/hora del reloj del header (clave => definición).
     *
     * @return array<string, array{php: string, has_seconds: bool, label_key: string, preview: string, js: string}>
     */
    public static function headerDatetimeFormatPresets(): array
    {
        return [
            'dmY_hi' => [
                'php'         => 'd/m/Y H:i',
                'has_seconds' => false,
                'label_key'   => 'Config.config_style_header_datetime_fmt_dmY_hi',
                'preview'     => '01/06/2026 14:30',
                'js'          => 'dmY_hi',
            ],
            'dmY_his' => [
                'php'         => 'd/m/Y H:i:s',
                'has_seconds' => true,
                'label_key'   => 'Config.config_style_header_datetime_fmt_dmY_his',
                'preview'     => '01/06/2026 14:30:45',
                'js'          => 'dmY_his',
            ],
            'dm_hi' => [
                'php'         => 'd/m H:i',
                'has_seconds' => false,
                'label_key'   => 'Config.config_style_header_datetime_fmt_dm_hi',
                'preview'     => '01/06 14:30',
                'js'          => 'dm_hi',
            ],
            'ymd_hi' => [
                'php'         => 'Y-m-d H:i',
                'has_seconds' => false,
                'label_key'   => 'Config.config_style_header_datetime_fmt_ymd_hi',
                'preview'     => '2026-06-01 14:30',
                'js'          => 'ymd_hi',
            ],
            'dmY_hi_12' => [
                'php'         => 'd/m/Y h:i A',
                'has_seconds' => false,
                'label_key'   => 'Config.config_style_header_datetime_fmt_dmY_hi_12',
                'preview'     => '01/06/2026 02:30 PM',
                'js'          => 'dmY_hi_12',
            ],
            'long_es' => [
                'php'         => 'd/m/Y H:i',
                'has_seconds' => false,
                'label_key'   => 'Config.config_style_header_datetime_fmt_long_es',
                'preview'     => '1 jun 2026, 14:30',
                'js'          => 'long_es',
            ],
        ];
    }

    public static function normalizeHeaderDatetimeFormat(string $value): string
    {
        $key = strtolower(trim($value));
        $presets = self::headerDatetimeFormatPresets();

        return isset($presets[$key]) ? $key : 'dmY_hi';
    }

    /**
     * @return array<string, string> clave => etiqueta traducida
     */
    public static function headerDatetimeFormatOptionsForView(): array
    {
        $out = [];
        foreach (self::headerDatetimeFormatPresets() as $key => $preset) {
            $out[$key] = lang($preset['label_key']);
        }

        return $out;
    }

    public static function headerDatetimeFormatHasSeconds(string $formatKey): bool
    {
        $key = self::normalizeHeaderDatetimeFormat($formatKey);
        $presets = self::headerDatetimeFormatPresets();

        return ! empty($presets[$key]['has_seconds']);
    }

    public static function formatHeaderDatetime(\DateTimeInterface $dt, string $formatKey): string
    {
        $key = self::normalizeHeaderDatetimeFormat($formatKey);
        $presets = self::headerDatetimeFormatPresets();

        if ($key === 'long_es' && class_exists(\IntlDateFormatter::class)) {
            try {
                $tz = $dt->getTimezone()->getName();
                $fmt = new \IntlDateFormatter(
                    'es_ES',
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::SHORT,
                    $tz,
                    \IntlDateFormatter::GREGORIAN,
                    'd MMM y, HH:mm'
                );
                $formatted = $fmt->format($dt);

                return $formatted !== false ? $formatted : $dt->format($presets[$key]['php']);
            } catch (\Throwable $e) {
                return $dt->format($presets[$key]['php']);
            }
        }

        return $dt->format($presets[$key]['php']);
    }
}
