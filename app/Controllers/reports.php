<?php

namespace App\Controllers;

use App\Models\ReportModel;
use App\Models\ReactivoModel;
use App\Models\AppConfigModel;
use App\Models\ToquoteModel;

class Reports extends SecureArea
{
    protected ?string $moduleId = 'reports';

    protected ReportModel $reportModel;
    protected ToquoteModel $toquoteModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel   = model(ReportModel::class);
        $this->toquoteModel  = model(ToquoteModel::class);
    }

    public function index()
    {
        return view('reports/listing', [
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'reports',
        ]);
    }

    /**
     * Reporte: todas las cotizaciones guardadas (con detalle y re-imprimir PDF)
     */
    public function cotizacionesGuardadas()
    {
        $perPage    = 25;
        $page       = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset     = ($page - 1) * $perPage;

        $data       = $this->toquoteModel->getAllCotizaciones($perPage, $offset);
        $total      = $this->toquoteModel->countAllCotizaciones();
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('reports/cotizaciones_guardadas', [
            'title'           => 'Todas las cotizaciones guardadas',
            'current_module'  => 'reports',
            'subtitle'        => 'Listado con detalle y opción de re-imprimir PDF',
            'data'            => $data,
            'page'            => $page,
            'totalPages'      => $totalPages,
            'total'           => $total,
            'perPage'         => $perPage,
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
            'current_module'  => 'reports',
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
            'current_module'  => 'reports',
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
            'current_module'  => 'reports',
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
            'current_module'  => 'reports',
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
            'current_module'  => 'reports',
            'subtitle'        => date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)),
            'data'            => $data,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * Reporte de insumos por fecha de ingreso y vencimiento.
     * Marca en amarillo los que están por vencer (según config días_alerta_vencimiento)
     * y en rojo los ya vencidos.
     */
    public function insumosVencimiento()
    {
        $reactivoModel = model(ReactivoModel::class);
        $appConfig     = model(AppConfigModel::class);
        $diasAlerta    = max(1, (int) ($appConfig->getValue('dias_alerta_vencimiento') ?: 40));

        $lotes = $reactivoModel->getTodosLotesParaReporte();
        $hoy   = date('Y-m-d');
        $enX   = date('Y-m-d', strtotime("+{$diasAlerta} days"));

        foreach ($lotes as &$l) {
            $venc = $l['fecha_vencimiento'] ?? null;
            if (!$venc) {
                $l['estado']       = 'sin_fecha';
                $l['dias_restantes'] = null;
                $l['estado_label'] = '-';
            } else {
                $diff = (strtotime($venc) - strtotime($hoy)) / 86400;
                $l['dias_restantes'] = (int) round($diff);
                if ($diff < 0) {
                    $l['estado'] = 'vencido';
                    $l['estado_label'] = 'Vencido';
                } elseif ($venc <= $enX) {
                    $l['estado'] = 'por_vencer';
                    $l['estado_label'] = 'Por vencer (' . $l['dias_restantes'] . ' días)';
                } else {
                    $l['estado'] = 'ok';
                    $l['estado_label'] = $l['dias_restantes'] . ' días';
                }
            }
        }
        unset($l);

        return view('reports/insumos_vencimiento', [
            'title'           => 'Reporte de insumos por ingreso y vencimiento',
            'current_module'  => 'reports',
            'subtitle'        => 'Alerta configurada: ' . $diasAlerta . ' días antes del vencimiento',
            'data'            => $lotes,
            'dias_alerta'     => $diasAlerta,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }
}
