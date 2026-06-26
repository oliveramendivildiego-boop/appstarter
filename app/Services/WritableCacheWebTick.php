<?php

namespace App\Services;

/**
 * Tras peticiones web (si tickOnWeb en .env): comprueba si toca limpiar writable/cache.
 */
class WritableCacheWebTick
{
    /** @var bool */
    private static $registered = false;

    public static function registerDeferredRun(): void
    {
        if (self::$registered || is_cli()) {
            return;
        }
        if (! filter_var((string) env('writableCache.tickOnWeb', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        self::$registered = true;

        register_shutdown_function(static function (): void {
            $interval = max(120, (int) env('writableCache.tickIntervalSeconds', 300));
            $timePath = WRITEPATH . 'writable_cache_web_tick.time';
            $lockPath = WRITEPATH . 'writable_cache_web_tick.lock';

            $fh = fopen($lockPath, 'c+');
            if ($fh === false) {
                return;
            }
            if (! flock($fh, LOCK_EX | LOCK_NB)) {
                fclose($fh);

                return;
            }
            try {
                $last = is_file($timePath) ? (int) file_get_contents($timePath) : 0;
                if (time() - $last < $interval) {
                    return;
                }

                $schedule = new WritableCacheScheduleService();
                if (! $schedule->isAutomationEnabled()) {
                    return;
                }
                if (! $schedule->isDue()) {
                    return;
                }

                file_put_contents($timePath, (string) time());

                $useDirect = filter_var((string) env('writableCache.tickDirectRunner', 'true'), FILTER_VALIDATE_BOOLEAN);
                if ($useDirect) {
                    (new ScheduledWritableCachePurgeRunner())->run(false);

                    return;
                }

                if (function_exists('fastcgi_finish_request')) {
                    @fastcgi_finish_request();
                }

                WritableCacheSelfHttpTrigger::dispatch();
            } catch (\Throwable $e) {
                // No interrumpir el cierre del request
            } finally {
                flock($fh, LOCK_UN);
                fclose($fh);
            }
        });
    }
}
