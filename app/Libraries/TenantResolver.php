<?php

namespace App\Libraries;

use CodeIgniter\Database\Config as DbConfig;
use CodeIgniter\HTTP\IncomingRequest;
use Config\Database as AppDatabaseConfig;
use Config\Session as SessionConfig;

class TenantResolver
{
    /**
     * Solo request explícito (header, query, subdominio). Sin sesión.
     * Debe usarse al construir Config\Database para no re-entrar en session()
     * cuando el driver de sesión es DatabaseHandler (evita recursión y agotar memoria).
     */
    public function resolveTenantKeyFromRequestOnly(?IncomingRequest $request = null): ?string
    {
        if ($request === null) {
            if (! function_exists('service')) {
                return null;
            }
            $request = service('request');
            if (! $request instanceof IncomingRequest) {
                return null;
            }
        }

        $headerKey = trim((string) $request->getHeaderLine('X-Tenant-Key'));
        if ($headerKey !== '') {
            return $this->normalize($headerKey);
        }

        $queryKey = trim((string) $request->getGet('tenant'));
        if ($queryKey !== '') {
            return $this->normalize($queryKey);
        }

        $host = strtolower((string) $request->getServer('HTTP_HOST'));
        if ($host !== '') {
            $host = explode(':', $host)[0];
            if (! $this->isBaseApplicationHost($host)) {
                $parts = explode('.', $host);
                if (count($parts) >= 2) {
                    $firstLabel = $parts[0] ?? '';
                    if ($firstLabel !== '' && ! in_array($firstLabel, ['www', 'localhost'], true)) {
                        $fromHost = $this->normalize($firstLabel);
                        if ($fromHost !== null) {
                            return $fromHost;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Detecta la llave de tenant desde header, query, subdominio y modo fantasma (sesión).
     */
    public function resolveTenantKey(?IncomingRequest $request = null): ?string
    {
        $fromRequest = $this->resolveTenantKeyFromRequestOnly($request);
        if ($fromRequest !== null) {
            return $fromRequest;
        }

        $fromGhost = $this->resolveGhostTenantKeyFromSession();
        if ($fromGhost !== null) {
            return $fromGhost;
        }

        return $this->resolveTenantKeyFromSession();
    }

    /**
     * Tenant fijado en sesión por navegación interna sin ?tenant=.
     */
    public function resolveTenantKeyFromSession(): ?string
    {
        if (! function_exists('session')) {
            return null;
        }
        try {
            $sess = session();
        } catch (\Throwable $e) {
            return null;
        }

        $raw = trim((string) $sess->get('tenant_key'));
        if ($raw === '') {
            return null;
        }

        $key = $this->normalize($raw);
        if ($key === null) {
            return null;
        }

        if ($this->resolveDatabaseConfig($key) === []) {
            return null;
        }

        return $key;
    }

    /**
     * Si la sesión tiene modo super-usuario (sin auditoría en tenant), fija el tenant
     * aunque no venga en query/subdominio (navegación interna sin ?tenant=).
     */
    public function resolveGhostTenantKeyFromSession(): ?string
    {
        if (! function_exists('session')) {
            return null;
        }
        try {
            $sess = session();
        } catch (\Throwable $e) {
            return null;
        }
        if (! $sess->get('suppress_tenant_audit')) {
            return null;
        }
        $raw = trim((string) $sess->get('ghost_target_tenant_key'));
        if ($raw === '') {
            return null;
        }
        $key = $this->normalize($raw);
        if ($key === null) {
            return null;
        }
        if ($this->resolveDatabaseConfig($key) === []) {
            return null;
        }

        return $key;
    }

    /**
     * Limpia flags de acceso fantasma si el tenant guardado ya no es válido (evita suprimir auditoría en default por error).
     */
    public function clearGhostSessionIfTenantInvalid(): void
    {
        if (! function_exists('session')) {
            return;
        }
        try {
            $sess = session();
        } catch (\Throwable $e) {
            return;
        }
        if (! $sess->get('suppress_tenant_audit')) {
            return;
        }
        $raw = trim((string) $sess->get('ghost_target_tenant_key'));
        if ($raw === '') {
            $sess->remove(['suppress_tenant_audit', 'ghost_target_tenant_key']);

            return;
        }
        $key = $this->normalize($raw);
        if ($key === null || $this->resolveDatabaseConfig($key) === []) {
            $sess->remove(['suppress_tenant_audit', 'ghost_target_tenant_key']);
        }
    }

    /**
     * Tras resolver tenant por sesión fantasma, alinea Config\Database con ese tenant y fuerza un nuevo
     * conector 'default' para los modelos.
     *
     * Si las sesiones usan DatabaseHandler en el mismo grupo `default`, no se resetea para evitar
     * cerrar la misma conexión compartida por la sesión.
     */
    public function applyResolvedTenantToAppDatabase(string $tenantKey): void
    {
        $tenantDb = $this->resolveDatabaseConfig($tenantKey);
        if ($tenantDb === []) {
            return;
        }
        $dbConfig = config(AppDatabaseConfig::class);
        foreach ($tenantDb as $k => $v) {
            $dbConfig->default[$k] = $v;
        }

        $sessionCfg = config(SessionConfig::class);
        $driver = (string) ($sessionCfg->driver ?? '');
        $sessionGroup = strtolower((string) ($sessionCfg->DBGroup ?? 'default'));
        if (str_contains($driver, 'DatabaseHandler') && ($sessionGroup === '' || $sessionGroup === 'default')) {
            return;
        }

        $this->resetSharedDefaultDatabaseConnection();
    }

    private function resetSharedDefaultDatabaseConnection(): void
    {
        $connections = DbConfig::getConnections();
        if (! isset($connections['default'])) {
            return;
        }
        try {
            $connections['default']->close();
        } catch (\Throwable $e) {
            // ignorar
        }
        $ref = new \ReflectionClass(DbConfig::class);
        if (! $ref->hasProperty('instances')) {
            return;
        }
        $prop = $ref->getProperty('instances');
        $prop->setAccessible(true);
        /** @var array<string, mixed> $instances */
        $instances = $prop->getValue();
        if (! is_array($instances)) {
            return;
        }
        unset($instances['default']);
        $prop->setValue(null, $instances);
    }

    /**
     * Retorna la configuración de DB para el tenant.
     *
     * Formatos soportados:
     * - tenancy.map = "acme:laboratorio_acme,globex:laboratorio_globex"
     * - tenancy.mapJson = {"acme":{"database":"laboratorio_acme","hostname":"localhost"}}
     */
    public function resolveDatabaseConfig(?string $tenantKey): array
    {
        $tenantKey = $this->normalize((string) $tenantKey);
        if ($tenantKey === null) {
            return [];
        }

        $fileMapConfig = $this->resolveFromPublishedMap($tenantKey);
        if ($fileMapConfig !== []) {
            return $fileMapConfig;
        }

        $json = trim((string) env('tenancy.mapJson', ''));
        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded[$tenantKey]) && is_array($decoded[$tenantKey])) {
                return $this->sanitizeDbConfig($decoded[$tenantKey]);
            }
        }

        $map = trim((string) env('tenancy.map', ''));
        if ($map === '') {
            return [];
        }

        foreach (explode(',', $map) as $entry) {
            $entry = trim($entry);
            if ($entry === '' || !str_contains($entry, ':')) {
                continue;
            }
            [$key, $database] = array_map('trim', explode(':', $entry, 2));
            if ($this->normalize($key) === $tenantKey && $database !== '') {
                return ['database' => $database];
            }
        }

        return [];
    }

    private function resolveFromPublishedMap(string $tenantKey): array
    {
        if (!defined('WRITEPATH')) {
            return [];
        }

        $path = WRITEPATH . 'tenants_map.json';
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $content = @file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['tenants']) && is_array($decoded['tenants'])) {
            $tenants = $decoded['tenants'];
            if (!isset($tenants[$tenantKey]) || !is_array($tenants[$tenantKey])) {
                return [];
            }
            return $this->sanitizeDbConfig($tenants[$tenantKey]);
        }

        if (!isset($decoded[$tenantKey]) || !is_array($decoded[$tenantKey])) {
            return [];
        }

        return $this->sanitizeDbConfig($decoded[$tenantKey]);
    }

    public function resolveDefaultTenantKey(): ?string
    {
        $fromMap = $this->resolveDefaultFromPublishedMap();
        if ($fromMap !== null) {
            return $fromMap;
        }

        $default = trim((string) env('tenancy.defaultTenant', ''));
        return $default === '' ? null : $this->normalize($default);
    }

    private function resolveDefaultFromPublishedMap(): ?string
    {
        if (!defined('WRITEPATH')) {
            return null;
        }

        $path = WRITEPATH . 'tenants_map.json';
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded) || !isset($decoded['_default'])) {
            return null;
        }

        return $this->normalize((string) $decoded['_default']);
    }

    private function sanitizeDbConfig(array $data): array
    {
        $allowed = ['hostname', 'username', 'password', 'database', 'DBDriver', 'DBPrefix', 'port', 'charset', 'DBCollat'];
        $result = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                $result[$key] = $data[$key];
            }
        }

        if (isset($result['port'])) {
            $result['port'] = (int) $result['port'];
        }

        return $result;
    }

    private function normalize(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }
        return preg_replace('/[^a-z0-9_\-]/', '', $value) ?: null;
    }

    /**
     * Host de la instalación principal (baseURL / tenancy.ignoreHosts), sin subdominio de tenant.
     * Útil para no enrutar database.default al tenant por defecto cuando el admin central usa la misma URL.
     */
    public function isBaseApplicationHost(string $host): bool
    {
        $baseUrl = trim((string) env('app.baseURL', ''));
        if ($baseUrl === '') {
            $baseUrl = (string) (config('App')->baseURL ?? '');
        }
        $baseHost = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        if ($baseHost !== '' && $host === $baseHost) {
            return true;
        }

        $ignoreHostsRaw = trim((string) env('tenancy.ignoreHosts', ''));
        if ($ignoreHostsRaw === '') {
            return false;
        }

        $ignoreHosts = array_filter(array_map(
            static fn ($v) => strtolower(trim($v)),
            explode(',', $ignoreHostsRaw)
        ));

        return in_array($host, $ignoreHosts, true);
    }
}
