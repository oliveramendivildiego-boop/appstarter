<?php

namespace App\Controllers;

use App\Models\ReactivoModel;
use App\Models\RegisterModel;
use App\Models\EmployeeModel;

class Reactivos extends SecureArea
{
    protected ?string $moduleId = 'reactivos';
    protected ReactivoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = model(ReactivoModel::class);
    }

    public function editar($reactivoId)
    {
        $reactivoId = (int) $reactivoId;
        $reactivo = $this->model->getReactivo($reactivoId);
        if (!$reactivo) {
            return redirect()->to('inventario')->with('error', 'Insumo no encontrado');
        }
        return view('reactivos/form_editar', [
            'reactivo'        => $reactivo,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'reactivos',
        ]);
    }

    public function index()
    {
        $grupo = $this->request->getGet('grupo') ?: null;
        $reactivos = $this->model->getAllWithStockAndTipo($grupo);
        $alertas = $this->model->getAlertas();
        $grupos = $this->model->getGrupos();
        return view('reactivos/index', [
            'reactivos'       => $reactivos,
            'alertas'         => $alertas,
            'grupos'          => $grupos,
            'grupo_filtro'    => $grupo,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'reactivos',
        ]);
    }


    public function kardex()
    {
        $startDate   = $this->request->getGet('start') ?? date('Y-m-01');
        $endDate     = $this->request->getGet('end') ?? date('Y-m-d');
        $personId    = (int) ($this->request->getGet('person_id') ?? 0);
        $reactivoId  = (int) ($this->request->getGet('reactivo_id') ?? 0);
        $tipoGet     = $this->request->getGet('tipo');
        $tipo        = in_array($tipoGet, ['entrada', 'salida'], true) ? $tipoGet : null;

        $rows = $this->model->getKardexMovimientos($startDate, $endDate, $personId > 0 ? $personId : null, $reactivoId > 0 ? $reactivoId : null, $tipo, 2500);
        $resumen = $this->model->getKardexResumenPorUsuario($startDate, $endDate, $reactivoId > 0 ? $reactivoId : null, $tipo);
        $totalMovs = $this->model->countKardexMovimientos($startDate, $endDate, $personId > 0 ? $personId : null, $reactivoId > 0 ? $reactivoId : null, $tipo);

        $empleadosOpts = [];
        foreach (model(EmployeeModel::class)->getAll(5000, 0) as $emp) {
            $nombre = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name_fa ?? '') . ' ' . ($emp->last_name_mom ?? ''));
            $empleadosOpts[] = ['person_id' => (int) $emp->person_id, 'nombre' => $nombre !== '' ? $nombre : ('ID ' . (int) $emp->person_id)];
        }

        return view('reactivos/kardex', [
            'subtitle'          => date('d/m/Y', strtotime($startDate)) . ' – ' . date('d/m/Y', strtotime($endDate)),
            'rows'              => $rows,
            'resumen'           => $resumen,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'person_id'         => $personId,
            'reactivo_id'       => $reactivoId,
            'tipo'              => $tipo ?? '',
            'total_movimientos' => $totalMovs,
            'limite_lista'      => 2500,
            'lista_truncada'    => $totalMovs > count($rows),
            'show_saldo'        => $reactivoId > 0,
            'stock_actual'      => $reactivoId > 0 ? $this->model->getStockTotal($reactivoId) : null,
            'insumos'           => $this->model->getAll(),
            'empleados'         => $empleadosOpts,
            'form_action'       => site_url('inventario/kardex'),
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
            'current_module'    => 'reactivos',
        ]);
    }

    public function lotes($reactivoId)
    {
        $reactivoId = (int) $reactivoId;
        $reactivo = $this->model->getReactivo($reactivoId);
        if (!$reactivo) {
            return redirect()->to('inventario')->with('error', 'Insumo no encontrado');
        }
        $reactivo['tipo_nombre'] = $this->model->getNombreTipo((int) ($reactivo['tipo'] ?? 1));
        $lotes = $this->model->getLotes($reactivoId);
        $movimientos = [];
        try {
            $movimientos = $this->model->getMovimientos($reactivoId, 30);
        } catch (\Throwable $e) {
        }
        return view('reactivos/lotes', [
            'reactivo'        => $reactivo,
            'lotes'           => $lotes,
            'movimientos'     => $movimientos,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
            'current_module'  => 'reactivos',
        ]);
    }

    public function savereactivo()
    {
        $validation = \Config\Services::validation();
        $validation->setRules(config('Validation')->reactivo);
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors())->with('error', implode(' ', $validation->getErrors()));
        }
        $id = (int) ($this->request->getPost('reactivo_id') ?? 0);
        $this->model->saveReactivo([
            'nombre'                    => $this->request->getPost('nombre') ?? '',
            'unidad'                    => $this->request->getPost('unidad_base') ?: $this->request->getPost('unidad') ?? '',
            'unidad_base'               => $this->request->getPost('unidad_base') ?: $this->request->getPost('unidad') ?? '',
            'stock_minimo'               => (int) ($this->request->getPost('stock_minimo') ?? 0),
            'grupo'                     => $this->request->getPost('grupo') ?: null,
            'subgrupo'                  => $this->request->getPost('subgrupo') ?: null,
            'contenido_por_presentacion'=> (int) ($this->request->getPost('contenido_por_presentacion') ?? 1) ?: 1,
            'tipo'                      => (int) ($this->request->getPost('tipo') ?? ReactivoModel::TIPO_REACTIVO),
        ], $id > 0 ? $id : null);
        \App\Models\AuditoriaModel::log('reactivos', $id > 0 ? 'actualizar' : 'crear', $id > 0 ? (string) $id : null);
        return redirect()->to('inventario')->with('success', 'Insumo guardado');
    }

    public function savelote()
    {
        $validation = \Config\Services::validation();
        $validation->setRules(config('Validation')->lote);
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
        }
        $reactivoId = (int) ($this->request->getPost('reactivo_id') ?? 0);
        $codigo = trim($this->request->getPost('codigo_lote') ?? '');
        $cantidad = (int) ($this->request->getPost('cantidad') ?? 0);
        $vencimiento = \App\Models\ReactivoModel::normalizarFecha($this->request->getPost('fecha_vencimiento'));
        $fechaIngreso = \App\Models\ReactivoModel::normalizarFecha($this->request->getPost('fecha_ingreso')) ?: date('Y-m-d');
        $personId = session()->get('person_id') ? (int) session()->get('person_id') : null;
        if ($codigo && $cantidad > 0) {
            try {
                if ($this->model->registrarEntrada($reactivoId, $codigo, $cantidad, $vencimiento, $personId, $fechaIngreso)) {
                    \App\Models\AuditoriaModel::log('reactivos', 'lote_entrada', (string) $reactivoId, $codigo);
                    return redirect()->to("reactivos/lotes/{$reactivoId}")->with('success', 'Lote agregado y movimiento registrado');
                }
            } catch (\Throwable $e) {
                // Si no existe tabla movimientos, usar solo saveLote
            }
        }
        $this->model->saveLote([
            'reactivo_id'       => $reactivoId,
            'codigo_lote'       => $codigo,
            'cantidad'          => $cantidad,
            'fecha_vencimiento' => $vencimiento,
            'fecha_ingreso'     => $fechaIngreso,
        ]);
        \App\Models\AuditoriaModel::log('reactivos', 'lote_crear', (string) $reactivoId, $codigo);
        return redirect()->to("reactivos/lotes/{$reactivoId}")->with('success', 'Lote guardado');
    }

    public function registrarsalida()
    {
        $validation = \Config\Services::validation();
        $validation->setRules(config('Validation')->salida);
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
        }
        $reactivoId = (int) ($this->request->getPost('reactivo_id') ?? 0);
        $cantidad = (int) ($this->request->getPost('cantidad') ?? 0);
        $obs = $this->request->getPost('observaciones') ?: null;
        $registroId = $this->request->getPost('registro_id') ? (int) $this->request->getPost('registro_id') : null;
        $personId = session()->get('person_id') ? (int) session()->get('person_id') : null;
        if ($registroId !== null && $registroId > 0 && model(RegisterModel::class)->isRegistroAnulado($registroId)) {
            return redirect()->back()->with('error', 'No puede registrar consumo de inventario vinculado a una orden anulada.');
        }
        if ($cantidad <= 0) {
            return redirect()->back()->with('error', 'Cantidad debe ser mayor a 0');
        }
        if ($this->model->registrarSalida($reactivoId, $cantidad, $personId, $obs, $registroId)) {
            \App\Models\AuditoriaModel::log('reactivos', 'salida', (string) $reactivoId, "cant:{$cantidad}");
            return redirect()->back()->with('success', 'Consumo registrado');
        }
        return redirect()->back()->with('error', 'Stock insuficiente o error al registrar');
    }

    public function eliminar($reactivoId)
    {
        $reactivoId = (int) $reactivoId;
        if ($reactivoId > 0 && $this->model->deleteReactivo($reactivoId)) {
            \App\Models\AuditoriaModel::log('reactivos', 'eliminar', (string) $reactivoId);
            return redirect()->to('inventario')->with('success', 'Insumo eliminado');
        }
        return redirect()->to('inventario')->with('error', 'No se pudo eliminar');
    }
}
