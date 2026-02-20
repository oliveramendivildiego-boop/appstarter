<?php

namespace App\Controllers;

use App\Models\ReportModel;

class Reports extends SecureArea
{
    protected ?string $moduleId = 'reports';

    protected ReportModel $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = model(ReportModel::class);
    }

    public function index()
    {
        return view('reports/listing', [
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function registrosFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data     = $this->reportModel->getRegistrosByDateRange($startDate, $endDate);
        $totales  = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/registros_fecha', [
            'title'           => 'Reporte de registros por fecha',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'totales'         => $totales,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function ingresosFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data    = $this->reportModel->getIngresosByDateRange($startDate, $endDate);
        $totales = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/ingresos_fecha', [
            'title'           => 'Reporte de ingresos por fecha',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'totales'         => $totales,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function porDoctor()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data    = $this->reportModel->getRegistrosByDoctor($startDate, $endDate);
        $totales = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/por_doctor', [
            'title'           => 'Reporte de registros por doctor',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'totales'         => $totales,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function pagos()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $todos     = $this->reportModel->getReportePagos($startDate, $endDate);
        $pendientes = $this->reportModel->getPendientesPago($startDate, $endDate);
        $totales   = $this->reportModel->getTotalesPagos($startDate, $endDate);

        $tipoPagoMap = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];

        return view('reports/pagos', [
            'title'           => 'Reporte de pagos',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'todos'           => $todos,
            'pendientes'      => $pendientes,
            'totales'         => $totales,
            'tipoPagoMap'     => $tipoPagoMap,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function pruebasFecha()
    {
        $startDate = $this->request->getGet('start') ?? date('Y-m-d');
        $endDate   = $this->request->getGet('end') ?? date('Y-m-d');

        $data = $this->reportModel->getPruebasPorFecha($startDate, $endDate);

        return view('reports/pruebas_fecha', [
            'title'           => 'Reporte de pruebas realizadas por fecha',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }
}
