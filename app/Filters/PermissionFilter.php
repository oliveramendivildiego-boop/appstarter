<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro de permisos: verifica que el usuario tenga acceso al módulo.
 * Requiere que AuthFilter ya haya ejecutado (usuario logueado).
 * El argumento es el module_id a verificar.
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $moduleId = $arguments[0] ?? null;
        if (!$moduleId) {
            return null;
        }

        $employeeModel = model(\App\Models\EmployeeModel::class);
        $userInfo     = $employeeModel->getLoggedInEmployeeInfo();

        if (!$userInfo || !$employeeModel->hasPermission($moduleId, $userInfo->person_id)) {
            return redirect()->to(site_url('no_access/' . $moduleId));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return $response;
    }
}
