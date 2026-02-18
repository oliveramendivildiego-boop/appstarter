<?php

namespace App\Controllers;

class NoAccess extends BaseController
{
    public function index(?string $module_id = null)
    {
        return view('errors/html/error_403', [
            'message' => 'No tienes permiso para acceder a este módulo.',
            'module'  => $module_id,
        ]);
    }
}
