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
    ];

    public function __construct()
    {
        $this->appConfig = model(AppConfigModel::class);
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
            'ui_font_size_footer', 'ui_font_size_heading',
            'ui_sidebar_position', 'ui_body_text_color', 'ui_sidebar_bg', 'ui_sidebar_link_color',
            'ui_sidebar_hover_bg', 'ui_sidebar_active_bg', 'ui_header_bg', 'ui_header_text_color', 'ui_main_bg',
            'ui_footer_bg', 'ui_footer_text_color', 'ui_footer_text_align', 'ui_card_radius', 'ui_link_color',
            'ui_header_text_weight', 'ui_header_text_style',
            'ui_sidebar_link_weight', 'ui_sidebar_link_style',
            'ui_body_text_weight', 'ui_body_text_style',
            'ui_link_weight', 'ui_link_style',
            'ui_footer_text_weight', 'ui_footer_text_style',
            'ui_btn_primary_text_weight', 'ui_btn_primary_text_style',
            'ui_btn_primary_bg', 'ui_btn_primary_text', 'ui_btn_primary_hover_bg',
            'ui_btn_border_width', 'ui_btn_border_color', 'ui_btn_border_sides', 'ui_btn_shadow',
            'ui_card_border_width', 'ui_card_border_color', 'ui_card_border_sides', 'ui_card_shadow',
            'ui_labotests_card_header_title_color', 'ui_labotests_card_header_title_weight', 'ui_labotests_card_header_title_style',
            'ui_pagination_link_color', 'ui_pagination_link_weight', 'ui_pagination_link_style',
            'ui_pagination_active_bg', 'ui_pagination_active_color',
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

        $headerBgRaw = trim((string) ($keys['ui_header_bg'] ?? ''));
        if (strtolower($headerBgRaw) === 'transparent') {
            $headerBg = 'transparent';
        } elseif ($headerBgRaw === '') {
            $headerBg = $themeColor;
        } else {
            $headerBg = $this->normalizeHex($headerBgRaw) ?? $themeColor;
        }
        $headerText = $this->normalizeHex($keys['ui_header_text_color'] ?? '') ?? '#ffffff';

        $mainBg = $this->resolveUiBackground($keys['ui_main_bg'] ?? null, '#ffffff');
        $footerBg = $this->resolveUiBackground($keys['ui_footer_bg'] ?? null, '#f8f9fa');
        $footerText = $this->normalizeHex($keys['ui_footer_text_color'] ?? '') ?? '#6c757d';
        $linkColor = $this->normalizeHex($keys['ui_link_color'] ?? '');

        $headerFontW    = self::normalizeUiFontWeight((string) ($keys['ui_header_text_weight'] ?? ''), '500');
        $headerFontS    = self::normalizeUiFontStyle((string) ($keys['ui_header_text_style'] ?? ''), 'normal');
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
        $typoExtra = sprintf(
            '--ui-header-font-weight:%s;--ui-header-font-style:%s;--ui-sidebar-link-font-weight:%s;--ui-sidebar-link-font-style:%s;--ui-body-font-weight:%s;--ui-body-font-style:%s;--ui-link-font-weight:%s;--ui-link-font-style:%s;--ui-footer-font-weight:%s;--ui-footer-font-style:%s;--ui-btn-font-weight:%s;--ui-btn-font-style:%s;',
            $headerFontW,
            $headerFontS,
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

        $labotestsCardTitle = $this->normalizeHex($keys['ui_labotests_card_header_title_color'] ?? '') ?? '#ffffff';
        $labotestsCardTitleW = self::normalizeUiFontWeight((string) ($keys['ui_labotests_card_header_title_weight'] ?? ''), '600');
        $labotestsCardTitleS = self::normalizeUiFontStyle((string) ($keys['ui_labotests_card_header_title_style'] ?? ''), 'normal');

        $paginationLinkHex = $this->normalizeHex($keys['ui_pagination_link_color'] ?? '') ?? $themeColor;
        $paginationLinkW = self::normalizeUiFontWeight((string) ($keys['ui_pagination_link_weight'] ?? ''), '400');
        $paginationLinkS = self::normalizeUiFontStyle((string) ($keys['ui_pagination_link_style'] ?? ''), 'normal');
        $paginationActiveBg = $this->normalizeHex($keys['ui_pagination_active_bg'] ?? '') ?? $themeColor;
        $paginationActiveColor = $this->normalizeHex($keys['ui_pagination_active_color'] ?? '') ?? '#ffffff';

        $uiInlineStyle = '--theme-gradient-end:' . $gradientEnd . ';--ui-font-family:' . $fontPreset['family'] . ';' . $fontSizeCss
            . '--ui-footer-justify:' . $footerJustify . ';'
            . sprintf(
                '--ui-body-color:%s;--ui-sidebar-bg:%s;--ui-sidebar-link:%s;--ui-sidebar-hover-bg:%s;--ui-sidebar-active-bg:%s;--ui-header-bg:%s;--ui-header-text:%s;--ui-main-bg:%s;--ui-footer-bg:%s;--ui-footer-text:%s;--ui-card-radius:%dpx;%s%s%s%s--ui-labotests-card-header-title:%s;',
                $bodyColor,
                $sidebarBg,
                $sidebarLinkCss,
                $sidebarHoverCss,
                $sidebarActiveCss,
                $headerBg,
                $headerText,
                $mainBg,
                $footerBg,
                $footerText,
                $radius,
                $linkExtra,
                $btnExtra,
                $cardExtra,
                $typoExtra,
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
            );

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
            'sidebar_right'        => $sidebarRight,
            'ui_inline_style'      => $uiInlineStyle,
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
            'merriweather' => 'Merriweather (serif)',
        ];
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
}
