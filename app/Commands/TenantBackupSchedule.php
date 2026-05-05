<?php

namespace App\Commands;

use App\Services\TenantBackupScheduleService;
use App\Services\TenantBackupService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Ejecutar periódicamente (Programador de tareas / cron), p. ej. cada 5 minutos:
 * php spark lab:tenant-backup-schedule
 *
 * --force : ejecuta aunque no toque o esté deshabilitado (sigue guardando el ZIP).
 */
class TenantBackupSchedule extends BaseCommand
{
    protected $group       = 'Laboratorio';
    protected $name        = 'lab:tenant-backup-schedule';
    protected $usage       = 'lab:tenant-backup-schedule [options]';
    protected $arguments   = [];
    protected $options     = [
        '--force' => 'Forzar ejecución ahora',
    ];
    protected $description = 'Respaldo ZIP de tenants según horario en Configuración (app_config)';

    public function run(array $params)
    {
        $force = false;
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if ($arg === '--force') {
                $force = true;
                break;
            }
        }

        $schedule = new TenantBackupScheduleService();

        if (! $schedule->isAutomationEnabled() && ! $force) {
            CLI::write('Respaldos automáticos de tenants deshabilitados (Configuración → Tenants).', 'yellow');

            return;
        }

        if (! $force && ! $schedule->isDue()) {
            CLI::write('No corresponde ejecutar respaldo en este momento.', 'dark_gray');

            return;
        }

        if (! class_exists(\ZipArchive::class)) {
            CLI::error('ZipArchive no está disponible.');

            return;
        }

        $backup = new TenantBackupService();
        $dir    = WRITEPATH . 'tenant_backups_scheduled';
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            CLI::error('No se pudo crear ' . $dir);

            return;
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'respaldo_tenants_' . date('Y-m-d_His') . '.zip';
        $r    = $backup->saveZipToFile($path, false);
        if (! $r['success']) {
            CLI::error($r['message']);

            return;
        }

        $schedule->markLastRunNow();
        $backup->pruneScheduledBackups($dir, $schedule->getKeepCount());

        CLI::write('Respaldo guardado: ' . $path, 'green');
        CLI::write('Dumps OK: ' . (int) ($r['ok'] ?? 0) . ' | Fallos: ' . (int) ($r['fail'] ?? 0), 'white');
        if (! empty($r['errors'])) {
            CLI::write('Claves con error: ' . implode(', ', $r['errors']), 'yellow');
        }

        try {
            \App\Models\AuditoriaModel::log('config', 'tenant_backup_programado', null, basename($path));
        } catch (\Throwable $e) {
            // CLI sin sesión: no bloquear
        }
    }
}
