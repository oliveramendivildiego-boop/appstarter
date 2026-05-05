<?php

namespace App\Services;

use App\Models\AuditoriaModel;

/**
 * Ejecuta el respaldo programado de tenants (misma lógica que spark lab:tenant-backup-schedule).
 */
class ScheduledTenantBackupRunner
{
    /**
     * @return array<string, mixed>
     */
    public function run(bool $force = false): array
    {
        $schedule = new TenantBackupScheduleService();

        if (! $schedule->isAutomationEnabled() && ! $force) {
            return [
                'success' => true,
                'ran'     => false,
                'code'    => 'disabled',
                'message' => 'Respaldos automáticos deshabilitados en Configuración.',
            ];
        }

        if (! $force && ! $schedule->isDue()) {
            return [
                'success' => true,
                'ran'     => false,
                'code'    => 'not_due',
                'message' => 'Aún no corresponde ejecutar para este período o ya se registró la corrida.',
            ];
        }

        if (! class_exists(\ZipArchive::class)) {
            return [
                'success' => false,
                'ran'     => false,
                'code'    => 'no_zip',
                'message' => 'La extensión ZipArchive no está disponible.',
            ];
        }

        $backup = new TenantBackupService();
        $dir    = WRITEPATH . 'tenant_backups_scheduled';
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return [
                'success' => false,
                'ran'     => false,
                'code'    => 'mkdir',
                'message' => 'No se pudo crear el directorio de respaldos programados.',
            ];
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'respaldo_tenants_' . date('Y-m-d_His') . '.zip';
        $r    = $backup->saveZipToFile($path, false);
        if (! $r['success']) {
            return [
                'success' => false,
                'ran'     => false,
                'code'    => 'backup_failed',
                'message' => (string) ($r['message'] ?? 'Error al generar respaldo.'),
                'ok'      => (int) ($r['ok'] ?? 0),
                'fail'    => (int) ($r['fail'] ?? 0),
                'errors'  => (array) ($r['errors'] ?? []),
            ];
        }

        $schedule->markLastRunNow();
        $backup->pruneScheduledBackups($dir, $schedule->getKeepCount());

        try {
            AuditoriaModel::log('config', 'tenant_backup_programado', null, basename($path));
        } catch (\Throwable $e) {
            // Sin sesión o auditoría no disponible
        }

        return [
            'success' => true,
            'ran'     => true,
            'code'    => 'ok',
            'message' => 'Respaldo guardado.',
            'path'    => $path,
            'ok'      => (int) ($r['ok'] ?? 0),
            'fail'    => (int) ($r['fail'] ?? 0),
            'errors'  => (array) ($r['errors'] ?? []),
        ];
    }
}
