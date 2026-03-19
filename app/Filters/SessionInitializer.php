<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SessionInitializer implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Inicializar sesión explícitamente
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Asegurar que la sesión se está persistiendo
        session()->set('_session_initialized', time());

        // Log para debugging
        $sessionPath = WRITEPATH . 'session';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0775, true);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
