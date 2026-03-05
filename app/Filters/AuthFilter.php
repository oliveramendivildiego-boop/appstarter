<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro de autenticación: verifica que el usuario esté logueado.
 * Las rutas de login, no_access y APIs públicas deben excluirse.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $employeeModel = model(\App\Models\EmployeeModel::class);

        if (!$employeeModel->isLoggedIn()) {
            return redirect()->to(site_url('login'))->with('error', lang('Common.common_session_expired'));
        }

        return null;
    }

    /**
     * Verifica si hay un empleado logueado (no doctor).
     */
    public static function isEmployeeLoggedIn(): bool
    {
        return session()->has('person_id') && session()->get('person_id') !== null;
    }

    /**
     * Verifica si hay un doctor logueado.
     */
    public static function isDoctorLoggedIn(): bool
    {
        return session()->has('doctor_id') && session()->get('doctor_id') !== null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return $response;
    }
}
