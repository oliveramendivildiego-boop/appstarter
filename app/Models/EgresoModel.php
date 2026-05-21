<?php

namespace App\Models;

use App\Services\RegisterService;
use CodeIgniter\Model;

class EgresoModel extends Model
{
    protected $table            = 'egresos';
    protected $primaryKey       = 'egreso_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['monto', 'tipopago', 'tipo_movimiento', 'desglose', 'fecha', 'deleted', 'created_at', 'updated_at'];

    /**
     * @return list<array<string,mixed>>
     */
    public function getFiltered(string $search = '', ?string $startDate = null, ?string $endDate = null, int $limit = 30, int $offset = 0): array
    {
        $builder = $this->db->table($this->table);
        $this->applyFilters($builder, $search, $startDate, $endDate);

        return $builder
            ->orderBy('fecha', 'DESC')
            ->orderBy('egreso_id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(string $search = '', ?string $startDate = null, ?string $endDate = null): int
    {
        $builder = $this->db->table($this->table);
        $this->applyFilters($builder, $search, $startDate, $endDate);

        return $builder->countAllResults();
    }

    public function getById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $row = $this->db->table($this->table)
            ->where('egreso_id', $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function saveEgreso(array $data, ?int $id = null): bool
    {
        $now = RegisterService::mysqlNowForReport();
        $fechaInput = trim((string) ($data['fecha'] ?? ''));
        if ($fechaInput !== '') {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fechaInput)) {
                $fechaInput .= ':00';
            }
        } else {
            $fechaInput = $now;
        }
        $save = [
            'monto'      => number_format((float) ($data['monto'] ?? 0), 2, '.', ''),
            'tipopago'   => trim((string) ($data['tipopago'] ?? '1')),
            'tipo_movimiento' => trim((string) ($data['tipo_movimiento'] ?? 'egreso')),
            'desglose'   => trim((string) ($data['desglose'] ?? '')),
            'fecha'      => $fechaInput,
            'updated_at' => $now,
            'deleted'    => 0,
        ];
        if ($id && $id > 0) {
            return $this->db->table($this->table)
                ->where('egreso_id', $id)
                ->update($save) !== false;
        }
        $save['created_at'] = $now;

        return $this->db->table($this->table)->insert($save) !== false;
    }

    public function softDelete(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        return $this->db->table($this->table)
            ->where('egreso_id', $id)
            ->update([
                'deleted' => 1,
                'updated_at' => RegisterService::mysqlNowForReport(),
            ]) !== false;
    }

    private function applyFilters(\CodeIgniter\Database\BaseBuilder $builder, string $search = '', ?string $startDate = null, ?string $endDate = null): void
    {
        $builder->where('(deleted = 0 OR deleted IS NULL)');

        $search = trim($search);
        if ($search !== '') {
            $builder->groupStart()
                ->like('desglose', $search)
                ->orLike('tipopago', $search)
                ->orLike('tipo_movimiento', $search)
                ->orLike('monto', $search)
                ->groupEnd();
        }

        if ($startDate !== null && $startDate !== '') {
            $builder->where('DATE(fecha) >=', $startDate);
        }
        if ($endDate !== null && $endDate !== '') {
            $builder->where('DATE(fecha) <=', $endDate);
        }
    }
}

