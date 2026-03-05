<?php

namespace App\Controllers;

use App\Models\ControlCalidadModel;

class Controlcalidad extends SecureArea
{
    protected ?string $moduleId = 'controlcalidad';
    protected ControlCalidadModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = model(ControlCalidadModel::class);
    }

    public function index()
    {
        $controles = $this->model->getAll();
        return view('controlcalidad/index', [
            'controles'        => $controles,
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'controlcalidad',
        ]);
    }

    public function grafica($controlId)
    {
        $controlId = (int) $controlId;
        $control = $this->model->getById($controlId);
        if (!$control) return redirect()->to('controlcalidad')->with('error', 'Control no encontrado');

        $fechaIni = $this->request->getGet('fecha_ini') ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin = $this->request->getGet('fecha_fin') ?? date('Y-m-d');
        $valores = $this->model->getValores($controlId, $fechaIni, $fechaFin);

        return view('controlcalidad/grafica', [
            'control'          => $control,
            'valores'          => $valores,
            'fecha_ini'        => $fechaIni,
            'fecha_fin'        => $fechaFin,
            'allowed_modules'  => $this->allowed_modules,
            'current_module'   => 'controlcalidad',
            'user_info'       => $this->user_info,
        ]);
    }

    public function savecontrol()
    {
        $id = (int) ($this->request->getPost('control_id') ?? 0);
        $this->model->saveControl([
            'nombre' => $this->request->getPost('nombre') ?? '',
            'tipo'   => (int) ($this->request->getPost('tipo') ?? 1),
        ], $id > 0 ? $id : null);
        \App\Models\AuditoriaModel::log('controlcalidad', $id > 0 ? 'actualizar' : 'crear', $id > 0 ? (string) $id : null);
        return redirect()->to('controlcalidad')->with('success', 'Control guardado');
    }

    public function savevalor()
    {
        $controlId = (int) ($this->request->getPost('control_id') ?? 0);
        $this->model->saveValor([
            'control_id'     => $controlId,
            'fecha'          => $this->request->getPost('fecha') ?? date('Y-m-d'),
            'valor'          => $this->request->getPost('valor') ?? 0,
            'esperado'       => $this->request->getPost('esperado') ?: null,
            'observaciones'  => $this->request->getPost('observaciones') ?? '',
        ]);
        \App\Models\AuditoriaModel::log('controlcalidad', 'valor_registrar', (string) $controlId);
        return redirect()->to("controlcalidad/grafica/{$controlId}")->with('success', 'Valor registrado');
    }
}
