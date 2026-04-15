<?php

namespace App\Controllers;

use App\Models\EgresoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Egresos extends SecureArea
{
    protected ?string $moduleId = 'egresos';
    protected EgresoModel $egresoModel;

    private const TIPO_PAGO = [
        '1' => 'Efectivo',
        '2' => 'QR',
        '3' => 'Transferencia',
        '4' => 'Pendiente',
    ];
    private const TIPO_MOVIMIENTO = [
        'egreso' => 'Egreso',
        'ingreso' => 'Ingreso',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->egresoModel = model(EgresoModel::class);
    }

    public function index()
    {
        $perPage = 30;
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) ($this->request->getGet('q') ?? ''));
        $startDate = trim((string) ($this->request->getGet('start') ?? ''));
        $endDate = trim((string) ($this->request->getGet('end') ?? ''));

        if ($startDate !== '' && strtotime($startDate) === false) {
            $startDate = '';
        }
        if ($endDate !== '' && strtotime($endDate) === false) {
            $endDate = '';
        }

        $total = $this->egresoModel->countFiltered($search, $startDate ?: null, $endDate ?: null);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $editar = (int) ($this->request->getGet('editar') ?? 0);
        $egresoEditar = $editar > 0 ? $this->egresoModel->getById($editar) : null;

        return view('egresos/manage', [
            'current_module' => 'egresos',
            'egresos'        => $this->egresoModel->getFiltered($search, $startDate ?: null, $endDate ?: null, $perPage, $offset),
            'egreso_editar'  => $egresoEditar,
            'tipos_pago'     => self::TIPO_PAGO,
            'tipos_movimiento' => self::TIPO_MOVIMIENTO,
            'q'              => $search,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'page'           => $page,
            'perPage'        => $perPage,
            'total'          => $total,
            'totalPages'     => $totalPages,
            'allowed_modules'=> $this->allowed_modules,
            'user_info'      => $this->user_info,
        ]);
    }

    public function save(): ResponseInterface
    {
        $id = (int) ($this->request->getPost('egreso_id') ?? 0);
        $monto = (float) ($this->request->getPost('monto') ?? 0);
        $tipopago = trim((string) ($this->request->getPost('tipopago') ?? ''));
        $tipoMovimiento = trim((string) ($this->request->getPost('tipo_movimiento') ?? 'egreso'));
        $desglose = trim((string) ($this->request->getPost('desglose') ?? ''));
        $fecha = trim((string) ($this->request->getPost('fecha') ?? ''));

        if ($monto <= 0) {
            return redirect()->back()->withInput()->with('error', 'El monto debe ser mayor a 0.');
        }
        if (!array_key_exists($tipopago, self::TIPO_PAGO)) {
            return redirect()->back()->withInput()->with('error', 'Seleccione un tipo de pago válido.');
        }
        if (!array_key_exists($tipoMovimiento, self::TIPO_MOVIMIENTO)) {
            return redirect()->back()->withInput()->with('error', 'Seleccione un tipo de movimiento válido.');
        }
        if ($desglose === '') {
            return redirect()->back()->withInput()->with('error', 'El desglose es obligatorio.');
        }
        if ($fecha !== '' && strtotime($fecha) === false) {
            return redirect()->back()->withInput()->with('error', 'La fecha no es válida.');
        }

        $ok = $this->egresoModel->saveEgreso([
            'monto' => $monto,
            'tipopago' => $tipopago,
            'tipo_movimiento' => $tipoMovimiento,
            'desglose' => $desglose,
            'fecha' => $fecha,
        ], $id > 0 ? $id : null);

        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar el egreso.');
        }

        \App\Models\AuditoriaModel::log('egresos', $id > 0 ? 'actualizar' : 'crear', $id > 0 ? (string) $id : null, \App\Models\AuditoriaModel::detail([
            'monto' => $monto,
            'tipo_movimiento' => self::TIPO_MOVIMIENTO[$tipoMovimiento] ?? $tipoMovimiento,
            'tipopago' => self::TIPO_PAGO[$tipopago] ?? $tipopago,
        ]));

        return redirect()->to('egresos')->with('success', 'Movimiento guardado correctamente.');
    }

    public function delete($id): ResponseInterface
    {
        $id = (int) $id;
        if ($id < 1) {
            return redirect()->to('egresos')->with('error', 'ID inválido.');
        }
        if (!$this->egresoModel->softDelete($id)) {
            return redirect()->to('egresos')->with('error', 'No se pudo eliminar el egreso.');
        }
        \App\Models\AuditoriaModel::log('egresos', 'eliminar', (string) $id);

        return redirect()->to('egresos')->with('success', 'Egreso eliminado.');
    }
}

