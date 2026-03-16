<?php

namespace App\Services;

use App\Models\AppConfigModel;
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
            'decimales_sugerencia', 'dias_alerta_vencimiento',
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

        if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
            $logoPath = $this->processLogoUpload($logoFile);
            if ($logoPath) {
                $batch['logo'] = $logoPath;
            }
        }

        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }

        return [
            'success' => $ok,
            'message' => $ok ? lang('Config.config_saved') : lang('Config.config_error'),
        ];
    }

    /**
     * Guarda la configuración de SIN (Servicio de Impuestos Nacionales)
     */
    public function saveSinConfig(array $postData): bool
    {
        $keys = [
            'sin_billing_enabled',
            'sin_api_endpoint',
            'sin_certificate_path',
            'sin_certificate_password',
            'sin_nit',
            'sin_business_name',
            'sin_branch_code',
            'sin_system_type',
            'sin_emission_mode',
            'sin_activity_code',
        ];
        $batch = [];
        foreach ($keys as $k) {
            if ($k === 'sin_billing_enabled') {
                $batch[$k] = (isset($postData[$k]) && $postData[$k] == '1') ? '1' : '0';
            } else {
                $batch[$k] = trim($postData[$k] ?? '');
            }
        }
        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }
        return $ok;
    }

    /**
     * Prueba la conexión con SIN
     */
    public function testSinConnection(): array
    {
        $config = $this->getAllAsArray();
        
        // Validar que la configuración esté completa
        if (empty($config['sin_api_endpoint'] ?? '')) {
            return [
                'success' => false,
                'message' => 'Endpoint de API no configurado'
            ];
        }
        
        if (empty($config['sin_certificate_path'] ?? '')) {
            return [
                'success' => false,
                'message' => 'Ruta del certificado no configurada'
            ];
        }
        
        if (empty($config['sin_nit'] ?? '')) {
            return [
                'success' => false,
                'message' => 'NIT de empresa no configurado'
            ];
        }

        // Verificar que el certificado exista
        $certificatePath = $config['sin_certificate_path'];
        if (!file_exists($certificatePath)) {
            return [
                'success' => false,
                'message' => 'Archivo de certificado no encontrado: ' . $certificatePath
            ];
        }

        try {
            // Intentar hacer una llamada de prueba a SIN
            $client = new \GuzzleHttp\Client([
                'verify' => false, // En producción, cambiar a true
                'timeout' => 10,
            ]);

            $response = $client->get($config['sin_api_endpoint'] . '/health', [
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'message' => 'Conexión establecida correctamente'
                ];
            }

            return [
                'success' => false,
                'message' => 'Respuesta inesperada del servidor: ' . $response->getStatusCode()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }

    protected function processLogoUpload(UploadedFile $file): ?string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            return null;
        }
        if ($file->getSize() > 2 * 1024 * 1024) { // 2MB
            return null;
        }

        $uploadPath = FCPATH . 'images' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        $ext = $file->getClientExtension() ?: $file->guessExtension() ?: 'png';
        $newName = 'logo-lab.' . $ext;
        if ($file->move($uploadPath, $newName)) {
            return 'images/' . $newName;
        }
        return null;
    }
}
