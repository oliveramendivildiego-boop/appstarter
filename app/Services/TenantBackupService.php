<?php

namespace App\Services;

/**
 * Respaldo ZIP de todas las bases de datos de tenants (mysqldump + ZipArchive).
 */
class TenantBackupService
{
    /** @var TenantConfigService */
    private $tenantConfigService;

    public function __construct(?TenantConfigService $tenantConfigService = null)
    {
        $this->tenantConfigService = $tenantConfigService ?? new TenantConfigService();
    }

    /**
     * @return array{success: bool, message: string, binary: ?string, ok: int, fail: int, errors: list<string>}
     */
    public function buildZipBinary(bool $excludeDefaultTenant = false): array
    {
        $tmp = $this->buildZipInTempDir($excludeDefaultTenant);
        if (! ($tmp['success'] ?? false)) {
            return [
                'success' => false,
                'message' => (string) ($tmp['message'] ?? 'Error'),
                'binary'  => null,
                'ok'      => 0,
                'fail'    => 0,
                'errors'  => [],
            ];
        }

        $path = (string) ($tmp['zipPath'] ?? '');
        $binary = @file_get_contents($path);
        $this->cleanupTenantBackupTempDir((string) ($tmp['tmpDir'] ?? ''));

        if ($binary === false) {
            return [
                'success' => false,
                'message' => 'No se pudo leer el ZIP generado.',
                'binary'  => null,
                'ok'      => (int) ($tmp['ok'] ?? 0),
                'fail'    => (int) ($tmp['fail'] ?? 0),
                'errors'  => (array) ($tmp['errors'] ?? []),
            ];
        }

        return [
            'success' => true,
            'message' => 'Respaldo generado correctamente',
            'binary'  => $binary,
            'ok'      => (int) ($tmp['ok'] ?? 0),
            'fail'    => (int) ($tmp['fail'] ?? 0),
            'errors'  => (array) ($tmp['errors'] ?? []),
        ];
    }

    /**
     * @return array{success: bool, message: string, zipPath?: string, tmpDir?: string, ok: int, fail: int, errors: list<string>}
     */
    public function buildZipInTempDir(bool $excludeDefaultTenant = false): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return [
                'success' => false,
                'message' => 'La extensión ZipArchive no está disponible en PHP.',
                'ok'      => 0,
                'fail'    => 0,
                'errors'  => [],
            ];
        }

        $targets = $this->tenantConfigService->listBackupConnections($excludeDefaultTenant);
        if ($targets === []) {
            return [
                'success' => false,
                'message' => 'No hay tenants activos para respaldar.',
                'ok'      => 0,
                'fail'    => 0,
                'errors'  => [],
            ];
        }

        $tmpDir = WRITEPATH . 'tenant_backups' . DIRECTORY_SEPARATOR . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        if (! is_dir($tmpDir) && ! @mkdir($tmpDir, 0755, true)) {
            return [
                'success' => false,
                'message' => 'No se pudo crear carpeta temporal para respaldos.',
                'ok'      => 0,
                'fail'    => 0,
                'errors'  => [],
            ];
        }

        $dumped = [];
        $errors = [];
        foreach ($targets as $t) {
            $tenantKey = trim((string) ($t['tenant_key'] ?? ''));
            if ($tenantKey === '') {
                $tenantKey = 'tenant_' . (int) ($t['id'] ?? 0);
            }
            $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tenantKey) ?: 'tenant';
            $dumpPath = $tmpDir . DIRECTORY_SEPARATOR . $safeKey . '.sql';

            $cmd = sprintf(
                'mysqldump -h %s -P %d -u %s%s --databases %s > %s',
                escapeshellarg((string) ($t['db_host'] ?? 'localhost')),
                (int) ($t['db_port'] ?? 3306),
                escapeshellarg((string) ($t['db_user'] ?? 'root')),
                ((string) ($t['db_pass'] ?? '')) !== '' ? ' -p' . escapeshellarg((string) $t['db_pass']) : '',
                escapeshellarg((string) ($t['db_name'] ?? '')),
                escapeshellarg($dumpPath)
            );
            $cmd .= (PHP_OS_FAMILY === 'Windows') ? ' 2>NUL' : ' 2>/dev/null';
            @exec($cmd, $out, $code);

            if ($code !== 0 || ! is_file($dumpPath) || filesize($dumpPath) < 1) {
                $errors[] = $tenantKey;
                continue;
            }
            $dumped[] = [
                'tenant_key' => $tenantKey,
                'path'       => $dumpPath,
            ];
        }

        if ($dumped === []) {
            $this->cleanupTenantBackupTempDir($tmpDir);

            return [
                'success' => false,
                'message' => 'No se pudo generar ningún dump de tenants. Verifique mysqldump y credenciales.',
                'ok'      => 0,
                'fail'    => count($errors),
                'errors'  => $errors,
            ];
        }

        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'tenant_backups_' . date('Ymd_His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->cleanupTenantBackupTempDir($tmpDir);

            return [
                'success' => false,
                'message' => 'No se pudo crear el archivo ZIP de respaldo.',
                'ok'      => 0,
                'fail'    => count($errors),
                'errors'  => $errors,
            ];
        }
        foreach ($dumped as $d) {
            $zip->addFile((string) $d['path'], basename((string) $d['path']));
        }
        $zip->close();

        return [
            'success' => true,
            'message' => 'Respaldo generado correctamente',
            'zipPath' => $zipPath,
            'tmpDir'  => $tmpDir,
            'ok'      => count($dumped),
            'fail'    => count($errors),
            'errors'  => $errors,
        ];
    }

    /**
     * Genera ZIP y lo mueve a $absolutePath (directorio debe existir o crearse).
     *
     * @return array{success: bool, message: string, path?: string, ok: int, fail: int, errors: list<string>}
     */
    public function saveZipToFile(string $absolutePath, bool $excludeDefaultTenant = false): array
    {
        $built = $this->buildZipInTempDir($excludeDefaultTenant);
        if (! ($built['success'] ?? false)) {
            return [
                'success' => false,
                'message' => (string) ($built['message'] ?? 'Error'),
                'ok'      => (int) ($built['ok'] ?? 0),
                'fail'    => (int) ($built['fail'] ?? 0),
                'errors'  => (array) ($built['errors'] ?? []),
            ];
        }

        $zipPath = (string) ($built['zipPath'] ?? '');
        $tmpDir  = (string) ($built['tmpDir'] ?? '');
        if ($zipPath === '' || ! is_file($zipPath)) {
            $this->cleanupTenantBackupTempDir($tmpDir);

            return [
                'success' => false,
                'message' => 'Archivo ZIP no encontrado tras generar.',
                'ok'      => 0,
                'fail'    => 0,
                'errors'  => [],
            ];
        }

        $dir = dirname($absolutePath);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            $this->cleanupTenantBackupTempDir($tmpDir);

            return [
                'success' => false,
                'message' => 'No se pudo crear el directorio de destino.',
                'ok'      => 0,
                'fail'    => 0,
                'errors'  => [],
            ];
        }

        if (! @rename($zipPath, $absolutePath)) {
            $ok = @copy($zipPath, $absolutePath);
            @unlink($zipPath);
            if (! $ok) {
                $this->cleanupTenantBackupTempDir($tmpDir);

                return [
                    'success' => false,
                    'message' => 'No se pudo guardar el ZIP en destino.',
                    'ok'      => 0,
                    'fail'    => 0,
                    'errors'  => [],
                ];
            }
        }

        $this->cleanupTenantBackupTempDir($tmpDir);

        return [
            'success' => true,
            'message' => 'Respaldo guardado.',
            'path'    => $absolutePath,
            'ok'      => (int) ($built['ok'] ?? 0),
            'fail'    => (int) ($built['fail'] ?? 0),
            'errors'  => (array) ($built['errors'] ?? []),
        ];
    }

    /**
     * Conserva los $keep archivos más recientes que coincidan con el patrón.
     */
    public function pruneScheduledBackups(string $directory, int $keep = 14): void
    {
        $keep = max(1, min(100, $keep));
        if (! is_dir($directory)) {
            return;
        }
        $files = glob($directory . DIRECTORY_SEPARATOR . 'respaldo_tenants_*.zip') ?: [];
        $mapped = [];
        foreach ($files as $f) {
            if (! is_file($f)) {
                continue;
            }
            $mapped[] = ['path' => $f, 'mtime' => (int) @filemtime($f)];
        }
        usort($mapped, static fn (array $a, array $b): int => ($b['mtime'] <=> $a['mtime']));
        foreach (array_slice($mapped, $keep) as $row) {
            @unlink((string) $row['path']);
        }
    }

    public function cleanupTenantBackupTempDir(string $dir): void
    {
        if ($dir === '' || ! is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        if (! is_array($files)) {
            @rmdir($dir);

            return;
        }
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
