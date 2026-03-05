<?php

namespace App\Models;

use CodeIgniter\Model;

class ToquoteModel extends Model
{
    protected $table = 'toquotelogs';
    protected $primaryKey = 'toquotelogs_id';
    protected $allowedFields = ['person_id', 'cotizo', 'costo', 'refe', 'items_json', 'fecha'];

    /**
     * Obtiene categorías con análisis para cotizar (mismo formato que LabotestModel)
     */
    public function getAllWithAnalysis(): array
    {
        $ana = $this->db->prefixTable('anacategoria');
        $pri = $this->db->prefixTable('prianacategoria');

        return $this->db->table('anacategoria')
            ->select("{$ana}.anacategoria_id, {$ana}.name as cat_name, {$ana}.order as ana_order,
                      {$pri}.prianacategoria_id, {$pri}.name as pria_nombre, {$pri}.order as pria_order,
                      {$pri}.cost as cost, {$pri}.cost_deriv as refe")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id AND ({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->orderBy("{$ana}.order", 'ASC')
            ->orderBy("{$pri}.order", 'ASC')
            ->get()
            ->getResult();
    }

    public function getGroupedForQuote(): array
    {
        $rows = $this->getAllWithAnalysis();
        $grouped = [];

        foreach ($rows as $row) {
            $catId = $row->anacategoria_id;
            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [
                    'id' => $row->anacategoria_id,
                    'name' => $row->cat_name,
                    'items' => [],
                ];
            }
            if ($row->prianacategoria_id) {
                $grouped[$catId]['items'][] = [
                    'id' => $row->prianacategoria_id,
                    'name' => $row->pria_nombre,
                    'cost' => (int) ($row->cost ?? 0),
                    'refe' => (int) ($row->refe ?? 0),
                ];
            }
        }

        return array_values($grouped);
    }

    /**
     * Guarda una cotización en el log
     * @param string $cotizo Nombres de análisis (comma-sep), por compatibilidad
     * @param int $costo Costo total
     * @param int $refe Costo de referencia total
     * @param string|null $itemsJson JSON de items [{"id", "name", "cost", "refe"}, ...]
     */
    public function saveLog(string $cotizo, int $costo, int $refe = 0, ?string $itemsJson = null): bool
    {
        $personId = session()->get('person_id') ?? 0;
        $data = [
            'person_id' => $personId,
            'cotizo'    => $cotizo,
            'costo'     => $costo,
        ];
        try {
            $tableName = $this->db->prefixTable('toquotelogs');
            $tableInfo = $this->db->getFieldNames($tableName);
            if (in_array('refe', $tableInfo, true)) {
                $data['refe'] = $refe;
            }
            if ($itemsJson !== null && in_array('items_json', $tableInfo, true)) {
                $data['items_json'] = $itemsJson;
            }
        } catch (\Throwable $e) {
            // Si las columnas no existen, usar solo campos básicos
        }
        return $this->db->table('toquotelogs')->insert($data);
    }

    /**
     * Busca análisis para cotizar (por nombre o categoría)
     */
    public function searchForQuote(string $q): array
    {
        $ana = $this->db->prefixTable('anacategoria');
        $pri = $this->db->prefixTable('prianacategoria');
        $esc = $this->db->escapeLikeString(trim($q));
        $pat = "%{$esc}%";

        $rows = $this->db->table('anacategoria')
            ->select("{$pri}.prianacategoria_id as id, {$pri}.name, {$ana}.name as cat_name, {$pri}.cost, {$pri}.cost_deriv as refe")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id AND ({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)", 'inner')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->groupStart()
                ->like("{$ana}.name", $esc, 'both')
                ->orLike("{$pri}.name", $esc, 'both')
            ->groupEnd()
            ->orderBy("{$ana}.name", 'ASC')
            ->orderBy("{$pri}.name", 'ASC')
            ->limit(50)
            ->get()
            ->getResult();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'       => (int) $r->id,
                'name'     => $r->name,
                'cat_name' => $r->cat_name,
                'cost'     => (int) ($r->cost ?? 0),
                'refe'     => (int) ($r->refe ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Columnas disponibles en toquotelogs (refe e items_json pueden no existir si no se ejecutó la migración)
     */
    private function getToquotelogsSelectColumns(): string
    {
        $tbl  = $this->db->prefixTable('toquotelogs');
        $base = "{$tbl}.toquotelogs_id, {$tbl}.cotizo, {$tbl}.costo, {$tbl}.fecha";
        try {
            $fields = $this->db->getFieldNames($this->db->prefixTable('toquotelogs'));
            $extras = [];
            if (in_array('refe', $fields, true)) {
                $extras[] = "{$tbl}.refe";
            }
            if (in_array('items_json', $fields, true)) {
                $extras[] = "{$tbl}.items_json";
            }
            return $extras ? $base . ', ' . implode(', ', $extras) : $base;
        } catch (\Throwable $e) {
            return $base;
        }
    }

    /**
     * Todas las cotizaciones guardadas (con paginación)
     * Incluye nombre del usuario que cotizó (LEFT JOIN people)
     */
    public function getAllCotizaciones(int $perPage, int $offset): array
    {
        $tbl = $this->db->prefixTable('toquotelogs');
        $p   = $this->db->prefixTable('people');
        $select = $this->getToquotelogsSelectColumns();
        $select .= ", CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS usuario_cotizo";
        return $this->db->table('toquotelogs')
            ->select($select)
            ->join('people', "{$p}.person_id = {$tbl}.person_id", 'left')
            ->orderBy("{$tbl}.fecha", 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Total de cotizaciones guardadas
     */
    public function countAllCotizaciones(): int
    {
        $row = $this->db->table('toquotelogs')->select('COUNT(*) as total')->get()->getRow();
        return (int) ($row->total ?? 0);
    }

    /**
     * Obtiene una cotización por ID
     */
    public function getById(int $id): ?array
    {
        $select = $this->getToquotelogsSelectColumns();
        $row = $this->db->table('toquotelogs')->select($select)->where('toquotelogs_id', $id)->get()->getRowArray();
        return $row ?: null;
    }
}
