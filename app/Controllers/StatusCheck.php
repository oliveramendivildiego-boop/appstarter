<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use CodeIgniter\HTTP\ResponseInterface;

class StatusCheck extends BaseController
{
    /**
     * Verificar si el empleado sigue siendo activo (se usa para logout automático)
     * No requiere SecureArea ni permisos especiales
     */
    public function checkEmployeeActive(): ResponseInterface
    {
        $employeeModel = model(EmployeeModel::class);
        $personId = (int) session()->get('person_id');
        
        // Si no hay sesión de empleado, no está activo
        if (!$personId) {
            return $this->response->setJSON(['active' => false, 'reason' => 'sesión no iniciada']);
        }
        
        // Si no está logueado, no está activo
        if (!$employeeModel->isLoggedIn()) {
            return $this->response->setJSON(['active' => false, 'reason' => 'sesión no válida']);
        }
        
        // Verificar si el empleado sigue siendo activo en la BD
        $isActive = $employeeModel->getEmployeeStatus($personId);
        
        // Solo consulta: no destruir la sesión aquí (evita cortar formularios en curso).
        // El cierre de sesión lo hace toggleStatus / logoutAllSessions al desactivar al empleado.
        if ($isActive !== true) {
            return $this->response->setJSON(['active' => false, 'reason' => 'usuario deshabilitado o no encontrado']);
        }

        return $this->response->setJSON([
            'active'      => true,
            'csrf_token'  => csrf_hash(),
            'csrf_name'   => csrf_token(),
        ]);
    }
}
