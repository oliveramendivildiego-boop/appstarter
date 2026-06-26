<?php

namespace App\Services;

use App\Models\AuditoriaModel;

/**
 * Ejecuta la limpieza programada de writable/cache.
 */
class ScheduledWritableCachePurgeRunner
{
    /**
     * @return array<string, mixed>
     */
    public function run(bool $force = false): array
    {
        $schedule = new WritableCacheScheduleService();

        if (! $schedule->isAutomationEnabled() && ! $force) {
            return [
                'success' => true,
                'ran'     => false,
                'code'    => 'disabled',
                'message' => 'Limpieza automática de caché deshabilitada en Configuración.',
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

        $purge  = new WritableCachePurgeService();
        $result = $purge->purge();
        if (! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'ran'     => false,
                'code'    => 'purge_failed',
                'message' => (string) ($result['message'] ?? 'Error al borrar la caché.'),
            ];
        }

        $schedule->markLastRunNow();

        try {
            AuditoriaModel::log(
                'config',
                'writable_cache_programado',
                null,
                (string) (($result['files_removed'] ?? 0) . ' archivos')
            );
        } catch (\Throwable $e) {
            // Sin sesión o auditoría no disponible
        }

        return [
            'success'       => true,
            'ran'           => true,
            'code'          => 'ok',
            'message'       => (string) ($result['message'] ?? 'Caché borrada.'),
            'files_removed' => (int) ($result['files_removed'] ?? 0),
            'dirs_removed'  => (int) ($result['dirs_removed'] ?? 0),
            'bytes_freed'   => (int) ($result['bytes_freed'] ?? 0),
        ];
    }
}
