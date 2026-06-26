<?php

namespace App\Controllers;

use App\Services\ScheduledTenantBackupRunner;
use App\Services\ScheduledWritableCachePurgeRunner;

/**
 * Endpoints sin sesión para tareas programadas (cPanel wget/cron-job.org, etc.).
 */
class Cron extends BaseController
{
    /**
     * GET cron/tenant-backup-schedule con query token y opcional force=1.
     * Token: env tenantBackup.cronKey en .env del servidor.
     */
    public function tenantBackupSchedule()
    {
        $expected = trim((string) env('tenantBackup.cronKey', ''));
        if ($expected === '') {
            return $this->response->setStatusCode(404)->setContentType('text/plain')->setBody('Not Found');
        }

        $token = trim((string) $this->request->getGet('token'));
        if ($token === '' || ! hash_equals($expected, $token)) {
            return $this->response->setStatusCode(404)->setContentType('text/plain')->setBody('Not Found');
        }

        $force  = $this->request->getGet('force') === '1';
        $result = (new ScheduledTenantBackupRunner())->run($force);

        $lines = [];
        if (! $result['success']) {
            $lines[] = 'ERROR: ' . $result['message'];
            if (! empty($result['errors'])) {
                $lines[] = 'Detalle: ' . implode(', ', $result['errors']);
            }

            return $this->response->setStatusCode(500)->setContentType('text/plain; charset=UTF-8')->setBody(implode("\n", $lines));
        }

        if (! $result['ran']) {
            $lines[] = 'SKIP: ' . $result['message'];
            $lines[] = 'code=' . $result['code'];

            return $this->response->setStatusCode(200)->setContentType('text/plain; charset=UTF-8')->setBody(implode("\n", $lines));
        }

        $lines[] = 'OK: ' . ($result['message'] ?? '');
        if (! empty($result['path'])) {
            $lines[] = 'file=' . basename((string) $result['path']);
        }
        $lines[] = 'dumps_ok=' . (int) ($result['ok'] ?? 0) . ' dumps_fail=' . (int) ($result['fail'] ?? 0);
        if (! empty($result['errors'])) {
            $lines[] = 'errors=' . implode(',', $result['errors']);
        }

        return $this->response->setStatusCode(200)->setContentType('text/plain; charset=UTF-8')->setBody(implode("\n", $lines));
    }

    /**
     * GET cron/writable-cache-purge con query token y opcional force=1.
     * Token: env writableCache.cronKey en .env del servidor.
     */
    public function writableCachePurge()
    {
        $expected = trim((string) env('writableCache.cronKey', ''));
        if ($expected === '') {
            return $this->response->setStatusCode(404)->setContentType('text/plain')->setBody('Not Found');
        }

        $token = trim((string) $this->request->getGet('token'));
        if ($token === '' || ! hash_equals($expected, $token)) {
            return $this->response->setStatusCode(404)->setContentType('text/plain')->setBody('Not Found');
        }

        $force  = $this->request->getGet('force') === '1';
        $result = (new ScheduledWritableCachePurgeRunner())->run($force);

        $lines = [];
        if (! $result['success']) {
            $lines[] = 'ERROR: ' . $result['message'];

            return $this->response->setStatusCode(500)->setContentType('text/plain; charset=UTF-8')->setBody(implode("\n", $lines));
        }

        if (! $result['ran']) {
            $lines[] = 'SKIP: ' . $result['message'];
            $lines[] = 'code=' . $result['code'];

            return $this->response->setStatusCode(200)->setContentType('text/plain; charset=UTF-8')->setBody(implode("\n", $lines));
        }

        $lines[] = 'OK: ' . ($result['message'] ?? '');
        $lines[] = 'files_removed=' . (int) ($result['files_removed'] ?? 0);
        $lines[] = 'dirs_removed=' . (int) ($result['dirs_removed'] ?? 0);
        $lines[] = 'bytes_freed=' . (int) ($result['bytes_freed'] ?? 0);

        return $this->response->setStatusCode(200)->setContentType('text/plain; charset=UTF-8')->setBody(implode("\n", $lines));
    }
}
