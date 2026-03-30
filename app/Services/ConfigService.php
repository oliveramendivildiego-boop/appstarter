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
}
