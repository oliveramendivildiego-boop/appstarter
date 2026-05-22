<?php

namespace App\Commands;

use App\Services\ScheduledTenantBackupRunner;
use App\Services\TenantBackupScheduleService;
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

        $runner = new ScheduledTenantBackupRunner();
        $result = $runner->run($force);

        if (! $result['success']) {
            CLI::error($result['message']);
            if (! empty($result['errors'])) {
                CLI::write('Claves con error: ' . implode(', ', $result['errors']), 'yellow');
            }

            return;
        }

        if (! $result['ran']) {
            if (($result['code'] ?? '') === 'disabled') {
                CLI::write('Respaldos automáticos de tenants deshabilitados (Configuración → Tenants).', 'yellow');

                return;
            }

            $schedule = new TenantBackupScheduleService();
            $st       = $schedule->getFormState();
            $tz       = $schedule->getResolvedTimezoneIdentifier();
            CLI::write($result['message'], 'dark_gray');
            CLI::write(sprintf(
                'Referencia: %s a las %s (%s).',
                (string) ($st[TenantBackupScheduleService::$keyFrequency] ?? ''),
                (string) ($st[TenantBackupScheduleService::$keyTime] ?? ''),
                $tz
            ), 'dark_gray');
            CLI::write('Hosting (cPanel): Cron cada p. ej. 5 min con wget/curl a la URL secreta (ver Configuración → Tenants) o: php spark lab:tenant-backup-schedule', 'yellow');
            CLI::write('Prueba forzada: php spark lab:tenant-backup-schedule --force', 'dark_gray');

            return;
        }

        CLI::write('Respaldo guardado: ' . ($result['path'] ?? ''), 'green');
        CLI::write('Dumps OK: ' . (int) ($result['ok'] ?? 0) . ' | Fallos: ' . (int) ($result['fail'] ?? 0), 'white');
        if (! empty($result['errors'])) {
            CLI::write('Claves con error: ' . implode(', ', $result['errors']), 'yellow');
        }
    }
}
