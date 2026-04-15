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
        $data['lab_validators_json'] ??= '[]';
        $data['lab_approvers_json'] ??= '[]';
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
            'decimales_sugerencia', 'dias_alerta_vencimiento', 'stock_alerta_factor', 'show_order_barcode', 'order_barcode_print_layout', 'order_barcode_print_size_percent', 'leyendas_enabled',
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
