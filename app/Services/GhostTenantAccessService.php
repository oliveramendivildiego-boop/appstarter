<?php

namespace App\Services;

use App\Libraries\TenantResolver;
use Config\Database as AppDatabaseConfig;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Config as DbConfig;

/**
 * Resuelve con qué empleado del tenant debe operar el superusuario en modo fantasma.
 * No usa contraseña: solo comprueba existencia en la BD del laboratorio cliente.
 */
class GhostTenantAccessService
{
    /**
     * @return array{person_id: int, username: string, matched: string}|null
     */
    public function resolveEmployeeForGhost(int $centralPersonId, string $centralUsername, string $tenantKey): ?array
    {
        $tenantKey = trim($tenantKey);
        if ($tenantKey === '') {
            return null;
        }

        $db = $this->connectToTenant($tenantKey);
        if ($db === null) {
            return null;
        }

        try {
            $centralUsername = strtolower(trim($centralUsername));

            if ($centralPersonId > 0 && $centralUsername !== '') {
                $row = $db->table('employees')
                    ->select('person_id, username')
                    ->where('person_id', $centralPersonId)
                    ->where('username', $centralUsername)
                    ->where('deleted', 0)
                    ->where('active', 1)
                    ->get()
                    ->getRow();
                if ($row) {
                    return $this->packEmployee($row, 'exact');
                }
            }

            if ($centralPersonId > 0) {
                $row = $db->table('employees')
                    ->select('person_id, username')
                    ->where('person_id', $centralPersonId)
                    ->where('deleted', 0)
                    ->where('active', 1)
                    ->get()
                    ->getRow();
                if ($row) {
                    return $this->packEmployee($row, 'person_id');
                }
            }

            if ($centralUsername !== '') {
                $row = $db->table('employees')
                    ->select('person_id, username')
                    ->where('username', $centralUsername)
                    ->where('deleted', 0)
                    ->where('active', 1)
                    ->get()
                    ->getRow();
                if ($row) {
                    return $this->packEmployee($row, 'username');
                }
            }

            $fallback = $this->findFallbackEmployee($db);

            return $fallback !== null ? $this->packEmployee($fallback, 'fallback') : null;
        } finally {
            $this->disconnectProbe($db);
        }
    }

    private function packEmployee(object $row, string $matched): array
    {
        return [
            'person_id' => (int) ($row->person_id ?? 0),
            'username'  => strtolower(trim((string) ($row->username ?? ''))),
            'matched'   => $matched,
        ];
    }

    private function findFallbackEmployee(BaseConnection $db): ?object
    {
        $row = $db->table('employees')
            ->select('person_id, username')
            ->where('username', 'admin')
            ->where('deleted', 0)
            ->where('active', 1)
            ->get()
            ->getRow();
        if ($row) {
            return $row;
        }

        $row = $db->table('employees')
            ->select('employees.person_id, employees.username')
            ->join('permissions', 'permissions.person_id = employees.person_id')
            ->where('permissions.module_id', 'config')
            ->where('employees.deleted', 0)
            ->where('employees.active', 1)
            ->limit(1)
            ->get()
            ->getRow();
        if ($row) {
            return $row;
        }

        return $db->table('employees')
            ->select('person_id, username')
            ->where('deleted', 0)
            ->where('active', 1)
            ->orderBy('person_id', 'ASC')
            ->limit(1)
            ->get()
            ->getRow();
    }

    private function connectToTenant(string $tenantKey): ?BaseConnection
    {
        $resolver = new TenantResolver();
        $tenantDb = $resolver->resolveDatabaseConfig($tenantKey);
        if ($tenantDb === []) {
            return null;
        }

        $dbConfig = config(AppDatabaseConfig::class);
        $cfg = array_merge($dbConfig->default, $tenantDb);
        $cfg['DBDebug'] = false;

        try {
            return DbConfig::connect($cfg, 'ghost_probe', false);
        } catch (\Throwable $e) {
            log_message('error', 'GhostTenantAccess: no se pudo conectar al tenant ' . $tenantKey . ': ' . $e->getMessage());

            return null;
        }
    }

    private function disconnectProbe(BaseConnection $db): void
    {
        try {
            $db->close();
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
        unset($instances['ghost_probe']);
        $prop->setValue(null, $instances);
    }

    /**
     * Aplica sesión de modo fantasma con el empleado resuelto en el tenant.
     */
    public function applyGhostSession(
        int $centralPersonId,
        string $tenantKey,
        array $tenantEmployee,
        bool $impersonating = false
    ): void {
        $personId = (int) ($tenantEmployee['person_id'] ?? 0);
        if ($personId < 1) {
            return;
        }

        $sess = session();
        if ($centralPersonId > 0 && $centralPersonId !== $personId) {
            $sess->set('ghost_central_person_id', $centralPersonId);
            $sess->set('ghost_impersonating', true);
        } else {
            $sess->remove(['ghost_central_person_id', 'ghost_impersonating']);
        }

        $sess->remove('user_info_' . $personId);
        $sess->remove('allowed_modules_' . $personId);
        if ($centralPersonId > 0 && $centralPersonId !== $personId) {
            $sess->remove('user_info_' . $centralPersonId);
            $sess->remove('allowed_modules_' . $centralPersonId);
        }

        $sess->set('person_id', $personId);
        $sess->set('user_type', 'employee');
        $sess->remove('doctor_id');
        $sess->set('suppress_tenant_audit', true);
        $sess->set('ghost_target_tenant_key', $tenantKey);

        if ($impersonating) {
            $sess->set('ghost_impersonating', true);
        }
    }

    public function restoreCentralSessionAfterGhost(): void
    {
        if (! function_exists('session')) {
            return;
        }
        $sess = session();
        $central = (int) ($sess->get('ghost_central_person_id') ?? 0);
        $current = (int) ($sess->get('person_id') ?? 0);

        if ($central > 0 && $central !== $current) {
            $sess->remove('user_info_' . $current);
            $sess->remove('allowed_modules_' . $current);
            $sess->set('person_id', $central);
            $sess->set('user_type', 'employee');
            $sess->remove('user_info_' . $central);
            $sess->remove('allowed_modules_' . $central);
        }

        $sess->remove([
            'suppress_tenant_audit',
            'ghost_target_tenant_key',
            'ghost_central_person_id',
            'ghost_impersonating',
        ]);
    }

    public static function isGhostSupportSession(): bool
    {
        if (! function_exists('session')) {
            return false;
        }

        return (bool) session()->get('suppress_tenant_audit');
    }
}
