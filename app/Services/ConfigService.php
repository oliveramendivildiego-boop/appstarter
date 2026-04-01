<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\ReportPdfTemplateModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Servicio de configuración del sistema.
 * Lógica de negocio para guardar config, validar logo, etc.
 */
class ConfigService
{
    protected AppConfigModel $appConfigModel;

    public function __construct(?AppConfigModel $appConfigModel = null)
    {
        $this->appConfigModel = $appConfigModel ?? model(AppConfigModel::class);
    }

    private const CACHE_KEY = 'app_config_array';
    private const CACHE_TTL = 300;

    public function getAllAsArray(): array
    {
        $cache = \Config\Services::cache();
        $cached = $cache->get(self::CACHE_KEY);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }
        $rows = $this->appConfigModel->findAll();
        $data = [];
        foreach ($rows as $row) {
            $data[$row->key] = $row->value;
        }
        $data['theme_color'] ??= '#FF7218';
        $data['theme_gradient_end'] ??= '#4f46e5';
        $data['ui_font_size_base'] ??= '1';
        $data['ui_font_size_main'] ??= '1';
        $data['ui_font_size_header'] ??= '1';
        $data['ui_font_size_sidebar'] ??= '1';
        $data['ui_font_size_footer'] ??= '0.875';
        $data['ui_font_size_heading'] ??= '1.125';
        $data['ui_footer_text_align'] ??= 'left';
        $cache->save(self::CACHE_KEY, $data, self::CACHE_TTL);
        return $data;
    }

    public function invalidateCache(): void
    {
        \Config\Services::cache()->delete(self::CACHE_KEY);
    }

    /**
     * Guarda la configuración de WhatsApp
     */
    public function saveWhatsappConfig(array $postData): bool
    {
        $keys = [
            'whatsapp_provider',
            'whatsapp_base_url',
            'whatsapp_meta_phone_id',
            'whatsapp_meta_token',
            'whatsapp_meta_template',
            'whatsapp_meta_lang',
            'whatsapp_twilio_account_sid',
            'whatsapp_twilio_auth_token',
            'whatsapp_twilio_from',
            'whatsapp_message_paciente',
            'whatsapp_message_doctor',
        ];
        $batch = [];
        foreach ($keys as $k) {
            $batch[$k] = trim($postData[$k] ?? '');
        }
        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }
        return $ok;
    }

    /**
     * Procesa y guarda la configuración desde datos POST
     */
    public function saveFromRequest(array $postData, ?UploadedFile $logoFile = null): array
    {
        $keys = [
            'company', 'address', 'phone', 'email', 'fax', 'website',
            'language', 'timezone', 'currency_symbol', 'currency_side',
            'default_tax_rate', 'default_tax_1_name', 'default_tax_1_rate',
            'default_tax_2_name', 'default_tax_2_rate', 'return_policy',
            'print_after_sale', 'logo', 'theme_color', 'header_brand',
            'decimales_sugerencia', 'dias_alerta_vencimiento', 'show_order_barcode', 'leyendas_enabled',
            'custom1_name', 'custom2_name', 'custom3_name', 'custom4_name', 'custom5_name',
            'custom6_name', 'custom7_name', 'custom8_name', 'custom9_name', 'custom10_name',
        ];

        $batch = array_filter(
            array_intersect_key($postData, array_flip($keys)),
            fn (mixed $v, mixed $k): bool => in_array($k, ['decimales_sugerencia', 'dias_alerta_vencimiento']) || ($v !== null && $v !== ''),
            ARRAY_FILTER_USE_BOTH
        );
        if (isset($batch['decimales_sugerencia'])) {
            $batch['decimales_sugerencia'] = (string) max(0, min(10, (int) $batch['decimales_sugerencia']));
        }
        if (isset($batch['dias_alerta_vencimiento'])) {
            $val = (int) $batch['dias_alerta_vencimiento'];
            $batch['dias_alerta_vencimiento'] = (string) ($val > 0 ? max(1, min(365, $val)) : 40);
        }
        if (array_key_exists('show_order_barcode', $postData)) {
            $batch['show_order_barcode'] = ($postData['show_order_barcode'] === '1') ? '1' : '0';
        }
        if (array_key_exists('leyendas_enabled', $postData)) {
            $batch['leyendas_enabled'] = ($postData['leyendas_enabled'] === '1') ? '1' : '0';
        }
        if (array_key_exists('registro_folio_format', $postData)) {
            $fmt = trim((string) $postData['registro_folio_format']);
            if (strlen($fmt) > 128) {
                $fmt = mb_substr($fmt, 0, 128);
            }
            $batch['registro_folio_format'] = $fmt;
        }

        if (array_key_exists('pdf_result_template_id', $postData)) {
            $tid = (int) $postData['pdf_result_template_id'];
            if ($tid > 0) {
                try {
                    $tplModel = model(ReportPdfTemplateModel::class);
                    if ($tplModel->find($tid)) {
                        $batch['pdf_result_template_id'] = (string) $tid;
                    }
                } catch (\Throwable $e) {
                    // tabla aún no migrada u otro error: no guardar clave inválida
                }
            }
        }
        if (array_key_exists('print_result_template_id', $postData)) {
            $tidP = (int) $postData['print_result_template_id'];
            if ($tidP > 0) {
                try {
                    $tplModel = model(ReportPdfTemplateModel::class);
                    if ($tplModel->find($tidP)) {
                        $batch['print_result_template_id'] = (string) $tidP;
                    }
                } catch (\Throwable $e) {
                    // ignorar
                }
            }
        }

        $logoFailed = false;
        if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
            $logoPath = $this->processLogoUpload($logoFile);
            if ($logoPath) {
                $batch['logo'] = $logoPath;
            } else {
                $logoFailed = true;
            }
        }

        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }

        $message = $ok ? lang('Config.config_saved') : lang('Config.config_error');
        if ($ok && $logoFailed) {
            $message = lang('Config.config_saved') . ' ' . lang('Config.config_logo_error');
        }

        $out = [
            'success' => $ok,
            'message' => $message,
        ];
        if ($ok && !empty($batch['logo'])) {
            $out['logo_path'] = $batch['logo'];
        }

        return $out;
    }

    protected function processLogoUpload(UploadedFile $file): ?string
    {
        if ($file->getSize() > 2 * 1024 * 1024) { // 2MB
            return null;
        }

        $tmp = $file->getTempName();
        if ($tmp === '' || !is_readable($tmp)) {
            return null;
        }

        // Validar por contenido (getMimeType() falla a menudo en Windows: octet-stream, pjpeg, etc.)
        $info = @getimagesize($tmp);
        if ($info === false) {
            return null;
        }

        $type = (int) ($info[2] ?? 0);
        $extMap = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
        ];
        if (defined('IMAGETYPE_WEBP')) {
            $extMap[IMAGETYPE_WEBP] = 'webp';
        }
        if (!isset($extMap[$type])) {
            return null;
        }
        $ext = $extMap[$type];

        $uploadPath = FCPATH . 'images' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $newName = 'logo-lab.' . $ext;
        if (!$file->move($uploadPath, $newName, true)) {
            return null;
        }

        // Dejar un solo archivo logo-lab.* (evita que quede logo-lab.png viejo al pasar a .jpg)
        foreach (glob($uploadPath . 'logo-lab.*') ?: [] as $old) {
            if (is_file($old) && basename($old) !== $newName) {
                @unlink($old);
            }
        }

        return 'images/' . $newName;
    }

    /**
     * Guarda opciones de apariencia (pestaña Config → Apariencia).
     */
    public function saveUiStyleFromRequest(array $post): bool
    {
        $fonts = array_keys(\App\Services\LayoutService::uiFontOptionsForView());
        $font  = strtolower(trim((string) ($post['ui_font_family'] ?? 'poppins')));
        if (! in_array($font, $fonts, true)) {
            $font = 'poppins';
        }
        $side = strtolower(trim((string) ($post['ui_sidebar_position'] ?? 'left')));
        $side = ($side === 'right') ? 'right' : 'left';

        $radius = (int) ($post['ui_card_radius'] ?? 8);
        $radius = max(0, min(24, $radius));

        $normFs = static function (string $postKey, string $default) use ($post): string {
            return \App\Services\LayoutService::normalizeUiFontSizeRemInput((string) ($post[$postKey] ?? ''), $default);
        };

        $headerCustom = (($post['ui_header_mode'] ?? '') === 'custom');
        $linkCustom   = (($post['ui_sidebar_link_mode'] ?? '') === 'custom');

        $footerAlign = strtolower(trim((string) ($post['ui_footer_text_align'] ?? 'left')));
        if (! in_array($footerAlign, ['left', 'center', 'right'], true)) {
            $footerAlign = 'left';
        }

        $batch = [
            'theme_color'         => $this->normalizeUiHex((string) ($post['theme_color'] ?? ''), '#FF7218'),
            'theme_gradient_end'  => $this->normalizeUiHex((string) ($post['theme_gradient_end'] ?? ''), '#4f46e5'),
            'ui_font_family'        => $font,
            'ui_font_size_base'     => $normFs('ui_font_size_base', '1'),
            'ui_font_size_main'     => $normFs('ui_font_size_main', '1'),
            'ui_font_size_header'   => $normFs('ui_font_size_header', '1'),
            'ui_font_size_sidebar'  => $normFs('ui_font_size_sidebar', '1'),
            'ui_font_size_footer'   => $normFs('ui_font_size_footer', '0.875'),
            'ui_font_size_heading'  => $normFs('ui_font_size_heading', '1.125'),
            'ui_sidebar_position'   => $side,
            'ui_body_text_color'    => $this->normalizeUiHex((string) ($post['ui_body_text_color'] ?? ''), '#212529'),
            'ui_sidebar_bg'         => $this->normalizeUiHex((string) ($post['ui_sidebar_bg'] ?? ''), '#f8f9fa'),
            'ui_sidebar_link_color' => $linkCustom
                ? $this->normalizeUiHex((string) ($post['ui_sidebar_link_custom'] ?? ''), '#0d6efd')
                : '',
            'ui_sidebar_hover_bg' => ! empty($post['ui_sidebar_hover_default'])
                ? ''
                : $this->normalizeUiHex((string) ($post['ui_sidebar_hover_bg'] ?? ''), '#dee2e6'),
            'ui_sidebar_active_bg' => ! empty($post['ui_sidebar_active_default'])
                ? ''
                : $this->normalizeUiHex((string) ($post['ui_sidebar_active_bg'] ?? ''), '#ced4da'),
            'ui_header_bg'          => $headerCustom
                ? $this->normalizeUiHex((string) ($post['ui_header_bg_custom'] ?? ''), '#0d6efd')
                : '',
            'ui_header_text_color' => $this->normalizeUiHex((string) ($post['ui_header_text_color'] ?? ''), '#ffffff'),
            'ui_main_bg'            => $this->normalizeUiHex((string) ($post['ui_main_bg'] ?? ''), '#ffffff'),
            'ui_footer_bg'          => $this->normalizeUiHex((string) ($post['ui_footer_bg'] ?? ''), '#f8f9fa'),
            'ui_footer_text_color' => $this->normalizeUiHex((string) ($post['ui_footer_text_color'] ?? ''), '#6c757d'),
            'ui_footer_text_align' => $footerAlign,
            'ui_link_color' => ! empty($post['ui_link_default'])
                ? ''
                : $this->normalizeUiHex((string) ($post['ui_link_color'] ?? ''), '#0d6efd'),
            'ui_card_radius'        => (string) $radius,
        ];

        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }

        return $ok;
    }

    private function normalizeUiHex(string $value, string $fallback): string
    {
        $out = $this->normalizeUiHexOrEmpty($value);

        return $out !== '' ? $out : $fallback;
    }

    private function normalizeUiHexOrEmpty(string $value): string
    {
        $v = strtoupper(trim($value));
        if ($v === '') {
            return '';
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

        return '';
    }
}
