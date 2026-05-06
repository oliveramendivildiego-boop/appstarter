<?php

namespace App\Services;

/**
 * Llama al endpoint cron por HTTP (misma URL que curl externo), desde el propio servidor.
 * Útil para alinear con el flujo del token y ejecutar tras cerrar la respuesta al navegador.
 */
class TenantBackupSelfHttpTrigger
{
    /**
     * GET al cron con el token de .env (sin forzar; isDue se evalúa en el servidor al atender la petición).
     *
     * @return string|null
     */
    public static function getCronUrl()
    {
        $token = trim((string) env('tenantBackup.cronKey', ''));
        if ($token === '') {
            return null;
        }
        $base = trim((string) env('tenantBackup.selfTriggerBaseUrl', ''));
        if ($base !== '') {
            return rtrim($base, '/') . '/cron/tenant-backup-schedule?token=' . rawurlencode($token);
        }

        return site_url('cron/tenant-backup-schedule') . '?token=' . rawurlencode($token);
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
                CURLOPT_TIMEOUT        => 600,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            curl_close($ch);

            return;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 600,
                'header'  => "User-Agent: LaboratorioTenantBackup/1\r\n",
            ],
        ]);
        @file_get_contents($url, false, $ctx);
    }
}
