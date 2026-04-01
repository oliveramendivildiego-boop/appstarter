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
        $sidebarBg = $this->normalizeHex($keys['ui_sidebar_bg'] ?? '') ?? '#f8f9fa';
        $sidebarLink = $this->normalizeHex($keys['ui_sidebar_link_color'] ?? '');
        $sidebarHoverBg = $this->normalizeHex($keys['ui_sidebar_hover_bg'] ?? '');
        $sidebarActiveBg = $this->normalizeHex($keys['ui_sidebar_active_bg'] ?? '');

        $headerBg = $this->normalizeHex($keys['ui_header_bg'] ?? '') ?? $themeColor;
        $headerText = $this->normalizeHex($keys['ui_header_text_color'] ?? '') ?? '#ffffff';

        $mainBg = $this->normalizeHex($keys['ui_main_bg'] ?? '') ?? '#ffffff';
        $footerBg = $this->normalizeHex($keys['ui_footer_bg'] ?? '') ?? '#f8f9fa';
        $footerText = $this->normalizeHex($keys['ui_footer_text_color'] ?? '') ?? '#6c757d';
        $linkColor = $this->normalizeHex($keys['ui_link_color'] ?? '');

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
        $fontSizeCss = sprintf(
            '--ui-font-size-base:%srem;--ui-font-size-main:%srem;--ui-font-size-header:%srem;--ui-font-size-sidebar:%srem;--ui-font-size-footer:%srem;--ui-font-size-heading:%srem;',
            $fsBase,
            $fsMain,
            $fsHeader,
            $fsSidebar,
            $fsFooter,
            $fsHeading
        );
        $uiInlineStyle = '--theme-gradient-end:' . $gradientEnd . ';--ui-font-family:' . $fontPreset['family'] . ';' . $fontSizeCss
            . '--ui-footer-justify:' . $footerJustify . ';'
            . sprintf(
                '--ui-body-color:%s;--ui-sidebar-bg:%s;--ui-sidebar-link:%s;--ui-sidebar-hover-bg:%s;--ui-sidebar-active-bg:%s;--ui-header-bg:%s;--ui-header-text:%s;--ui-main-bg:%s;--ui-footer-bg:%s;--ui-footer-text:%s;--ui-card-radius:%dpx;%s',
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
                $linkExtra
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
}
