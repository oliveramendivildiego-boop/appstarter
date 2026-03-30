<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

class TenantResolver
{
    /**
     * Detecta la llave de tenant desde header, query o subdominio.
     */
    public function resolveTenantKey(?IncomingRequest $request = null): ?string
    {
        if ($request === null) {
            if (!function_exists('service')) {
                return null;
            }
            $request = service('request');
            if (!$request instanceof IncomingRequest) {
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
        if ($host === '') {
            return null;
        }

        $host = explode(':', $host)[0];
        if ($this->isBaseApplicationHost($host)) {
            return null;
        }

        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return null;
        }

        $firstLabel = $parts[0] ?? '';
        if ($firstLabel === '' || in_array($firstLabel, ['www', 'localhost'], true)) {
            return null;
        }

        return $this->normalize($firstLabel);
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

    private function isBaseApplicationHost(string $host): bool
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
