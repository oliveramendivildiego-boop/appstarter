<?php

namespace App\Services;

/**
 * Llama al endpoint cron de limpieza de caché por HTTP.
 */
class WritableCacheSelfHttpTrigger
{
    /**
     * @return string|null
     */
    public static function getCronUrl()
    {
        $token = trim((string) env('writableCache.cronKey', ''));
        if ($token === '') {
            return null;
        }
        $base = trim((string) env('writableCache.selfTriggerBaseUrl', ''));
        if ($base !== '') {
            return rtrim($base, '/') . '/cron/writable-cache-purge?token=' . rawurlencode($token);
        }

        return site_url('cron/writable-cache-purge') . '?token=' . rawurlencode($token);
    }

    /**
     * @param string|null $url
     */
    public static function dispatch($url = null): void
    {
        if ($url === null || $url === '') {
            $url = self::getCronUrl();
        }
        if ($url === null || $url === '') {
            return;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            curl_close($ch);

            return;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 120,
                'header'  => "User-Agent: LaboratorioWritableCache/1\r\n",
            ],
        ]);
        @file_get_contents($url, false, $ctx);
    }
}
