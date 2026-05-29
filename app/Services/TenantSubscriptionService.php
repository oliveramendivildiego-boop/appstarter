<?php

namespace App\Services;

use App\Libraries\PdfService;
use App\Libraries\TenantResolver;
use App\Models\AppConfigModel;
use App\Models\TenantConfigModel;
use App\Models\TenantSubscriptionPaymentModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Pagos / vigencia de suscripción por tenant (BD central management).
 * Solo el tenant default gestiona comprobantes; los laboratorios cliente solo consultan.
 */
class TenantSubscriptionService
{
    public function voucherStorageDir(): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tenant_vouchers';
    }

    public function ensureVoucherDir(): bool
    {
        $d = $this->voucherStorageDir();
        if (is_dir($d)) {
            return true;
        }

        return @mkdir($d, 0755, true);
    }

    public function isMultiTenant(): bool
    {
        return (new TenantResolver())->resolveDefaultTenantKey() !== null;
    }

    public function sessionTenantKey(): ?string
    {
        if (! function_exists('session')) {
            return null;
        }
        $k = session()->get('tenant_key');

        return is_string($k) && $k !== '' ? $k : null;
    }

    /** True si la sesión corresponde a un laboratorio hijo (no el default). */
    public function isNonDefaultTenantSession(): bool
    {
        if (! $this->isMultiTenant()) {
            return false;
        }
        $def = (new TenantResolver())->resolveDefaultTenantKey();
        $cur = $this->sessionTenantKey();
        if ($cur === null || $def === null) {
            return false;
        }

        return (string) $cur !== (string) $def;
    }

    public static function shouldShowClientPortal(): bool
    {
        return (new self())->isNonDefaultTenantSession();
    }

    /**
     * Días antes del fin de vigencia del último pago en que se muestra aviso (config: dias_alerta_suscripcion_tenant, default 4).
     */
    public function getSubscriptionWarningDays(): int
    {
        $raw = model(AppConfigModel::class)->getValue('dias_alerta_suscripcion_tenant');
        $n   = (int) $raw;
        if ($n < 1) {
            $n = 4;
        }

        return min(90, $n);
    }

    /**
     * @return array{type: string, message: string}|null
     */
    public static function alertForCurrentSession(): ?array
    {
        $svc = new self();
        $key = $svc->sessionTenantKey();
        if ($key === null) {
            return null;
        }

        return $svc->getExpiryAlertForTenantKey($key);
    }

    /**
     * Último período pagado (mayor period_end) y días hasta el fin de vigencia (negativo = vencido).
     *
     * @return array{period_end: string, days_left: int, payment_id: int}|null
     */
    public function getLatestSubscriptionExpiryInfo(string $tenantKey): ?array
    {
        if ($tenantKey === '') {
            return null;
        }
        $list = $this->listPaymentsForTenantKey($tenantKey);
        if ($list === []) {
            return null;
        }
        usort($list, static function ($a, $b) {
            return strcmp((string) ($b['period_end'] ?? ''), (string) ($a['period_end'] ?? ''));
        });
        $latest = $list[0];
        $endStr = (string) ($latest['period_end'] ?? '');
        $tsEnd  = strtotime($endStr . ' 23:59:59');
        $tsToday = strtotime('today');
        if ($tsEnd === false || $tsToday === false) {
            return null;
        }
        $daysLeft = (int) floor(($tsEnd - $tsToday) / 86400);
        $pid      = (int) ($latest['id'] ?? 0);

        return [
            'period_end' => $endStr,
            'days_left'  => $daysLeft,
            'payment_id' => $pid,
        ];
    }

    /**
     * Rutas permitidas cuando la suscripción del laboratorio cliente está vencida.
     *
     * @return list<string>
     */
    public static function subscriptionBlockedAllowedUriPrefixes(): array
    {
        return [
            'subscription-blocked',
            'home/logout',
            'tenant-subscription',
            'status/checkEmployeeActive',
        ];
    }

    public static function isUriAllowedWhenSubscriptionBlocked(string $uri): bool
    {
        $uri = trim($uri, '/');
        foreach (self::subscriptionBlockedAllowedUriPrefixes() as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bloquear acceso al sistema (misma regla que la alerta roja de vigencia vencida).
     */
    public function isSubscriptionAccessBlocked(): bool
    {
        $key = $this->sessionTenantKey();
        if ($key === null) {
            return false;
        }
        $alert = $this->getExpiryAlertForTenantKey($key);

        return $alert !== null && ($alert['type'] ?? '') === 'danger';
    }

    /**
     * Laboratorio cliente (sesión no default) con vigencia del último pago ya vencida.
     */
    public function isChildTenantSubscriptionExpired(): bool
    {
        return $this->isSubscriptionAccessBlocked();
    }

    /**
     * Para el dashboard del tenant principal: laboratorios cliente con último pago por vencer o vencido (según días de aviso).
     *
     * @return list<array{tenant_config_id: int, tenant_name: string, tenant_key: string, period_end: string, days_left: int, estado: string}>
     */
    public function getBillableTenantsSuscripcionResumen(int $warningDays): array
    {
        if (! $this->isMultiTenant()) {
            return [];
        }
        $warningDays = max(1, min(90, $warningDays));
        $out         = [];
        foreach ($this->getBillableTenants() as $t) {
            $key = (string) ($t['tenant_key'] ?? '');
            $info = $this->getLatestSubscriptionExpiryInfo($key);
            if ($info === null) {
                continue;
            }
            $d = (int) ($info['days_left'] ?? 999);
            if ($d < 0 || ($d >= 0 && $d <= $warningDays)) {
                $out[] = [
                    'tenant_config_id' => (int) ($t['id'] ?? 0),
                    'tenant_name'      => (string) ($t['tenant_name'] ?? ''),
                    'tenant_key'       => $key,
                    'period_end'       => (string) ($info['period_end'] ?? ''),
                    'days_left'        => $d,
                    'estado'           => $d < 0 ? 'vencido' : 'por_vencer',
                ];
            }
        }
        usort($out, static function (array $a, array $b): int {
            if (($a['estado'] ?? '') !== ($b['estado'] ?? '')) {
                return ($a['estado'] ?? '') === 'vencido' ? -1 : 1;
            }

            return ($a['days_left'] ?? 999) <=> ($b['days_left'] ?? 999);
        });

        return $out;
    }

    public function getTenantConfigRowByKey(string $tenantKey): ?array
    {
        $m = model(TenantConfigModel::class);
        if (! $m->db->tableExists('tenant_configs')) {
            return null;
        }
        $row = $m->where('tenant_key', $tenantKey)->first();

        return is_array($row) ? $row : null;
    }

    /**
     * Tenants que pueden recibir comprobantes (excluye default).
     *
     * @return list<array<string,mixed>>
     */
    public function getBillableTenants(): array
    {
        $m = model(TenantConfigModel::class);
        if (! $m->db->tableExists('tenant_configs')) {
            return [];
        }
        $rows = $m->orderBy('tenant_name', 'ASC')->findAll();

        return array_values(array_filter(
            $rows,
            static fn ($r) => (int) ($r['is_default'] ?? 0) !== 1 && (int) ($r['id'] ?? 0) > 0
        ));
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listPaymentsForManagement(): array
    {
        $payModel = model(TenantSubscriptionPaymentModel::class);
        if (! $payModel->db->tableExists('tenant_subscription_payments')) {
            return [];
        }

        return $payModel->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * @param list<array<string,mixed>> $payments
     * @return list<array<string,mixed>>
     */
    public function listPaymentsWithTenantNames(array $payments): array
    {
        $cfg = model(TenantConfigModel::class);
        $byId = [];
        if ($cfg->db->tableExists('tenant_configs')) {
            foreach ($cfg->findAll() as $t) {
                $byId[(int) ($t['id'] ?? 0)] = $t;
            }
        }
        foreach ($payments as &$p) {
            $tid = (int) ($p['tenant_config_id'] ?? 0);
            $p['_tenant_name'] = $byId[$tid]['tenant_name'] ?? ('#' . $tid);
            $p['_tenant_key']  = $byId[$tid]['tenant_key'] ?? '';
        }
        unset($p);

        return $payments;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listPaymentsForTenantKey(string $tenantKey): array
    {
        $row = $this->getTenantConfigRowByKey($tenantKey);
        if (! $row) {
            return [];
        }
        $payModel = model(TenantSubscriptionPaymentModel::class);
        if (! $payModel->db->tableExists('tenant_subscription_payments')) {
            return [];
        }

        return $payModel->where('tenant_config_id', (int) $row['id'])
            ->orderBy('period_end', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function findPayment(int $id): ?array
    {
        $payModel = model(TenantSubscriptionPaymentModel::class);
        if (! $payModel->db->tableExists('tenant_subscription_payments')) {
            return null;
        }
        $r = $payModel->find($id);

        return is_array($r) ? $r : null;
    }

    public function tenantCanAccessPayment(string $tenantKey, int $paymentId): bool
    {
        $t = $this->getTenantConfigRowByKey($tenantKey);
        if (! $t) {
            return false;
        }
        $p = $this->findPayment($paymentId);
        if (! $p) {
            return false;
        }

        return (int) ($p['tenant_config_id'] ?? 0) === (int) ($t['id'] ?? 0);
    }

    public function voucherPath(string $filename): string
    {
        return $this->voucherStorageDir() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Nombre al descargar: Recibo_{NombreLaboratorio}_{mdY}.pdf (mesdíaaño = MMDDAAAA según fecha de registro del pago).
     */
    public function buildVoucherDownloadFilename(int $paymentId): string
    {
        $p = $this->findPayment($paymentId);
        if (! is_array($p)) {
            return 'Recibo_Laboratorio_' . date('mdY') . '.pdf';
        }

        $labName = '';
        $tcId    = (int) ($p['tenant_config_id'] ?? 0);
        if ($tcId > 0) {
            $tc = model(TenantConfigModel::class)->find($tcId);
            if (is_array($tc)) {
                $labName = (string) ($tc['tenant_name'] ?? '');
            }
        }

        $slug = $this->slugifyLabNameForFile($labName);
        if (strlen($slug) > 55) {
            $slug = substr($slug, 0, 55);
        }

        $created = $p['created_at'] ?? null;
        $ts      = is_string($created) && $created !== '' ? strtotime($created) : time();
        if ($ts === false) {
            $ts = time();
        }
        $datePart = date('mdY', $ts);

        return 'Recibo_' . $slug . '_' . $datePart . '.pdf';
    }

    private function slugifyLabNameForFile(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Laboratorio';
        }

        if (class_exists(\Normalizer::class, false) && function_exists('normalizer_normalize')) {
            $n = normalizer_normalize($name, \Normalizer::FORM_D);
            if (is_string($n) && $n !== '') {
                $stripped = preg_replace('/\p{Mn}/u', '', $n);
                if (is_string($stripped) && $stripped !== '') {
                    $name = $stripped;
                }
            }
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if (is_string($ascii) && $ascii !== '') {
            $name = $ascii;
        }

        $name = (string) preg_replace('/[^A-Za-z0-9]+/', '_', $name);
        $name = trim($name, '_');

        return $name !== '' ? $name : 'Laboratorio';
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function validateUploadedPdf(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            return ['ok' => false, 'message' => 'El archivo no se subió correctamente. Intente de nuevo.'];
        }
        $maxBytes = 15 * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            return ['ok' => false, 'message' => 'El PDF no debe superar 15 MB.'];
        }
        $ext = strtolower((string) $file->getClientExtension());
        if ($ext !== '' && $ext !== 'pdf') {
            return ['ok' => false, 'message' => 'Solo se permiten archivos PDF (.pdf)'];
        }
        $tmp = $file->getTempName();
        if ($tmp === '' || ! is_readable($tmp)) {
            return ['ok' => false, 'message' => 'No se pudo leer el archivo temporal.'];
        }
        $head = @file_get_contents($tmp, false, null, 0, 8);
        if ($head === false || strncmp($head, '%PDF', 4) !== 0) {
            return ['ok' => false, 'message' => 'El archivo no es un PDF válido (cabecera incorrecta).'];
        }

        return ['ok' => true, 'message' => ''];
    }

    /**
     * Sustituye o crea el PDF en disco para un pago ya registrado.
     *
     * @return array{success: bool, message: string}
     */
    public function replaceVoucherPdf(int $paymentId, UploadedFile $file): array
    {
        $payModel = model(TenantSubscriptionPaymentModel::class);
        if (! $payModel->db->tableExists('tenant_subscription_payments')) {
            return ['success' => false, 'message' => 'Ejecute las migraciones (tenant_subscription_payments).'];
        }
        $p = $this->findPayment($paymentId);
        if (! $p) {
            return ['success' => false, 'message' => 'Pago no encontrado.'];
        }
        $chk = $this->validateUploadedPdf($file);
        if (! $chk['ok']) {
            return ['success' => false, 'message' => $chk['message']];
        }
        if (! $this->ensureVoucherDir()) {
            return ['success' => false, 'message' => 'No se pudo crear el directorio de comprobantes.'];
        }
        $filename = 'tenant_voucher_' . $paymentId . '.pdf';
        $full     = $this->voucherPath($filename);
        if (is_file($full)) {
            @unlink($full);
        }
        if (! $file->move($this->voucherStorageDir(), $filename, true)) {
            return ['success' => false, 'message' => 'No se pudo guardar el PDF en el servidor.'];
        }
        $payModel->update($paymentId, ['voucher_filename' => $filename]);

        return ['success' => true, 'message' => 'PDF guardado. El cliente ya puede descargarlo.'];
    }

    /**
     * @return array{success: bool, message: string, id?: int}
     */
    public function createPaymentAndVoucher(int $tenantConfigId, string $periodStart, string $periodEnd, float $amount, string $currency, string $notes, ?UploadedFile $voucherPdf = null): array
    {
        $payModel = model(TenantSubscriptionPaymentModel::class);
        if (! $payModel->db->tableExists('tenant_subscription_payments')) {
            return ['success' => false, 'message' => 'Ejecute las migraciones (tenant_subscription_payments).'];
        }
        $tc = model(TenantConfigModel::class)->find($tenantConfigId);
        if (! is_array($tc) || (int) ($tc['is_default'] ?? 0) === 1) {
            return ['success' => false, 'message' => 'Seleccione un laboratorio cliente (no el tenant default).'];
        }
        try {
            $ds = new \DateTime($periodStart);
            $de = new \DateTime($periodEnd);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Fechas inválidas.'];
        }
        if ($de < $ds) {
            return ['success' => false, 'message' => 'La fecha fin debe ser mayor o igual al inicio.'];
        }
        if (! $this->ensureVoucherDir()) {
            return ['success' => false, 'message' => 'No se pudo crear el directorio de comprobantes.'];
        }

        $payModel->insert([
            'tenant_config_id' => $tenantConfigId,
            'period_start'     => $ds->format('Y-m-d'),
            'period_end'       => $de->format('Y-m-d'),
            'amount'           => round($amount, 2),
            'currency'         => $currency !== '' ? substr($currency, 0, 8) : 'Bs',
            'notes'            => $notes !== '' ? $notes : null,
            'voucher_filename' => null,
        ]);
        $id = (int) $payModel->getInsertID();
        if ($id < 1) {
            return ['success' => false, 'message' => 'No se pudo registrar el pago.'];
        }
        $filename = 'tenant_voucher_' . $id . '.pdf';

        if ($voucherPdf !== null) {
            $chk = $this->validateUploadedPdf($voucherPdf);
            if (! $chk['ok']) {
                $payModel->delete($id);

                return ['success' => false, 'message' => $chk['message']];
            }
            if (! $voucherPdf->move($this->voucherStorageDir(), $filename, true)) {
                $payModel->delete($id);

                return ['success' => false, 'message' => 'No se pudo guardar el PDF subido.'];
            }
            $payModel->update($id, ['voucher_filename' => $filename]);

            return ['success' => true, 'message' => 'Pago registrado con su PDF adjunto.', 'id' => $id];
        }

        $html = $this->renderVoucherHtml($id, $tc, $ds, $de, $amount, $currency, $notes);
        $pdf  = (new PdfService())->generate($html, $filename);
        $full = $this->voucherPath($filename);
        if (@file_put_contents($full, $pdf) === false) {
            $payModel->delete($id);

            return ['success' => false, 'message' => 'No se pudo guardar el PDF.'];
        }
        $payModel->update($id, ['voucher_filename' => $filename]);

        return ['success' => true, 'message' => 'Comprobante registrado y PDF generado.', 'id' => $id];
    }

    /**
     * @return array{type: string, message: string}|null
     */
    public function getExpiryAlertForTenantKey(string $tenantKey): ?array
    {
        if ($tenantKey === '' || ! $this->isMultiTenant()) {
            return null;
        }
        $def = (new TenantResolver())->resolveDefaultTenantKey();
        if ($def !== null && (string) $tenantKey === (string) $def) {
            return null;
        }
        $list = $this->listPaymentsForTenantKey($tenantKey);
        if ($list === []) {
            return null;
        }
        usort($list, static function ($a, $b) {
            return strcmp((string) ($b['period_end'] ?? ''), (string) ($a['period_end'] ?? ''));
        });
        $latest  = $list[0];
        $endStr  = (string) ($latest['period_end'] ?? '');
        $tsEnd   = strtotime($endStr . ' 23:59:59');
        $tsToday = strtotime('today');
        if ($tsEnd === false || $tsToday === false) {
            return null;
        }
        $daysLeft = (int) floor(($tsEnd - $tsToday) / 86400);
        $endFmt   = date('d/m/Y', $tsEnd);
        $warnDays = $this->getSubscriptionWarningDays();
        if ($daysLeft < 0) {
            return [
                'type'    => 'danger',
                'message' => 'La vigencia de su suscripción finalizó el ' . $endFmt . '. Contacte a administración.',
            ];
        }
        if ($daysLeft <= $warnDays) {
            return [
                'type'    => 'warning',
                'message' => 'Su suscripción vence el ' . $endFmt . ' (quedan ' . $daysLeft . ' día(s)).',
            ];
        }

        return null;
    }

    /**
     * Marca del emisor (tenant default / BD actual al generar el PDF).
     *
     * @return array{company: string, logo_data_uri: string, address: string, phone: string, email: string, website: string}
     */
    private function getIssuerBrandingForVoucher(): array
    {
        $cfg = model(AppConfigModel::class);
        $vals = $cfg->getMultiple(['company', 'logo', 'address', 'phone', 'email', 'website']);

        $company = trim((string) ($vals['company'] ?? ''));
        if ($company === '') {
            $company = 'Laboratorio';
        }

        $logoRel  = trim((string) ($vals['logo'] ?? ''));
        if ($logoRel === '') {
            $logoRel = 'images/logo-john.png';
        }
        $logoPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
        $logoDataUri = '';
        if (is_file($logoPath) && is_readable($logoPath)) {
            $raw = @file_get_contents($logoPath);
            if ($raw !== false && $raw !== '') {
                $mime = 'image/png';
                if (function_exists('finfo_open')) {
                    $f = finfo_open(FILEINFO_MIME_TYPE);
                    if ($f) {
                        $m = finfo_file($f, $logoPath);
                        finfo_close($f);
                        if (is_string($m) && $m !== '') {
                            $mime = $m;
                        }
                    }
                }
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw);
            }
        }

        return [
            'company'       => $company,
            'logo_data_uri' => $logoDataUri,
            'address'       => trim((string) ($vals['address'] ?? '')),
            'phone'         => trim((string) ($vals['phone'] ?? '')),
            'email'         => trim((string) ($vals['email'] ?? '')),
            'website'       => trim((string) ($vals['website'] ?? '')),
        ];
    }

    /**
     * @param array<string,mixed> $tenant
     */
    private function renderVoucherHtml(int $id, array $tenant, \DateTime $start, \DateTime $end, float $amount, string $currency, string $notes): string
    {
        $issuer = $this->getIssuerBrandingForVoucher();

        $lab   = esc($tenant['tenant_name'] ?? '');
        $key   = esc($tenant['tenant_key'] ?? '');
        $s     = esc($start->format('d/m/Y'));
        $e     = esc($end->format('d/m/Y'));
        $amt   = esc(number_format($amount, 2, ',', '.'));
        $cur   = esc($currency !== '' ? $currency : 'Bs');
        $nowStr = esc((new \DateTime())->format('d/m/Y H:i'));
        $idEsc = esc((string) $id);
        $companyEsc = esc($issuer['company']);

        $initial = function_exists('mb_substr')
            ? mb_strtoupper(mb_substr($issuer['company'], 0, 1, 'UTF-8'), 'UTF-8')
            : strtoupper(substr($issuer['company'], 0, 1));

        $logoBlock = $issuer['logo_data_uri'] !== ''
            ? '<img src="' . $issuer['logo_data_uri'] . '" alt="" class="logo-img">'
            : '<div class="logo-fallback">' . esc($initial) . '</div>';

        $issuerLines = '';
        if ($issuer['address'] !== '') {
            $issuerLines .= '<p class="issuer-line">' . esc($issuer['address']) . '</p>';
        }
        if ($issuer['phone'] !== '') {
            $issuerLines .= '<p class="issuer-line"><span class="lbl">Tel.</span> ' . esc($issuer['phone']) . '</p>';
        }
        if ($issuer['email'] !== '') {
            $issuerLines .= '<p class="issuer-line"><span class="lbl">Email</span> ' . esc($issuer['email']) . '</p>';
        }
        if ($issuer['website'] !== '') {
            $issuerLines .= '<p class="issuer-line"><span class="lbl">Web</span> ' . esc($issuer['website']) . '</p>';
        }

        $notesBlock = '';
        if ($notes !== '') {
            $notesBlock = '<div class="panel notes-panel"><div class="section-title">Observaciones</div><p class="notes-text">' . nl2br(esc($notes)) . '</p></div>';
        }

        $styles = <<<'CSS'
@page { margin: 14mm 16mm; }
* { box-sizing: border-box; }
body {
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    font-size: 10.5pt;
    line-height: 1.45;
    color: #1e293b;
    margin: 0;
    padding: 0;
}
.accent-bar {
    height: 5px;
    background: #0d9488;
    margin: 0 0 18px 0;
    border-radius: 3px;
}
.header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4px;
}
.header-table td { vertical-align: middle; padding: 0; }
.logo-cell {
    width: 34%;
    padding-right: 16px;
}
.logo-img {
    max-height: 72px;
    max-width: 220px;
    width: auto;
    height: auto;
    display: block;
}
.logo-fallback {
    width: 64px;
    height: 64px;
    background: #0d9488;
    border-radius: 12px;
    color: #fff;
    font-size: 22pt;
    font-weight: bold;
    text-align: center;
    line-height: 64px;
}
.issuer-name {
    font-size: 17pt;
    font-weight: bold;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0 0 6px 0;
}
.issuer-tagline {
    font-size: 7.5pt;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    margin: 0 0 8px 0;
}
.issuer-line {
    font-size: 8.5pt;
    color: #475569;
    margin: 2px 0;
}
.issuer-line .lbl { color: #94a3b8; font-weight: 600; margin-right: 4px; }
.meta-row {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0 18px 0;
}
.meta-row td { vertical-align: top; }
.badge-wrap { text-align: right; }
.receipt-badge {
    display: inline-block;
    text-align: right;
    border: 2px solid #0d9488;
    border-radius: 8px;
    padding: 12px 16px;
    background: #f0fdfa;
}
.receipt-badge-k {
    font-size: 7.5pt;
    font-weight: bold;
    color: #0f766e;
    letter-spacing: 0.16em;
    margin: 0 0 6px 0;
}
.receipt-badge-num {
    font-size: 15pt;
    font-weight: bold;
    color: #134e4a;
    margin: 0;
}
.receipt-badge-date {
    font-size: 8.5pt;
    color: #64748b;
    margin: 8px 0 0 0;
}
.section-title {
    font-size: 7.5pt;
    font-weight: bold;
    color: #0d9488;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin: 0 0 10px 0;
    padding-bottom: 5px;
    border-bottom: 1px solid #cbd5e1;
}
.panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 16px;
}
.cliente-block .cliente-nombre {
    font-size: 12pt;
    font-weight: bold;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.cliente-block .cliente-key {
    font-size: 9pt;
    color: #64748b;
    font-family: DejaVu Sans Mono, monospace;
    margin: 0;
}
.tbl-concepto {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
    margin: 0 0 14px 0;
}
.tbl-concepto th {
    text-align: left;
    font-size: 7.5pt;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    padding: 8px 10px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
}
.tbl-concepto td {
    padding: 12px 10px;
    border: 1px solid #e2e8f0;
    vertical-align: top;
}
.tbl-concepto .c-desc { color: #334155; }
.tbl-concepto .c-amount { text-align: right; font-weight: bold; color: #0f172a; white-space: nowrap; }
.tbl-total {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
}
.tbl-total td {
    padding: 14px 12px;
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
}
.tbl-total .total-label {
    width: 42%;
    font-size: 9pt;
    font-weight: bold;
    color: #065f46;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}
.tbl-total .total-value {
    text-align: right;
    font-size: 16pt;
    font-weight: bold;
    color: #064e3b;
}
.notes-panel .notes-text { margin: 0; font-size: 9.5pt; color: #475569; line-height: 1.5; }
.footer-note {
    margin-top: 22px;
    padding-top: 12px;
    border-top: 1px dashed #cbd5e1;
    font-size: 8pt;
    color: #94a3b8;
    text-align: center;
    line-height: 1.5;
}
CSS;

        $title = 'Comprobante de pago — Suscripción';

        return '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
            . '<title>' . esc($title) . '</title>'
            . '<style>' . $styles . '</style></head><body>'
            . '<div class="accent-bar"></div>'
            . '<table class="header-table"><tr>'
            . '<td class="logo-cell">' . $logoBlock . '</td>'
            . '<td><div class="issuer-name">' . $companyEsc . '</div>'
            . '<p class="issuer-tagline">Emisor del comprobante</p>'
            . $issuerLines
            . '</td></tr></table>'
            . '<table class="meta-row"><tr><td></td><td class="badge-wrap">'
            . '<div class="receipt-badge">'
            . '<p class="receipt-badge-k">Recibo de pago</p>'
            . '<p class="receipt-badge-num">N.º ' . $idEsc . '</p>'
            . '<p class="receipt-badge-date">Emitido: ' . $nowStr . '</p>'
            . '</div></td></tr></table>'
            . '<div class="section-title">Cliente / Laboratorio facturado</div>'
            . '<div class="panel cliente-block">'
            . '<p class="cliente-nombre">' . $lab . '</p>'
            . '<p class="cliente-key">Tenant: ' . $key . '</p>'
            . '</div>'
            . '<div class="section-title">Detalle</div>'
            . '<table class="tbl-concepto">'
            . '<thead><tr><th>Concepto</th><th style="text-align:right;width:32%">Importe</th></tr></thead><tbody>'
            . '<tr><td class="c-desc">Suscripción / servicio de plataforma<br>'
            . '<span style="font-size:8.5pt;color:#64748b;">Período de vigencia: ' . $s . ' — ' . $e . '</span></td>'
            . '<td class="c-amount">' . $amt . ' ' . $cur . '</td></tr>'
            . '</tbody></table>'
            . '<table class="tbl-total"><tr>'
            . '<td class="total-label">Total pagado</td>'
            . '<td class="total-value">' . $amt . ' ' . $cur . '</td>'
            . '</tr></table>'
            . $notesBlock
            . '<p class="footer-note">Documento generado electrónicamente. Conserve este comprobante para sus registros.<br>'
            . 'Válido como acuse de pago del período indicado respecto del laboratorio cliente arriba identificado.</p>'
            . '</body></html>';
    }
}
