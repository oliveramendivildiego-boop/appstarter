<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\ReportPdfTemplateModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Servicio de configuración del sistema.
 * Lógica de negocio para guardar config, validar logo, etc.
 */
class ConfigService
{
    public const CUSTOMER_INSTITUCION_DISCOUNTS_KEY = 'customer_institucion_discounts_json';

    protected AppConfigModel $appConfigModel;

    public function __construct(?AppConfigModel $appConfigModel = null)
    {
        $this->appConfigModel = $appConfigModel ?? model(AppConfigModel::class);
    }

    private const CACHE_KEY = 'app_config_array';
    private const CACHE_TTL = 300;

    /**
     * Clave de caché por conexión BD activa (multi-tenant): evita mezclar app_config de un tenant con otro.
     */
    private function appConfigCacheKey(): string
    {
        $db = config('Database')->default;
        $sig = implode("\0", [
            (string) ($db['hostname'] ?? ''),
            (string) ($db['port'] ?? ''),
            (string) ($db['database'] ?? ''),
            (string) ($db['username'] ?? ''),
            (string) ($db['DBPrefix'] ?? ''),
        ]);

        return self::CACHE_KEY . '_' . hash('sha256', $sig);
    }

    public function getAllAsArray(): array
    {
        $cache = \Config\Services::cache();
        $cacheKey = $this->appConfigCacheKey();
        $cached = $cache->get($cacheKey);
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
        $data['ui_labotests_card_header_title_color'] ??= '#ffffff';
        $data['ui_labotests_card_header_title_weight'] ??= '600';
        $data['ui_labotests_card_header_title_style'] ??= 'normal';
        $data['ui_pagination_link_color'] ??= '';
        $data['ui_pagination_link_weight'] ??= '400';
        $data['ui_pagination_link_style'] ??= 'normal';
        $data['ui_pagination_active_bg'] ??= '';
        $data['ui_pagination_active_color'] ??= '';
        $data['order_barcode_print_layout'] ??= 'vertical';
        $data['order_barcode_print_size_percent'] ??= '100';
        $data['print_paper_size'] ??= 'letter';
        $data['print_paper_width_mm'] ??= '210';
        $data['print_paper_height_mm'] ??= '297';
        $data['print_pagination_enabled'] ??= '0';
        $data['print_pagination_position'] ??= 'bottom-right';
        $data['lab_validators_json'] ??= '[]';
        $data['lab_approvers_json'] ??= '[]';
        $data['comprobante_primary_color'] ??= '#0f766e';
        $data['comprobante_secondary_color'] ??= '#134e4a';
        $data['comprobante_text_color'] ??= '#1e293b';
        $data['comprobante_tagline'] ??= 'Constancia de pago';
        $data['comprobante_footer_note'] ??= 'Documento interno de constancia de pago emitido por el laboratorio. No reemplaza un comprobante fiscal electrónico ni factura validada ante el SIN.';
        $data['comprobante_show_doctor'] ??= '1';
        $data['label_sin_doctor'] ??= 'Sin doctor';
        $cache->save($cacheKey, $data, self::CACHE_TTL);
        return $data;
    }

    public function invalidateCache(): void
    {
        $cache = \Config\Services::cache();
        $cache->delete($this->appConfigCacheKey());
        // Clave legacy (sin sufijo): evitar datos cruzados tras actualizar desde cualquier tenant
        $cache->delete(self::CACHE_KEY);
    }

    /**
     * Obtiene descuentos por institución en formato [institucion => porcentaje].
     *
     * @return array<string,float>
     */
    public function getCustomerInstitutionDiscounts(): array
    {
        $raw = trim((string) $this->appConfigModel->getValue(self::CUSTOMER_INSTITUCION_DISCOUNTS_KEY));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $institucion => $descuento) {
            if (is_array($descuento) && isset($descuento['institucion'], $descuento['descuento'])) {
                $institucion = (string) ($descuento['institucion'] ?? '');
                $descuento = $descuento['descuento'] ?? 0;
            }
            $institucion = trim((string) $institucion);
            if ($institucion === '') {
                continue;
            }
            $val = (float) $descuento;
            $val = max(0, min(100, $val));
            $out[$institucion] = $val;
        }

        uksort($out, static fn (string $a, string $b): int => strcasecmp($a, $b));
        return $out;
    }

    /**
     * Guarda descuentos por institución (0-100).
     *
     * @param array<string,float|int|string> $discounts
     */
    public function saveCustomerInstitutionDiscounts(array $discounts): bool
    {
        $normalized = [];
        foreach ($discounts as $institucion => $descuento) {
            $institucion = trim((string) $institucion);
            if ($institucion === '') {
                continue;
            }
            $val = max(0, min(100, (float) $descuento));
            $normalized[$institucion] = round($val, 2);
        }
        uksort($normalized, static fn (string $a, string $b): int => strcasecmp($a, $b));

        $ok = $this->appConfigModel->saveValue(
            self::CUSTOMER_INSTITUCION_DISCOUNTS_KEY,
            json_encode($normalized, JSON_UNESCAPED_UNICODE)
        );
        if ($ok) {
            $this->invalidateCache();
        }
        return $ok;
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
     * Directorio de certificados SIN (relativo a WRITEPATH).
     */
    public function getSinCertificateStorageDir(): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sin_certificates';
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function saveSinConfig(array $postData, ?UploadedFile $p12File = null): array
    {
        $enabled = ! empty($postData['sin_billing_enabled']);
        $batch   = [
            'sin_billing_enabled' => $enabled ? '1' : '0',
        ];

        if ($enabled) {
            $keys = [
                'sin_api_endpoint',
                'sin_nit',
                'sin_business_name',
                'sin_branch_code',
                'sin_system_type',
                'sin_emission_mode',
                'sin_activity_code',
            ];
            foreach ($keys as $k) {
                $batch[$k] = trim((string) ($postData[$k] ?? ''));
            }
            if ($batch['sin_branch_code'] === '') {
                $batch['sin_branch_code'] = '1';
            }

            $newPassword = trim((string) ($postData['sin_certificate_password'] ?? ''));
            $storedPassword = trim((string) $this->appConfigModel->getValue('sin_certificate_password'));
            $password = $newPassword !== '' ? $newPassword : $storedPassword;

            $hasP12 = trim((string) $this->appConfigModel->getValue('sin_certificate_p12_path')) !== '';
            $uploadingP12 = $p12File !== null && $p12File->getError() !== UPLOAD_ERR_NO_FILE;

            if ($uploadingP12) {
                if ($password === '') {
                    return ['success' => false, 'message' => 'Indique la contraseña del certificado .p12.'];
                }
                $certResult = $this->processSinCertificateP12Upload($p12File, $password);
                if (! ($certResult['success'] ?? false)) {
                    return ['success' => false, 'message' => (string) ($certResult['message'] ?? 'No se pudo procesar el certificado.')];
                }
                $batch['sin_certificate_p12_path'] = (string) ($certResult['p12_path'] ?? '');
                $batch['sin_certificate_path']       = (string) ($certResult['pem_path'] ?? '');
            } elseif (! $hasP12) {
                return ['success' => false, 'message' => 'Debe subir el certificado digital (.p12) emitido por DigiCert.'];
            }

            if ($newPassword !== '') {
                $batch['sin_certificate_password'] = $newPassword;
            }

            $missing = [];
            if ($batch['sin_api_endpoint'] === '') {
                $missing[] = 'endpoint de API';
            }
            if ($batch['sin_nit'] === '') {
                $missing[] = 'NIT';
            }
            if ($batch['sin_business_name'] === '') {
                $missing[] = 'razón social';
            }
            if ($batch['sin_system_type'] === '') {
                $missing[] = 'tipo de sistema';
            }
            if ($batch['sin_emission_mode'] === '') {
                $missing[] = 'modalidad de emisión';
            }
            if ($batch['sin_activity_code'] === '') {
                $missing[] = 'código de actividad';
            }
            if ($missing !== []) {
                return [
                    'success' => false,
                    'message' => 'Complete los campos obligatorios: ' . implode(', ', $missing) . '.',
                ];
            }
        }

        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }

        return [
            'success' => $ok,
            'message' => $ok ? 'Configuración de SIN guardada.' : 'No se pudo guardar la configuración SIN.',
        ];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function testSinConnection(): array
    {
        if ($this->appConfigModel->getValue('sin_billing_enabled') !== '1') {
            return ['success' => false, 'message' => 'La facturación SIN está deshabilitada.'];
        }

        $endpoint = trim((string) $this->appConfigModel->getValue('sin_api_endpoint'));
        if ($endpoint === '' || filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            return ['success' => false, 'message' => 'El endpoint de API no es una URL válida.'];
        }

        $p12Full = $this->resolveSinCertificateFullPath('sin_certificate_p12_path');
        if ($p12Full === null || ! is_readable($p12Full)) {
            return ['success' => false, 'message' => 'No hay certificado .p12 cargado o no es accesible en el servidor.'];
        }

        $password = trim((string) $this->appConfigModel->getValue('sin_certificate_password'));
        if ($password === '') {
            return ['success' => false, 'message' => 'Falta la contraseña del certificado. Guarde la configuración con la contraseña correcta.'];
        }

        $parsed = $this->readPkcs12Bundle($p12Full, $password);
        if (! ($parsed['success'] ?? false)) {
            return ['success' => false, 'message' => (string) ($parsed['message'] ?? 'No se pudo leer el certificado.')];
        }

        $pemFull = $this->resolveSinCertificateFullPath('sin_certificate_path');
        if ($pemFull === null || ! is_readable($pemFull)) {
            return ['success' => false, 'message' => 'El PEM derivado del certificado no está disponible. Vuelva a subir el archivo .p12.'];
        }

        $subject = (string) ($parsed['subject'] ?? '');
        $msg     = 'Certificado válido';
        if ($subject !== '') {
            $msg .= ' (' . $subject . ')';
        }
        $msg .= '. Endpoint configurado.';

        if (! extension_loaded('openssl')) {
            return ['success' => false, 'message' => 'La extensión OpenSSL de PHP no está habilitada en el servidor.'];
        }

        return ['success' => true, 'message' => $msg];
    }

    /**
     * @return array{success:bool,message:string,p12_path?:string,pem_path?:string}
     */
    private function processSinCertificateP12Upload(UploadedFile $file, string $password): array
    {
        if (! $file->isValid()) {
            return ['success' => false, 'message' => 'El archivo del certificado no se subió correctamente.'];
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'El certificado no debe superar 2 MB.'];
        }

        if (! extension_loaded('openssl')) {
            return ['success' => false, 'message' => 'OpenSSL no está disponible en PHP; no se puede procesar el certificado.'];
        }

        $tmp = $file->getTempName();
        if ($tmp === '' || ! is_readable($tmp)) {
            return ['success' => false, 'message' => 'No se pudo leer el archivo temporal del certificado.'];
        }

        $parsed = $this->readPkcs12Bundle($tmp, $password);
        if (! ($parsed['success'] ?? false)) {
            return ['success' => false, 'message' => (string) ($parsed['message'] ?? 'Contraseña incorrecta o archivo .p12 inválido.')];
        }

        $ext = $this->resolveSinPkcs12Extension($file);
        if ($ext === null) {
            return ['success' => false, 'message' => 'El archivo debe ser un certificado .p12 (DigiCert). Renombre el archivo con extensión .p12 e intente de nuevo.'];
        }

        $dir = $this->getSinCertificateStorageDir();
        if (! is_dir($dir) && ! @mkdir($dir, 0750, true)) {
            return ['success' => false, 'message' => 'No se pudo crear la carpeta de certificados en el servidor.'];
        }

        $token   = bin2hex(random_bytes(8));
        $p12Name = 'sin-cert-' . $token . '.' . $ext;
        if (! $file->move($dir, $p12Name)) {
            return ['success' => false, 'message' => 'No se pudo guardar el certificado en el servidor.'];
        }

        $p12Rel = 'uploads/sin_certificates/' . $p12Name;
        $pemRel = 'uploads/sin_certificates/sin-cert-' . $token . '.pem';
        $pemFull = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $pemRel);
        $pemBody = (string) ($parsed['cert'] ?? '') . "\n" . (string) ($parsed['pkey'] ?? '');
        if (isset($parsed['extracerts']) && is_string($parsed['extracerts']) && $parsed['extracerts'] !== '') {
            $pemBody .= "\n" . $parsed['extracerts'];
        }
        if (@file_put_contents($pemFull, $pemBody) === false) {
            @unlink($dir . DIRECTORY_SEPARATOR . $p12Name);

            return ['success' => false, 'message' => 'No se pudo generar el archivo PEM del certificado.'];
        }
        @chmod($pemFull, 0640);

        $this->removePreviousSinCertificateFiles($p12Rel, $pemRel);

        return [
            'success'  => true,
            'message'  => 'Certificado cargado correctamente.',
            'p12_path' => $p12Rel,
            'pem_path' => $pemRel,
        ];
    }

    /**
     * Extensión según nombre original (getExtension() de CI4 suele devolver "bin" u otro valor con .p12 en Windows).
     * Solo se invoca después de validar el contenido PKCS#12 con OpenSSL.
     */
    private function resolveSinPkcs12Extension(UploadedFile $file): ?string
    {
        $blocked = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'doc', 'docx', 'txt', 'json', 'xml', 'html', 'php'];
        $candidates = array_filter([
            strtolower((string) $file->getClientExtension()),
            strtolower(pathinfo((string) $file->getClientName(), PATHINFO_EXTENSION)),
            strtolower(pathinfo((string) $file->getName(), PATHINFO_EXTENSION)),
            strtolower((string) $file->getExtension()),
        ], static fn (string $v): bool => $v !== '');

        foreach ($candidates as $ext) {
            $ext = ltrim($ext, '.');
            if (in_array($ext, $blocked, true)) {
                return null;
            }
            if ($ext === 'p12' || $ext === 'pfx') {
                return $ext;
            }
        }

        $clientName = strtolower(trim((string) $file->getClientName()));
        if (str_ends_with($clientName, '.p12')) {
            return 'p12';
        }
        if (str_ends_with($clientName, '.pfx')) {
            return 'pfx';
        }

        // Contenido PKCS#12 válido: el servidor a menudo reporta "bin" u otra extensión incorrecta
        return 'p12';
    }

    /**
     * @return array{success:bool,message:string,cert?:string,pkey?:string,extracerts?:string,subject?:string}
     */
    private function readPkcs12Bundle(string $p12Path, string $password): array
    {
        if (! extension_loaded('openssl')) {
            return ['success' => false, 'message' => 'OpenSSL no está habilitado.'];
        }

        $raw = @file_get_contents($p12Path);
        if ($raw === false || $raw === '') {
            return ['success' => false, 'message' => 'No se pudo leer el archivo del certificado.'];
        }

        $certs = [];
        if (! @openssl_pkcs12_read($raw, $certs, $password)) {
            return ['success' => false, 'message' => 'Contraseña incorrecta o archivo .p12 corrupto.'];
        }

        $cert = trim((string) ($certs['cert'] ?? ''));
        $pkey = trim((string) ($certs['pkey'] ?? ''));
        if ($cert === '' || $pkey === '') {
            return ['success' => false, 'message' => 'El certificado no contiene clave privada o certificado público.'];
        }

        $subject = '';
        $x509 = @openssl_x509_read($cert);
        if ($x509 !== false) {
            $info = @openssl_x509_parse($x509);
            if (is_array($info)) {
                $subject = trim((string) ($info['name'] ?? ($info['subject']['CN'] ?? '')));
            }
        }

        $extra = '';
        if (! empty($certs['extracerts']) && is_array($certs['extracerts'])) {
            $extra = implode("\n", array_map('strval', $certs['extracerts']));
        }

        return [
            'success'    => true,
            'message'    => 'OK',
            'cert'       => $cert,
            'pkey'       => $pkey,
            'extracerts' => $extra,
            'subject'    => $subject,
        ];
    }

    private function resolveSinCertificateFullPath(string $configKey): ?string
    {
        $rel = trim((string) $this->appConfigModel->getValue($configKey));
        if ($rel === '' || str_contains($rel, '..')) {
            return null;
        }
        $rel = str_replace('\\', '/', $rel);
        if (! preg_match('#^uploads/sin_certificates/[a-zA-Z0-9._-]+$#', $rel)) {
            return null;
        }
        $full = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $base = realpath($this->getSinCertificateStorageDir());
        $real = realpath($full);
        if ($base === false || $real === false || ! str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return is_file($full) ? $full : null;
        }

        return $real;
    }

    private function removePreviousSinCertificateFiles(string $newP12Rel, string $newPemRel): void
    {
        foreach (['sin_certificate_p12_path', 'sin_certificate_path'] as $key) {
            $rel = trim((string) $this->appConfigModel->getValue($key));
            if ($rel === '' || $rel === $newP12Rel || $rel === $newPemRel) {
                continue;
            }
            $full = $this->resolveSinCertificateFullPath($key);
            if ($full !== null && is_file($full)) {
                @unlink($full);
            }
        }
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
            'decimales_sugerencia', 'dias_alerta_vencimiento', 'stock_alerta_factor', 'show_order_barcode', 'order_barcode_print_layout', 'order_barcode_print_size_percent',
            'print_paper_size', 'print_pagination_enabled', 'print_pagination_position', 'leyendas_enabled',
            'custom1_name', 'custom2_name', 'custom3_name', 'custom4_name', 'custom5_name',
            'custom6_name', 'custom7_name', 'custom8_name', 'custom9_name', 'custom10_name',
        ];

        $batch = array_filter(
            array_intersect_key($postData, array_flip($keys)),
            fn (mixed $v, mixed $k): bool => in_array($k, ['decimales_sugerencia', 'dias_alerta_vencimiento', 'stock_alerta_factor']) || ($v !== null && $v !== ''),
            ARRAY_FILTER_USE_BOTH
        );
        if (isset($batch['decimales_sugerencia'])) {
            $batch['decimales_sugerencia'] = (string) max(0, min(10, (int) $batch['decimales_sugerencia']));
        }
        if (isset($batch['dias_alerta_vencimiento'])) {
            $val = (int) $batch['dias_alerta_vencimiento'];
            $batch['dias_alerta_vencimiento'] = (string) ($val > 0 ? max(1, min(365, $val)) : 40);
        }
        if (isset($batch['stock_alerta_factor'])) {
            $val = (float) $batch['stock_alerta_factor'];
            $batch['stock_alerta_factor'] = (string) max(0.5, min(3, $val > 0 ? $val : 1));
        }
        if (array_key_exists('show_order_barcode', $postData)) {
            $batch['show_order_barcode'] = ($postData['show_order_barcode'] === '1') ? '1' : '0';
        }
        if (array_key_exists('order_barcode_print_layout', $postData)) {
            $v = strtolower(trim((string) $postData['order_barcode_print_layout']));
            $batch['order_barcode_print_layout'] = ($v === 'horizontal') ? 'horizontal' : 'vertical';
        }
        if (array_key_exists('order_barcode_print_size_percent', $postData)) {
            $p = (int) $postData['order_barcode_print_size_percent'];
            if ($p < 1) {
                $p = 100;
            }
            $batch['order_barcode_print_size_percent'] = (string) max(30, min(250, $p));
        }
        if (array_key_exists('print_paper_size', $postData)) {
            $paper = strtolower(trim((string) $postData['print_paper_size']));
            $allowedPaper = ['letter', 'a4', 'legal', 'custom'];
            $batch['print_paper_size'] = in_array($paper, $allowedPaper, true) ? $paper : 'letter';
        }
        if (($batch['print_paper_size'] ?? '') === 'custom') {
            $w = isset($postData['print_paper_width_mm']) ? (float) $postData['print_paper_width_mm'] : 210.0;
            $h = isset($postData['print_paper_height_mm']) ? (float) $postData['print_paper_height_mm'] : 297.0;
            $w = max(50.0, min(999.0, $w));
            $h = max(50.0, min(999.0, $h));
            $batch['print_paper_width_mm'] = (string) round($w, 1);
            $batch['print_paper_height_mm'] = (string) round($h, 1);
        }
        if (array_key_exists('print_pagination_enabled', $postData)) {
            $batch['print_pagination_enabled'] = ($postData['print_pagination_enabled'] === '1') ? '1' : '0';
        }
        if (array_key_exists('print_pagination_position', $postData)) {
            $pos = strtolower(trim((string) $postData['print_pagination_position']));
            $allowedPos = [
                'top-left', 'top-center', 'top-right',
                'bottom-left', 'bottom-center', 'bottom-right',
            ];
            $batch['print_pagination_position'] = in_array($pos, $allowedPos, true) ? $pos : 'bottom-right';
        }
        if (array_key_exists('leyendas_enabled', $postData)) {
            $batch['leyendas_enabled'] = ($postData['leyendas_enabled'] === '1') ? '1' : '0';
        }
        if (array_key_exists('label_sin_doctor', $postData)) {
            $t = trim((string) ($postData['label_sin_doctor'] ?? ''));
            if (mb_strlen($t) > 160) {
                $t = mb_substr($t, 0, 160);
            }
            $batch['label_sin_doctor'] = $t;
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

        $logoFailed       = false;
        $previousLogoPath = trim((string) ($this->getAllAsArray()['logo'] ?? ''));
        $newLogoFromUpload = null;

        if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
            $logoPath = $this->processLogoUpload($logoFile);
            if ($logoPath) {
                $batch['logo'] = $logoPath;
                $newLogoFromUpload = $logoPath;
            } else {
                $logoFailed = true;
            }
        }

        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
            if ($newLogoFromUpload !== null) {
                $this->removeManagedConfigImage($previousLogoPath, $newLogoFromUpload, '#^images/logo-lab#');
            }
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
        return $this->processConfigImageUpload($file, 'logo-lab-');
    }

    /**
     * Sube imagen validada a public/images/ con prefijo de nombre conocido (logo, sello, firma).
     */
    protected function processConfigImageUpload(UploadedFile $file, string $basenamePrefix): ?string
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

        $newName = $basenamePrefix . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!$file->move($uploadPath, $newName)) {
            return null;
        }

        return 'images/' . $newName;
    }

    /**
     * Elimina la imagen anterior solo si coincide con un prefijo gestionado por esta app.
     *
     * @param non-empty-string $pathPatternRegex p.ej. '#^images/logo-lab#'
     */
    private function removeManagedConfigImage(string $previousRelative, string $newRelative, string $pathPatternRegex): void
    {
        if ($previousRelative === '' || $previousRelative === $newRelative) {
            return;
        }
        $previousRelative = str_replace('\\', '/', $previousRelative);
        if (!preg_match($pathPatternRegex, $previousRelative)) {
            return;
        }
        $full = realpath(FCPATH . $previousRelative);
        $base = realpath(FCPATH . 'images');
        if ($full === false || $base === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
            return;
        }
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * Elimina un archivo bajo images/ si coincide con el prefijo gestionado (p. ej. al quitar un aprobador).
     */
    private function deleteManagedConfigImage(string $relativePath, string $pathPatternRegex): void
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || !preg_match($pathPatternRegex, $relativePath)) {
            return;
        }
        $full = realpath(FCPATH . $relativePath);
        $base = realpath(FCPATH . 'images');
        if ($full === false || $base === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
            return;
        }
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * Validadores y aprobadores para la pestaña de configuración (incluye migración desde campos antiguos).
     *
     * @return array{validators: list<array{id: string, name: string}>, approvers: list<array{id: string, name: string, cargo: string, matricula: string, seal: string, signature: string}>}
     */
    public function getLabValidationStateForView(): array
    {
        $cfg = $this->getAllAsArray();
        $validators = $this->decodeValidatorsFromStored((string) ($cfg['lab_validators_json'] ?? ''));
        if ($validators === []) {
            $legacy = trim((string) ($cfg['lab_validators_names'] ?? ''));
            if ($legacy !== '') {
                $validators = $this->legacyValidatorNamesToRows($legacy);
            }
        }

        $approvers = $this->decodeApproversFromStored((string) ($cfg['lab_approvers_json'] ?? ''));
        if ($approvers === []) {
            $approvers = $this->legacyApproverRowsFromCfg($cfg);
        }

        return [
            'validators' => $validators,
            'approvers'  => $approvers,
        ];
    }

    /**
     * Guarda listas de validación/aprobación y archivos por aprobador (pestaña dedicada).
     *
     * @return array{success: bool, message: string}
     */
    public function saveLabValidationFromRequest(array $post, IncomingRequest $request): array
    {
        $state = $this->getLabValidationStateForView();
        $oldApprovers = $state['approvers'];
        $oldById = [];
        foreach ($oldApprovers as $o) {
            $oldById[$o['id']] = $o;
        }

        $validatorIds = $post['validator_id'] ?? [];
        $validatorNames = $post['validator_name'] ?? [];
        if (!is_array($validatorIds)) {
            $validatorIds = [];
        }
        if (!is_array($validatorNames)) {
            $validatorNames = [];
        }
        $nVal = max(count($validatorIds), count($validatorNames));
        $validators = [];
        for ($i = 0; $i < $nVal; $i++) {
            $vname = mb_substr(trim((string) ($validatorNames[$i] ?? '')), 0, 500);
            if ($vname === '') {
                continue;
            }
            $vid = strtolower(preg_replace('/[^a-f0-9]/i', '', (string) ($validatorIds[$i] ?? '')));
            if (strlen($vid) < 8 || strlen($vid) > 32) {
                $vid = bin2hex(random_bytes(8));
            }
            $validators[] = ['id' => $vid, 'name' => $vname];
        }

        $ids = $post['approver_id'] ?? [];
        $names = $post['approver_name'] ?? [];
        $cargos = $post['approver_cargo'] ?? [];
        $matriculas = $post['approver_matricula'] ?? [];
        $fileSlots = $post['approver_file_slot'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        if (!is_array($names)) {
            $names = [];
        }
        if (!is_array($cargos)) {
            $cargos = [];
        }
        if (!is_array($matriculas)) {
            $matriculas = [];
        }
        if (!is_array($fileSlots)) {
            $fileSlots = [];
        }
        $nApp = max(count($ids), count($names), count($cargos), count($matriculas));
        $newApprovers = [];
        $sealFailed = false;
        $sigFailed = false;

        for ($i = 0; $i < $nApp; $i++) {
            $aname = mb_substr(trim((string) ($names[$i] ?? '')), 0, 500);
            if ($aname === '') {
                continue;
            }
            $id = strtolower(preg_replace('/[^a-f0-9]/i', '', (string) ($ids[$i] ?? '')));
            if (strlen($id) < 8 || strlen($id) > 32) {
                $id = bin2hex(random_bytes(8));
            }
            $cargo = mb_substr(trim((string) ($cargos[$i] ?? '')), 0, 255);
            $matricula = mb_substr(trim((string) ($matriculas[$i] ?? '')), 0, 255);
            $prev = $oldById[$id] ?? null;
            $seal = is_array($prev) ? trim((string) ($prev['seal'] ?? '')) : '';
            $signature = is_array($prev) ? trim((string) ($prev['signature'] ?? '')) : '';

            $slot = isset($fileSlots[$i]) ? (int) $fileSlots[$i] : $i;
            if ($slot < 0 || $slot > 500) {
                $slot = $i;
            }

            $fSeal = $request->getFile('approver_seal_' . $slot);
            if ($fSeal && $fSeal->isValid() && !$fSeal->hasMoved()) {
                $np = $this->processConfigImageUpload($fSeal, 'lab-approver-seal-');
                if ($np) {
                    if ($seal !== '') {
                        $this->removeManagedConfigImage($seal, $np, '#^images/lab-approver-seal-#');
                    }
                    $seal = $np;
                } else {
                    $sealFailed = true;
                }
            }

            $fSig = $request->getFile('approver_signature_' . $slot);
            if ($fSig && $fSig->isValid() && !$fSig->hasMoved()) {
                $np = $this->processConfigImageUpload($fSig, 'lab-approver-sig-');
                if ($np) {
                    if ($signature !== '') {
                        $this->removeManagedConfigImage($signature, $np, '#^images/lab-approver-sig-#');
                    }
                    $signature = $np;
                } else {
                    $sigFailed = true;
                }
            }

            $newApprovers[] = [
                'id'          => $id,
                'name'        => $aname,
                'cargo'       => $cargo,
                'matricula'   => $matricula,
                'seal'        => $seal,
                'signature'   => $signature,
            ];
        }

        $newIds = array_column($newApprovers, 'id');
        foreach ($oldApprovers as $o) {
            if (!in_array($o['id'], $newIds, true)) {
                $this->deleteManagedConfigImage((string) ($o['seal'] ?? ''), '#^images/lab-approver-seal-#');
                $this->deleteManagedConfigImage((string) ($o['signature'] ?? ''), '#^images/lab-approver-sig-#');
            }
        }

        $batch = [
            'lab_validators_json'        => json_encode($validators, JSON_UNESCAPED_UNICODE),
            'lab_approvers_json'         => json_encode($newApprovers, JSON_UNESCAPED_UNICODE),
            'lab_validators_names'       => '',
            'lab_approvers_names'        => '',
            'lab_signatory_cargo'        => '',
            'lab_signatory_seal'         => '',
            'lab_signatory_signature'    => '',
        ];

        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }

        $message = $ok ? lang('Config.config_saved') : lang('Config.config_error');
        if ($ok && ($sealFailed || $sigFailed)) {
            $parts = [lang('Config.config_saved')];
            if ($sealFailed) {
                $parts[] = lang('Config.config_lab_seal_error');
            }
            if ($sigFailed) {
                $parts[] = lang('Config.config_lab_signature_error');
            }
            $message = implode(' ', $parts);
        }

        return ['success' => $ok, 'message' => $message];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function decodeValidatorsFromStored(string $json): array
    {
        $json = trim($json);
        if ($json === '' || $json === '[]') {
            return [];
        }
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return [];
        }
        $out = [];
        foreach ($arr as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = strtolower(preg_replace('/[^a-f0-9]/i', '', (string) ($row['id'] ?? '')));
            if (strlen($id) < 8 || strlen($id) > 32) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = ['id' => $id, 'name' => mb_substr($name, 0, 500)];
        }

        return $out;
    }

    /**
     * @return list<array{id: string, name: string, cargo: string, matricula: string, seal: string, signature: string}>
     */
    private function decodeApproversFromStored(string $json): array
    {
        $json = trim($json);
        if ($json === '' || $json === '[]') {
            return [];
        }
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return [];
        }
        $out = [];
        foreach ($arr as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = strtolower(preg_replace('/[^a-f0-9]/i', '', (string) ($row['id'] ?? '')));
            if (strlen($id) < 8 || strlen($id) > 32) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'id'        => $id,
                'name'      => mb_substr($name, 0, 500),
                'cargo'     => mb_substr(trim((string) ($row['cargo'] ?? '')), 0, 255),
                'matricula' => mb_substr(trim((string) ($row['matricula'] ?? '')), 0, 255),
                'seal'      => trim((string) ($row['seal'] ?? '')),
                'signature' => trim((string) ($row['signature'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function legacyValidatorNamesToRows(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $names = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            if (str_contains($line, ',')) {
                foreach (explode(',', $line) as $p) {
                    $p = trim($p);
                    if ($p !== '') {
                        $names[] = $p;
                    }
                }
            } else {
                $names[] = $line;
            }
        }
        $rows = [];
        foreach ($names as $idx => $n) {
            $stableId = substr(hash('sha256', 'lab_val_legacy|' . $idx . '|' . $n), 0, 16);
            $rows[] = ['id' => $stableId, 'name' => mb_substr($n, 0, 500)];
        }

        return $rows;
    }

    /**
     * @param array<string, string> $cfg
     *
     * @return list<array{id: string, name: string, cargo: string, matricula: string, seal: string, signature: string}>
     */
    private function legacyApproverRowsFromCfg(array $cfg): array
    {
        $text = trim((string) ($cfg['lab_approvers_names'] ?? ''));
        $cargo = trim((string) ($cfg['lab_signatory_cargo'] ?? ''));
        $seal = trim((string) ($cfg['lab_signatory_seal'] ?? ''));
        $signature = trim((string) ($cfg['lab_signatory_signature'] ?? ''));
        if ($text === '' && $cargo === '' && $seal === '' && $signature === '') {
            return [];
        }
        $name = '—';
        if ($text !== '') {
            $parts = preg_split('/\r\n|\r|\n/', $text) ?: [];
            $name = trim((string) ($parts[0] ?? ''));
            if ($name !== '' && str_contains($name, ',')) {
                $name = trim(explode(',', $name)[0]);
            }
            if ($name === '') {
                $name = '—';
            }
        }
        $stableId = substr(hash('sha256', 'lab_approver_legacy|' . $name . '|' . $cargo), 0, 16);

        return [[
            'id'          => $stableId,
            'name'        => mb_substr($name, 0, 500),
            'cargo'       => mb_substr($cargo, 0, 255),
            'matricula'   => '',
            'seal'        => $seal,
            'signature'   => $signature,
        ]];
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

        $btnBorderW = (int) ($post['ui_btn_border_width'] ?? 0);
        $btnBorderW = max(0, min(8, $btnBorderW));
        $cardBorderW = (int) ($post['ui_card_border_width'] ?? 0);
        $cardBorderW = max(0, min(8, $cardBorderW));
        $btnSides   = \App\Services\LayoutService::normalizeUiBorderSides((string) ($post['ui_btn_border_sides'] ?? 'all'));
        $cardSides  = \App\Services\LayoutService::normalizeUiBorderSides((string) ($post['ui_card_border_sides'] ?? 'all'));
        $btnShadow  = \App\Services\LayoutService::normalizeUiShadowKey((string) ($post['ui_btn_shadow'] ?? 'none'));
        $cardShadow = \App\Services\LayoutService::normalizeUiShadowKey((string) ($post['ui_card_shadow'] ?? 'none'));

        $btnBgMode = (($post['ui_btn_primary_mode'] ?? '') === 'custom');
        $btnHovMode = (($post['ui_btn_hover_mode'] ?? '') === 'custom');

        $normFs = static function (string $postKey, string $default) use ($post): string {
            return \App\Services\LayoutService::normalizeUiFontSizeRemInput((string) ($post[$postKey] ?? ''), $default);
        };

        $headerMode = strtolower(trim((string) ($post['ui_header_mode'] ?? 'theme')));
        if (! in_array($headerMode, ['theme', 'custom', 'transparent'], true)) {
            $headerMode = 'theme';
        }
        $linkCustom = (($post['ui_sidebar_link_mode'] ?? '') === 'custom');

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
            'ui_body_text_weight'   => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_body_text_weight'] ?? ''), '400'),
            'ui_body_text_style'    => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_body_text_style'] ?? ''), 'normal'),
            'ui_sidebar_bg'         => ! empty($post['ui_sidebar_bg_transparent'])
                ? 'transparent'
                : $this->normalizeUiHex((string) ($post['ui_sidebar_bg'] ?? ''), '#f8f9fa'),
            'ui_sidebar_link_color' => $linkCustom
                ? $this->normalizeUiHex((string) ($post['ui_sidebar_link_custom'] ?? ''), '#0d6efd')
                : '',
            'ui_sidebar_link_weight' => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_sidebar_link_weight'] ?? ''), '500'),
            'ui_sidebar_link_style'  => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_sidebar_link_style'] ?? ''), 'normal'),
            'ui_sidebar_hover_bg' => ! empty($post['ui_sidebar_hover_default'])
                ? ''
                : $this->normalizeUiHex((string) ($post['ui_sidebar_hover_bg'] ?? ''), '#dee2e6'),
            'ui_sidebar_active_bg' => ! empty($post['ui_sidebar_active_default'])
                ? ''
                : $this->normalizeUiHex((string) ($post['ui_sidebar_active_bg'] ?? ''), '#ced4da'),
            'ui_header_bg'          => match ($headerMode) {
                'transparent' => 'transparent',
                'custom'      => $this->normalizeUiHex((string) ($post['ui_header_bg_custom'] ?? ''), '#0d6efd'),
                default       => '',
            },
            'ui_header_text_color' => $this->normalizeUiHex((string) ($post['ui_header_text_color'] ?? ''), '#ffffff'),
            'ui_header_text_weight' => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_header_text_weight'] ?? ''), '500'),
            'ui_header_text_style'  => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_header_text_style'] ?? ''), 'normal'),
            'ui_labotests_card_header_title_color' => $this->normalizeUiHex((string) ($post['ui_labotests_card_header_title_color'] ?? ''), '#ffffff'),
            'ui_labotests_card_header_title_weight' => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_labotests_card_header_title_weight'] ?? ''), '600'),
            'ui_labotests_card_header_title_style' => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_labotests_card_header_title_style'] ?? ''), 'normal'),
            'ui_pagination_link_color' => $this->normalizeUiHex(
                (string) ($post['ui_pagination_link_color'] ?? ''),
                $this->normalizeUiHex((string) ($post['theme_color'] ?? ''), '#FF7218')
            ),
            'ui_pagination_link_weight' => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_pagination_link_weight'] ?? ''), '400'),
            'ui_pagination_link_style' => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_pagination_link_style'] ?? ''), 'normal'),
            'ui_pagination_active_bg' => $this->normalizeUiHex(
                (string) ($post['ui_pagination_active_bg'] ?? ''),
                $this->normalizeUiHex((string) ($post['theme_color'] ?? ''), '#FF7218')
            ),
            'ui_pagination_active_color' => $this->normalizeUiHex((string) ($post['ui_pagination_active_color'] ?? ''), '#ffffff'),
            'ui_main_bg'            => ! empty($post['ui_main_bg_transparent'])
                ? 'transparent'
                : $this->normalizeUiHex((string) ($post['ui_main_bg'] ?? ''), '#ffffff'),
            'ui_footer_bg'          => ! empty($post['ui_footer_bg_transparent'])
                ? 'transparent'
                : $this->normalizeUiHex((string) ($post['ui_footer_bg'] ?? ''), '#f8f9fa'),
            'ui_footer_text_color' => $this->normalizeUiHex((string) ($post['ui_footer_text_color'] ?? ''), '#6c757d'),
            'ui_footer_text_weight' => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_footer_text_weight'] ?? ''), '400'),
            'ui_footer_text_style'  => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_footer_text_style'] ?? ''), 'normal'),
            'ui_footer_text_align' => $footerAlign,
            'ui_link_color' => ! empty($post['ui_link_default'])
                ? ''
                : $this->normalizeUiHex((string) ($post['ui_link_color'] ?? ''), '#0d6efd'),
            'ui_link_weight' => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_link_weight'] ?? ''), '400'),
            'ui_link_style'  => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_link_style'] ?? ''), 'normal'),
            'ui_card_radius'        => (string) $radius,
            'ui_btn_primary_bg' => $btnBgMode
                ? $this->normalizeUiHex((string) ($post['ui_btn_primary_bg_custom'] ?? ''), '#FF7218')
                : '',
            'ui_btn_primary_text' => $this->normalizeUiHex((string) ($post['ui_btn_primary_text'] ?? ''), '#ffffff'),
            'ui_btn_primary_text_weight' => \App\Services\LayoutService::normalizeUiFontWeight((string) ($post['ui_btn_primary_text_weight'] ?? ''), '500'),
            'ui_btn_primary_text_style'  => \App\Services\LayoutService::normalizeUiFontStyle((string) ($post['ui_btn_primary_text_style'] ?? ''), 'normal'),
            'ui_btn_primary_hover_bg' => $btnHovMode
                ? $this->normalizeUiHex((string) ($post['ui_btn_primary_hover_custom'] ?? ''), '#000000')
                : '',
            'ui_btn_border_width'  => (string) $btnBorderW,
            'ui_btn_border_color'  => $btnBorderW > 0
                ? $this->normalizeUiHex((string) ($post['ui_btn_border_color'] ?? ''), '#212529')
                : '',
            'ui_btn_border_sides'  => $btnSides,
            'ui_btn_shadow'        => $btnShadow,
            'ui_card_border_width' => (string) $cardBorderW,
            'ui_card_border_color' => $cardBorderW > 0
                ? $this->normalizeUiHex((string) ($post['ui_card_border_color'] ?? ''), '#dee2e6')
                : '',
            'ui_card_border_sides' => $cardSides,
            'ui_card_shadow'       => $cardShadow,
        ];

        $ok = $this->appConfigModel->batchSave($batch);
        if ($ok) {
            $this->invalidateCache();
        }

        return $ok;
    }

    /**
     * Guarda estilo/contenido del comprobante PDF.
     */
    public function saveComprobanteStyleFromRequest(array $post): bool
    {
        $primary = $this->normalizeUiHex((string) ($post['comprobante_primary_color'] ?? ''), '#0f766e');
        $secondary = $this->normalizeUiHex((string) ($post['comprobante_secondary_color'] ?? ''), '#134e4a');
        $text = $this->normalizeUiHex((string) ($post['comprobante_text_color'] ?? ''), '#1e293b');

        $tagline = trim((string) ($post['comprobante_tagline'] ?? ''));
        if ($tagline === '') {
            $tagline = 'Constancia de pago';
        }
        $tagline = mb_substr($tagline, 0, 120);

        $footer = trim((string) ($post['comprobante_footer_note'] ?? ''));
        if ($footer === '') {
            $footer = 'Documento interno de constancia de pago emitido por el laboratorio. No reemplaza un comprobante fiscal electrónico ni factura validada ante el SIN.';
        }
        $footer = mb_substr($footer, 0, 600);

        $showDoctor = ((string) ($post['comprobante_show_doctor'] ?? '1')) === '0' ? '0' : '1';

        $ok = $this->appConfigModel->batchSave([
            'comprobante_primary_color' => $primary,
            'comprobante_secondary_color' => $secondary,
            'comprobante_text_color' => $text,
            'comprobante_tagline' => $tagline,
            'comprobante_footer_note' => $footer,
            'comprobante_show_doctor' => $showDoctor,
        ]);
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
