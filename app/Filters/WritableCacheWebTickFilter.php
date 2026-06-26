<?php

namespace App\Filters;

use App\Models\EmployeeModel;
use App\Services\WritableCacheWebTick;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Tras respuestas GET autenticadas con permiso de config, programa limpieza de caché (si .env lo permite).
 */
class WritableCacheWebTickFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (is_cli()) {
            return null;
        }
        if (! filter_var((string) env('writableCache.tickOnWeb', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }
        if (strtoupper($request->getMethod()) !== 'GET') {
            return null;
        }

        $uri = trim((string) uri_string(), '/');
        if ($uri === '' || str_starts_with($uri, 'cron/') || str_starts_with($uri, 'login')) {
            return null;
        }

        $personId = (int) session()->get('person_id');
        if ($personId <= 0) {
            return null;
        }
        if (! model(EmployeeModel::class)->hasPermission('config', $personId)) {
            return null;
        }

        WritableCacheWebTick::registerDeferredRun();

        return null;
    }
}
