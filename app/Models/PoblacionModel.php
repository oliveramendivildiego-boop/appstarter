<?php

namespace App\Models;

use CodeIgniter\Model;

class PoblacionModel extends Model
{
    protected $table            = 'poblacion';
    protected $primaryKey       = 'id_poblacion';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'edad_min', 'edad_max', 'unidad', 'orden', 'deleted'];
    protected $useTimestamps    = false;

    /**
     * Decodifica entidades HTML en texto (ej. Ni&ntilde;os -> Niños)
     */
    private static function decodeEntities(?string $s): string
    {
        if ($s === null || $s === '') {
            return '';
        }
        return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Obtiene todas las poblaciones activas ordenadas
     */
    public function getAll(): array
    {
        $rows = $this->db->table($this->table)
            ->where('deleted', 0)
            ->orderBy('orden', 'ASC')
            ->orderBy('id_poblacion', 'ASC')
            ->get()
            ->getResultArray();
        foreach ($rows as &$r) {
            $r['name'] = self::decodeEntities($r['name'] ?? null);
        }
        return $rows;
    }

    /**
     * Obtiene una población por ID (para edición)
     */
    public function getById(int $id): ?array
    {
        $row = $this->db->table($this->table)
            ->where('id_poblacion', $id)
            ->get()
            ->getRowArray();
        if (!$row) {
            return null;
        }
        if (isset($row['name'])) {
            $row['name'] = self::decodeEntities($row['name']);
        }
        return $row;
    }

    /**
     * Obtiene el próximo id_poblacion disponible (para inserts sin autoincrement)
     */
    public function getNextId(): int
    {
        $row = $this->db->table($this->table)
            ->selectMax('id_poblacion')
            ->get()
            ->getRowArray();
        return ((int) ($row['id_poblacion'] ?? 0)) + 1;
    }

    /**
     * Guarda o actualiza una población
     */
    public function savePoblacion(array $data, ?int $id = null): bool
    {
        $save = [
            'name'     => trim($data['name'] ?? ''),
            'edad_min' => $this->parseEdad($data['edad_min'] ?? null),
            'edad_max' => $this->parseEdad($data['edad_max'] ?? null),
            'unidad'   => $this->validateUnidad($data['unidad'] ?? null),
            'orden'    => (int) ($data['orden'] ?? 0),
            'deleted'  => 0,
        ];
        if ($id !== null) {
            return $this->db->table($this->table)->where('id_poblacion', $id)->update($save);
        }
        $save['id_poblacion'] = (int) ($data['id_poblacion'] ?? $this->getNextId());
        return $this->db->table($this->table)->insert($save) !== false;
    }

    /**
     * Soft delete
     */
    public function deletePoblacion(int $id): bool
    {
        return $this->db->table($this->table)
            ->where('id_poblacion', $id)
            ->update(['deleted' => 1]);
    }

    private function parseEdad($v)
    {
        if ($v === null || $v === '') {
            return null;
        }
        $n = (float) str_replace(',', '.', (string) $v);
        return $n < 0 ? null : $n;
    }

    private function validateUnidad(?string $u): ?string
    {
        if ($u === null || $u === '') {
            return null;
        }
        $u = strtolower($u);
        return in_array($u, ['dias', 'meses', 'años'], true) ? $u : null;
    }

    /**
     * Devuelve descripción legible del rango (ej. "0 – 28 días")
     */
    public static function formatRangoEdad(array $p): string
    {
        $min = $p['edad_min'] ?? null;
        $max = $p['edad_max'] ?? null;
        $u   = $p['unidad'] ?? null;
        if ($min === null && $max === null) {
            return '-';
        }
        $sufijo = $u ? ' ' . $u : '';
        if ($max === null) {
            return '≥ ' . $min . $sufijo;
        }
        if ($min === null) {
            return '≤ ' . $max . $sufijo;
        }
        return $min . ' – ' . $max . $sufijo;
    }
}
