<?php

namespace App\Models;

use CodeIgniter\Model;

class ControlCalidadModel extends Model
{
    protected $table = 'control_calidad';
    protected $primaryKey = 'control_id';

    public function getById(int $controlId): ?array
    {
        $row = $this->db->table('control_calidad')
            ->where('control_id', $controlId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        return $row ?: null;
    }

    public function getAll(): array
    {
        return $this->db->table('control_calidad')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('nombre')
            ->get()
            ->getResultArray();
    }

    public function getValores(int $controlId, ?string $fechaIni = null, ?string $fechaFin = null, int $limit = 1000): array
    {
        $qb = $this->db->table('control_valor')
            ->where('control_id', $controlId)
            ->orderBy('fecha', 'ASC');
        if ($fechaIni) $qb->where('fecha >=', $fechaIni);
        if ($fechaFin) $qb->where('fecha <=', $fechaFin);
        return $qb->limit($limit)->get()->getResultArray();
    }

    public function saveControl(array $data, ?int $id = null): bool
    {
        $save = [
            'nombre' => trim($data['nombre'] ?? ''),
            'tipo'   => (int) ($data['tipo'] ?? 1),
            'deleted' => 0,
        ];
        if (array_key_exists('sesgo', $data)) {
            $raw = $data['sesgo'];
            $save['sesgo'] = ($raw === null || $raw === '') ? null : (float) $raw;
        }
        if ($id) {
            return $this->db->table('control_calidad')->where('control_id', $id)->update($save);
        }
        return $this->db->table('control_calidad')->insert($save) !== false;
    }

    public function saveValor(array $data, ?int $id = null): bool
    {
        $save = [
            'control_id'  => (int) ($data['control_id'] ?? 0),
            'fecha'       => $data['fecha'] ?? \App\Services\RegisterService::todayForReport(),
            'valor'       => (float) ($data['valor'] ?? 0),
            'esperado'    => isset($data['esperado']) ? (float) $data['esperado'] : null,
            'observaciones' => trim($data['observaciones'] ?? '') ?: null,
        ];
        if ($id) {
            return $this->db->table('control_valor')->where('valor_id', $id)->update($save);
        }
        return $this->db->table('control_valor')->insert($save) !== false;
    }
}
