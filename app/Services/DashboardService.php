<?php

namespace App\Services;

use App\Models\AppConfigModel;
use App\Models\CustomerModel;
use App\Models\DoctorModel;
use App\Models\EmployeeModel;
use App\Models\ReactivoModel;
use App\Models\RegisterModel;
use App\Models\ReportModel;

/**
 * Datos para el dashboard del sistema
 */
class DashboardService
{
    public function getStats(): array
    {
        $customerModel = model(CustomerModel::class);
        $doctorModel   = model(DoctorModel::class);
        $registerModel = model(RegisterModel::class);
        $employeeModel = model(EmployeeModel::class);

        $r = $registerModel->getRegistroTable();
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');

        $registersToday = $registerModel->countByDate($today, $today);
        $registersWeek  = $registerModel->countByDate($weekStart, $today);
        $registersMonth = $registerModel->countByDate($monthStart, $today);

        return [
            'customers'        => $customerModel->countAll(),
            'doctors'          => $doctorModel->countAll(),
            'employees'         => $employeeModel->countAll(),
            'registers_total'  => $registerModel->countAll(),
            'registers_today'  => $registersToday,
            'registers_week'   => $registersWeek,
            'registers_month'  => $registersMonth,
        ];
    }

    public function getRecentRegisters(int $limit = 10): array
    {
        $registerModel = model(RegisterModel::class);
        try {
            return $registerModel->getRecentRegisters($limit, 0);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Datos para gráfica de registros/ingresos por día (últimos 7 días)
     */
    public function getChartDataLast7Days(): array
    {
        $reportModel = model(\App\Models\ReportModel::class);
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-6 days'));
        try {
            return $reportModel->getIngresosByDateRange($startDate, $endDate);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Datos para gráfica donut: registros por período (hoy, semana, mes)
     */
    public function getRegistrosByPeriodo(): array
    {
        $registerModel = model(RegisterModel::class);
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');
        try {
            $hoy = $registerModel->countByDate($today, $today);
            $semana = $registerModel->countByDate($weekStart, $today);
            $mes = $registerModel->countByDate($monthStart, $today);
            return [
                ['label' => 'Hoy', 'value' => $hoy, 'color' => '#6366f1'],
                ['label' => 'Esta semana', 'value' => $semana - $hoy, 'color' => '#10b981'],
                ['label' => 'Este mes', 'value' => max(0, $mes - $semana), 'color' => '#94a3b8'],
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Ingresos cobrados del mes actual
     */
    public function getIngresosDelMes(): float
    {
        $reportModel = model(ReportModel::class);
        $monthStart = date('Y-m-01');
        $today = date('Y-m-d');
        try {
            $row = $reportModel->getTotalesByDateRange($monthStart, $today);
            return (float) ($row->total_cobrado ?? 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Ingresos cobrados por mes (últimos N meses) para gráfica de barras
     * @return array [['mes'=>'2025-01','label'=>'Ene 2025','cobrado'=>1234.56], ...]
     */
    public function getIngresosPorMeses(int $numMeses = 6): array
    {
        $reportModel = model(ReportModel::class);
        $result = [];
        $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        for ($i = $numMeses - 1; $i >= 0; $i--) {
            $fecha = strtotime("-{$i} months");
            $inicio = date('Y-m-01', $fecha);
            $fin = date('Y-m-t', $fecha);
            try {
                $row = $reportModel->getTotalesByDateRange($inicio, $fin);
                $cobrado = (float) ($row->total_cobrado ?? 0);
            } catch (\Throwable $e) {
                $cobrado = 0.0;
            }
            $result[] = [
                'mes'     => date('Y-m', $fecha),
                'label'   => $meses[(int) date('n', $fecha) - 1] . ' ' . date('Y', $fecha),
                'cobrado' => $cobrado,
            ];
        }
        return $result;
    }

    /**
     * Total pendiente por cobrar (registros con saldo > 0 en últimos 2 años)
     */
    public function getPendientesCobro(): array
    {
        $reportModel = model(ReportModel::class);
        $startDate = date('Y-m-d', strtotime('-2 years'));
        $endDate = date('Y-m-d');
        try {
            $row = $reportModel->getTotalesPagos($startDate, $endDate);
            $pendientes = $reportModel->getPendientesPago($startDate, $endDate);
            return [
                'total_pendiente' => max(0, (float) ($row->total_pendiente ?? 0)),
                'cantidad'        => count($pendientes),
            ];
        } catch (\Throwable $e) {
            return ['total_pendiente' => 0.0, 'cantidad' => 0];
        }
    }

    /**
     * Alertas de insumos: vencidos y por vencer (según config días_alerta_vencimiento)
     * @return array ['vencidos'=>int, 'por_vencer'=>int, 'items'=>[...] top 10 para mostrar]
     */
    public function getAlertasInsumos(): array
    {
        $reactivoModel = model(ReactivoModel::class);
        $appConfig    = model(AppConfigModel::class);
        $diasAlerta   = max(1, (int) ($appConfig->getValue('dias_alerta_vencimiento') ?: 40));

        $lotes = $reactivoModel->getTodosLotesParaReporte();
        $hoy   = date('Y-m-d');
        $enX   = date('Y-m-d', strtotime("+{$diasAlerta} days"));

        $vencidos = 0;
        $porVencer = 0;
        $items = [];

        foreach ($lotes as $l) {
            $venc = $l['fecha_vencimiento'] ?? null;
            if (!$venc) continue;
            $diff = (strtotime($venc) - strtotime($hoy)) / 86400;
            $dias = (int) round($diff);
            if ($diff < 0) {
                $vencidos++;
                $items[] = array_merge($l, ['estado' => 'vencido', 'dias_restantes' => $dias]);
            } elseif ($venc <= $enX) {
                $porVencer++;
                $items[] = array_merge($l, ['estado' => 'por_vencer', 'dias_restantes' => $dias]);
            }
        }
        usort($items, function ($a, $b) {
            if ($a['estado'] !== $b['estado']) return $a['estado'] === 'vencido' ? -1 : 1;
            return ($a['dias_restantes'] ?? 999) <=> ($b['dias_restantes'] ?? 999);
        });
        $items = array_slice($items, 0, 10);

        return [
            'vencidos'   => $vencidos,
            'por_vencer' => $porVencer,
            'total'      => $vencidos + $porVencer,
            'items'      => $items,
            'dias_alerta'=> $diasAlerta,
        ];
    }

    /**
     * Top doctores por ingresos (este mes)
     */
    public function getTopDoctores(int $limit = 5): array
    {
        $reportModel = model(ReportModel::class);
        $monthStart = date('Y-m-01');
        $today = date('Y-m-d');
        try {
            return array_slice(
                $reportModel->getRegistrosByDoctor($monthStart, $today),
                0,
                $limit
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Serie diaria de cierres de pagos (por día en que se registró el cierre en el sistema).
     * Suma los totales del snapshot de cada cierre ese día.
     *
     * @return list<array{fecha: string, cierres: int, cobrado: float, facturado: float}>
     */
    public function getCierresPagosSeriesUltimosDias(int $dias = 30): array
    {
        $dias = max(7, min(90, $dias));
        try {
            $cierreModel = model(\App\Models\ReportePagosCierreModel::class);
            $desde       = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
            $map         = $cierreModel->getAgregadoPorDiaRegistro($desde);
        } catch (\Throwable $e) {
            $map = [];
        }
        $out = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $m = $map[$d] ?? null;
            $out[] = [
                'fecha'     => $d,
                'cierres'   => (int) ($m['cierres'] ?? 0),
                'cobrado'   => (float) ($m['cobrado'] ?? 0),
                'facturado' => (float) ($m['facturado'] ?? 0),
            ];
        }

        return $out;
    }
}
