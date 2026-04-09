<?php

namespace App\Services;

use App\Models\TenantConfigModel;
use CodeIgniter\Database\MigrationRunner;
use Config\Migrations as MigrationsConfig;

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
        $publicBase = trim((string) ($data['public_base_url'] ?? ''));
        if ($publicBase !== '' && filter_var($publicBase, FILTER_VALIDATE_URL) === false) {
            return ['success' => false, 'message' => 'La URL pública del tenant no es válida (use http:// o https://).'];
        }

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
        if ($this->tenantModel->db->fieldExists('public_base_url', 'tenant_configs')) {
            $payload['public_base_url'] = $publicBase !== '' ? rtrim($publicBase, '/') : null;
        }

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
        $rawRows = $this->tenantModel->orderBy('id', 'ASC')->findAll();
        $byNormKey = [];
        foreach ($rawRows as $row) {
            $norm = $this->normalizeTenantKey((string) ($row['tenant_key'] ?? ''));
            if ($norm === '') {
                continue;
            }
            $prev = $byNormKey[$norm] ?? null;
            if ($prev === null) {
                $byNormKey[$norm] = $row;
                continue;
            }
            $prevDef = (int) ($prev['is_default'] ?? 0) === 1;
            $curDef  = (int) ($row['is_default'] ?? 0) === 1;
            if ($curDef && ! $prevDef) {
                $byNormKey[$norm] = $row;
            } elseif ($prevDef && ! $curDef) {
                // conservar $prev
            } elseif ((int) ($row['id'] ?? 0) >= (int) ($prev['id'] ?? 0)) {
                $byNormKey[$norm] = $row;
            }
        }
        $rows = array_values($byNormKey);
        usort($rows, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['tenant_name'] ?? ''), (string) ($b['tenant_name'] ?? ''));
        });

        $map = [
            '_default' => null,
            'tenants' => [],
        ];

        foreach ($rows as $row) {
            $key = $this->normalizeTenantKey((string) ($row['tenant_key'] ?? ''));
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

        $pub = $this->publishTenantMap();
        if (! $pub['success']) {
            return $pub;
        }

        return ['success' => true, 'message' => 'Tenant aprovisionado correctamente (DB + migraciones). Mapa de tenants actualizado.'];
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

    /**
     * Ejecuta migraciones contra la BD del tenant usando una conexión explícita.
     *
     * Antes se invocaba `php spark migrate` con variables de entorno; en Windows (y con .env)
     * esas variables suelen no aplicarse y las migraciones corrían contra database.default del .env,
     * mezclando o dañando datos (p. ej. app_config del laboratorio principal).
     */
    private function runMigrationsForTenant(array $cfg): array
    {
        $prefix = (string) ($cfg['DBPrefix'] ?? 'dom_');

        $params = [
            'DSN'          => '',
            'hostname'     => (string) $cfg['hostname'],
            'username'     => (string) $cfg['username'],
            'password'     => (string) $cfg['password'],
            'database'     => (string) $cfg['database'],
            'DBDriver'     => 'MySQLi',
            'DBPrefix'     => $prefix,
            'pConnect'     => false,
            'DBDebug'      => false,
            'charset'      => 'utf8mb4',
            'DBCollat'     => 'utf8mb4_general_ci',
            'port'         => (int) ($cfg['port'] ?? 3306),
            'swapPre'      => '',
            'encrypt'      => false,
            'compress'     => false,
            'strictOn'     => false,
            'failover'     => [],
            'foreignKeys'  => true,
            'dateFormat'   => [
                'date'     => 'Y-m-d',
                'datetime' => 'Y-m-d H:i:s',
                'time'     => 'H:i:s',
            ],
        ];

        $db = null;

        try {
            $db = \Config\Database::connect($params, false);
            $db->initialize();

            $runner = new MigrationRunner(config(MigrationsConfig::class), $db);
            $runner->setNamespace(null);
            $runner->clearCliMessages();

            if (! $runner->latest()) {
                $msgs = $runner->getCliMessages();

                return [
                    'success' => false,
                    'message' => 'Error al ejecutar migraciones en la BD del tenant: '
                        . (implode(' | ', $msgs) !== '' ? implode(' | ', $msgs) : 'revise el registro del servidor'),
                ];
            }

            return ['success' => true, 'message' => 'Migraciones ejecutadas.'];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al migrar la BD del tenant: ' . $e->getMessage(),
            ];
        } finally {
            if ($db !== null) {
                try {
                    $db->close();
                } catch (\Throwable $e) {
                    // ignorar cierre
                }
            }
        }
    }
}
