<?php

namespace App\Models;

use CodeIgniter\Model;

class LabotestReactivoConfigModel extends Model
{
    protected $table         = 'labotest_reactivo_config';
    protected $primaryKey    = 'config_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'prianacategoria_id',
        'reactivo_id',
        'consumo_default',
        'lote_policy',
        'enabled',
        'deleted',
    ];
    protected $useTimestamps = true;

    public function getByPrianacategoria(int $prianacategoriaId): array
    {
        return $this->db->table($this->table . ' c')
            ->select('c.*, r.nombre as reactivo_nombre, r.unidad_base')
            ->join('reactivo r', 'r.reactivo_id = c.reactivo_id', 'left')
            ->where('c.prianacategoria_id', $prianacategoriaId)
            ->where('c.deleted', 0)
            ->where('c.enabled', 1)
            ->orderBy('r.nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getByPrianacategoriaIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        return $this->db->table($this->table . ' c')
            ->select('c.*')
            ->whereIn('c.prianacategoria_id', $ids)
            ->where('c.deleted', 0)
            ->where('c.enabled', 1)
            ->get()
            ->getResultArray();
    }

    public function softDeleteConfig(int $configId): bool
    {
        return (bool) $this->update($configId, ['deleted' => 1, 'enabled' => 0]);
    }
}

