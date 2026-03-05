<?php

namespace App\Controllers;

use App\Models\AppConfigModel;
use App\Models\EmployeeModel;
use App\Services\DashboardService;

class Home extends SecureArea
{
    protected ?string $moduleId = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $dashboardService = new DashboardService();
        $stats = $dashboardService->getStats();
        $recentRegisters = $dashboardService->getRecentRegisters(8);
        $chartData = $dashboardService->getChartDataLast7Days();
        $donutData = $dashboardService->getRegistrosByPeriodo();
        $ingresosMes = $dashboardService->getIngresosDelMes();
        $ingresosPorMeses = $dashboardService->getIngresosPorMeses(6);
        $pendientes = $dashboardService->getPendientesCobro();
        $alertasInsumos = $dashboardService->getAlertasInsumos();
        $topDoctores = $dashboardService->getTopDoctores(5);
        $currencySym = model(AppConfigModel::class)->getValue('currency_symbol') ?: '$';

        return view('home', [
            'allowed_modules'   => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'current_module'   => 'home',
            'dashboard_stats'   => $stats,
            'recent_registers'  => $recentRegisters,
            'chart_data'       => $chartData,
            'donut_data'       => $donutData,
            'ingresos_mes'     => $ingresosMes,
            'ingresos_por_meses'=> $ingresosPorMeses,
            'pendientes'       => $pendientes,
            'alertas_insumos'  => $alertasInsumos,
            'top_doctores'     => $topDoctores,
            'currency_symbol' => $currencySym,
        ]);
    }

    public function logout()
    {
        $employeeModel = model(EmployeeModel::class);
        $employeeModel->logout();
        return redirect()->to(site_url('login'));
    }
}
