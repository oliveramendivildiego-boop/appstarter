<?php

namespace App\Models;

use CodeIgniter\Model;

class ToquoteModel extends Model
{
    protected $table = 'toquotelogs';
    protected $primaryKey = 'toquotelogs_id';
    protected $allowedFields = ['person_id', 'cotizo', 'costo', 'fecha'];

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

    public function saveLog(string $cotizo, int $costo): bool
    {
        $personId = session()->get('person_id') ?? 0;
        return $this->db->table('toquotelogs')->insert([
            'person_id' => $personId,
            'cotizo'   => $cotizo,
            'costo'    => $costo,
        ]);
    }
}
