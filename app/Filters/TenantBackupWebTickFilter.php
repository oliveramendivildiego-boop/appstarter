<?php

namespace App\Filters;

use App\Libraries\TenantResolver;
use App\Models\EmployeeModel;
use App\Services\TenantBackupWebTick;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Tras respuestas GET autenticadas, programa comprobación de respaldo de tenants (si .env lo permite).
 */
class TenantBackupWebTickFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (is_cli()) {
            return null;
        }
        if (! filter_var((string) env('tenantBackup.tickOnWeb', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }
        if (strtoupper($request->getMethod()) !== 'GET') {
            return null;
        }

        $uri = trim((string) uri_string(), '/');
        if ($uri === '' || str_starts_with($uri, 'cron/') || str_starts_with($uri, 'login')) {
            return null;
        }

        $personId = (int) session()->get('person_id');
        if ($personId <= 0) {
            return null;
        }
        if (! model(EmployeeModel::class)->hasPermission('config', $personId)) {
            return null;
        }
        if (! $this->mayManageTenants()) {
            return null;
        }

        TenantBackupWebTick::registerDeferredRun();

        return null;
    }

    private function mayManageTenants()
    {
        $resolver = new TenantResolver();
        $default  = $resolver->resolveDefaultTenantKey();
        if ($default === null) {
            return true;
        }
        $current = session()->get('tenant_key');
        if ($current === null || $current === '') {
            $current = $resolver->resolveTenantKey();
        }
        if ($current === null || $current === '') {
            return true;
        }

        return (string) $current === (string) $default;
    }
}
