<?php

namespace App\Controllers;

use App\Libraries\QcStatistics;
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

        $fechaIni = $this->request->getGet('fecha_ini') ?? lab_date_ymd('-30 days');
        $fechaFin = $this->request->getGet('fecha_fin') ?? lab_today_ymd();
        $valores = $this->model->getValores($controlId, $fechaIni, $fechaFin);

        $numeric = array_map(static fn ($v) => (float) ($v['valor'] ?? 0), $valores);
        $qcStats = QcStatistics::sampleStats($numeric);

        $series = [];
        foreach ($valores as $v) {
            $series[] = [
                'fecha' => (string) ($v['fecha'] ?? ''),
                'valor' => (float) ($v['valor'] ?? 0),
            ];
        }

        $westgard = [];
        if ($qcStats !== null && $qcStats['sd'] > 0) {
            $westgard = QcStatistics::evaluateWestgard($series, $qcStats['mean'], $qcStats['sd']);
        }

        return view('controlcalidad/grafica', [
            'control'          => $control,
            'valores'          => $valores,
            'fecha_ini'        => $fechaIni,
            'fecha_fin'        => $fechaFin,
            'qc_stats'         => $qcStats,
            'westgard'         => $westgard,
            'allowed_modules'  => $this->allowed_modules,
            'current_module'   => 'controlcalidad',
            'user_info'       => $this->user_info,
        ]);
    }

    public function savecontrol()
    {
        $id = (int) ($this->request->getPost('control_id') ?? 0);
        $post = $this->request->getPost();
        $payload = [
            'nombre' => $post['nombre'] ?? '',
            'tipo'   => (int) ($post['tipo'] ?? 1),
        ];
        if (array_key_exists('sesgo', $post)) {
            $rawSesgo = $post['sesgo'];
            $payload['sesgo'] = ($rawSesgo === null || $rawSesgo === '') ? null : $rawSesgo;
        }
        $this->model->saveControl($payload, $id > 0 ? $id : null);
        \App\Models\AuditoriaModel::log('controlcalidad', $id > 0 ? 'actualizar' : 'crear', $id > 0 ? (string) $id : null);
        if ($id > 0) {
            return redirect()->to("controlcalidad/grafica/{$id}")->with('success', 'Control actualizado');
        }
        return redirect()->to('controlcalidad')->with('success', 'Control guardado');
    }

    public function savevalor()
    {
        $controlId = (int) ($this->request->getPost('control_id') ?? 0);
        $this->model->saveValor([
            'control_id'     => $controlId,
            'fecha'          => $this->request->getPost('fecha') ?? lab_today_ymd(),
            'valor'          => $this->request->getPost('valor') ?? 0,
            'esperado'       => $this->request->getPost('esperado') ?: null,
            'observaciones'  => $this->request->getPost('observaciones') ?? '',
        ]);
        \App\Models\AuditoriaModel::log('controlcalidad', 'valor_registrar', (string) $controlId);
        return redirect()->to("controlcalidad/grafica/{$controlId}")->with('success', 'Valor registrado');
    }
}
