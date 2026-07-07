<?php

namespace App\Controllers;

use App\Models\DoctorCommissionModel;
use App\Models\DoctorModel;
use CodeIgniter\HTTP\ResponseInterface;

class DoctorCommissions extends SecureArea
{
    protected ?string $moduleId = 'doctor_commissions';

    protected DoctorCommissionModel $commissionModel;
    protected DoctorModel $doctorModel;

    public function __construct()
    {
        parent::__construct();
        $this->commissionModel = model(DoctorCommissionModel::class);
        $this->doctorModel = model(DoctorModel::class);
    }

    public function index()
    {
        try {
            $this->commissionModel->syncAllEnabledDoctorsCommissions();
        } catch (\Throwable $e) {
            log_message('error', 'DoctorCommissions::index syncAllEnabledDoctorsCommissions ' . $e->getMessage());
        }

        $this->commissionModel->prepareCommissionsForDisplay();

        $perPage = 20;
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;
        $status = $this->request->getGet('status') ?? '';

        $commissions = $this->commissionModel->getCommissionsGroupedByDoctor($perPage, $offset, $status);
        $total = $this->commissionModel->countDoctorsByStatus($status);
        $totalPages = max(1, (int) ceil($total / $perPage));

        return view('doctor_commissions/manage', [
            'commissions' => $commissions,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'status' => $status,
            'current_module' => 'doctor_commissions',
            'allowed_modules' => $this->allowed_modules,
            'user_info' => $this->user_info,
        ]);
    }

    public function bulkPay($doctorId = null)
    {
        if (!$doctorId) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'ID de doctor no válido');
        }

        $doctor = $this->doctorModel->find($doctorId);
        if (!$doctor) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'Doctor no encontrado');
        }

        // Obtener comisiones pendientes del doctor
        $pendingCommissions = $this->commissionModel->getCommissionsByDoctorId($doctorId, '0');
        
        if (empty($pendingCommissions)) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'No hay comisiones pendientes para este doctor');
        }

        return view('doctor_commissions/bulk_pay', [
            'doctor' => $doctor,
            'commissions' => $pendingCommissions,
            'current_module' => 'doctor_commissions',
            'allowed_modules' => $this->allowed_modules,
            'user_info' => $this->user_info,
        ]);
    }

    public function details($doctorId = null)
    {
        if (!$doctorId) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'ID de doctor no válido');
        }

        $doctor = $this->doctorModel->find($doctorId);
        if (!$doctor) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'Doctor no encontrado');
        }

        try {
            $this->commissionModel->prepareCommissionsForDisplay();
        } catch (\Throwable $e) {
            log_message('error', 'DoctorCommissions::details prepareCommissionsForDisplay ' . $e->getMessage());
        }

        $status = $this->request->getGet('status') ?? '';
        $commissions = $this->commissionModel->getCommissionsByDoctorId($doctorId, $status);

        return view('doctor_commissions/details', [
            'doctor' => $doctor,
            'commissions' => $commissions,
            'status' => $status,
            'current_module' => 'doctor_commissions',
            'allowed_modules' => $this->allowed_modules,
            'user_info' => $this->user_info,
        ]);
    }

    public function pay($commissionId = null)
    {
        if (!$commissionId) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'ID de comisión no válido');
        }

        $commission = $this->commissionModel->find($commissionId);
        if (!$commission) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'Comisión no encontrada');
        }

        if ($commission->status == 1) {
            return redirect()->to(site_url('doctor_commissions'))->with('error', 'Esta comisión ya está pagada');
        }

        $doctor = $this->doctorModel->find($commission->doctor_id);
        
        return view('doctor_commissions/pay', [
            'commission' => $commission,
            'doctor' => $doctor,
            'current_module' => 'doctor_commissions',
            'allowed_modules' => $this->allowed_modules,
            'user_info' => $this->user_info,
        ]);
    }

    public function processPayment()
    {
        $commissionId = $this->request->getPost('commission_id');
        $notes = $this->request->getPost('notes') ?? '';

        if (!$commissionId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID de comisión no válido'
            ])->setStatusCode(400);
        }

        $commission = $this->commissionModel->find($commissionId);
        if (!$commission) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Comisión no encontrada'
            ])->setStatusCode(404);
        }

        if ($commission->status == 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Esta comisión ya está pagada'
            ])->setStatusCode(400);
        }

        if ($this->commissionModel->markAsPaid($commissionId, $notes)) {
            // Registrar en auditoría
            \App\Models\AuditoriaModel::log('doctor_commissions', 'pagar', $commissionId);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Comisión marcada como pagada correctamente',
                'redirect_url' => site_url('doctor_commissions')
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al marcar la comisión como pagada'
            ])->setStatusCode(500);
        }
    }

    public function processBulkPayment()
    {
        $commissionIds = $this->request->getPost('commission_ids');
        $notes = $this->request->getPost('notes') ?? '';

        if (empty($commissionIds) || !is_array($commissionIds)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Seleccione al menos una comisión para pagar'
            ])->setStatusCode(400);
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($commissionIds as $commissionId) {
            $commission = $this->commissionModel->find($commissionId);
            if ($commission && $commission->status == 0) {
                if ($this->commissionModel->markAsPaid($commissionId, $notes)) {
                    $successCount++;
                    \App\Models\AuditoriaModel::log('doctor_commissions', 'pagar', $commissionId);
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }

        $message = "Proceso completado. {$successCount} comisiones pagadas correctamente";
        if ($errorCount > 0) {
            $message .= ". {$errorCount} comisiones con errores";
        }

        return $this->response->setJSON([
            'success' => $successCount > 0,
            'message' => $message,
            'redirect_url' => site_url('doctor_commissions')
        ]);
    }

    public function delete($commissionId = null)
    {
        if (!$commissionId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID de comisión no válido'
            ])->setStatusCode(400);
        }

        $commission = $this->commissionModel->find($commissionId);
        if (!$commission) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Comisión no encontrada'
            ])->setStatusCode(404);
        }

        if ($commission->status == 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se puede eliminar una comisión ya pagada'
            ])->setStatusCode(400);
        }

        if ($this->commissionModel->delete($commissionId)) {
            \App\Models\AuditoriaModel::log('doctor_commissions', 'eliminar', $commissionId);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Comisión eliminada correctamente'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al eliminar la comisión'
            ])->setStatusCode(500);
        }
    }
}
