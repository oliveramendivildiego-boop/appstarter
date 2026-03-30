<?php

namespace App\Services;

use App\Models\TenantConfigModel;

class TenantConfigService
{
    private TenantConfigModel $tenantModel;

    public function __construct(?TenantConfigModel $tenantModel = null)
    {
        $this->tenantModel = $tenantModel ?? model(TenantConfigModel::class);
    }

    public function getAll(): array
    {
        if (!$this->tenantModel->db->tableExists('tenant_configs')) {
            return [];
        }

        return $this->tenantModel
            ->orderBy('tenant_name', 'ASC')
            ->findAll();
    }

    public function saveFromRequest(array $data): array
    {
        if (!$this->tenantModel->db->tableExists('tenant_configs')) {
            return ['success' => false, 'message' => 'Falta ejecutar migraciones de tenants.'];
        }

        $id = (int) ($data['tenant_id'] ?? 0);
        $tenantKey = $this->normalizeTenantKey((string) ($data['tenant_key'] ?? ''));
        $tenantName = trim((string) ($data['tenant_name'] ?? ''));

        if ($tenantKey === '' || $tenantName === '') {
            return ['success' => false, 'message' => 'Tenant key y nombre son obligatorios.'];
        }

        $payload = [
            'tenant_key' => $tenantKey,
            'tenant_name' => $tenantName,
            'db_host' => trim((string) ($data['db_host'] ?? 'localhost')),
            'db_port' => (int) ($data['db_port'] ?? 3306),
            'db_name' => trim((string) ($data['db_name'] ?? '')),
            'db_user' => trim((string) ($data['db_user'] ?? '')),
            'db_pass' => (string) ($data['db_pass'] ?? ''),
            'db_prefix' => trim((string) ($data['db_prefix'] ?? 'dom_')),
            'is_active' => (($data['is_active'] ?? '0') === '1') ? 1 : 0,
            'is_default' => (($data['is_default'] ?? '0') === '1') ? 1 : 0,
        ];

        if ($payload['db_name'] === '' || $payload['db_user'] === '') {
            return ['success' => false, 'message' => 'Base de datos y usuario son obligatorios.'];
        }

        $connectionTest = $this->testConnection(
            $payload['db_host'],
            $payload['db_port'],
            $payload['db_name'],
            $payload['db_user'],
            $payload['db_pass'],
            $payload['db_prefix']
        );
        if (!$connectionTest['success']) {
            return $connectionTest;
        }

        $existsQuery = $this->tenantModel->where('tenant_key', $tenantKey);
        if ($id > 0) {
            $existsQuery->where('id !=', $id);
        }
        if ($existsQuery->countAllResults() > 0) {
            return ['success' => false, 'message' => 'El tenant key ya existe.'];
        }

        $encrypted = $this->encryptSecret($payload['db_pass']);
        if ($encrypted === null) {
            return ['success' => false, 'message' => 'No se pudo cifrar la contraseña de DB. Configure encryption.key en .env.'];
        }
        $payload['db_pass'] = $encrypted;

        if ($id > 0) {
            $ok = $this->tenantModel->update($id, $payload);
        } else {
            $ok = $this->tenantModel->insert($payload) !== false;
        }

        if (!$ok) {
            return ['success' => false, 'message' => 'No se pudo guardar el tenant.'];
        }

        if ((int) $payload['is_default'] === 1) {
            $savedId = $id > 0 ? $id : (int) $this->tenantModel->getInsertID();
            if ($savedId > 0) {
                $this->tenantModel->where('id !=', $savedId)->set(['is_default' => 0])->update();
            }
        }

        $publish = $this->publishTenantMap();
        if (!$publish['success']) {
            return $publish;
        }

        return ['success' => true, 'message' => 'Tenant guardado correctamente.'];
    }

    public function delete(int $id): array
    {
        if (!$this->tenantModel->db->tableExists('tenant_configs')) {
            return ['success' => false, 'message' => 'Falta ejecutar migraciones de tenants.'];
        }

        $ok = $this->tenantModel->delete($id);
        if (!$ok) {
            return ['success' => false, 'message' => 'No se pudo eliminar el tenant.'];
        }

        return $this->publishTenantMap();
    }

    public function publishTenantMap(): array
    {
        $rows = $this->tenantModel->orderBy('tenant_name', 'ASC')->findAll();
        $map = [
            '_default' => null,
            'tenants' => [],
        ];

        foreach ($rows as $row) {
            $key = (string) ($row['tenant_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $decryptedPass = $this->decryptSecret((string) ($row['db_pass'] ?? ''));
            if ($decryptedPass === null) {
                return ['success' => false, 'message' => 'No se pudo descifrar una contraseña de tenant existente. Revise encryption.key.'];
            }

            if ((int) ($row['is_default'] ?? 0) === 1) {
                $map['_default'] = $key;
            }

            if ((int) ($row['is_active'] ?? 0) !== 1) {
                continue;
            }

            $map['tenants'][$key] = [
                'hostname' => (string) ($row['db_host'] ?? 'localhost'),
                'port' => (int) ($row['db_port'] ?? 3306),
                'database' => (string) ($row['db_name'] ?? ''),
                'username' => (string) ($row['db_user'] ?? ''),
                'password' => $decryptedPass,
                'DBPrefix' => (string) ($row['db_prefix'] ?? 'dom_'),
            ];
        }

        $path = WRITEPATH . 'tenants_map.json';
        $json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['success' => false, 'message' => 'Error al serializar el mapa de tenants.'];
        }

        $ok = @file_put_contents($path, $json);
        if ($ok === false) {
            return ['success' => false, 'message' => 'No se pudo publicar el mapa de tenants. Revise permisos de escritura.'];
        }

        return ['success' => true, 'message' => 'Mapa de tenants publicado.'];
    }

    public function provisionTenant(int $id): array
    {
        $tenant = $this->tenantModel->find($id);
        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant no encontrado.'];
        }

        $dbName = (string) ($tenant['db_name'] ?? '');
        $dbHost = (string) ($tenant['db_host'] ?? 'localhost');
        $dbPort = (int) ($tenant['db_port'] ?? 3306);
        $dbUser = (string) ($tenant['db_user'] ?? '');
        $dbPass = $this->decryptSecret((string) ($tenant['db_pass'] ?? ''));
        if ($dbPass === null) {
            return ['success' => false, 'message' => 'No se pudo descifrar la contraseña del tenant.'];
        }

        if ($dbName === '' || $dbUser === '') {
            return ['success' => false, 'message' => 'Falta configuración de base de datos del tenant.'];
        }

        $admin = \Config\Database::connect();
        $admin->query('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');

        $migrate = $this->runMigrationsForTenant([
            'hostname' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'DBPrefix' => (string) ($tenant['db_prefix'] ?? 'dom_'),
        ]);

        if (!$migrate['success']) {
            return $migrate;
        }

        return ['success' => true, 'message' => 'Tenant aprovisionado correctamente (DB + migraciones).'];
    }

    private function normalizeTenantKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key) ?: '';
        return $key;
    }

    private function testConnection(string $host, int $port, string $dbName, string $user, string $pass, string $prefix): array
    {
        try {
            $db = \Config\Database::connect([
                'hostname' => $host,
                'username' => $user,
                'password' => $pass,
                'database' => $dbName,
                'DBDriver' => 'MySQLi',
                'DBPrefix' => $prefix,
                'pConnect' => false,
                'DBDebug' => true,
                'charset' => 'utf8mb4',
                'DBCollat' => 'utf8mb4_general_ci',
                'port' => $port,
            ], false);
            $db->query('SELECT 1');
            return ['success' => true, 'message' => 'Conexión OK'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'No se pudo conectar a la DB del tenant: ' . $e->getMessage()];
        }
    }

    private function encryptSecret(string $plain): ?string
    {
        if ($plain === '') {
            return '';
        }
        try {
            $enc = service('encrypter');
            return 'enc::' . base64_encode($enc->encrypt($plain));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function decryptSecret(string $stored): ?string
    {
        if ($stored === '') {
            return '';
        }
        if (!str_starts_with($stored, 'enc::')) {
            return $stored;
        }
        $raw = base64_decode(substr($stored, 5), true);
        if ($raw === false) {
            return null;
        }
        try {
            $enc = service('encrypter');
            return (string) $enc->decrypt($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function runMigrationsForTenant(array $cfg): array
    {
        $phpFromEnv = trim((string) env('tenancy.phpBinary', ''));
        $php = $phpFromEnv !== '' ? $phpFromEnv : 'php';
        $quotedPhp = '"' . str_replace('"', '\"', $php) . '"';
        $prefix = (string) ($cfg['DBPrefix'] ?? 'dom_');
        $sparkPath = rtrim((string) ROOTPATH, "\\/") . DIRECTORY_SEPARATOR . 'spark';
        $quotedSpark = '"' . str_replace('"', '\"', $sparkPath) . '"';

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'cmd /C "set database.default.hostname=' . $this->escCmd((string) $cfg['hostname'])
                . '&& set database.default.username=' . $this->escCmd((string) $cfg['username'])
                . '&& set database.default.password=' . $this->escCmd((string) $cfg['password'])
                . '&& set database.default.database=' . $this->escCmd((string) $cfg['database'])
                . '&& set database.default.DBPrefix=' . $this->escCmd($prefix)
                . '&& set database.default.port=' . $this->escCmd((string) ($cfg['port'] ?? 3306))
                . '&& ' . $quotedPhp . ' ' . $quotedSpark . ' migrate --all"';
        } else {
            $cmd = 'database.default.hostname=' . escapeshellarg((string) $cfg['hostname'])
                . ' database.default.username=' . escapeshellarg((string) $cfg['username'])
                . ' database.default.password=' . escapeshellarg((string) $cfg['password'])
                . ' database.default.database=' . escapeshellarg((string) $cfg['database'])
                . ' database.default.DBPrefix=' . escapeshellarg($prefix)
                . ' database.default.port=' . escapeshellarg((string) ($cfg['port'] ?? 3306))
                . ' ' . $quotedPhp . ' ' . $quotedSpark . ' migrate --all';
        }

        $out = [];
        $code = 1;
        @exec($cmd . ' 2>&1', $out, $code);
        if ($code !== 0) {
            return [
                'success' => false,
                'message' => 'Error al ejecutar migraciones del tenant con PHP CLI "' . $php . '": '
                    . implode(' | ', array_slice($out, -8)),
            ];
        }
        return ['success' => true, 'message' => 'Migraciones ejecutadas.'];
    }

    private function escCmd(string $value): string
    {
        return str_replace(['^', '&', '|', '<', '>', '%', '"'], ['^^', '^&', '^|', '^<', '^>', '%%', '\"'], $value);
    }
}
