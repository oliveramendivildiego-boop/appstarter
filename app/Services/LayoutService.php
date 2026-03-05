<?php

namespace App\Services;

use App\Models\AppConfigModel;

/**
 * Servicio para datos del layout (logo, tema, empresa).
 * Centraliza el acceso a configuración que usan header y layout.
 */
class LayoutService
{
    protected AppConfigModel $appConfig;

    public function __construct()
    {
        $this->appConfig = model(AppConfigModel::class);
    }

    /**
     * Obtiene la configuración para el layout/header
     */
    public function getConfig(): array
    {
        helper('config');
        $keys = $this->appConfig->getMultiple(['company', 'logo', 'header_brand', 'theme_color', 'website']);

        $themeColor = '#FF7218';
        if (!empty($keys['theme_color']) && preg_match('/^#[a-fA-F0-9]{3,6}$/', $keys['theme_color'])) {
            $themeColor = $keys['theme_color'];
        }

        $themeHover = function_exists('darken_hex_color') ? darken_hex_color($themeColor, 12) : $themeColor;
        $themeActive = function_exists('darken_hex_color') ? darken_hex_color($themeColor, 25) : $themeColor;

        $logoPath = !empty(trim((string)($keys['logo'] ?? ''))) ? trim($keys['logo']) : 'images/logo-john.png';
        $headerBrand = $keys['header_brand'] ?? 'logo';
        $showLogoInHeader = ($headerBrand === 'logo') && !empty($logoPath) && file_exists(FCPATH . $logoPath);

        return [
            'company'         => $keys['company'] ?? 'Laboratorio',
            'logo'            => $logoPath,
            'header_brand'    => $headerBrand,
            'show_logo'       => $showLogoInHeader,
            'theme_color'     => $themeColor,
            'theme_hover'     => $themeHover,
            'theme_active'    => $themeActive,
            'website'         => trim($keys['website'] ?? ''),
        ];
    }
}
