<?php

namespace App\Filters;

use App\Libraries\TenantResolver;
use App\Services\RegisterService;
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

        // Reaplicar tenant sobre la conexión compartida `default` para que la navegación
        // interna (sin ?tenant=) no regrese a la BD central/default.
        $explicit = $resolver->resolveTenantKeyFromRequestOnly();
        if ($tenantKey !== null && $explicit === null) {
            $resolver->applyResolvedTenantToAppDatabase($tenantKey);
        }

        if (function_exists('session') && session_status() === PHP_SESSION_ACTIVE) {
            $resolver->clearGhostSessionIfTenantInvalid();
        }

        RegisterService::applyRequestTimezone();

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
