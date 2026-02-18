<?php

namespace App\Controllers;

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

        return view('home', [
            'allowed_modules'  => $this->allowed_modules,
            'user_info'        => $this->user_info,
            'dashboard_stats'   => $stats,
            'recent_registers' => $recentRegisters,
        ]);
    }

    public function logout()
    {
        $employeeModel = model(EmployeeModel::class);
        $employeeModel->logout();
        return redirect()->to(site_url('login'));
    }
}
