<?php

namespace App\Models;

use CodeIgniter\Model;

class DoctorModel extends Model
{
    protected $table            = 'doctors';
    protected $primaryKey       = 'doctor_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = ['name', 'phone_number', 'gender', 'speciality', 'address', 'deleted', 'comments'];

    /**
     * Obtiene todos los doctores (no eliminados)
     */
    public function getAll(int $limit = 10000, int $offset = 0): array
    {
        return $this->where('deleted', 0)
            ->orderBy('name', 'ASC')
            ->findAll($limit, $offset);
    }

    /**
     * Cuenta todos los doctores no eliminados
     */
    public function countAll(): int
    {
        return $this->where('deleted', 0)->countAllResults();
    }

    /**
     * Obtiene un doctor por ID
     */
    public function getInfo(int $doctor_id)
    {
        $row = $this->find($doctor_id);
        if ($row) {
            return $row;
        }
        return (object) [
            'doctor_id'    => '',
            'name'         => '',
            'phone_number' => '',
            'gender'       => 2,
            'speciality'   => '',
            'address'      => '',
            'comments'     => '',
        ];
    }

    /**
     * Guarda un doctor (insert o update)
     */
    /**
     * @return int|bool
     */
    public function saveDoctor(array $data, $doctor_id = null)
    {
        $payload = [
            'name'         => $data['name'] ?? '',
            'phone_number' => $data['phone_number'] ?? '',
            'gender'       => (int) ($data['gender'] ?? 2),
            'speciality'   => $data['speciality'] ?? '',
            'address'      => $data['address'] ?? '',
            'comments'     => $data['comments'] ?? '',
        ];

        if ($doctor_id && $this->find($doctor_id)) {
            $this->update($doctor_id, $payload);
            return $doctor_id;
        }
        $payload['deleted'] = 0;
        return $this->insert($payload) ? $this->getInsertID() : false;
    }

    /**
     * Soft delete
     */
    public function deleteDoctor(int $doctor_id): bool
    {
        return $this->update($doctor_id, ['deleted' => 1]);
    }

    /**
     * Búsqueda por nombre, teléfono, especialidad
     */
    public function search(string $search): array
    {
        $escaped = $this->db->escapeLikeString($search);
        return $this->groupStart()
            ->like('name', $escaped, 'both')
            ->orLike('phone_number', $escaped, 'both')
            ->orLike('speciality', $escaped, 'both')
            ->orLike('address', $escaped, 'both')
            ->groupEnd()
            ->where('deleted', 0)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Sugerencias para autocomplete
     */
    public function getSearchSuggestions(string $search, int $limit = 25): array
    {
        $search = trim($search);
        if ($search === '') {
            return [];
        }
        $escaped = $this->db->escapeLikeString($search);
        $rows = $this->like('name', $escaped, 'both')
            ->orLike('speciality', $escaped, 'both')
            ->where('deleted', 0)
            ->orderBy('name', 'ASC')
            ->findAll($limit);
        return array_map(fn ($r) => $r->name . ' (' . $r->speciality . ')', $rows);
    }
}
