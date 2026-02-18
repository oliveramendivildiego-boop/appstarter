<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonModel extends Model
{
    protected $table            = 'people';
    protected $primaryKey       = 'person_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['first_name', 'last_name', 'last_name_fa', 'last_name_mom', 'ci', 'phone_number', 'email', 'birthday', 'gender', 'address_1', 'address_2', 'city', 'state', 'zip', 'country', 'comments'];
    protected $useTimestamps    = false;

    private function usesSplitNames(): bool
    {
        return (bool) env('people.useSplitNames', true);
    }

    /**
     * Verifica si la persona existe
     */
    public function exists(int $person_id): bool
    {
        return $this->where('person_id', $person_id)->countAllResults() === 1;
    }

    /**
     * Obtiene todas las personas
     */
    public function getAll(int $limit = 10000, int $offset = 0)
    {
        $orderCol = $this->usesSplitNames() ? 'last_name_fa' : 'last_name';
        return $this->orderBy($orderCol, 'ASC')
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Cuenta todas las personas
     */
    public function countAll(): int
    {
        return $this->db->table($this->table)->countAllResults();
    }

    /**
     * Obtiene información de una persona
     */
    public function getInfo(int $person_id)
    {
        $row = $this->find($person_id);
        if ($row) {
            $row->last_name_fa = $row->last_name_fa ?? $row->last_name ?? '';
            $row->last_name_mom = $row->last_name_mom ?? '';
            $row->last_name = $this->usesSplitNames()
                ? trim(($row->last_name_fa ?? '') . ' ' . ($row->last_name_mom ?? ''))
                : ($row->last_name ?? '');
            return $row;
        }
        $obj = new \stdClass();
        foreach ($this->allowedFields as $field) {
            $obj->$field = '';
        }
        $obj->person_id = null;
        return $obj;
    }

    /**
     * Obtiene múltiples personas por IDs
     */
    public function getMultipleInfo(array $person_ids): array
    {
        if (empty($person_ids)) {
            return [];
        }
        $orderCol = $this->usesSplitNames() ? 'last_name_fa' : 'last_name';
        return $this->whereIn('person_id', $person_ids)
            ->orderBy($orderCol, 'ASC')
            ->findAll();
    }

    /**
     * Inserta o actualiza una persona.
     * Retorna el person_id en éxito, false en fallo.
     * @return int|bool
     */
    public function savePerson(array $person_data, ?int $person_id = null)
    {
        $data = $person_data;
        if (!$this->usesSplitNames()) {
            unset($data['last_name_fa'], $data['last_name_mom']);
            $data['last_name'] = trim(($person_data['last_name_fa'] ?? $person_data['last_name'] ?? '') . ' ' . ($person_data['last_name_mom'] ?? ''));
        } else {
            unset($data['last_name']);
        }
        if (!$person_id || !$this->exists($person_id)) {
            unset($data['person_id']);
            if ($this->insert($data)) {
                return (int) $this->getInsertID();
            }
            return false;
        }
        unset($data['person_id']);
        return $this->update($person_id, $data) ? $person_id : false;
    }

    /**
     * Elimina (soft delete) - no hace nada en Person base
     */
    public function deletePerson(int $person_id): bool
    {
        return true;
    }
}
