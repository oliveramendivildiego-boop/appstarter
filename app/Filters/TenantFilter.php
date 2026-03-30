<?php

namespace App\Filters;

use App\Libraries\TenantResolver;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $resolver = new TenantResolver();

        $requestedTenant = $resolver->resolveTenantKey($request);
        $tenantKey = $requestedTenant;

        if ($requestedTenant !== null) {
            $cfg = $resolver->resolveDatabaseConfig($requestedTenant);
            if ($cfg === []) {
                return Services::response()
                    ->setStatusCode(403)
                    ->setBody('Tenant inválido o inactivo.');
            }
        } else {
            $tenantKey = $resolver->resolveDefaultTenantKey();
            $requireTenant = filter_var((string) env('tenancy.requireTenant', 'false'), FILTER_VALIDATE_BOOLEAN);
            if ($requireTenant && $tenantKey === null) {
                return Services::response()
                    ->setStatusCode(403)
                    ->setBody('Debe indicar un tenant válido.');
            }
        }

        if ($tenantKey !== null && function_exists('session')) {
            session()->set('tenant_key', $tenantKey);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
