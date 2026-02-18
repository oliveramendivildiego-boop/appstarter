<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;

class Auditoria extends SecureArea
{
    protected ?string $moduleId = 'auditoria';

    public function index()
    {
        $model = model(AuditoriaModel::class);
        $registros = $model->getRecientes(150);
        return view('auditoria/index', [
            'registros'       => $registros,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }
}
