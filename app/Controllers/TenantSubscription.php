<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Services\TenantSubscriptionService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Vista de solo lectura de pagos / comprobantes para laboratorios cliente (tenant no default).
 */
class TenantSubscription extends SecureArea
{
    protected ?string $moduleId = null;

    public function index()
    {
        $svc = new TenantSubscriptionService();
        if (! $svc->isNonDefaultTenantSession()) {
            return redirect()->to(model(EmployeeModel::class)->getDefaultLandingUrl((int) session()->get('person_id')))->with('error', 'Esta sección solo está disponible para laboratorios cliente.');
        }
        $key      = (string) $svc->sessionTenantKey();
        $payments = $svc->listPaymentsForTenantKey($key);

        return view('tenant_subscription/index', [
            'payments'         => $payments,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'tenant_subscription',
        ]);
    }

    public function voucher($id): ResponseInterface
    {
        $svc = new TenantSubscriptionService();
        if (! $svc->isNonDefaultTenantSession()) {
            return redirect()->to(model(EmployeeModel::class)->getDefaultLandingUrl((int) session()->get('person_id')))->with('error', 'Acceso no permitido.');
        }
        $key   = (string) $svc->sessionTenantKey();
        $payId = (int) $id;
        if (! $svc->tenantCanAccessPayment($key, $payId)) {
            return redirect()->to(site_url('tenant-subscription'))->with('error', 'Comprobante no encontrado.');
        }
        $p = $svc->findPayment($payId);
        if (! $p) {
            return redirect()->to(site_url('tenant-subscription'))->with('error', 'Comprobante no encontrado.');
        }
        $fn = (string) ($p['voucher_filename'] ?? '');
        if ($fn === '' || ! preg_match('/^[a-zA-Z0-9._-]+$/', $fn)) {
            return redirect()->to(site_url('tenant-subscription'))->with('error', 'Archivo no disponible.');
        }
        $path = $svc->voucherPath($fn);
        if (! is_file($path) || ! is_readable($path)) {
            return redirect()->to(site_url('tenant-subscription'))->with('error', 'Archivo no encontrado en disco.');
        }
        $binary = @file_get_contents($path);
        if ($binary === false) {
            return redirect()->to(site_url('tenant-subscription'))->with('error', 'No se pudo leer el PDF.');
        }

        $downloadName = $svc->buildVoucherDownloadFilename($payId);
        $downloadName = str_replace(['"', "\r", "\n", '\\'], '', $downloadName);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $downloadName . '"')
            ->setBody($binary);
    }
}
