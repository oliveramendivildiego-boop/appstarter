<?php

namespace App\Models;

use App\Services\RegisterService;
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
        return $this->syncPendingCommissionForRegistro(
            $registroId,
            $doctorId,
            $totalAmount,
            $commissionPercent,
            $commissionPercent > 0
        );
    }

    /**
     * Nombre de tabla con prefijo de tenant.
     */
    private function commissionsTable(): string
    {
        return $this->db->prefixTable($this->table);
    }

    /**
     * Deja una sola comisión pendiente por orden (elimina duplicados).
     */
    private function deduplicatePendingForRegistro(int $registroId): int
    {
        if ($registroId < 1) {
            return 0;
        }

        $table = $this->commissionsTable();
        $this->db->query("
            DELETE c1 FROM {$table} c1
            INNER JOIN {$table} c2
                ON c1.registro_id = c2.registro_id
                AND c1.status = 0
                AND c2.status = 0
                AND c1.commission_id < c2.commission_id
            WHERE c1.registro_id = ?
        ", [$registroId]);

        return (int) $this->db->affectedRows();
    }

    /**
     * Elimina comisiones pendientes duplicadas en toda la tabla.
     */
    public function deduplicateAllPendingCommissions(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $table = $this->commissionsTable();
        $this->db->query("
            DELETE c1 FROM {$table} c1
            INNER JOIN {$table} c2
                ON c1.registro_id = c2.registro_id
                AND c1.status = 0
                AND c2.status = 0
                AND c1.commission_id < c2.commission_id
        ");

        return (int) $this->db->affectedRows();
    }

    /**
     * Limpia duplicados antes de mostrar listados (sin recalcular montos).
     */
    public function prepareCommissionsForDisplay(): void
    {
        $this->deduplicateAllPendingCommissions();
    }

    /**
     * Mantiene la comisión pendiente alineada con el total real de la orden.
     * Las comisiones ya pagadas quedan como snapshot histórico.
     */
    public function syncPendingCommissionForRegistro(
        int $registroId,
        int $doctorId,
        float $totalAmount,
        float $commissionPercent,
        bool $enabled
    ): bool {
        if (!$this->tableExists()) {
            return false;
        }

        $table = $this->table;

        if (!$enabled || $doctorId < 1 || $totalAmount <= 0 || $commissionPercent <= 0) {
            $this->db->table($table)
                ->where('registro_id', $registroId)
                ->where('status', 0)
                ->delete();

            return true;
        }

        $commissionAmount = round(($totalAmount * $commissionPercent) / 100, 2);
        $data = [
            'doctor_id'          => $doctorId,
            'registro_id'        => $registroId,
            'total_amount'       => $totalAmount,
            'commission_percent' => $commissionPercent,
            'commission_amount'  => $commissionAmount,
        ];

        $pending = $this->db->table($table)
            ->where('registro_id', $registroId)
            ->where('status', 0)
            ->orderBy('commission_id', 'DESC')
            ->get()
            ->getRow();

        if ($pending) {
            $ok = $this->db->table($table)
                ->where('commission_id', (int) $pending->commission_id)
                ->update($data) !== false;
        } else {
            $hasPaid = $this->db->table($table)
                ->where('registro_id', $registroId)
                ->where('status', 1)
                ->countAllResults() > 0;
            if ($hasPaid) {
                return true;
            }

            $data['created_date'] = RegisterService::mysqlNowForReport();
            $data['status']       = 0;
            $ok = $this->db->table($table)->insert($data) !== false;
        }

        $this->deduplicatePendingForRegistro($registroId);

        return $ok;
    }

    /**
     * Expresión SQL para nombre de paciente (tolerante a datos faltantes).
     */
    private function patientNameSelect(string $peopleAlias = 'pe'): string
    {
        return 'TRIM(CONCAT(COALESCE(' . $peopleAlias . '.first_name, ""), " ", COALESCE(' . $peopleAlias . '.last_name_fa, ""), " ", COALESCE(' . $peopleAlias . '.last_name_mom, "")))';
    }

    /**
     * Elimina comisiones pendientes huérfanas (orden ya no pertenece al doctor).
     */
    private function removeOrphanPendingCommissionsForDoctor(int $doctorId): void
    {
        if (!$this->tableExists() || $doctorId < 1) {
            return;
        }

        $pending = $this->db->table($this->table)
            ->where('doctor_id', $doctorId)
            ->where('status', 0)
            ->get()
            ->getResult();
        foreach ($pending as $row) {
            $registroId = (int) ($row->registro_id ?? 0);
            if ($registroId < 1) {
                $this->db->table($this->table)->where('commission_id', (int) $row->commission_id)->delete();
                continue;
            }

            $registro = $this->db->table('registro')
                ->select('doctor_id')
                ->where('registro_id', $registroId)
                ->get()
                ->getRow();

            if ($registro === null || (int) ($registro->doctor_id ?? 0) !== $doctorId) {
                $this->db->table($this->table)->where('commission_id', (int) $row->commission_id)->delete();
            }
        }
    }

    /**
     * Elimina duplicados pendientes de todas las órdenes de un doctor.
     */
    private function deduplicatePendingForDoctor(int $doctorId): void
    {
        if ($doctorId < 1) {
            return;
        }

        $rows = $this->db->table($this->table)
            ->distinct()
            ->select('registro_id')
            ->where('doctor_id', $doctorId)
            ->where('status', 0)
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $this->deduplicatePendingForRegistro((int) $row->registro_id);
        }
    }

    /**
     * Recalcula comisiones pendientes de todas las órdenes de un doctor.
     * Se usa al activar comisión o cambiar el porcentaje desde /doctors.
     */
    public function syncPendingCommissionsForDoctor(int $doctorId, bool $enabled, float $commissionPercent): void
    {
        if (!$this->tableExists() || $doctorId < 1) {
            return;
        }

        $this->deduplicatePendingForDoctor($doctorId);

        $r = $this->db->prefixTable('registro');
        $p = $this->db->prefixTable('pago');

        $builder = $this->db->table('registro')
            ->select("{$r}.registro_id, COALESCE(MAX({$p}.total), 0) AS total", false)
            ->join('pago', "{$r}.registro_id = {$p}.registro_id", 'left')
            ->where("{$r}.doctor_id", $doctorId)
            ->groupBy("{$r}.registro_id");

        $registroFields = $this->db->getFieldNames('registro');
        if (in_array('anulado', $registroFields, true)) {
            $builder->where("COALESCE({$r}.anulado, 0) = 0", null, false);
        }

        $processed = [];
        foreach ($builder->get()->getResult() as $row) {
            $registroId = (int) $row->registro_id;
            if ($registroId < 1 || isset($processed[$registroId])) {
                continue;
            }
            $processed[$registroId] = true;

            $this->syncPendingCommissionForRegistro(
                $registroId,
                $doctorId,
                (float) $row->total,
                $commissionPercent,
                $enabled
            );
        }

        $this->removeOrphanPendingCommissionsForDoctor($doctorId);
    }

    /**
     * Sincroniza comisiones pendientes de todos los doctores con comisión activa.
     */
    public function syncAllEnabledDoctorsCommissions(): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $this->deduplicateAllPendingCommissions();

        $doctorFields = $this->db->getFieldNames('doctors');
        $hasCommissionFlag = in_array('has_commission', $doctorFields, true);

        $builder = $this->db->table('doctors')
            ->select('doctor_id, commission_percent')
            ->where('deleted', 0)
            ->where('commission_percent >', 0);

        if ($hasCommissionFlag) {
            $builder->where('has_commission', 1);
        }

        foreach ($builder->get()->getResult() as $doctor) {
            $this->syncPendingCommissionsForDoctor(
                (int) $doctor->doctor_id,
                true,
                (float) $doctor->commission_percent
            );
        }

        $this->deduplicateAllPendingCommissions();
    }

    /**
     * Subconsulta: una fila por orden y estado (la comisión más reciente).
     */
    private function latestCommissionSubquery(string $alias = 'latest'): string
    {
        $table = $this->commissionsTable();

        return "(
            SELECT registro_id, status, MAX(commission_id) AS commission_id
            FROM {$table}
            GROUP BY registro_id, status
        ) {$alias}";
    }

    /**
     * Obtiene comisiones agrupadas por doctor con paginación y filtros
     */
    public function getCommissionsGroupedByDoctor(int $limit = 20, int $offset = 0, string $status = ''): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $table = $this->commissionsTable();
        $doctors = $this->db->prefixTable('doctors');
        $latest = $this->latestCommissionSubquery('latest');
        $statusInt = $status !== '' ? (int) $status : null;
        $having = $statusInt !== null
            ? "HAVING SUM(CASE WHEN dc.status = {$statusInt} THEN 1 ELSE 0 END) > 0"
            : '';
        $orderColumn = ($status === '' || $status === '0') ? 'total_pending' : 'total_pruebas_amount';

        $sql = "
            SELECT
                dc.doctor_id,
                d.name AS doctor_name,
                d.speciality,
                d.commission_percent AS doctor_commission_percent,
                COUNT(dc.commission_id) AS total_commissions,
                COALESCE(SUM(dc.total_amount), 0) AS total_pruebas_amount,
                COALESCE(SUM(dc.commission_amount), 0) AS total_commission_amount,
                COALESCE(SUM(CASE WHEN dc.status = 0 THEN dc.commission_amount ELSE 0 END), 0) AS total_pending,
                COALESCE(SUM(CASE WHEN dc.status = 1 THEN dc.commission_amount ELSE 0 END), 0) AS total_paid,
                COUNT(CASE WHEN dc.status = 0 THEN 1 END) AS pending_count,
                COUNT(CASE WHEN dc.status = 1 THEN 1 END) AS paid_count
            FROM {$table} dc
            INNER JOIN {$latest} ON latest.commission_id = dc.commission_id
            JOIN {$doctors} d ON d.doctor_id = dc.doctor_id
            GROUP BY dc.doctor_id, d.name, d.speciality, d.commission_percent
            {$having}
            ORDER BY {$orderColumn} DESC
            LIMIT ? OFFSET ?
        ";

        return $this->db->query($sql, [$limit, $offset])->getResult();
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
                ' . $this->patientNameSelect('pe') . ' as paciente_nombre
            ')
            ->from($this->table . ' dc')
            ->join('registro r', 'r.registro_id = dc.registro_id', 'left')
            ->join('people pe', 'pe.person_id = r.person_id', 'left')
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

        $table = $this->commissionsTable();
        $registro = $this->db->prefixTable('registro');
        $people = $this->db->prefixTable('people');
        $patientName = $this->patientNameSelect('pe');
        $statusSql = $status !== '' ? ' AND dc.status = ' . (int) $status : '';

        $sql = "
            SELECT
                dc.commission_id,
                dc.registro_id,
                dc.doctor_id,
                dc.total_amount,
                dc.commission_percent,
                dc.commission_amount,
                dc.created_date,
                dc.status,
                dc.paid_date,
                dc.notes,
                r.person_id,
                {$patientName} AS paciente_nombre
            FROM {$table} dc
            INNER JOIN (
                SELECT registro_id, MAX(commission_id) AS commission_id
                FROM {$table}
                WHERE doctor_id = ?
                {$statusSql}
                GROUP BY registro_id
            ) latest ON latest.commission_id = dc.commission_id
            LEFT JOIN {$registro} r ON r.registro_id = dc.registro_id
            LEFT JOIN {$people} pe ON pe.person_id = r.person_id
            WHERE dc.doctor_id = ?
            ORDER BY dc.created_date DESC
        ";

        return $this->db->query($sql, [$doctorId, $doctorId])->getResult();
    }

    /**
     * Cuenta doctores con comisiones por estado
     */
    public function countDoctorsByStatus(string $status = ''): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $table = $this->commissionsTable();
        $latest = $this->latestCommissionSubquery('latest');
        $statusInt = $status !== '' ? (int) $status : null;
        $having = $statusInt !== null
            ? "HAVING SUM(CASE WHEN dc.status = {$statusInt} THEN 1 ELSE 0 END) > 0"
            : '';

        $sql = "
            SELECT dc.doctor_id
            FROM {$table} dc
            INNER JOIN {$latest} ON latest.commission_id = dc.commission_id
            GROUP BY dc.doctor_id
            {$having}
        ";

        return count($this->db->query($sql)->getResult());
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

        $table = $this->commissionsTable();
        $latest = $this->latestCommissionSubquery('latest');

        $result = $this->db->query("
            SELECT
                COALESCE(SUM(CASE WHEN dc.status = 0 THEN dc.commission_amount ELSE 0 END), 0) AS total_pending,
                COALESCE(SUM(CASE WHEN dc.status = 1 THEN dc.commission_amount ELSE 0 END), 0) AS total_paid,
                COALESCE(SUM(dc.commission_amount), 0) AS total_commissions,
                COUNT(CASE WHEN dc.status = 0 THEN 1 END) AS pending_count,
                COUNT(CASE WHEN dc.status = 1 THEN 1 END) AS paid_count
            FROM {$table} dc
            INNER JOIN {$latest} ON latest.commission_id = dc.commission_id
            WHERE dc.doctor_id = ?
        ", [$doctorId])->getRow();

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
            'paid_date' => RegisterService::mysqlNowForReport(),
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
