<?php

namespace App\Controllers;

use App\Models\EquipoModel;

class Equipos extends SecureArea
{
    protected ?string $moduleId = 'equipos';
    protected EquipoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = model(EquipoModel::class);
    }

    public function index()
    {
        $equipos = $this->model->getAll();
        return view('equipos/index', [
            'equipos'        => $equipos,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function detalle($equipoId)
    {
        $equipoId = (int) $equipoId;
        $equipo = \Config\Database::connect()->table('equipo')->where('equipo_id', $equipoId)->get()->getRowArray();
        if (!$equipo) return redirect()->to('equipos')->with('error', 'Equipo no encontrado');
        $mantenimientos = $this->model->getMantenimientos($equipoId);
        return view('equipos/detalle', [
            'equipo'          => $equipo,
            'mantenimientos'  => $mantenimientos,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function saveequipo()
    {
        $id = (int) ($this->request->getPost('equipo_id') ?? 0);
        $this->model->saveEquipo([
            'nombre' => $this->request->getPost('nombre') ?? '',
            'codigo' => $this->request->getPost('codigo') ?? '',
            'ubicacion' => $this->request->getPost('ubicacion') ?? '',
        ], $id > 0 ? $id : null);
        return redirect()->to('equipos')->with('success', 'Equipo guardado');
    }

    public function savemantenimiento()
    {
        $equipoId = (int) ($this->request->getPost('equipo_id') ?? 0);
        $this->model->saveMantenimiento([
            'equipo_id' => $equipoId,
            'tipo' => (int) ($this->request->getPost('tipo') ?? 1),
            'fecha' => $this->request->getPost('fecha') ?? date('Y-m-d'),
            'descripcion' => $this->request->getPost('descripcion') ?? '',
            'realizado_por' => session()->get('person_id'),
        ]);
        return redirect()->to("equipos/detalle/{$equipoId}")->with('success', 'Mantenimiento registrado');
    }
}
