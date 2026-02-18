<?php

namespace App\Controllers;

/**
 * Muestra "En desarrollo" para módulos sin controlador completo.
 */
class ModulePlaceholder extends SecureArea
{
    protected ?string $moduleId = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        $this->moduleId = $request->getUri()->getSegment(1) ?: null;
        parent::initController($request, $response, $logger);
    }

    public function index()
    {
        return view('module_placeholder', [
            'module_id'       => $this->moduleId,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }
}
