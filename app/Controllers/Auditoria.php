<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;

class Auditoria extends SecureArea
{
    protected ?string $moduleId = 'auditoria';

    public function index()
    {
        $perPage = 12;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'modulo'      => trim((string)($this->request->getGet('modulo') ?? '')),
            'accion'      => trim((string)($this->request->getGet('accion') ?? '')),
            'fecha_desde' => trim((string)($this->request->getGet('fecha_desde') ?? '')),
            'fecha_hasta' => trim((string)($this->request->getGet('fecha_hasta') ?? '')),
            'buscar'      => trim((string)($this->request->getGet('buscar') ?? '')),
        ];

        $model     = model(AuditoriaModel::class);
        $registros = $model->getPaginados($perPage, $offset, $filters);
        $total     = $model->countFiltered($filters);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('auditoria/index', [
            'registros'       => $registros,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'auditoria',
            'page'            => $page,
            'totalPages'      => $totalPages,
            'total'           => $total,
            'perPage'         => $perPage,
            'filters'         => $filters,
            'modulos_list'    => $model->getDistinctModulos(),
            'acciones_list'   => $model->getDistinctAcciones(),
        ]);
    }
}
