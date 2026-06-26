<?php

namespace App\Commands;

use App\Services\ScheduledWritableCachePurgeRunner;
use App\Services\WritableCacheScheduleService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Ejecutar periódicamente (cron cada 5 minutos):
 * php spark lab:writable-cache-purge
 */
class WritableCachePurge extends BaseCommand
{
    protected $group       = 'Laboratorio';
    protected $name        = 'lab:writable-cache-purge';
    protected $usage       = 'lab:writable-cache-purge [options]';
    protected $arguments   = [];
    protected $options     = [
        '--force' => 'Forzar limpieza ahora',
    ];
    protected $description = 'Borra writable/cache según horario en Configuración (app_config)';

    public function run(array $params)
    {
        $force = false;
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if ($arg === '--force') {
                $force = true;
                break;
            }
        }

        $runner = new ScheduledWritableCachePurgeRunner();
        $result = $runner->run($force);

        if (! $result['success']) {
            CLI::error($result['message']);

            return;
        }

        if (! $result['ran']) {
            if (($result['code'] ?? '') === 'disabled') {
                CLI::write('Limpieza automática de caché deshabilitada (Configuración → Sistema).', 'yellow');

                return;
            }

            $schedule = new WritableCacheScheduleService();
            $st       = $schedule->getFormState();
            $tz       = $schedule->getResolvedTimezoneIdentifier();
            CLI::write($result['message'], 'dark_gray');
            CLI::write(sprintf(
                'Referencia: %s a las %s (%s).',
                (string) ($st[WritableCacheScheduleService::$keyFrequency] ?? ''),
                (string) ($st[WritableCacheScheduleService::$keyTime] ?? ''),
                $tz
            ), 'dark_gray');
            CLI::write('Cron: php spark lab:writable-cache-purge (cada ~5 min) o URL en Configuración → Sistema.', 'yellow');
            CLI::write('Prueba forzada: php spark lab:writable-cache-purge --force', 'dark_gray');

            return;
        }

        CLI::write($result['message'], 'green');
    }
}
