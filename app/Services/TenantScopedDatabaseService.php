<?php

namespace App\Services;

use App\Libraries\TenantResolver;
use CodeIgniter\HTTP\IncomingRequest;
use Config\Database as AppDatabaseConfig;

/**
 * Asegura que las operaciones de datos usen la BD del tenant activo (sesión/subdominio).
 */
class TenantScopedDatabaseService
{
    public function __construct(private ?TenantResolver $resolver = null)
    {
        $this->resolver ??= new TenantResolver();
    }

    /**
     * @return array{tenant_key: string, database: string}
     */
    public function ensureActiveTenantConnection(?IncomingRequest $request = null): array
    {
        $tenantKey = $this->resolver->resolveTenantKey($request);
        if ($tenantKey === null) {
            throw new \RuntimeException('No hay un tenant activo para esta sesión.');
        }

        $tenantDb = $this->resolver->resolveDatabaseConfig($tenantKey);
        if ($tenantDb === []) {
            throw new \RuntimeException('El tenant activo no tiene base de datos configurada.');
        }

        $this->resolver->applyResolvedTenantToAppDatabase($tenantKey);
        $this->resolver->forceReconnectDefaultDatabase();

        $dbConfig = config(AppDatabaseConfig::class);
        $database = trim((string) ($tenantDb['database'] ?? $dbConfig->default['database'] ?? ''));

        return [
            'tenant_key' => $tenantKey,
            'database'   => $database,
        ];
    }

    public function resolveActiveTenantKey(?IncomingRequest $request = null): ?string
    {
        return $this->resolver->resolveTenantKey($request);
    }
}
