<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;

class Auditoria extends SecureArea
{
    protected ?string $moduleId = 'auditoria';

    public function index()
    {
        $perPage   = 25;
        $page      = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset    = ($page - 1) * $perPage;

        $model     = model(AuditoriaModel::class);
        $registros = $model->getPaginados($perPage, $offset);
        $total     = $model->countAll();
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('auditoria/index', [
            'registros'        => $registros,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'auditoria',
            'page'            => $page,
            'totalPages'      => $totalPages,
            'total'           => $total,
            'perPage'         => $perPage,
        ]);
    }
}
