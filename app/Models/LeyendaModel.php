<?php

namespace App\Models;

use CodeIgniter\Model;

class LeyendaModel extends Model
{
    protected $table            = 'leyendas';
    protected $primaryKey       = 'leyenda_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['titulo', 'mensaje', 'activo', 'deleted', 'created_at', 'updated_at'];

    public function getAll(): array
    {
        return $this->db->table($this->table)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('leyenda_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        if ($id < 1) return null;
        $row = $this->db->table($this->table)
            ->where('leyenda_id', $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        return $row ?: null;
    }

    public function saveLeyenda(array $data, ?int $id = null): bool
    {
        $save = [
            'titulo'     => trim((string)($data['titulo'] ?? '')),
            'mensaje'    => (string)($data['mensaje'] ?? ''),
            'activo'     => (int)($data['activo'] ?? 1) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted'    => 0,
        ];
        if ($id && $id > 0) {
            return $this->db->table($this->table)
                ->where('leyenda_id', $id)
                ->update($save);
        }
        $save['created_at'] = date('Y-m-d H:i:s');
        return $this->db->table($this->table)->insert($save) !== false;
    }

    public function softDeleteLeyenda(int $id): bool
    {
        if ($id < 1) return false;
        return $this->db->table($this->table)
            ->where('leyenda_id', $id)
            ->update([
                'deleted' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) !== false;
    }
}

