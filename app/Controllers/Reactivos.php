<?php

namespace App\Controllers;

use App\Models\ReactivoModel;

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
        $reactivo = $this->model->db->table('reactivo')->where('reactivo_id', $reactivoId)->get()->getRowArray();
        if (!$reactivo) {
            return redirect()->to('reactivos')->with('error', 'Insumo no encontrado');
        }
        return view('reactivos/form_editar', [
            'reactivo'        => $reactivo,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function index()
    {
        $grupo = $this->request->getGet('grupo') ?: null;
        $reactivos = $this->model->getAll($grupo);
        foreach ($reactivos as &$r) {
            $r['stock_actual'] = $this->model->getStockTotal($r['reactivo_id']);
            $r['tipo_nombre'] = $this->model->getNombreTipo((int) ($r['tipo'] ?? 1));
        }
        unset($r);
        $alertas = $this->model->getAlertas();
        $grupos = $this->model->getGrupos();
        return view('reactivos/index', [
            'reactivos'       => $reactivos,
            'alertas'         => $alertas,
            'grupos'          => $grupos,
            'grupo_filtro'    => $grupo,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function lotes($reactivoId)
    {
        $reactivoId = (int) $reactivoId;
        $reactivo = $this->model->db->table('reactivo')->where('reactivo_id', $reactivoId)->get()->getRowArray();
        if (!$reactivo) {
            return redirect()->to('reactivos')->with('error', 'Insumo no encontrado');
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
        ]);
    }

    public function savereactivo()
    {
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
        return redirect()->to('reactivos')->with('success', 'Insumo guardado');
    }

    public function savelote()
    {
        $reactivoId = (int) ($this->request->getPost('reactivo_id') ?? 0);
        $codigo = trim($this->request->getPost('codigo_lote') ?? '');
        $cantidad = (int) ($this->request->getPost('cantidad') ?? 0);
        $vencimiento = $this->request->getPost('fecha_vencimiento') ?: null;
        $personId = session()->get('person_id') ? (int) session()->get('person_id') : null;
        if ($codigo && $cantidad > 0) {
            try {
                if ($this->model->registrarEntrada($reactivoId, $codigo, $cantidad, $vencimiento, $personId)) {
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
            'fecha_ingreso'     => $this->request->getPost('fecha_ingreso') ?: date('Y-m-d'),
        ]);
        return redirect()->to("reactivos/lotes/{$reactivoId}")->with('success', 'Lote guardado');
    }

    public function registrarsalida()
    {
        $reactivoId = (int) ($this->request->getPost('reactivo_id') ?? 0);
        $cantidad = (int) ($this->request->getPost('cantidad') ?? 0);
        $obs = $this->request->getPost('observaciones') ?: null;
        $registroId = $this->request->getPost('registro_id') ? (int) $this->request->getPost('registro_id') : null;
        $personId = session()->get('person_id') ? (int) session()->get('person_id') : null;
        if ($cantidad <= 0) {
            return redirect()->back()->with('error', 'Cantidad debe ser mayor a 0');
        }
        if ($this->model->registrarSalida($reactivoId, $cantidad, $personId, $obs, $registroId)) {
            return redirect()->back()->with('success', 'Consumo registrado');
        }
        return redirect()->back()->with('error', 'Stock insuficiente o error al registrar');
    }

    public function eliminar($reactivoId)
    {
        $reactivoId = (int) $reactivoId;
        if ($reactivoId > 0 && $this->model->deleteReactivo($reactivoId)) {
            return redirect()->to('reactivos')->with('success', 'Insumo eliminado');
        }
        return redirect()->to('reactivos')->with('error', 'No se pudo eliminar');
    }
}
