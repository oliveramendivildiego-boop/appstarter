<?php

namespace App\Models;

use CodeIgniter\Model;

class DoctorCommissionModel extends Model
{
    protected $table            = 'doctor_commissions';
    protected $primaryKey       = 'commission_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = ['doctor_id', 'registro_id', 'total_amount', 'commission_percent', 'commission_amount', 'created_date', 'paid_date', 'status', 'notes'];

    /**
     * Verifica si la tabla existe
     */
    public function tableExists(): bool
    {
        return $this->db->tableExists($this->table);
    }

    /**
     * Crea una comisión cuando se realiza una prueba
     */
    public function createCommission(int $doctorId, int $registroId, float $totalAmount, float $commissionPercent): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $commissionAmount = ($totalAmount * $commissionPercent) / 100;

        $data = [
            'doctor_id'         => $doctorId,
            'registro_id'       => $registroId,
            'total_amount'      => $totalAmount,
            'commission_percent'=> $commissionPercent,
            'commission_amount' => $commissionAmount,
            'created_date'      => date('Y-m-d H:i:s'),
            'status'            => 0, // pendiente
        ];

        return $this->insert($data) !== false;
    }

    /**
     * Obtiene comisiones agrupadas por doctor con paginación y filtros
     */
    public function getCommissionsGroupedByDoctor(int $limit = 20, int $offset = 0, string $status = ''): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $builder = $this->select('
                dc.doctor_id,
                d.name as doctor_name,
                d.speciality,
                COUNT(dc.commission_id) as total_commissions,
                SUM(dc.commission_amount) as total_amount,
                SUM(CASE WHEN dc.status = 0 THEN dc.commission_amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN dc.status = 1 THEN dc.commission_amount ELSE 0 END) as total_paid,
                COUNT(CASE WHEN dc.status = 0 THEN 1 END) as pending_count,
                COUNT(CASE WHEN dc.status = 1 THEN 1 END) as paid_count,
                GROUP_CONCAT(CONCAT(dc.registro_id, ":", dc.commission_amount) ORDER BY dc.created_date DESC SEPARATOR "|") as registros_detalle
            ')
            ->from($this->table . ' dc')
            ->join('doctors d', 'd.doctor_id = dc.doctor_id')
            ->groupBy('dc.doctor_id, d.name, d.speciality');

        if ($status !== '') {
            $builder->having("SUM(CASE WHEN dc.status = {$status} THEN 1 ELSE 0 END) > 0");
        }

        return $builder->orderBy('total_amount', 'DESC')
                       ->limit($limit, $offset)
                       ->get()
                       ->getResult();
    }

    /**
     * Obtiene comisiones con paginación y filtros (método original)
     */
    public function getCommissionsWithPagination(int $limit = 20, int $offset = 0, string $status = ''): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $builder = $this->select('dc.*, d.name as doctor_name, r.registro_id, p.total as registro_total')
                    ->from($this->table . ' dc')
                    ->join('doctors d', 'd.doctor_id = dc.doctor_id')
                    ->join('registro r', 'r.registro_id = dc.registro_id')
                    ->join('pago p', 'p.registro_id = r.registro_id');

        if ($status !== '') {
            $builder->where('dc.status', $status);
        }

        return $builder->orderBy('dc.created_date', 'DESC')
                       ->limit($limit, $offset)
                       ->get()
                       ->getResult();
    }

    /**
     * Obtiene comisiones pagadas con detalles completos para el portal del doctor
     */
    public function getPaidCommissionsWithDetails(int $doctorId, int $limit = 5): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $builder = $this->select('
                dc.commission_id,
                dc.registro_id,
                dc.total_amount,
                dc.commission_percent,
                dc.commission_amount,
                dc.created_date,
                dc.paid_date,
                dc.notes,
                r.pruebas,
                r.ingreso as fecha_registro,
                CONCAT(pe.first_name, " ", pe.last_name_fa, " ", pe.last_name_mom) as paciente_nombre
            ')
            ->from($this->table . ' dc')
            ->join('registro r', 'r.registro_id = dc.registro_id')
            ->join('people pe', 'pe.person_id = r.person_id')
            ->where('dc.doctor_id', $doctorId)
            ->where('dc.status', 1)
            ->orderBy('dc.paid_date', 'DESC')
            ->limit($limit);

        return $builder->get()->getResult();
    }

    /**
     * Obtiene comisiones individuales de un doctor específico
     */
    public function getCommissionsByDoctorId(int $doctorId, string $status = ''): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $builder = $this->select('dc.commission_id, dc.registro_id, dc.doctor_id, dc.total_amount, dc.commission_percent, dc.commission_amount, dc.created_date, dc.status, dc.paid_date, dc.notes, r.person_id, CONCAT(pe.first_name, " ", pe.last_name_fa, " ", pe.last_name_mom) as paciente_nombre')
                    ->from($this->table . ' dc')
                    ->join('registro r', 'r.registro_id = dc.registro_id')
                    ->join('people pe', 'pe.person_id = r.person_id')
                    ->where('dc.doctor_id', $doctorId)
                    ->groupBy('dc.commission_id');

        if ($status !== '') {
            $builder->where('dc.status', $status);
        }

        return $builder->orderBy('dc.created_date', 'DESC')
                       ->get()
                       ->getResult();
    }

    /**
     * Cuenta doctores con comisiones por estado
     */
    public function countDoctorsByStatus(string $status = ''): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $builder = $this->select('COUNT(DISTINCT dc.doctor_id) as count')
                    ->from($this->table . ' dc');
        
        if ($status !== '') {
            $builder->where('dc.status', $status);
        }

        $result = $builder->get()->getRow();
        return $result ? (int) $result->count : 0;
    }

    /**
     * Cuenta comisiones por estado
     */
    public function countByStatus(string $status = ''): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $builder = $this->db->table($this->table);
        
        if ($status !== '') {
            $builder->where('status', $status);
        }

        return $builder->countAllResults();
    }

    /**
     * Obtiene comisiones de un doctor (corregido para aceptar parámetros)
     */
    public function getCommissionsByDoctor(int $doctorId, string $status = '', int $limit = 0): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $builder = $this->where('doctor_id', $doctorId);
        
        if ($status !== '') {
            $builder->where('status', $status);
        }

        $builder = $builder->orderBy('created_date', 'DESC');
        
        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Obtiene resumen de comisiones de un doctor
     */
    public function getCommissionSummaryByDoctor(int $doctorId): object
    {
        if (!$this->tableExists()) {
            return (object) [
                'total_pending' => 0,
                'total_paid' => 0,
                'total_commissions' => 0,
                'pending_count' => 0,
                'paid_count' => 0
            ];
        }

        $result = $this->select('
                SUM(CASE WHEN status = 0 THEN commission_amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = 1 THEN commission_amount ELSE 0 END) as total_paid,
                SUM(commission_amount) as total_commissions,
                COUNT(CASE WHEN status = 0 THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 1 THEN 1 END) as paid_count
            ')
            ->where('doctor_id', $doctorId)
            ->get()
            ->getRow();

        return $result ?: (object) [
            'total_pending' => 0,
            'total_paid' => 0,
            'total_commissions' => 0,
            'pending_count' => 0,
            'paid_count' => 0
        ];
    }

    /**
     * Marca comisión como pagada
     */
    public function markAsPaid(int $commissionId, string $notes = ''): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        return $this->update($commissionId, [
            'status' => 1,
            'paid_date' => date('Y-m-d H:i:s'),
            'notes' => $notes
        ]) !== false;
    }

    /**
     * Obtiene comisiones pendientes de pago
     */
    public function getPendingCommissions(): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        return $this->select('dc.*, d.name as doctor_name, r.registro_id, p.total as registro_total')
                    ->from($this->table . ' dc')
                    ->join('doctors d', 'd.doctor_id = dc.doctor_id')
                    ->join('registro r', 'r.registro_id = dc.registro_id')
                    ->join('pago p', 'p.registro_id = r.registro_id')
                    ->where('dc.status', 0)
                    ->orderBy('dc.created_date', 'DESC')
                    ->findAll();
    }
}
