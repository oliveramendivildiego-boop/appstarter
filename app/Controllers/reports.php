<?php

namespace App\Controllers;

use App\Models\ReportModel;
use App\Models\ReactivoModel;
use App\Models\AppConfigModel;
use App\Models\ToquoteModel;
use App\Models\EmployeeModel;
use App\Models\PoblacionModel;
use App\Models\ReportePagosCierreModel;
use App\Models\LabotestModel;
use App\Services\RegisterService;
use App\Services\TenantScopedDatabaseService;
use App\Libraries\PdfService;
use App\Libraries\ReportPdfDocument;
use CodeIgniter\HTTP\ResponseInterface;

class Reports extends SecureArea
{
    protected ?string $moduleId = 'reports';

    protected ReportModel $reportModel;
    protected ToquoteModel $toquoteModel;
    protected ReactivoModel $reactivoModel;
    protected ReportePagosCierreModel $pagosCierreModel;
    protected LabotestModel $labotestModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel      = model(ReportModel::class);
        $this->toquoteModel     = model(ToquoteModel::class);
        $this->reactivoModel    = model(ReactivoModel::class);
        $this->pagosCierreModel = model(ReportePagosCierreModel::class);
        $this->labotestModel    = model(LabotestModel::class);
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
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $data     = $this->reportModel->getRegistrosByDateRange($startDate, $endDate);
        $totales  = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/registros_fecha', [
            'title'           => 'Reporte de registros por fecha',
            'current_module'  => 'reports',
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
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
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $facturables = $this->reportModel->getIngresosByDateRange($startDate, $endDate);
        $anulPorDia  = $this->reportModel->getIngresosAnuladosPorDia($startDate, $endDate);
        $totales     = $this->reportModel->getTotalesByDateRange($startDate, $endDate);
        $totalesAnul = $this->reportModel->getTotalesAnuladosByDateRange($startDate, $endDate);

        $byFecha = [];
        foreach ($facturables as $r) {
            $f = $r['fecha'];
            $byFecha[$f] = [
                'fecha'               => $f,
                'cantidad'            => (int) ($r['cantidad'] ?? 0),
                'total'               => (float) ($r['total'] ?? 0),
                'cobrado'             => (float) ($r['cobrado'] ?? 0),
                'cantidad_anuladas'   => 0,
                'total_anulado_ref'   => 0.0,
                'cobrado_anulado_ref' => 0.0,
            ];
        }
        foreach ($anulPorDia as $r) {
            $f = $r['fecha'];
            if (! isset($byFecha[$f])) {
                $byFecha[$f] = [
                    'fecha'               => $f,
                    'cantidad'            => 0,
                    'total'               => 0.0,
                    'cobrado'             => 0.0,
                    'cantidad_anuladas'   => 0,
                    'total_anulado_ref'   => 0.0,
                    'cobrado_anulado_ref' => 0.0,
                ];
            }
            $byFecha[$f]['cantidad_anuladas']   = (int) ($r['cantidad'] ?? 0);
            $byFecha[$f]['total_anulado_ref']   = (float) ($r['total'] ?? 0);
            $byFecha[$f]['cobrado_anulado_ref'] = (float) ($r['cobrado'] ?? 0);
        }
        ksort($byFecha);
        $data = array_values($byFecha);

        return view('reports/ingresos_fecha', [
            'title'           => 'Reporte de ingresos por fecha',
            'current_module'  => 'reports',
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'data'            => $data,
            'totales'         => $totales,
            'totalesAnulados' => $totalesAnul,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function porDoctor()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $data    = $this->reportModel->getRegistrosByDoctor($startDate, $endDate);
        $totales = $this->reportModel->getTotalesByDateRange($startDate, $endDate);

        return view('reports/por_doctor', [
            'title'           => 'Reporte de registros por doctor',
            'current_module'  => 'reports',
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
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
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $todos     = $this->reportModel->getReportePagos($startDate, $endDate);
        $pendientes = $this->reportModel->getCobrosParcialesEnPeriodo($startDate, $endDate);
        $pagosPagados = $this->reportModel->getPagosPagadosDetalle($startDate, $endDate);
        $tipoPagoMap = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];
        $totales   = $this->reportModel->getTotalesPagos($startDate, $endDate);
        $resumenPagosPorTipo = $this->reportModel->getResumenPagosPorTipo($startDate, $endDate);
        $resumenPagosPorDia = $this->reportModel->getResumenPagosPorDia($startDate, $endDate);
        $resumenPagosPorDoctor = $this->reportModel->getResumenPagosPorDoctor($startDate, $endDate);
        $egresos = $this->reportModel->getEgresosByDateRange($startDate, $endDate);
        $totalesEgresos = $this->reportModel->getTotalesEgresosByDateRange($startDate, $endDate);
        $resumenEgresosPorTipo = $this->reportModel->getResumenEgresosPorTipo($startDate, $endDate);
        $ingresosCajaMov = $this->reportModel->getIngresosCajaByDateRange($startDate, $endDate);
        $totalesIngresosCaja = $this->reportModel->getTotalesIngresosCajaByDateRange($startDate, $endDate);
        $resumenIngresosCajaPorTipo = $this->reportModel->getResumenIngresosCajaPorTipo($startDate, $endDate);

        $cajaCuadre = $this->buildCuadreCajaPagos(
            $startDate,
            $endDate,
            $resumenPagosPorTipo,
            $resumenIngresosCajaPorTipo,
            $resumenEgresosPorTipo,
            $totalesIngresosCaja,
            $totalesEgresos
        );

        return view('reports/pagos', [
            'title'           => 'Reporte de pagos',
            'current_module'  => 'reports',
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'todos'           => $todos,
            'pendientes'      => $pendientes,
            'pagosPagados'   => $pagosPagados,
            'totales'         => $totales,
            'tipoPagoMap'     => $tipoPagoMap,
            'resumenPagosPorTipo' => $resumenPagosPorTipo,
            'resumenPagosPorDia' => $resumenPagosPorDia,
            'resumenPagosPorDoctor' => $resumenPagosPorDoctor,
            'egresos'         => $egresos,
            'ingresosCajaMov' => $ingresosCajaMov,
            'totalesEgresos'  => $totalesEgresos,
            'totalesIngresosCaja' => $totalesIngresosCaja,
            'resumenEgresosPorTipo' => $resumenEgresosPorTipo,
            'resumenIngresosCajaPorTipo' => $resumenIngresosCajaPorTipo,
            'cajaResumen'     => $cajaCuadre['cajaResumen'],
            'cajaPorTipo'     => $cajaCuadre['cajaPorTipo'],
            'cajaPorTipoTotales' => $cajaCuadre['cajaPorTipoTotales'],
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * Detalle JSON de cobros del período filtrados por tipo de pago (modal en reporte de pagos).
     */
    public function pagosDetallePorTipo(): ResponseInterface
    {
        helper('layout');

        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $tipopago  = trim((string) $this->request->getGet('tipopago'));

        if ($tipopago === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tipo de pago no indicado.',
            ])->setStatusCode(400);
        }

        $tipoPagoMap = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];
        $rows        = $this->reportModel->getCobrosDetallePorFecha($startDate, $endDate, null, $tipopago);

        $items = [];
        $totalPendiente = 0.0;
        $ordenesVistas  = [];
        foreach ($rows as $row) {
            $saldo = (float) ($row['saldo'] ?? 0);
            $rid   = (string) ($row['registro_id'] ?? '');
            if ($rid !== '' && !isset($ordenesVistas[$rid])) {
                $ordenesVistas[$rid] = true;
                $totalPendiente += $saldo;
            }
            $items[] = [
                'orden'              => registro_orden_display($row),
                'paciente'           => trim((string) ($row['paciente'] ?? '')),
                'doctor'             => trim((string) ($row['doctor'] ?? '')),
                'monto_pendiente'    => round($saldo, 2),
                'monto_pendiente_fmt'=> format_currency($saldo),
                'fecha_cobro'        => RegisterService::formatStoredReporteFechaCorta($row['fecha_cobro'] ?? $row['ingreso'] ?? ''),
            ];
        }

        return $this->response->setJSON([
            'success'    => true,
            'tipo_label' => $tipoPagoMap[$tipopago] ?? $tipopago,
            'periodo'    => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'items'      => $items,
            'count'      => count($items),
            'total'      => round($totalPendiente, 2),
            'total_fmt'  => format_currency($totalPendiente),
        ]);
    }

    /**
     * Solo órdenes con saldo pendiente de pago en el rango de fechas.
     */
    public function pagosPendientes()
    {
        $startDate  = $this->request->getGet('start') ?? RegisterService::reportDateFromModifier('-2 years');
        $endDate    = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $pendientes = $this->reportModel->getPendientesPago($startDate, $endDate);
        $totalSaldo = 0.0;
        foreach ($pendientes as $row) {
            $totalSaldo += (float) ($row['saldo'] ?? 0);
        }

        return view('reports/pagos_pendientes', [
            'title'             => 'Pendientes de pago',
            'current_module'    => 'reports',
            'subtitle'          => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'pendientes'        => $pendientes,
            'total_saldo'       => $totalSaldo,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    /**
     * Listado de cierres de pagos guardados (cada fila tiene imprimir/PDF del documento de cierre).
     */
    public function pagosCierres()
    {
        $rows = [];
        try {
            $rows = $this->pagosCierreModel->getListado(500);
        } catch (\Throwable $e) {
        }

        return view('reports/pagos_cierres', [
            'title'             => 'Cierres de pagos',
            'current_module'    => 'reports',
            'cierres'           => $rows,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    /**
     * Guarda un cierre con snapshot del período (POST start, end).
     */
    public function pagosCierreCrear()
    {
        $validation = \Config\Services::validation();
        $validation->setRules(config('Validation')->pagos_cierre_crear);
        $backQs     = static function (string $s, string $e): string {
            return 'reports/pagos?' . http_build_query(['start' => $s, 'end' => $e]);
        };
        if (!$validation->withRequest($this->request)->run()) {
            $s = (string) ($this->request->getPost('start') ?? RegisterService::todayForReport());
            $e = (string) ($this->request->getPost('end') ?? RegisterService::todayForReport());

            return redirect()->to($backQs($s, $e))->with('error', implode(' ', $validation->getErrors()));
        }
        $startDate = (string) $this->request->getPost('start');
        $endDate   = (string) $this->request->getPost('end');
        if (strtotime($startDate) > strtotime($endDate)) {
            return redirect()->to($backQs($startDate, $endDate))
                ->with('error', 'La fecha inicial no puede ser posterior a la final.');
        }

        try {
            $snapshot = $this->buildPagosCierreSnapshotPayload($startDate, $endDate);
        } catch (\Throwable $e) {
            return redirect()->to($backQs($startDate, $endDate))
                ->with('error', 'No se pudo generar el cierre.');
        }

        $personId = session()->get('person_id') ? (int) session()->get('person_id') : null;
        $nombre   = '';
        if ($this->user_info !== null) {
            $nombre = trim(
                ($this->user_info->first_name ?? '') . ' ' . ($this->user_info->last_name_fa ?? '')
            );
        }

        try {
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return redirect()->to($backQs($startDate, $endDate))
                ->with('error', 'No se pudo serializar el cierre.');
        }

        try {
            $this->pagosCierreModel->insert([
                'fecha_desde'       => $startDate,
                'fecha_hasta'       => $endDate,
                'snapshot_json'     => $json,
                'person_id'         => $personId,
                'elaborado_nombre'  => $nombre !== '' ? $nombre : null,
                'created_at'        => RegisterService::mysqlNowForReport(),
            ]);
        } catch (\Throwable $e) {
            return redirect()->to($backQs($startDate, $endDate))
                ->with('error', 'No se pudo guardar el cierre. Ejecute la migración de base de datos (reporte_pagos_cierre).');
        }
        $newId = (int) $this->pagosCierreModel->getInsertID();

        return redirect()->to('reports/pagosCierres')->with('success', 'Cierre #' . $newId . ' registrado. Desde el listado puede imprimir o exportar PDF.');
    }

    /**
     * Documento de un cierre guardado (imprimir).
     */
    public function pagosCierreVer($cierreId)
    {
        $cierreId = (int) $cierreId;
        $row      = $this->pagosCierreModel->findCierre($cierreId);
        if ($row === null) {
            return redirect()->to('reports/pagosCierres')->with('error', 'Cierre no encontrado.');
        }
        try {
            $snap = json_decode($row['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return redirect()->to('reports/pagosCierres')->with('error', 'Datos del cierre corruptos.');
        }
        if (!is_array($snap)) {
            return redirect()->to('reports/pagosCierres')->with('error', 'Datos del cierre inválidos.');
        }

        $data               = $this->dataFromSnapshot($snap, $row['fecha_desde'], $row['fecha_hasta'], $cierreId);
        $data['show_toolbar'] = true;

        return view('reports/pagos_cierre_document', $data);
    }

    /**
     * PDF de un cierre guardado.
     */
    public function pagosCierrePdf($cierreId)
    {
        $cierreId = (int) $cierreId;
        $row      = $this->pagosCierreModel->findCierre($cierreId);
        if ($row === null) {
            return redirect()->to('reports/pagosCierres')->with('error', 'Cierre no encontrado.');
        }
        try {
            $snap = json_decode($row['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return redirect()->to('reports/pagosCierres')->with('error', 'Datos del cierre corruptos.');
        }
        if (!is_array($snap)) {
            return redirect()->to('reports/pagosCierres')->with('error', 'Datos del cierre inválidos.');
        }

        $data               = $this->dataFromSnapshot($snap, $row['fecha_desde'], $row['fecha_hasta'], $cierreId);
        $data['show_toolbar'] = false;

        $html     = view('reports/pagos_cierre_document', $data);
        $filename = 'cierre_pagos_' . $cierreId . '.pdf';

        (new PdfService())->download($html, $filename);
    }

    /**
     * Cuadre de caja: cobros por fecha real del abono, no por fecha de ingreso de la orden.
     *
     * @param array<int, array<string, mixed>> $resumenPagosPorTipo
     * @param array<int, array<string, mixed>> $resumenIngresosCajaPorTipo
     * @param array<int, array<string, mixed>> $resumenEgresosPorTipo
     *
     * @return array{cajaResumen: array<string, mixed>, cajaPorTipo: list<array<string, mixed>>, cajaPorTipoTotales: array<string, float>}
     */
    private function buildCuadreCajaPagos(
        string $startDate,
        string $endDate,
        array $resumenPagosPorTipo,
        array $resumenIngresosCajaPorTipo,
        array $resumenEgresosPorTipo,
        object $totalesIngresosCaja,
        object $totalesEgresos
    ): array {
        $totalesCobrosCaja = $this->reportModel->getTotalesCobrosCaja($startDate, $endDate);
        $ingresosVentas = (float) ($totalesCobrosCaja->total_cobrado ?? 0);
        $ingresosMovimientos = (float) ($totalesIngresosCaja->total_ingresos ?? 0);
        $ingresosCaja = $ingresosVentas + $ingresosMovimientos;
        $egresosCaja = (float) ($totalesEgresos->total_egresos ?? 0);
        $saldoNetoCaja = round($ingresosCaja - $egresosCaja, 2);
        $cajaResumen = [
            'ingresos_ventas' => $ingresosVentas,
            'ingresos_movimientos' => $ingresosMovimientos,
            'ingresos' => $ingresosCaja,
            'egresos' => $egresosCaja,
            'saldo_neto' => $saldoNetoCaja,
            'estado' => $saldoNetoCaja >= 0 ? 'positivo' : 'negativo',
        ];
        $cajaPorTipo = [];
        foreach ($resumenPagosPorTipo as $row) {
            $tipo = (string) ($row['tipopago'] ?? '');
            if ($tipo === '' || $tipo === '4') {
                continue;
            }
            $cajaPorTipo[$tipo] = [
                'tipopago' => $tipo,
                'ingresos' => (float) ($row['total_cobrado'] ?? 0),
                'egresos' => 0.0,
                'saldo_neto' => 0.0,
            ];
        }
        foreach ($resumenIngresosCajaPorTipo as $row) {
            $tipo = (string) ($row['tipopago'] ?? '');
            if ($tipo === '') {
                continue;
            }
            if (!isset($cajaPorTipo[$tipo])) {
                $cajaPorTipo[$tipo] = [
                    'tipopago' => $tipo,
                    'ingresos' => 0.0,
                    'egresos' => 0.0,
                    'saldo_neto' => 0.0,
                ];
            }
            $cajaPorTipo[$tipo]['ingresos'] += (float) ($row['total_ingresos'] ?? 0);
        }
        foreach ($resumenEgresosPorTipo as $row) {
            $tipo = (string) ($row['tipopago'] ?? '');
            if ($tipo === '') {
                continue;
            }
            if (!isset($cajaPorTipo[$tipo])) {
                $cajaPorTipo[$tipo] = [
                    'tipopago' => $tipo,
                    'ingresos' => 0.0,
                    'egresos' => 0.0,
                    'saldo_neto' => 0.0,
                ];
            }
            $cajaPorTipo[$tipo]['egresos'] = (float) ($row['total_egresos'] ?? 0);
        }
        $cajaPorTipoTotales = [
            'ingresos' => 0.0,
            'egresos' => 0.0,
            'saldo_neto' => 0.0,
        ];
        foreach ($cajaPorTipo as &$row) {
            $row['saldo_neto'] = round(((float) $row['ingresos']) - ((float) $row['egresos']), 2);
            $cajaPorTipoTotales['ingresos'] += (float) $row['ingresos'];
            $cajaPorTipoTotales['egresos'] += (float) $row['egresos'];
            $cajaPorTipoTotales['saldo_neto'] += (float) $row['saldo_neto'];
        }
        unset($row);
        ksort($cajaPorTipo, SORT_NATURAL);
        $cajaPorTipo = array_values($cajaPorTipo);
        $cajaPorTipoTotales['ingresos'] = round($cajaPorTipoTotales['ingresos'], 2);
        $cajaPorTipoTotales['egresos'] = round($cajaPorTipoTotales['egresos'], 2);
        $cajaPorTipoTotales['saldo_neto'] = round($cajaPorTipoTotales['saldo_neto'], 2);

        return [
            'cajaResumen' => $cajaResumen,
            'cajaPorTipo' => $cajaPorTipo,
            'cajaPorTipoTotales' => $cajaPorTipoTotales,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPagosCierreData(string $startDate, string $endDate): array
    {
        helper('layout');
        $layoutConfig = layout_config();

        $pendientes            = $this->reportModel->getCobrosParcialesEnPeriodo($startDate, $endDate);
        $pagosPagados          = $this->reportModel->getPagosPagadosDetalle($startDate, $endDate);
        $totales               = $this->reportModel->getTotalesPagos($startDate, $endDate);
        $tipoPagoMap           = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];
        $resumenPagosPorTipo   = $this->reportModel->getResumenPagosPorTipo($startDate, $endDate);
        $resumenPagosPorDia    = $this->reportModel->getResumenPagosPorDia($startDate, $endDate);
        $resumenPagosPorDoctor = $this->reportModel->getResumenPagosPorDoctor($startDate, $endDate);
        $egresos               = $this->reportModel->getEgresosByDateRange($startDate, $endDate);
        $totalesEgresos        = $this->reportModel->getTotalesEgresosByDateRange($startDate, $endDate);
        $resumenEgresosPorTipo = $this->reportModel->getResumenEgresosPorTipo($startDate, $endDate);
        $ingresosCajaMov       = $this->reportModel->getIngresosCajaByDateRange($startDate, $endDate);
        $totalesIngresosCaja   = $this->reportModel->getTotalesIngresosCajaByDateRange($startDate, $endDate);
        $resumenIngresosCajaPorTipo = $this->reportModel->getResumenIngresosCajaPorTipo($startDate, $endDate);

        $cajaCuadre = $this->buildCuadreCajaPagos(
            $startDate,
            $endDate,
            $resumenPagosPorTipo,
            $resumenIngresosCajaPorTipo,
            $resumenEgresosPorTipo,
            $totalesIngresosCaja,
            $totalesEgresos
        );
        $cajaResumen = $cajaCuadre['cajaResumen'];
        $cajaPorTipo = $cajaCuadre['cajaPorTipo'];
        $cajaPorTipoTotales = $cajaCuadre['cajaPorTipoTotales'];

        $elaboradoPor = '';
        if ($this->user_info !== null) {
            $elaboradoPor = trim(
                ($this->user_info->first_name ?? '') . ' ' . ($this->user_info->last_name_fa ?? '')
            );
        }

        return [
            'company_name'            => $layoutConfig['company'] ?? 'Laboratorio',
            'startDate'               => $startDate,
            'endDate'                 => $endDate,
            'periodo_texto'           => RegisterService::formatReportDate($startDate) . ' — ' . RegisterService::formatReportDate($endDate),
            'generado_en'             => RegisterService::formatNowForReportShort(),
            'elaborado_por'           => $elaboradoPor,
            'totales'                 => $totales,
            'totalesIngresosCaja'     => $totalesIngresosCaja,
            'totalesEgresos'          => $totalesEgresos,
            'tipoPagoMap'             => $tipoPagoMap,
            'resumenPagosPorTipo'     => $resumenPagosPorTipo,
            'resumenIngresosCajaPorTipo' => $resumenIngresosCajaPorTipo,
            'resumenEgresosPorTipo'   => $resumenEgresosPorTipo,
            'resumenPagosPorDia'      => $resumenPagosPorDia,
            'resumenPagosPorDoctor'   => $resumenPagosPorDoctor,
            'ingresosCajaMov'         => $ingresosCajaMov,
            'egresos'                 => $egresos,
            'cajaResumen'             => $cajaResumen,
            'cajaPorTipo'             => $cajaPorTipo,
            'cajaPorTipoTotales'      => $cajaPorTipoTotales,
            'count_pagados'           => count($pagosPagados),
            'count_pendientes'        => count($pendientes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPagosCierreSnapshotPayload(string $startDate, string $endDate): array
    {
        $live = $this->buildPagosCierreData($startDate, $endDate);
        $t    = $live['totales'];
        $ti   = $live['totalesIngresosCaja'];
        $te   = $live['totalesEgresos'];

        return [
            'company_name'          => $live['company_name'],
            'periodo_texto'         => $live['periodo_texto'],
            'generado_en'           => $live['generado_en'],
            'elaborado_por'         => $live['elaborado_por'],
            'totales'               => [
                'total_facturado'  => (float) ($t->total_facturado ?? 0),
                'total_cobrado'    => (float) ($t->total_cobrado ?? 0),
                'total_pendiente'  => (float) ($t->total_pendiente ?? 0),
                'total_registros'  => (int) ($t->total_registros ?? 0),
            ],
            'totalesIngresosCaja'   => [
                'cantidad_ingresos' => (int) ($ti->cantidad_ingresos ?? 0),
                'total_ingresos'    => (float) ($ti->total_ingresos ?? 0),
            ],
            'totalesEgresos'        => [
                'cantidad_egresos' => (int) ($te->cantidad_egresos ?? 0),
                'total_egresos'    => (float) ($te->total_egresos ?? 0),
            ],
            'resumenPagosPorTipo'   => $live['resumenPagosPorTipo'],
            'resumenIngresosCajaPorTipo' => $live['resumenIngresosCajaPorTipo'],
            'resumenEgresosPorTipo' => $live['resumenEgresosPorTipo'],
            'resumenPagosPorDia'    => $live['resumenPagosPorDia'],
            'resumenPagosPorDoctor' => $live['resumenPagosPorDoctor'],
            'ingresosCajaMov'       => $live['ingresosCajaMov'],
            'egresos'               => $live['egresos'],
            'cajaResumen'           => $live['cajaResumen'],
            'cajaPorTipo'           => $live['cajaPorTipo'],
            'cajaPorTipoTotales'    => $live['cajaPorTipoTotales'],
            'count_pagados'         => $live['count_pagados'],
            'count_pendientes'      => $live['count_pendientes'],
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    private function dataFromSnapshot(array $snapshot, string $startDate, string $endDate, ?int $cierreId = null): array
    {
        $tot = $snapshot['totales'] ?? [];
        $resumenPagosPorTipo = is_array($snapshot['resumenPagosPorTipo'] ?? null) ? $snapshot['resumenPagosPorTipo'] : [];

        $hasMovimientosSnapshotData = array_key_exists('totalesEgresos', $snapshot)
            || array_key_exists('totalesIngresosCaja', $snapshot)
            || array_key_exists('resumenEgresosPorTipo', $snapshot)
            || array_key_exists('resumenIngresosCajaPorTipo', $snapshot)
            || array_key_exists('ingresosCajaMov', $snapshot)
            || array_key_exists('egresos', $snapshot)
            || array_key_exists('cajaResumen', $snapshot)
            || array_key_exists('cajaPorTipo', $snapshot);

        $totIngresosCaja = is_array($snapshot['totalesIngresosCaja'] ?? null) ? $snapshot['totalesIngresosCaja'] : [];
        $totEgresos = is_array($snapshot['totalesEgresos'] ?? null) ? $snapshot['totalesEgresos'] : [];
        $resumenIngresosCajaPorTipo = is_array($snapshot['resumenIngresosCajaPorTipo'] ?? null) ? $snapshot['resumenIngresosCajaPorTipo'] : [];
        $resumenEgresosPorTipo = is_array($snapshot['resumenEgresosPorTipo'] ?? null) ? $snapshot['resumenEgresosPorTipo'] : [];
        $ingresosCajaMov = is_array($snapshot['ingresosCajaMov'] ?? null) ? $snapshot['ingresosCajaMov'] : [];
        $egresos = is_array($snapshot['egresos'] ?? null) ? $snapshot['egresos'] : [];

        $missingIngresosSnapshotData = !array_key_exists('totalesIngresosCaja', $snapshot)
            || !array_key_exists('resumenIngresosCajaPorTipo', $snapshot)
            || !array_key_exists('ingresosCajaMov', $snapshot);

        // Compatibilidad con cierres antiguos: si el snapshot no tenía movimientos de caja, recargar en vivo por período.
        if (!$hasMovimientosSnapshotData) {
            $liveTotIngresos = $this->reportModel->getTotalesIngresosCajaByDateRange($startDate, $endDate);
            $totIngresosCaja = [
                'cantidad_ingresos' => (int) ($liveTotIngresos->cantidad_ingresos ?? 0),
                'total_ingresos' => (float) ($liveTotIngresos->total_ingresos ?? 0),
            ];
            $liveTotEgresos = $this->reportModel->getTotalesEgresosByDateRange($startDate, $endDate);
            $totEgresos = [
                'cantidad_egresos' => (int) ($liveTotEgresos->cantidad_egresos ?? 0),
                'total_egresos' => (float) ($liveTotEgresos->total_egresos ?? 0),
            ];
            $resumenIngresosCajaPorTipo = $this->reportModel->getResumenIngresosCajaPorTipo($startDate, $endDate);
            $resumenEgresosPorTipo = $this->reportModel->getResumenEgresosPorTipo($startDate, $endDate);
            $ingresosCajaMov = $this->reportModel->getIngresosCajaByDateRange($startDate, $endDate);
            $egresos = $this->reportModel->getEgresosByDateRange($startDate, $endDate);
        }
        // Compatibilidad con cierres intermedios: tenían egresos pero no ingresos de caja.
        if ($missingIngresosSnapshotData) {
            $liveTotIngresos = $this->reportModel->getTotalesIngresosCajaByDateRange($startDate, $endDate);
            $totIngresosCaja = [
                'cantidad_ingresos' => (int) ($liveTotIngresos->cantidad_ingresos ?? 0),
                'total_ingresos' => (float) ($liveTotIngresos->total_ingresos ?? 0),
            ];
            $resumenIngresosCajaPorTipo = $this->reportModel->getResumenIngresosCajaPorTipo($startDate, $endDate);
            $ingresosCajaMov = $this->reportModel->getIngresosCajaByDateRange($startDate, $endDate);
        }

        $ingresosFallback = (float) ($tot['total_cobrado'] ?? 0) + (float) ($totIngresosCaja['total_ingresos'] ?? 0);
        $egresosFallback = (float) ($totEgresos['total_egresos'] ?? 0);

        $cajaResumenRaw = is_array($snapshot['cajaResumen'] ?? null) ? $snapshot['cajaResumen'] : [];
        $ingresosVentasCaja = (float) ($cajaResumenRaw['ingresos_ventas'] ?? ($tot['total_cobrado'] ?? 0));
        $ingresosMovimientosCaja = (float) ($cajaResumenRaw['ingresos_movimientos'] ?? ($totIngresosCaja['total_ingresos'] ?? 0));
        $cajaResumen = [
            'ingresos_ventas' => $ingresosVentasCaja,
            'ingresos_movimientos' => $ingresosMovimientosCaja,
            'ingresos' => (float) ($cajaResumenRaw['ingresos'] ?? ($ingresosVentasCaja + $ingresosMovimientosCaja)),
            'egresos' => (float) ($cajaResumenRaw['egresos'] ?? $egresosFallback),
            'saldo_neto' => 0.0,
            'estado' => 'positivo',
        ];
        $cajaResumen['saldo_neto'] = round($cajaResumen['ingresos'] - $cajaResumen['egresos'], 2);
        $cajaResumen['estado'] = $cajaResumen['saldo_neto'] >= 0 ? 'positivo' : 'negativo';

        $cajaPorTipo = is_array($snapshot['cajaPorTipo'] ?? null) ? $snapshot['cajaPorTipo'] : [];
        $cajaPorTipoTotales = is_array($snapshot['cajaPorTipoTotales'] ?? null) ? $snapshot['cajaPorTipoTotales'] : [];

        // Compatibilidad con cierres antiguos: reconstruye cuadro por tipo si no existe en snapshot.
        if ($cajaPorTipo === []) {
            $build = [];
            foreach ($resumenPagosPorTipo as $row) {
                $tipo = (string) ($row['tipopago'] ?? '');
                if ($tipo === '') {
                    continue;
                }
                $build[$tipo] = [
                    'tipopago' => $tipo,
                    'ingresos' => (float) ($row['total_cobrado'] ?? 0),
                    'egresos' => 0.0,
                    'saldo_neto' => 0.0,
                ];
            }
            foreach ($resumenIngresosCajaPorTipo as $row) {
                $tipo = (string) ($row['tipopago'] ?? '');
                if ($tipo === '') {
                    continue;
                }
                if (!isset($build[$tipo])) {
                    $build[$tipo] = [
                        'tipopago' => $tipo,
                        'ingresos' => 0.0,
                        'egresos' => 0.0,
                        'saldo_neto' => 0.0,
                    ];
                }
                $build[$tipo]['ingresos'] += (float) ($row['total_ingresos'] ?? 0);
            }
            foreach ($resumenEgresosPorTipo as $row) {
                $tipo = (string) ($row['tipopago'] ?? '');
                if ($tipo === '') {
                    continue;
                }
                if (!isset($build[$tipo])) {
                    $build[$tipo] = [
                        'tipopago' => $tipo,
                        'ingresos' => 0.0,
                        'egresos' => 0.0,
                        'saldo_neto' => 0.0,
                    ];
                }
                $build[$tipo]['egresos'] = (float) ($row['total_egresos'] ?? 0);
            }
            $totBuild = ['ingresos' => 0.0, 'egresos' => 0.0, 'saldo_neto' => 0.0];
            foreach ($build as &$row) {
                $row['saldo_neto'] = round(((float) $row['ingresos']) - ((float) $row['egresos']), 2);
                $totBuild['ingresos'] += (float) $row['ingresos'];
                $totBuild['egresos'] += (float) $row['egresos'];
                $totBuild['saldo_neto'] += (float) $row['saldo_neto'];
            }
            unset($row);
            ksort($build, SORT_NATURAL);
            $cajaPorTipo = array_values($build);
            $cajaPorTipoTotales = [
                'ingresos' => round((float) $totBuild['ingresos'], 2),
                'egresos' => round((float) $totBuild['egresos'], 2),
                'saldo_neto' => round((float) $totBuild['saldo_neto'], 2),
            ];
        } else {
            $cajaPorTipoTotales = [
                'ingresos' => (float) ($cajaPorTipoTotales['ingresos'] ?? 0),
                'egresos' => (float) ($cajaPorTipoTotales['egresos'] ?? 0),
                'saldo_neto' => (float) ($cajaPorTipoTotales['saldo_neto'] ?? 0),
            ];
        }

        return [
            'company_name'            => $snapshot['company_name'] ?? 'Laboratorio',
            'startDate'               => $startDate,
            'endDate'                 => $endDate,
            'periodo_texto'           => $snapshot['periodo_texto'] ?? '',
            'generado_en'             => $snapshot['generado_en'] ?? '',
            'elaborado_por'           => $snapshot['elaborado_por'] ?? '',
            'totales'                 => (object) [
                'total_facturado'  => (float) ($tot['total_facturado'] ?? 0),
                'total_cobrado'    => (float) ($tot['total_cobrado'] ?? 0),
                'total_pendiente'  => (float) ($tot['total_pendiente'] ?? 0),
                'total_registros'  => (int) ($tot['total_registros'] ?? 0),
            ],
            'totalesIngresosCaja'     => (object) [
                'cantidad_ingresos' => (int) ($totIngresosCaja['cantidad_ingresos'] ?? 0),
                'total_ingresos'    => (float) ($totIngresosCaja['total_ingresos'] ?? 0),
            ],
            'totalesEgresos'          => (object) [
                'cantidad_egresos' => (int) ($totEgresos['cantidad_egresos'] ?? 0),
                'total_egresos'    => (float) ($totEgresos['total_egresos'] ?? 0),
            ],
            'tipoPagoMap'             => ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'],
            'resumenPagosPorTipo'     => $resumenPagosPorTipo,
            'resumenIngresosCajaPorTipo' => $resumenIngresosCajaPorTipo,
            'resumenEgresosPorTipo'   => $resumenEgresosPorTipo,
            'resumenPagosPorDia'      => $snapshot['resumenPagosPorDia'] ?? [],
            'resumenPagosPorDoctor'   => $snapshot['resumenPagosPorDoctor'] ?? [],
            'ingresosCajaMov'         => $ingresosCajaMov,
            'egresos'                 => $egresos,
            'cajaResumen'             => $cajaResumen,
            'cajaPorTipo'             => $cajaPorTipo,
            'cajaPorTipoTotales'      => $cajaPorTipoTotales,
            'count_pagados'           => (int) ($snapshot['count_pagados'] ?? 0),
            'count_pendientes'        => (int) ($snapshot['count_pendientes'] ?? 0),
            'cierre_id'               => $cierreId,
        ];
    }

    public function pruebasFecha()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $data = $this->reportModel->getPruebasPorFecha($startDate, $endDate);

        return view('reports/pruebas_fecha', [
            'title'           => 'Reporte de pruebas realizadas por fecha',
            'current_module'  => 'reports',
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'data'            => $data,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * @return array{secciones: list<array>, ordenes_en_periodo: int, grupos_opts: list<array>, startDate: string, endDate: string, anacategoria_id: int, subtitle: string}
     */
    private function collectPruebasPorGrupoAnalisisPayload(string $startDate, string $endDate, int $anacategoriaId): array
    {
        $rows       = $this->reportModel->getRegistrosEstadisticasLaboratorio($startDate, $endDate);
        $parentMap  = $this->reportModel->getPrianacategoriaAnacategoriaMap();
        $labels     = $this->reportModel->getPrianacategoriaLabelsMap();
        $gruposOpts = $this->reportModel->getAnacategoriasParaReporte();

        $byGrupoId       = [];
        $registerService = new RegisterService();

        foreach ($rows as $row) {
            $pruebasStr = trim((string) ($row['pruebas'] ?? ''));
            $idsPrueba  = $registerService->extractPrianacategoriaIdsFromRegistroPruebas($pruebasStr);
            foreach ($idsPrueba as $prId) {
                if ($prId < 1) {
                    continue;
                }
                $gid = $parentMap[$prId] ?? 0;
                if ($gid < 1) {
                    continue;
                }
                if ($anacategoriaId > 0 && $gid !== $anacategoriaId) {
                    continue;
                }
                if (! isset($byGrupoId[$gid])) {
                    $byGrupoId[$gid] = [];
                }
                $byGrupoId[$gid][$prId] = ($byGrupoId[$gid][$prId] ?? 0) + 1;
            }
        }

        $nombreGrupo = static function (int $gid) use ($gruposOpts): string {
            foreach ($gruposOpts as $g) {
                if ((int) ($g['anacategoria_id'] ?? 0) === $gid) {
                    return (string) ($g['name'] ?? '');
                }
            }

            return 'Grupo #' . $gid;
        };

        $buildSeccion = static function (int $gid, array $conteoPorPrueba) use ($labels, $nombreGrupo): array {
            $detalle = [];
            $total   = 0;
            foreach ($conteoPorPrueba as $prId => $cnt) {
                $cnt   = (int) $cnt;
                $total += $cnt;
                $meta  = $labels[$prId] ?? null;
                $detalle[] = [
                    'prianacategoria_id' => $prId,
                    'prueba'             => $meta['prueba'] ?? ('ID ' . $prId),
                    'cantidad'           => $cnt,
                ];
            }
            usort($detalle, static function (array $a, array $b): int {
                $c = ($b['cantidad'] ?? 0) <=> ($a['cantidad'] ?? 0);
                if ($c !== 0) {
                    return $c;
                }

                return strcasecmp((string) ($a['prueba'] ?? ''), (string) ($b['prueba'] ?? ''));
            });

            return [
                'anacategoria_id' => $gid,
                'nombre'          => $nombreGrupo($gid),
                'total_pruebas'   => $total,
                'detalle'         => $detalle,
            ];
        };

        $secciones = [];
        if ($anacategoriaId > 0) {
            $conteo = $byGrupoId[$anacategoriaId] ?? [];
            if ($conteo !== []) {
                $secciones[] = $buildSeccion($anacategoriaId, $conteo);
            }
        } else {
            $idsGrupos = array_keys($byGrupoId);
            usort($idsGrupos, static function (int $a, int $b) use ($gruposOpts): int {
                $oa = 999;
                $ob = 999;
                foreach ($gruposOpts as $g) {
                    $id = (int) ($g['anacategoria_id'] ?? 0);
                    if ($id === $a) {
                        $oa = (int) ($g['orden'] ?? 999);
                    }
                    if ($id === $b) {
                        $ob = (int) ($g['orden'] ?? 999);
                    }
                }
                if ($oa !== $ob) {
                    return $oa <=> $ob;
                }

                return $a <=> $b;
            });
            foreach ($idsGrupos as $gid) {
                $secciones[] = $buildSeccion($gid, $byGrupoId[$gid] ?? []);
            }
        }

        return [
            'secciones'           => $secciones,
            'ordenes_en_periodo'  => count($rows),
            'grupos_opts'         => $gruposOpts,
            'startDate'           => $startDate,
            'endDate'             => $endDate,
            'anacategoria_id'     => $anacategoriaId,
            'subtitle'            => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
        ];
    }

    /**
     * Pruebas solicitadas en órdenes completas, agrupadas por grupo clínico (anacategoria) y detalle por análisis.
     */
    public function pruebasPorGrupoAnalisis()
    {
        $startDate      = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate        = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $anacategoriaId = (int) ($this->request->getGet('anacategoria_id') ?? 0);
        $payload        = $this->collectPruebasPorGrupoAnalisisPayload($startDate, $endDate, $anacategoriaId);

        return view('reports/pruebas_por_grupo_analisis', array_merge($payload, [
            'title'           => 'Pruebas por grupo de análisis clínico',
            'current_module'  => 'reports',
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]));
    }

    public function pruebasPorGrupoAnalisisPdf()
    {
        $startDate      = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate        = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $anacategoriaId = (int) ($this->request->getGet('anacategoria_id') ?? 0);
        $payload        = $this->collectPruebasPorGrupoAnalisisPayload($startDate, $endDate, $anacategoriaId);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('pruebas_por_grupo'),
            'Pruebas por grupo de análisis',
            (string) $payload['subtitle'],
            'reports/pdf/content/pruebas_por_grupo_analisis',
            [
                'secciones'          => $payload['secciones'],
                'ordenes_en_periodo' => $payload['ordenes_en_periodo'],
            ]
        );
    }

    public function pruebasIncompletasFecha()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $data = $this->reportModel->getPruebasIncompletasPorFecha($startDate, $endDate);

        return view('reports/pruebas_incompletas_fecha', [
            'title'           => 'Pruebas incompletas por fecha',
            'current_module'  => 'reports',
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'data'            => $data,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function pruebasAnuladasFecha()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $data = $this->reportModel->getPruebasAnuladasPorFecha($startDate, $endDate);

        return view('reports/pruebas_anuladas_fecha', [
            'title'              => 'Pruebas anuladas por fecha',
            'current_module'     => 'reports',
            'subtitle'           => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'data'               => $data,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'anulacionDisponible'=> $this->reportModel->tieneCampoAnuladoEnRegistro(),
            'allowed_modules'    => $this->allowed_modules,
            'user_info'          => $this->user_info,
        ]);
    }

    /**
     * @return array{resumen: array, porGeneroOrdenes: array, porGeneroPacientes: array, poblacionGrupoRows: array, porPruebaRows: array}
     */
    private function collectEstadisticasLaboratorioPayload(string $startDate, string $endDate): array
    {
        $rows = $this->reportModel->getRegistrosEstadisticasLaboratorio($startDate, $endDate);

        $poblaciones = model(PoblacionModel::class)->getAll();
        $byPobId     = [];
        foreach ($poblaciones as $p) {
            $id = (int) ($p['id_poblacion'] ?? 0);
            if ($id > 0) {
                $byPobId[$id] = $p;
            }
        }

        $registerService = new RegisterService();
        $labelsPrueba    = $this->reportModel->getPrianacategoriaLabelsMap();

        $resumen = [
            'ordenes'               => 0,
            'pruebas_realizadas'    => 0,
            'pacientes_distintos'   => 0,
            'ordenes_sin_persona'   => 0,
        ];
        $porGeneroOrdenes     = ['1' => 0, '2' => 0, '_' => 0];
        $porGeneroPacientes   = ['1' => 0, '2' => 0, '_' => 0];
        $seenPid              = [];
        $porPoblacion         = [];
        $conteoPorPruebaId    = [];
        $pacientesPorPruebaId = [];

        foreach ($rows as $row) {
            $resumen['ordenes']++;
            $pid = (int) ($row['person_id'] ?? 0);
            if ($pid <= 0) {
                $resumen['ordenes_sin_persona']++;
            }

            $pruebasStr = trim((string) ($row['pruebas'] ?? ''));
            $idsPrueba  = $registerService->extractPrianacategoriaIdsFromRegistroPruebas($pruebasStr);
            foreach ($idsPrueba as $prId) {
                if ($prId < 1) {
                    continue;
                }
                $resumen['pruebas_realizadas']++;
                $conteoPorPruebaId[$prId] = ($conteoPorPruebaId[$prId] ?? 0) + 1;
                if ($pid > 0) {
                    if (! isset($pacientesPorPruebaId[$prId])) {
                        $pacientesPorPruebaId[$prId] = [];
                    }
                    $pacientesPorPruebaId[$prId][$pid] = true;
                }
            }

            $gk = $this->generoKeyReporte($row['gender'] ?? null);
            $porGeneroOrdenes[$gk]++;

            if ($pid > 0 && ! isset($seenPid[$pid])) {
                $seenPid[$pid] = true;
                $resumen['pacientes_distintos']++;
                $porGeneroPacientes[$gk]++;
            }

            try {
                $ingreso = new \DateTime($row['ingreso']);
            } catch (\Throwable $e) {
                $ingreso = new \DateTime();
            }

            $birth    = ! empty($row['birthday']) ? (string) $row['birthday'] : null;
            $gen      = isset($row['gender']) && $row['gender'] !== '' ? (int) $row['gender'] : null;
            $matching = $registerService->getMatchingPoblacionIds($birth, $gen, $ingreso);
            $pobId    = $this->pickPrimaryPoblacionId($matching, $byPobId);
            $porPoblacion[$pobId] = ($porPoblacion[$pobId] ?? 0) + 1;
        }

        $porPruebaRows = [];
        foreach ($conteoPorPruebaId as $id => $cnt) {
            $meta = $labelsPrueba[$id] ?? null;
            $porPruebaRows[] = [
                'prianacategoria_id'  => $id,
                'prueba'              => $meta['prueba'] ?? ('ID ' . $id),
                'categoria'           => $meta['categoria'] ?? '',
                'ordenes_con_prueba'  => $cnt,
                'pacientes_distintos' => isset($pacientesPorPruebaId[$id]) ? count($pacientesPorPruebaId[$id]) : 0,
            ];
        }
        usort($porPruebaRows, static function (array $a, array $b): int {
            $c = ($b['ordenes_con_prueba'] ?? 0) <=> ($a['ordenes_con_prueba'] ?? 0);
            if ($c !== 0) {
                return $c;
            }
            $cc = strcasecmp((string) ($a['categoria'] ?? ''), (string) ($b['categoria'] ?? ''));
            if ($cc !== 0) {
                return $cc;
            }

            return strcasecmp((string) ($a['prueba'] ?? ''), (string) ($b['prueba'] ?? ''));
        });

        $poblacionGrupoRows = [];
        foreach ($poblaciones as $p) {
            $id = (int) ($p['id_poblacion'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $poblacionGrupoRows[] = [
                'nombre'     => (string) ($p['name'] ?? ''),
                'rango_edad' => PoblacionModel::formatRangoEdad($p),
                'ordenes'    => (int) ($porPoblacion[$id] ?? 0),
            ];
        }
        foreach ($porPoblacion as $id => $cnt) {
            $id = (int) $id;
            if ($id < 1 || isset($byPobId[$id])) {
                continue;
            }
            $poblacionGrupoRows[] = [
                'nombre'     => 'Grupo #' . $id,
                'rango_edad' => '—',
                'ordenes'    => (int) $cnt,
            ];
        }

        return [
            'resumen'              => $resumen,
            'porGeneroOrdenes'     => $porGeneroOrdenes,
            'porGeneroPacientes'   => $porGeneroPacientes,
            'poblacionGrupoRows'   => $poblacionGrupoRows,
            'porPruebaRows'        => $porPruebaRows,
        ];
    }

    /**
     * Estadísticas en rango: pruebas, desglose por tipo, pacientes, grupo poblacional (config) y género.
     */
    public function estadisticasLaboratorio()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $payload   = $this->collectEstadisticasLaboratorioPayload($startDate, $endDate);

        return view('reports/estadisticas_laboratorio', array_merge($payload, [
            'title'           => 'Estadísticas de laboratorio por período',
            'current_module'  => 'reports',
            'subtitle'        => RegisterService::formatReportDateRangeSubtitle($startDate, $endDate),
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]));
    }

    public function estadisticasLaboratorioPdf()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $payload   = $this->collectEstadisticasLaboratorioPayload($startDate, $endDate);
        $sub       = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('estadisticas_laboratorio'),
            'Estadísticas de laboratorio',
            $sub,
            'reports/pdf/content/estadisticas_laboratorio',
            $payload
        );
    }

    private function generoKeyReporte($gender): string
    {
        $g = (string) $gender;

        return in_array($g, ['1', '2'], true) ? $g : '_';
    }

    /**
     * Elige un solo id_poblacion para contabilizar la orden (misma tabla que en Config → Población).
     *
     * @param array<int, array<string, mixed>> $byPobId
     */
    private function pickPrimaryPoblacionId(array $matchingIds, array $byPobId): int
    {
        $ids = array_values(array_unique(array_map('intval', $matchingIds)));
        if ($ids === []) {
            return 3;
        }
        usort($ids, static function (int $a, int $b) use ($byPobId): int {
            $oa = (int) ($byPobId[$a]['orden'] ?? 999);
            $ob = (int) ($byPobId[$b]['orden'] ?? 999);
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }
            $aAd = $a === 3 ? 1 : 0;
            $bAd = $b === 3 ? 1 : 0;
            if ($aAd !== $bAd) {
                return $aAd <=> $bAd;
            }

            return $a <=> $b;
        });

        return $ids[0];
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
        $hoy   = RegisterService::todayForReport();
        $enX   = RegisterService::reportDateFromModifier("+{$diasAlerta} days");

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

    /**
     * Catálogo completo: categorías (padre) y análisis (hijos), vista para imprimir.
     */
    public function catalogoPruebas()
    {
        helper('layout');
        $layoutConfig = layout_config();
        $categories   = $this->labotestModel->getGroupedByCategory(null);

        return view('reports/catalogo_pruebas_document', [
            'company_name' => $layoutConfig['company'] ?? 'Laboratorio',
            'generado_en'  => RegisterService::formatNowForReportShort(),
            'categories'   => $categories,
            'show_toolbar' => true,
        ]);
    }

    /**
     * Mismo catálogo en PDF.
     */
    public function catalogoPruebasPdf()
    {
        helper('layout');
        $layoutConfig = layout_config();
        $categories   = $this->labotestModel->getGroupedByCategory(null);

        $data = [
            'company_name' => $layoutConfig['company'] ?? 'Laboratorio',
            'generado_en'  => RegisterService::formatNowForReportShort(),
            'categories'   => $categories,
            'show_toolbar' => false,
        ];

        $html     = view('reports/catalogo_pruebas_document', $data);
        $filename = 'catalogo_pruebas_' . lab_filename_date() . '.pdf';

        (new PdfService())->download($html, $filename);
    }

    /**
     * Catálogo en CSV (UTF-8 con BOM) para abrir en Excel: grupo + análisis por fila.
     */
    public function catalogoPruebasExcel()
    {
        $categories = $this->labotestModel->getGroupedByCategory(null);
        $filename   = 'catalogo_pruebas_' . lab_filename_datetime() . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ID grupo', 'Grupo', 'ID análisis', 'Análisis', lang('Labotests.labotests_tipo_analisis'), 'Tipo de muestra', 'Método']);

        foreach ($categories as $cat) {
            $gid   = (int) ($cat['id'] ?? 0);
            $gname = (string) ($cat['name'] ?? '');
            $items = $cat['items'] ?? [];

            if ($items === []) {
                fputcsv($output, [$gid, $gname, '', '', '', '', '']);
                continue;
            }

            foreach ($items as $it) {
                $tipo = match ((int) ($it['compleja'] ?? 0)) {
                    \App\Models\LabotestModel::COMPLEJA_COMPOUESTA => lang('Labotests.labotests_tipo_analisis_tabla'),
                    \App\Models\LabotestModel::COMPLEJA_CULTIVO => lang('Labotests.labotests_tipo_analisis_cultivo'),
                    default => lang('Labotests.labotests_tipo_analisis_simple'),
                };
                fputcsv($output, [
                    $gid,
                    $gname,
                    (int) ($it['id'] ?? 0),
                    (string) ($it['name'] ?? ''),
                    $tipo,
                    (string) ($it['tipo_muestra'] ?? ''),
                    (string) ($it['metodo'] ?? ''),
                ]);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Reporte de costos de todas las pruebas (precio y precio derivado)
     */
    public function costosPruebas()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data = $this->reportModel->getCostosPruebas($busqueda);

        return view('reports/costos_pruebas', [
            'title'           => 'Reporte de costos de pruebas',
            'current_module'  => 'reports',
            'subtitle'        => 'Precio y precio derivado de todas las pruebas',
            'data'            => $data,
            'busqueda'        => $busqueda,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * Lista editable de precios (mismo catálogo que costosPruebas).
     */
    public function editarCostosPruebas()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $scope    = $this->ensureTenantCostosScope();
        if ($scope === null) {
            return $this->redirectTenantCostosError($busqueda);
        }

        $data = $this->reportModel->getCostosPruebas($busqueda);

        return view('reports/editar_costos_pruebas', [
            'title'           => 'Editar costos de pruebas',
            'current_module'  => 'reports',
            'subtitle'        => 'Actualice precio y precio derivado manual o masivamente',
            'data'            => $data,
            'busqueda'        => $busqueda,
            'tenant_scope'    => $scope,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    /**
     * Guardar cambios masivos de precios desde la lista editable.
     */
    public function saveCostosPruebas()
    {
        $busqueda = trim((string) ($this->request->getPost('busqueda') ?? ''));
        $scope    = $this->ensureTenantCostosScope();
        if ($scope === null) {
            return $this->redirectTenantCostosError($busqueda);
        }

        $precios = $this->request->getPost('precio');
        $deriv   = $this->request->getPost('precio_derivado');

        if (! is_array($precios) || $precios === []) {
            return redirect()->to('reports/editarCostosPruebas' . ($busqueda !== '' ? '?busqueda=' . urlencode($busqueda) : ''))
                ->with('error', 'No se recibieron precios para guardar.');
        }

        $items = [];
        foreach ($precios as $id => $valor) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $items[$id] = [
                'cost'       => max(0, (int) $valor),
                'cost_deriv' => max(0, (int) (is_array($deriv) ? ($deriv[$id] ?? $valor) : $valor)),
            ];
        }

        if ($items === []) {
            return redirect()->to('reports/editarCostosPruebas' . ($busqueda !== '' ? '?busqueda=' . urlencode($busqueda) : ''))
                ->with('error', 'No hay pruebas válidas para actualizar.');
        }

        $items = $this->filterCostosItemsToTenantCatalog($items);
        if ($items === []) {
            return redirect()->to('reports/editarCostosPruebas' . ($busqueda !== '' ? '?busqueda=' . urlencode($busqueda) : ''))
                ->with('error', 'Ninguna prueba pertenece al catálogo de este laboratorio.');
        }

        $result = $this->labotestModel->updateCostsBulk($items);

        \App\Models\AuditoriaModel::log(
            'reports',
            'actualizar_costos_pruebas',
            'bulk',
            \App\Models\AuditoriaModel::detail([
                'actualizadas' => $result['updated'],
                'omitidas'     => $result['skipped'],
                'busqueda'     => $busqueda,
                'tenant_key'   => $scope['tenant_key'],
                'database'     => $scope['database'],
            ])
        );

        $redirect = 'reports/editarCostosPruebas' . ($busqueda !== '' ? '?busqueda=' . urlencode($busqueda) : '');

        if ($result['updated'] < 1) {
            return redirect()->to($redirect)->with('error', 'No se pudo guardar ningún precio.');
        }

        $msg = 'Se actualizaron ' . $result['updated'] . ' prueba(s).';
        if ($result['skipped'] > 0) {
            $msg .= ' (' . $result['skipped'] . ' omitida(s)).';
        }

        return redirect()->to($redirect)->with('success', $msg);
    }

    /**
     * Exportar todos los precios en CSV (GRUPO, PRUEBA, ID, PRECIO) para importación masiva.
     */
    public function exportCostosPruebasImport()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $scope = $this->ensureTenantCostosScope();
        if ($scope === null) {
            return $this->response->setStatusCode(403)->setBody('No se pudo determinar el laboratorio (tenant) activo.');
        }

        $data = $this->reportModel->getCostosPruebas('');

        $filename = 'costos_' . $scope['tenant_key'] . '_' . lab_filename_datetime() . '.csv';

        $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');
        $this->response->sendHeaders();

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['GRUPO', 'PRUEBA', 'ID', 'PRECIO', 'PRECIO_DERIVADO']);

        foreach ($data as $item) {
            $id = (int) ($item['prianacategoria_id'] ?? 0);
            if ($id < 1) {
                continue;
            }

            fputcsv($output, [
                $item['categoria'] ?? '',
                $item['prueba'] ?? '',
                $id,
                (int) ($item['precio'] ?? 0),
                (int) ($item['precio_derivado'] ?? 0),
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Importar desde CSV (GRUPO, PRUEBA, ID, PRECIO). Actualiza nombre y precio por ID.
     */
    public function importCostosPruebas()
    {
        $busqueda = trim((string) ($this->request->getPost('busqueda') ?? ''));
        $redirect = 'reports/editarCostosPruebas' . ($busqueda !== '' ? '?busqueda=' . urlencode($busqueda) : '');

        $scope = $this->ensureTenantCostosScope();
        if ($scope === null) {
            return $this->redirectTenantCostosError($busqueda);
        }

        $postedTenant = trim((string) ($this->request->getPost('tenant_key') ?? ''));
        if ($postedTenant !== '' && $postedTenant !== $scope['tenant_key']) {
            return redirect()->to($redirect)->with('error', 'El archivo no corresponde a este laboratorio. Exporte e importe dentro del mismo tenant.');
        }

        $file = $this->request->getFile('costos_csv');
        if (! $file || ! $file->isValid()) {
            return redirect()->to($redirect)->with('error', 'Seleccione un archivo CSV válido.');
        }

        $ext = strtolower((string) $file->getExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return redirect()->to($redirect)->with('error', 'El archivo debe ser .csv o .txt');
        }

        $tmpPath = $file->getTempName();
        $content = is_string($tmpPath) && $tmpPath !== '' ? @file_get_contents($tmpPath) : false;
        if (! is_string($content) || trim($content) === '') {
            return redirect()->to($redirect)->with('error', 'El archivo está vacío.');
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $handle  = fopen('php://memory', 'r+');
        if ($handle === false) {
            return redirect()->to($redirect)->with('error', 'No se pudo leer el archivo.');
        }
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle, 0, ',');
        if (! is_array($header) || $header === []) {
            fclose($handle);

            return redirect()->to($redirect)->with('error', 'El CSV no tiene encabezados.');
        }

        $colGrupo       = null;
        $colPrueba      = null;
        $colId          = null;
        $colPrecio      = null;
        $colPrecioDeriv = null;
        foreach ($header as $i => $label) {
            $key = $this->normalizeCostosCsvHeader((string) $label);
            if (in_array($key, ['GRUPO', 'CATEGORIA'], true)) {
                $colGrupo = (int) $i;
            } elseif (in_array($key, ['PRUEBA', 'NOMBRE', 'ANALISIS'], true)) {
                $colPrueba = (int) $i;
            } elseif ($key === 'ID') {
                $colId = (int) $i;
            } elseif ($this->isCostosCsvPrecioDerivadoHeader($key)) {
                $colPrecioDeriv = (int) $i;
            } elseif ($this->isCostosCsvPrecioHeader($key)) {
                $colPrecio = (int) $i;
            }
        }

        if ($colId === null || $colPrueba === null || $colPrecio === null) {
            fclose($handle);

            return redirect()->to($redirect)->with('error', 'El CSV debe incluir columnas PRUEBA, ID y PRECIO (PRECIO_DERIVADO y GRUPO son opcionales).');
        }

        $items      = [];
        $errores    = [];
        $linea      = 1;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $linea++;
            if (! is_array($row) || $this->csvRowIsEmpty($row)) {
                continue;
            }

            $id = (int) trim((string) ($row[$colId] ?? ''));
            if ($id < 1) {
                $errores[] = "Línea {$linea}: ID inválido o vacío.";
                continue;
            }

            $nombre = trim((string) ($row[$colPrueba] ?? ''));
            if ($nombre === '') {
                $errores[] = "Línea {$linea}: nombre de prueba vacío.";
                continue;
            }

            $precio = $this->parsePrecioFromCsvCell((string) ($row[$colPrecio] ?? ''));
            if ($precio < 0) {
                $errores[] = "Línea {$linea}: precio inválido.";
                continue;
            }

            $rowUpdate = [
                'name' => $nombre,
                'cost' => $precio,
            ];

            if ($colPrecioDeriv !== null) {
                $rawDeriv = trim((string) ($row[$colPrecioDeriv] ?? ''));
                if ($rawDeriv === '') {
                    $rowUpdate['cost_deriv'] = 0;
                } else {
                    $derivado = $this->parsePrecioFromCsvCell($rawDeriv);
                    if ($derivado < 0) {
                        $errores[] = "Línea {$linea}: precio derivado inválido.";
                        continue;
                    }
                    $rowUpdate['cost_deriv'] = $derivado;
                }
            }

            $items[$id] = $rowUpdate;
        }
        fclose($handle);

        if ($items === []) {
            $msg = 'No se encontraron filas válidas para importar.';
            if ($errores !== []) {
                $msg .= ' ' . implode(' ', array_slice($errores, 0, 5));
            }

            return redirect()->to($redirect)->with('error', $msg);
        }

        $antesImport = count($items);
        $items       = $this->filterCostosItemsToTenantCatalog($items, $errores);
        if ($items === []) {
            $msg = 'Ningún ID del CSV existe en el catálogo de este laboratorio (' . $scope['tenant_key'] . ').';
            if ($errores !== []) {
                $msg .= ' ' . implode(' ', array_slice($errores, 0, 5));
            }

            return redirect()->to($redirect)->with('error', $msg);
        }

        if (count($items) < $antesImport) {
            $errores[] = 'Se omitieron ' . ($antesImport - count($items)) . ' fila(s) con ID de otro laboratorio o inexistentes.';
        }

        $result = $this->labotestModel->updateCostsBulk($items);

        \App\Models\AuditoriaModel::log(
            'reports',
            'importar_costos_pruebas_csv',
            'bulk',
            \App\Models\AuditoriaModel::detail([
                'actualizadas' => $result['updated'],
                'omitidas'     => $result['skipped'],
                'filas_csv'    => count($items),
                'errores_csv'  => count($errores),
                'tenant_key'   => $scope['tenant_key'],
                'database'     => $scope['database'],
            ])
        );

        if ($result['updated'] < 1) {
            $msg = 'No se pudo actualizar ninguna prueba.';
            if ($errores !== []) {
                $msg .= ' ' . implode(' ', array_slice($errores, 0, 5));
            }

            return redirect()->to($redirect)->with('error', $msg);
        }

        $msg = 'Importación completada: ' . $result['updated'] . ' prueba(s) actualizada(s).';
        if ($result['skipped'] > 0) {
            $msg .= ' (' . $result['skipped'] . ' omitida(s) en base de datos).';
        }
        if ($errores !== []) {
            $msg .= ' Advertencias: ' . implode(' ', array_slice($errores, 0, 8));
            if (count($errores) > 8) {
                $msg .= ' …';
            }
        }

        return redirect()->to($redirect)->with('success', $msg);
    }

    /**
     * Conecta a la BD del tenant activo y recarga modelos de catálogo.
     *
     * @return array{tenant_key: string, database: string}|null
     */
    private function ensureTenantCostosScope(): ?array
    {
        try {
            $scope = (new TenantScopedDatabaseService())->ensureActiveTenantConnection($this->request);
            $this->reportModel  = model(ReportModel::class);
            $this->labotestModel = model(LabotestModel::class);

            return $scope;
        } catch (\Throwable $e) {
            log_message('error', 'ensureTenantCostosScope: ' . $e->getMessage());

            return null;
        }
    }

    private function redirectTenantCostosError(string $busqueda = ''): ResponseInterface
    {
        $redirect = 'reports/editarCostosPruebas' . ($busqueda !== '' ? '?busqueda=' . urlencode($busqueda) : '');

        return redirect()->to($redirect)->with(
            'error',
            'No se pudo usar la base de datos de su laboratorio. Acceda desde la URL de su tenant (ej. quantum.local) o con el tenant correcto en sesión.'
        );
    }

    /**
     * Solo IDs del catálogo del tenant actual (evita mezclar datos entre laboratorios).
     *
     * @param array<int, array<string, mixed>> $items
     * @param list<string>                   $errores
     * @return array<int, array<string, mixed>>
     */
    private function filterCostosItemsToTenantCatalog(array $items, array &$errores = []): array
    {
        if ($items === []) {
            return [];
        }

        $allowed = array_flip($this->labotestModel->existingPrianacategoriaIds(array_keys($items)));
        $filtered = [];

        foreach ($items as $id => $row) {
            $id = (int) $id;
            if (isset($allowed[$id])) {
                $filtered[$id] = $row;
                continue;
            }
            $errores[] = "ID {$id} no pertenece al catálogo de este laboratorio.";
        }

        return $filtered;
    }

    /**
     * Normaliza nombre de columna CSV de costos.
     */
    private function normalizeCostosCsvHeader(string $label): string
    {
        $label = strtoupper(trim($label));
        $label = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'U', 'N'], $label);
        // Sin guiones ni espacios: PRECIO_DERIVADO y "Precio derivado" → PRECIODERIVADO
        $label = preg_replace('/[^A-Z0-9]/', '', $label) ?? $label;

        return $label;
    }

    private function isCostosCsvPrecioHeader(string $key): bool
    {
        return in_array($key, ['PRECIO', 'COSTO', 'COST'], true);
    }

    private function isCostosCsvPrecioDerivadoHeader(string $key): bool
    {
        return in_array($key, ['PRECIODERIVADO', 'PRECODERIVADO', 'COSTDERIV', 'COSTODERIV', 'DERIVADO', 'REFERENCIA'], true)
            || str_contains($key, 'DERIVADO')
            || str_contains($key, 'DERIV');
    }

    /**
     * @param list<string|null> $row
     */
    private function csvRowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Extrae entero de celda de precio (ej. "60 Bs", "$ 120", "200").
     */
    private function parsePrecioFromCsvCell(string $raw): int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return -1;
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $m) !== 1) {
            return -1;
        }

        $num = str_replace(',', '.', $m[1]);

        return max(0, (int) round((float) $num));
    }

    /**
     * Kardex de inventario.
     * Reutiliza el módulo de inventario para evitar duplicar lógica.
     */
    public function inventarioKardex()
    {
        $query = $this->request->getServer('QUERY_STRING');
        $url = 'inventario/kardex' . ($query ? ('?' . $query) : '');

        return redirect()->to($url);
    }

    /**
     * Exportar reporte de costos de pruebas a Excel/CSV
     */
    public function exportCostosPruebas()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data = $this->reportModel->getCostosPruebas($busqueda);

        $filename = 'costos_pruebas_' . lab_filename_datetime() . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Cabeceras en español
        fwrite($output, "\xEF\xBB\xBF"); // BOM para UTF-8
        fputcsv($output, ['Categoría', 'Prueba', 'Precio', 'Precio Derivado']);

        $totalPrecio = 0;
        $totalDerivado = 0;

        foreach ($data as $item) {
            $precio = (float) ($item['precio'] ?? 0);
            $derivado = (float) ($item['precio_derivado'] ?? 0);
            
            $totalPrecio += $precio;
            $totalDerivado += $derivado;
            
            fputcsv($output, [
                $item['categoria'],
                $item['prueba'],
                number_format($precio, 2, '.', ''),
                number_format($derivado, 2, '.', ''),
            ]);
        }

        // Total general
        fputcsv($output, []);
        fputcsv($output, ['TOTAL GENERAL', '', number_format($totalPrecio, 2, '.', ''), number_format($totalDerivado, 2, '.', '')]);

        fclose($output);
        exit;
    }

    /**
     * Reporte de valores de referencia de todas las pruebas con buscador
     */
    public function valoresReferencia()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data = $this->reportModel->getValoresReferencia($busqueda);
        $poblacionLabels = $this->poblacionLabelMap();
        $scope = null;
        try {
            $scope = (new TenantScopedDatabaseService())->ensureActiveTenantConnection($this->request);
            $this->reportModel = model(ReportModel::class);
        } catch (\Throwable $e) {
            log_message('debug', 'valoresReferencia tenant scope: ' . $e->getMessage());
        }

        return view('reports/valores_referencia', [
            'title'             => 'Reporte de valores de referencia',
            'current_module'    => 'reports',
            'subtitle'          => 'Valores de referencia de todas las pruebas',
            'data'              => $data,
            'busqueda'          => $busqueda,
            'poblacion_labels'  => $poblacionLabels,
            'tenant_scope'      => $scope,
            'allowed_modules'   => $this->allowed_modules,
            'user_info'         => $this->user_info,
        ]);
    }

    /**
     * Exportar reporte de valores de referencia a Excel/CSV
     */
    public function exportValoresReferencia()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $soloSinValores = (int) ($this->request->getGet('solo_sin_valores') ?? 0) === 1;
        $data = $this->reportModel->getValoresReferencia($busqueda);
        $poblacionLabels = $this->poblacionLabelMap();

        $filename = ($soloSinValores ? 'valores_referencia_sin_valores_' : 'valores_referencia_') . lab_filename_datetime() . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Cabeceras en español
        fwrite($output, "\xEF\xBB\xBF"); // BOM para UTF-8
        fputcsv($output, ['Categoría', 'Prueba', 'Análisis', 'Población', 'Sexo', 'Valor Mínimo', 'Valor Máximo', 'Unidad']);

        foreach ($data as $index => $item) {
            $tieneValores = $this->hasValoresReferenciaConfigurados($item);
            if ($soloSinValores && $tieneValores) {
                continue;
            }
            if (! $soloSinValores && ! $tieneValores) {
                continue;
            }
            
            // Funciones helper para exportación
            $poblacion = $this->labelForPoblacionId($item['poblacion'] ?? null, $poblacionLabels);
            $sexo = $this->getSexoLabel($item['sexo'] ?? 'ambos');
            
            fputcsv($output, [
                $item['categoria'] ?? '',
                $item['prueba'] ?? '',
                $item['analisis'] ?? '',
                $poblacion,
                $sexo,
                $this->csvValue($item['valor_min'] ?? null),
                $this->csvValue($item['valor_max'] ?? null),
                $item['umedida'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar valores de referencia en CSV con IDs (importación masiva).
     */
    public function exportValoresReferenciaImport()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $scope = $this->ensureTenantCostosScope();
        if ($scope === null) {
            return $this->response->setStatusCode(403)->setBody('No se pudo determinar el laboratorio (tenant) activo.');
        }

        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data     = $this->reportModel->getValoresReferencia($busqueda);

        $filename = 'valores_referencia_' . $scope['tenant_key'] . '_' . lab_filename_datetime() . '.csv';

        $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');
        $this->response->sendHeaders();

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'TIPO',
            'ID_PRUEBA',
            'GRUPO',
            'PRUEBA',
            'ANALISIS',
            'ID_REFERENCIA',
            'POBLACION_ID',
            'SEXO',
            'VALOR_MIN',
            'VALOR_MAX',
            'UNIDAD',
            'ID_TIPO_RESULTADO',
        ]);

        foreach ($data as $item) {
            $tipo = (string) ($item['tipo_prueba'] ?? '');
            $idPrueba = (int) ($item['prianacategoria_id'] ?? 0);
            if ($idPrueba < 1) {
                continue;
            }

            if ($tipo === 'compuesto') {
                $idRef = (int) ($item['secanacategoria_id'] ?? 0);
            } else {
                $idRef = (int) ($item['priresultados_id'] ?? 0);
            }
            if ($idRef < 1) {
                continue;
            }

            fputcsv($output, [
                $tipo === 'compuesto' ? 'compuesto' : 'simple',
                $idPrueba,
                $item['categoria'] ?? '',
                $item['prueba'] ?? '',
                $item['analisis'] ?? '',
                $idRef,
                $this->csvValue($item['poblacion'] ?? null) !== '' ? (int) $item['poblacion'] : '',
                $this->normalizeSexoForCsv($item['sexo'] ?? 'ambos'),
                $this->csvValue($item['valor_min'] ?? null),
                $this->csvValue($item['valor_max'] ?? null),
                $item['umedida'] ?? '',
                (int) ($item['opcion_id'] ?? 3),
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Importar valores de referencia desde CSV (TIPO, ID_PRUEBA, ID_REFERENCIA, …).
     */
    public function importValoresReferencia()
    {
        $busqueda = trim((string) ($this->request->getPost('busqueda') ?? ''));
        $redirect = 'reports/valoresReferencia' . ($busqueda !== '' ? '?busqueda=' . urlencode($busqueda) : '');

        $scope = $this->ensureTenantCostosScope();
        if ($scope === null) {
            return redirect()->to($redirect)->with(
                'error',
                'No se pudo usar la base de datos de su laboratorio. Acceda desde la URL de su tenant o con el tenant correcto en sesión.'
            );
        }

        $postedTenant = trim((string) ($this->request->getPost('tenant_key') ?? ''));
        if ($postedTenant !== '' && $postedTenant !== $scope['tenant_key']) {
            return redirect()->to($redirect)->with('error', 'El archivo no corresponde a este laboratorio. Exporte e importe dentro del mismo tenant.');
        }

        $file = $this->request->getFile('valores_referencia_csv');
        if (! $file || ! $file->isValid()) {
            return redirect()->to($redirect)->with('error', 'Seleccione un archivo CSV válido.');
        }

        $ext = strtolower((string) $file->getExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return redirect()->to($redirect)->with('error', 'El archivo debe ser .csv o .txt');
        }

        $tmpPath = $file->getTempName();
        $content = is_string($tmpPath) && $tmpPath !== '' ? @file_get_contents($tmpPath) : false;
        if (! is_string($content) || trim($content) === '') {
            return redirect()->to($redirect)->with('error', 'El archivo está vacío.');
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $handle  = fopen('php://memory', 'r+');
        if ($handle === false) {
            return redirect()->to($redirect)->with('error', 'No se pudo leer el archivo.');
        }
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle, 0, ',');
        if (! is_array($header) || $header === []) {
            fclose($handle);

            return redirect()->to($redirect)->with('error', 'El CSV no tiene encabezados.');
        }

        $cols = $this->mapValoresReferenciaCsvColumns($header);
        if ($cols['tipo'] === null || $cols['id_referencia'] === null) {
            fclose($handle);

            return redirect()->to($redirect)->with('error', 'El CSV debe incluir columnas TIPO e ID_REFERENCIA (o ID).');
        }

        $secItems = [];
        $priItems = [];
        $errores  = [];
        $linea    = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $linea++;
            if (! is_array($row) || $this->csvRowIsEmpty($row)) {
                continue;
            }

            $tipoRaw = trim((string) ($row[$cols['tipo']] ?? ''));
            $tipo    = $this->normalizeTipoPruebaCsv($tipoRaw);
            if ($tipo === null) {
                $errores[] = "Línea {$linea}: TIPO inválido (use compuesto o simple).";
                continue;
            }

            $idRef = (int) trim((string) ($row[$cols['id_referencia']] ?? ''));
            if ($idRef < 1) {
                $errores[] = "Línea {$linea}: ID_REFERENCIA inválido o vacío.";
                continue;
            }

            $idPrueba = $cols['id_prueba'] !== null
                ? (int) trim((string) ($row[$cols['id_prueba']] ?? ''))
                : 0;

            $poblacion = 0;
            if ($cols['poblacion'] !== null) {
                $rawPob = trim((string) ($row[$cols['poblacion']] ?? ''));
                if ($rawPob !== '') {
                    $poblacion = (int) $rawPob;
                }
            }

            $sexo = 'ambos';
            if ($cols['sexo'] !== null) {
                $sexo = $this->normalizeSexoForCsv((string) ($row[$cols['sexo']] ?? 'ambos'));
            }

            $valorMin = $cols['valor_min'] !== null
                ? trim((string) ($row[$cols['valor_min']] ?? ''))
                : '';
            $valorMax = $cols['valor_max'] !== null
                ? trim((string) ($row[$cols['valor_max']] ?? ''))
                : '';
            $unidad   = $cols['unidad'] !== null
                ? trim((string) ($row[$cols['unidad']] ?? ''))
                : '';

            $opcionId = null;
            if ($cols['tipo_resultado'] !== null) {
                $rawOpcion = trim((string) ($row[$cols['tipo_resultado']] ?? ''));
                if ($rawOpcion !== '') {
                    $opcionId = (int) $rawOpcion;
                    if ($opcionId < 1) {
                        $errores[] = "Línea {$linea}: ID_TIPO_RESULTADO inválido.";
                        continue;
                    }
                }
            }

            $payload = [
                'valor_min' => $valorMin,
                'valor_max' => $valorMax,
                'umedida'   => $unidad,
                '_pri_id'   => $idPrueba,
            ];
            if ($opcionId !== null) {
                $payload['opcion_id'] = $opcionId;
                $payload['_opcion_id'] = $opcionId;
            }

            if ($tipo === 'compuesto') {
                $payload['paciente_id'] = $poblacion > 0 ? $poblacion : 15;
                if ($cols['sexo'] !== null) {
                    $payload['sexo'] = $sexo;
                }
                $secItems[$idRef] = $payload;
            } else {
                $payload['id_poblacion'] = $poblacion > 0 ? $poblacion : 15;
                if ($cols['sexo'] !== null) {
                    $payload['sexo'] = $sexo;
                }
                $priItems[$idRef] = $payload;
            }
        }
        fclose($handle);

        if ($secItems === [] && $priItems === []) {
            $msg = 'No se encontraron filas válidas para importar.';
            if ($errores !== []) {
                $msg .= ' ' . implode(' ', array_slice($errores, 0, 5));
            }

            return redirect()->to($redirect)->with('error', $msg);
        }

        $secItems = $this->filterValoresReferenciaSecToTenant($secItems, $errores);
        $priItems = $this->filterValoresReferenciaPriToTenant($priItems, $errores);
        $secItems = $this->filterValoresReferenciaOpcionIds($secItems, $errores);
        $priItems = $this->filterValoresReferenciaOpcionIds($priItems, $errores);

        if ($secItems === [] && $priItems === []) {
            $msg = 'Ningún ID_REFERENCIA del CSV existe en el catálogo de este laboratorio (' . $scope['tenant_key'] . ').';
            if ($errores !== []) {
                $msg .= ' ' . implode(' ', array_slice($errores, 0, 5));
            }

            return redirect()->to($redirect)->with('error', $msg);
        }

        $resultSec = $secItems !== [] ? $this->labotestModel->updateValoresReferenciaSecBulk($secItems) : ['updated' => 0, 'skipped' => 0];
        $resultPri = $priItems !== [] ? $this->labotestModel->updateValoresReferenciaPriBulk($priItems) : ['updated' => 0, 'skipped' => 0];
        $updated   = $resultSec['updated'] + $resultPri['updated'];
        $skipped   = $resultSec['skipped'] + $resultPri['skipped'];

        \App\Models\AuditoriaModel::log(
            'reports',
            'importar_valores_referencia_csv',
            'bulk',
            \App\Models\AuditoriaModel::detail([
                'actualizadas' => $updated,
                'omitidas'     => $skipped,
                'filas_sec'    => count($secItems),
                'filas_pri'    => count($priItems),
                'errores_csv'  => count($errores),
                'tenant_key'   => $scope['tenant_key'],
                'database'     => $scope['database'],
            ])
        );

        if ($updated < 1) {
            $msg = 'No se pudo actualizar ningún valor de referencia.';
            if ($errores !== []) {
                $msg .= ' ' . implode(' ', array_slice($errores, 0, 5));
            }

            return redirect()->to($redirect)->with('error', $msg);
        }

        $msg = 'Importación completada: ' . $updated . ' fila(s) actualizada(s).';
        if ($skipped > 0) {
            $msg .= ' (' . $skipped . ' omitida(s)).';
        }
        if ($errores !== []) {
            $msg .= ' Advertencias: ' . implode(' ', array_slice($errores, 0, 8));
            if (count($errores) > 8) {
                $msg .= ' …';
            }
        }

        return redirect()->to($redirect)->with('success', $msg);
    }

    private function safeReportPdfFilename(string $base): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) . '_' . lab_filename_date() . '.pdf';
    }

    public function registrosFechaPdf()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $data      = $this->reportModel->getRegistrosByDateRange($startDate, $endDate);
        $totales   = $this->reportModel->getTotalesByDateRange($startDate, $endDate);
        $sub       = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('registros_fecha'),
            'Registros por fecha',
            $sub,
            'reports/pdf/content/registros_fecha',
            ['data' => $data, 'totales' => $totales]
        );
    }

    public function ingresosFechaPdf()
    {
        $startDate     = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate       = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $facturables   = $this->reportModel->getIngresosByDateRange($startDate, $endDate);
        $anulPorDia    = $this->reportModel->getIngresosAnuladosPorDia($startDate, $endDate);
        $totales       = $this->reportModel->getTotalesByDateRange($startDate, $endDate);
        $totalesAnul   = $this->reportModel->getTotalesAnuladosByDateRange($startDate, $endDate);
        $byFecha       = [];
        foreach ($facturables as $r) {
            $f = $r['fecha'];
            $byFecha[$f] = [
                'fecha'               => $f,
                'cantidad'            => (int) ($r['cantidad'] ?? 0),
                'total'               => (float) ($r['total'] ?? 0),
                'cobrado'             => (float) ($r['cobrado'] ?? 0),
                'cantidad_anuladas'   => 0,
                'total_anulado_ref'   => 0.0,
                'cobrado_anulado_ref' => 0.0,
            ];
        }
        foreach ($anulPorDia as $r) {
            $f = $r['fecha'];
            if (! isset($byFecha[$f])) {
                $byFecha[$f] = [
                    'fecha'               => $f,
                    'cantidad'            => 0,
                    'total'               => 0.0,
                    'cobrado'             => 0.0,
                    'cantidad_anuladas'   => 0,
                    'total_anulado_ref'   => 0.0,
                    'cobrado_anulado_ref' => 0.0,
                ];
            }
            $byFecha[$f]['cantidad_anuladas']   = (int) ($r['cantidad'] ?? 0);
            $byFecha[$f]['total_anulado_ref']   = (float) ($r['total'] ?? 0);
            $byFecha[$f]['cobrado_anulado_ref'] = (float) ($r['cobrado'] ?? 0);
        }
        ksort($byFecha);
        $data = array_values($byFecha);
        $sub  = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('ingresos_fecha'),
            'Ingresos por fecha',
            $sub,
            'reports/pdf/content/ingresos_fecha',
            ['data' => $data, 'totales' => $totales, 'totalesAnulados' => $totalesAnul]
        );
    }

    public function porDoctorPdf()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $data      = $this->reportModel->getRegistrosByDoctor($startDate, $endDate);
        $totales   = $this->reportModel->getTotalesByDateRange($startDate, $endDate);
        $sub       = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('por_doctor'),
            'Registros por doctor',
            $sub,
            'reports/pdf/content/por_doctor',
            ['data' => $data, 'totales' => $totales]
        );
    }

    public function pagosPdf()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();

        $todos                 = $this->reportModel->getReportePagos($startDate, $endDate);
        $pendientes            = $this->reportModel->getCobrosParcialesEnPeriodo($startDate, $endDate);
        $pagosPagados          = $this->reportModel->getPagosPagadosDetalle($startDate, $endDate);
        $totales               = $this->reportModel->getTotalesPagos($startDate, $endDate);
        $resumenPagosPorTipo   = $this->reportModel->getResumenPagosPorTipo($startDate, $endDate);
        $resumenPagosPorDia    = $this->reportModel->getResumenPagosPorDia($startDate, $endDate);
        $resumenPagosPorDoctor = $this->reportModel->getResumenPagosPorDoctor($startDate, $endDate);
        $egresos               = $this->reportModel->getEgresosByDateRange($startDate, $endDate);
        $totalesEgresos        = $this->reportModel->getTotalesEgresosByDateRange($startDate, $endDate);
        $resumenEgresosPorTipo = $this->reportModel->getResumenEgresosPorTipo($startDate, $endDate);
        $ingresosCajaMov       = $this->reportModel->getIngresosCajaByDateRange($startDate, $endDate);
        $totalesIngresosCaja   = $this->reportModel->getTotalesIngresosCajaByDateRange($startDate, $endDate);
        $resumenIngresosCajaPorTipo = $this->reportModel->getResumenIngresosCajaPorTipo($startDate, $endDate);
        $tipoPagoMap           = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];
        $sub                   = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        $cajaCuadre            = $this->buildCuadreCajaPagos(
            $startDate,
            $endDate,
            $resumenPagosPorTipo,
            $resumenIngresosCajaPorTipo,
            $resumenEgresosPorTipo,
            $totalesIngresosCaja,
            $totalesEgresos
        );

        ReportPdfDocument::download(
            $this->safeReportPdfFilename('pagos'),
            'Reporte de pagos',
            $sub,
            'reports/pdf/content/pagos',
            [
                'tipoPagoMap'             => $tipoPagoMap,
                'totales'                 => $totales,
                'resumenPagosPorTipo'     => $resumenPagosPorTipo,
                'pagosPagados'            => $pagosPagados,
                'pendientes'              => $pendientes,
                'resumenPagosPorDia'      => $resumenPagosPorDia,
                'resumenPagosPorDoctor'   => $resumenPagosPorDoctor,
                'todos'                   => $todos,
                'ingresosCajaMov'         => $ingresosCajaMov,
                'egresos'                 => $egresos,
                'totalesIngresosCaja'     => $totalesIngresosCaja,
                'totalesEgresos'          => $totalesEgresos,
                'resumenIngresosCajaPorTipo' => $resumenIngresosCajaPorTipo,
                'resumenEgresosPorTipo'   => $resumenEgresosPorTipo,
                'cajaResumen'             => $cajaCuadre['cajaResumen'],
                'cajaPorTipo'             => $cajaCuadre['cajaPorTipo'],
                'cajaPorTipoTotales'      => $cajaCuadre['cajaPorTipoTotales'],
            ]
        );
    }

    public function pagosPendientesPdf()
    {
        $startDate  = $this->request->getGet('start') ?? RegisterService::reportDateFromModifier('-2 years');
        $endDate    = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $pendientes = $this->reportModel->getPendientesPago($startDate, $endDate);
        $totalSaldo = 0.0;
        foreach ($pendientes as $row) {
            $totalSaldo += (float) ($row['saldo'] ?? 0);
        }
        $sub = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('pagos_pendientes'),
            'Pendientes de pago',
            $sub,
            'reports/pdf/content/pagos_pendientes',
            ['pendientes' => $pendientes, 'total_saldo' => $totalSaldo]
        );
    }

    public function pagosCierresPdf()
    {
        $rows = [];
        try {
            $rows = $this->pagosCierreModel->getListado(500);
        } catch (\Throwable $e) {
        }
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('pagos_cierres'),
            'Cierres de pagos',
            'Listado',
            'reports/pdf/content/pagos_cierres',
            ['cierres' => $rows]
        );
    }

    public function cotizacionesGuardadasPdf()
    {
        $data = $this->toquoteModel->getAllCotizaciones(8000, 0);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('cotizaciones'),
            'Cotizaciones guardadas',
            'Hasta ' . count($data) . ' registro(s)',
            'reports/pdf/content/cotizaciones_guardadas',
            ['data' => $data]
        );
    }

    public function pruebasFechaPdf()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $data      = $this->reportModel->getPruebasPorFecha($startDate, $endDate);
        $sub       = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('pruebas_fecha'),
            'Pruebas realizadas por fecha',
            $sub,
            'reports/pdf/content/pruebas_fecha',
            ['data' => $data]
        );
    }

    public function pruebasIncompletasFechaPdf()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $data      = $this->reportModel->getPruebasIncompletasPorFecha($startDate, $endDate);
        $sub       = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('pruebas_incompletas'),
            'Pruebas incompletas por fecha',
            $sub,
            'reports/pdf/content/pruebas_incompletas_fecha',
            ['data' => $data]
        );
    }

    public function pruebasAnuladasFechaPdf()
    {
        $startDate = $this->request->getGet('start') ?? RegisterService::todayForReport();
        $endDate   = $this->request->getGet('end') ?? RegisterService::todayForReport();
        $data      = $this->reportModel->getPruebasAnuladasPorFecha($startDate, $endDate);
        $sub       = RegisterService::formatReportDateRangeSubtitle($startDate, $endDate);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('pruebas_anuladas'),
            'Pruebas anuladas por fecha',
            $sub,
            'reports/pdf/content/pruebas_anuladas_fecha',
            [
                'data'                => $data,
                'anulacionDisponible' => $this->reportModel->tieneCampoAnuladoEnRegistro(),
            ]
        );
    }

    public function insumosVencimientoPdf()
    {
        $reactivoModel = model(ReactivoModel::class);
        $appConfig     = model(AppConfigModel::class);
        $diasAlerta    = max(1, (int) ($appConfig->getValue('dias_alerta_vencimiento') ?: 40));
        $lotes         = $reactivoModel->getTodosLotesParaReporte();
        $hoy           = RegisterService::todayForReport();
        $enX           = RegisterService::reportDateFromModifier("+{$diasAlerta} days");
        foreach ($lotes as &$l) {
            $venc = $l['fecha_vencimiento'] ?? null;
            if (! $venc) {
                $l['estado']       = 'sin_fecha';
                $l['dias_restantes'] = null;
                $l['estado_label'] = '-';
            } else {
                $diff = (strtotime($venc) - strtotime($hoy)) / 86400;
                $l['dias_restantes'] = (int) round($diff);
                if ($diff < 0) {
                    $l['estado']       = 'vencido';
                    $l['estado_label'] = 'Vencido';
                } elseif ($venc <= $enX) {
                    $l['estado']       = 'por_vencer';
                    $l['estado_label'] = 'Por vencer (' . $l['dias_restantes'] . ' días)';
                } else {
                    $l['estado']       = 'ok';
                    $l['estado_label'] = $l['dias_restantes'] . ' días';
                }
            }
        }
        unset($l);
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('insumos_vencimiento'),
            'Insumos por ingreso y vencimiento',
            'Alerta ' . $diasAlerta . ' días',
            'reports/pdf/content/insumos_vencimiento',
            ['data' => $lotes, 'dias_alerta' => $diasAlerta]
        );
    }

    public function costosPruebasPdf()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data     = $this->reportModel->getCostosPruebas($busqueda);
        $sub      = $busqueda !== '' ? ('Búsqueda: ' . $busqueda) : 'Catálogo completo';
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('costos_pruebas'),
            'Costos de pruebas',
            $sub,
            'reports/pdf/content/costos_pruebas',
            ['data' => $data]
        );
    }

    public function valoresReferenciaPdf()
    {
        $busqueda = $this->request->getGet('busqueda') ?? '';
        $data     = $this->reportModel->getValoresReferencia($busqueda);
        $sub      = $busqueda !== '' ? ('Búsqueda: ' . $busqueda) : 'Todos';
        ReportPdfDocument::download(
            $this->safeReportPdfFilename('valores_referencia'),
            'Valores de referencia',
            $sub,
            'reports/pdf/content/valores_referencia',
            [
                'data'              => $data,
                'poblacion_labels'  => $this->poblacionLabelMap(),
            ]
        );
    }

    /**
     * id_poblacion / paciente_id => nombre (tabla poblacion, misma fuente que Config → Población).
     *
     * @return array<int, string>
     */
    private function poblacionLabelMap(): array
    {
        $map = [];
        foreach (model(PoblacionModel::class)->getAll() as $row) {
            $map[(int) ($row['id_poblacion'] ?? 0)] = (string) ($row['name'] ?? '');
        }

        return $map;
    }

    /**
     * Etiqueta legible para un id de población; si no está en catálogo, muestra el id numérico.
     */
    private function labelForPoblacionId($poblacionId, array $map): string
    {
        if ($poblacionId === null || $poblacionId === '') {
            return '—';
        }
        $id = (int) $poblacionId;
        if ($id === 0) {
            return '—';
        }
        if (isset($map[$id]) && $map[$id] !== '') {
            return $map[$id];
        }

        return (string) $id;
    }

    /**
     * Determina si una fila tiene valor mínimo/máximo configurado.
     * Considera "0" como valor válido.
     *
     * @param array<string, mixed> $item
     */
    private function hasValoresReferenciaConfigurados(array $item): bool
    {
        return $this->csvValue($item['valor_min'] ?? null) !== ''
            || $this->csvValue($item['valor_max'] ?? null) !== '';
    }

    /**
     * Normaliza valores para exportación CSV, preservando 0 como valor válido.
     */
    private function csvValue($value): string
    {
        if ($value === null) {
            return '';
        }
        $text = trim((string) $value);
        return $text;
    }

    /**
     * Helper para obtener etiqueta de sexo
     */
    private function getSexoLabel($sexo)
    {
        if ($sexo === 'masculino') return 'Masculino';
        if ($sexo === 'femenino') return 'Femenino';
        return 'Ambos';
    }

    private function normalizeSexoForCsv($sexo): string
    {
        $s = strtolower(trim((string) $sexo));
        if (in_array($s, ['m', 'masculino', 'hombre', 'varon'], true)) {
            return 'masculino';
        }
        if (in_array($s, ['f', 'femenino', 'mujer'], true)) {
            return 'femenino';
        }

        return 'ambos';
    }

    private function normalizeTipoPruebaCsv(string $raw): ?string
    {
        $key = $this->normalizeCostosCsvHeader($raw);
        if (in_array($key, ['COMPUESTO', 'COMPUESTA', 'COMPLEJA', 'COMPLEJO'], true)) {
            return 'compuesto';
        }
        if (in_array($key, ['SIMPLE', 'NO_COMPUESTA', 'NOCOMPUESTA'], true)) {
            return 'simple';
        }

        return null;
    }

    /**
     * @param list<string|null> $header
     * @return array{tipo: ?int, id_prueba: ?int, id_referencia: ?int, poblacion: ?int, sexo: ?int, valor_min: ?int, valor_max: ?int, unidad: ?int, tipo_resultado: ?int}
     */
    private function mapValoresReferenciaCsvColumns(array $header): array
    {
        $cols = [
            'tipo'           => null,
            'id_prueba'      => null,
            'id_referencia'  => null,
            'poblacion'      => null,
            'sexo'           => null,
            'valor_min'      => null,
            'valor_max'      => null,
            'unidad'         => null,
            'tipo_resultado' => null,
        ];

        foreach ($header as $i => $label) {
            $key = $this->normalizeCostosCsvHeader((string) $label);
            if (in_array($key, ['TIPO', 'TIPOPRUEBA'], true)) {
                $cols['tipo'] = (int) $i;
            } elseif (in_array($key, ['IDPRUEBA', 'IDPRIANACATEGORIA', 'IDPRUEBAPADRE'], true)) {
                $cols['id_prueba'] = (int) $i;
            } elseif (in_array($key, ['IDREFERENCIA', 'IDREF', 'IDSEC', 'IDPRIRESULTADOS', 'SECANACATEGORIAID', 'PRIRESULTADOSID'], true)) {
                $cols['id_referencia'] = (int) $i;
            } elseif (in_array($key, ['POBLACIONID', 'IDPOBLACION', 'PACIENTEID'], true)) {
                $cols['poblacion'] = (int) $i;
            } elseif ($key === 'POBLACION' && $cols['poblacion'] === null) {
                $cols['poblacion'] = (int) $i;
            } elseif ($key === 'SEXO') {
                $cols['sexo'] = (int) $i;
            } elseif (in_array($key, ['VALORMIN', 'MINIMO', 'MIN'], true)) {
                $cols['valor_min'] = (int) $i;
            } elseif (in_array($key, ['VALORMAX', 'MAXIMO', 'MAX'], true)) {
                $cols['valor_max'] = (int) $i;
            } elseif (in_array($key, ['UNIDAD', 'UMEDIDA', 'UM'], true)) {
                $cols['unidad'] = (int) $i;
            } elseif (in_array($key, ['IDTIPORESULTADO', 'TIPORESULTADOID', 'OPCIONID', 'IDOPCION'], true)) {
                $cols['tipo_resultado'] = (int) $i;
            } elseif ($key === 'ID' && $cols['id_referencia'] === null && $cols['id_prueba'] === null) {
                $cols['id_referencia'] = (int) $i;
            }
        }

        return $cols;
    }

    /**
     * Valida opcion_id (tipo de resultado) contra la tabla opciones del tenant.
     *
     * @param array<int, array<string, mixed>> $items
     * @param list<string>                     $errores
     * @return array<int, array<string, mixed>>
     */
    private function filterValoresReferenciaOpcionIds(array $items, array &$errores = []): array
    {
        if ($items === []) {
            return [];
        }

        $opcionIds = [];
        foreach ($items as $row) {
            if (array_key_exists('opcion_id', $row)) {
                $opcionIds[] = (int) $row['opcion_id'];
            }
        }

        if ($opcionIds === []) {
            $out = [];
            foreach ($items as $id => $row) {
                unset($row['_opcion_id']);
                $out[$id] = $row;
            }

            return $out;
        }

        $allowed = array_flip($this->labotestModel->existingOpcionesIds($opcionIds));
        $filtered = [];

        foreach ($items as $id => $row) {
            unset($row['_opcion_id']);
            if (array_key_exists('opcion_id', $row)) {
                $opcionId = (int) $row['opcion_id'];
                if (! isset($allowed[$opcionId])) {
                    $errores[] = "ID_TIPO_RESULTADO {$opcionId} no existe (fila ID_REFERENCIA {$id}).";
                    continue;
                }
            }
            $filtered[$id] = $row;
        }

        return $filtered;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param list<string>                     $errores
     * @return array<int, array<string, mixed>>
     */
    private function filterValoresReferenciaSecToTenant(array $items, array &$errores = []): array
    {
        if ($items === []) {
            return [];
        }

        $parentMap = $this->labotestModel->existingSecanacategoriaMap(array_keys($items));
        $filtered  = [];

        foreach ($items as $id => $row) {
            $id = (int) $id;
            if (! isset($parentMap[$id])) {
                $errores[] = "ID_REFERENCIA {$id} (compuesto) no existe en este laboratorio.";
                continue;
            }

            $expectedPri = (int) ($row['_pri_id'] ?? 0);
            if ($expectedPri > 0 && $expectedPri !== $parentMap[$id]) {
                $errores[] = "ID_REFERENCIA {$id}: no coincide con ID_PRUEBA {$expectedPri}.";
                continue;
            }

            unset($row['_pri_id']);
            $filtered[$id] = $row;
        }

        return $filtered;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param list<string>                     $errores
     * @return array<int, array<string, mixed>>
     */
    private function filterValoresReferenciaPriToTenant(array $items, array &$errores = []): array
    {
        if ($items === []) {
            return [];
        }

        $parentMap = $this->labotestModel->existingPriresultadosMap(array_keys($items));
        $filtered  = [];

        foreach ($items as $id => $row) {
            $id = (int) $id;
            if (! isset($parentMap[$id])) {
                $errores[] = "ID_REFERENCIA {$id} (simple) no existe en este laboratorio.";
                continue;
            }

            $expectedPri = (int) ($row['_pri_id'] ?? 0);
            if ($expectedPri > 0 && $expectedPri !== $parentMap[$id]) {
                $errores[] = "ID_REFERENCIA {$id}: no coincide con ID_PRUEBA {$expectedPri}.";
                continue;
            }

            unset($row['_pri_id']);
            $filtered[$id] = $row;
        }

        return $filtered;
    }
}
