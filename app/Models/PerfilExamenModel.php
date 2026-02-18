<?php

namespace App\Models;

use CodeIgniter\Model;

class PerfilExamenModel extends Model
{
    protected $table      = 'perfil_examen';
    protected $primaryKey = 'perfil_id';

    public function getAll(): array
    {
        return $this->db->table('perfil_examen')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('nombre')
            ->get()
            ->getResultArray();
    }

    public function getPruebas(int $perfilId): array
    {
        $row = $this->db->table('perfil_examen')
            ->where('perfil_id', $perfilId)
            ->get()
            ->getRow();
        if (!$row || empty($row->pruebas)) {
            return [];
        }
        return array_map('intval', array_filter(explode(',', $row->pruebas)));
    }

    public function savePerfil(array $data, ?int $id = null): bool
    {
        $save = [
            'nombre' => trim($data['nombre'] ?? ''),
            'pruebas' => is_array($data['pruebas'] ?? null) ? implode(',', $data['pruebas']) : (string) ($data['pruebas'] ?? ''),
            'deleted' => 0,
        ];
        if ($id) {
            return $this->db->table('perfil_examen')->where('perfil_id', $id)->update($save);
        }
        return $this->db->table('perfil_examen')->insert($save) !== false;
    }

    public function deletePerfil(int $id): bool
    {
        return $this->db->table('perfil_examen')->where('perfil_id', $id)->update(['deleted' => 1]);
    }
}
