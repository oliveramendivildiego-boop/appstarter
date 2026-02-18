<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\DoctorModel;
use App\Models\EmployeeModel;
use App\Models\RegisterModel;

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
}
