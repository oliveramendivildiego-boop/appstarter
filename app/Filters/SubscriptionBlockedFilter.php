<?php

namespace App\Filters;

use App\Models\EmployeeModel;
use App\Services\GhostTenantAccessService;
use App\Services\TenantSubscriptionService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bloquea el uso del sistema para laboratorios cliente con suscripción vencida.
 * Debe ejecutarse tras TenantFilter (tenant_key en sesión) y antes del controlador.
 */
class SubscriptionBlockedFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $employeeModel = model(EmployeeModel::class);
        if (! $employeeModel->isLoggedIn() || session()->has('doctor_id')) {
            return null;
        }
        if (GhostTenantAccessService::isGhostSupportSession()) {
            return null;
        }

        $svc = new TenantSubscriptionService();
        if (! $svc->isSubscriptionAccessBlocked()) {
            return null;
        }

        helper('url');
        $uri = trim((string) uri_string(), '/');
        if (TenantSubscriptionService::isUriAllowedWhenSubscriptionBlocked($uri)) {
            return null;
        }

        return redirect()->to(site_url('subscription-blocked'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
