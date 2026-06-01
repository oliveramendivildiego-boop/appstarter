<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipoModel extends Model
{
    protected $table = 'equipo';
    protected $primaryKey = 'equipo_id';

    public function getEquipo(int $equipoId): ?array
    {
        $row = $this->db->table('equipo')
            ->where('equipo_id', $equipoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        return $row ?: null;
    }

    public function getAll(): array
    {
        return $this->db->table('equipo')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('nombre')
            ->get()
            ->getResultArray();
    }

    public function getMantenimientos(int $equipoId): array
    {
        return $this->db->table('equipo_mantenimiento')
            ->where('equipo_id', $equipoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('fecha', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function saveEquipo(array $data, ?int $id = null): bool
    {
        $save = [
            'nombre' => trim($data['nombre'] ?? ''),
            'codigo' => trim($data['codigo'] ?? '') ?: null,
            'ubicacion' => trim($data['ubicacion'] ?? '') ?: null,
            'deleted' => 0,
        ];
        if ($id) return $this->db->table('equipo')->where('equipo_id', $id)->update($save);
        return $this->db->table('equipo')->insert($save) !== false;
    }

    public function saveMantenimiento(array $data, ?int $id = null): bool
    {
        $save = [
            'equipo_id' => (int) ($data['equipo_id'] ?? 0),
            'tipo' => (int) ($data['tipo'] ?? 1),
            'fecha' => $data['fecha'] ?? \App\Services\RegisterService::todayForReport(),
            'descripcion' => trim($data['descripcion'] ?? '') ?: null,
            'realizado_por' => (int) ($data['realizado_por'] ?? 0) ?: null,
            'deleted' => 0,
        ];
        if ($id) return $this->db->table('equipo_mantenimiento')->where('mantenimiento_id', $id)->update($save);
        return $this->db->table('equipo_mantenimiento')->insert($save) !== false;
    }
}
