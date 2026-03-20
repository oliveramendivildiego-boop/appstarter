<?php

namespace App\Models;

use CodeIgniter\Model;

class DoctorModel extends Model
{
    protected $table            = 'doctors';
    protected $primaryKey       = 'doctor_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = ['name', 'phone_number', 'gender', 'speciality', 'address', 'deleted', 'comments', 'username', 'password', 'email', 'commission_percent', 'has_commission'];
    /** @var array<string,bool>|null */
    private ?array $columnCache = null;

    public function hasColumn(string $column): bool
    {
        if ($this->columnCache === null) {
            $this->columnCache = [];
            foreach ($this->db->getFieldNames($this->table) as $field) {
                $this->columnCache[$field] = true;
            }
        }
        return isset($this->columnCache[$column]);
    }

    public function supportsLoginColumns(): bool
    {
        return $this->hasColumn('username') && $this->hasColumn('password') && $this->hasColumn('email');
    }

    public function supportsCommissionColumn(): bool
    {
        return $this->hasColumn('commission_percent');
    }

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
            'username'     => '',
            'password'     => '',
            'email'        => '',
            'commission_percent' => 0.00,
            'has_commission' => 0,
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

        if ($this->supportsCommissionColumn()) {
            $payload['commission_percent'] = is_numeric($data['commission_percent'] ?? 0) ? (float) ($data['commission_percent'] ?? 0) : 0.00;
        }
        
        if ($this->hasColumn('has_commission')) {
            $payload['has_commission'] = (int) ($data['has_commission'] ?? 0);
        }

        if ($this->supportsLoginColumns()) {
            $payload['username'] = trim($data['username'] ?? '') ?: null;
            $payload['email']    = trim($data['email'] ?? '') ?: null;
        }

        $password = $data['password'] ?? '';
        if ($password !== '' && $this->hasColumn('password')) {
            $payload['password'] = md5($password);
        }

        if ($doctor_id && $this->find($doctor_id)) {
            if (!isset($payload['password']) || $payload['password'] === '') {
                unset($payload['password']);
            }
            if (!empty($payload['username'] ?? '') && $this->usernameExists((string) $payload['username'], (int) $doctor_id)) {
                return false;
            }
            $this->update($doctor_id, $payload);
            return $doctor_id;
        }
        $payload['deleted'] = 0;
        if (!empty($payload['username'] ?? '') && $this->usernameExists((string) $payload['username'])) {
            return false;
        }
        if (empty($payload['password'] ?? '') && !empty($payload['username'] ?? '')) {
            $payload['password'] = md5('doctor123');
        }
        return $this->insert($payload) ? $this->getInsertID() : false;
    }

    /**
     * Login de doctor por username o email.
     */
    public function login(string $usernameOrEmail, string $password): bool
    {
        if (!$this->supportsLoginColumns()) {
            return false;
        }

        $hash = md5($password);
        $builder = $this->db->table('doctors')
            ->where('deleted', 0)
            ->where('active', 1)
            ->where('password', $hash);

        if (str_contains($usernameOrEmail, '@')) {
            $builder->where('email', $usernameOrEmail);
        } else {
            $builder->where('username', $usernameOrEmail);
        }

        $row = $builder->get()->getRow();
        if ($row && !empty($row->username)) {
            session()->set('doctor_id', $row->doctor_id);
            session()->set('user_type', 'doctor');
            session()->remove('person_id');
            return true;
        }
        return false;
    }

    /**
     * Login de doctor por email (OAuth Google).
     */
    public function loginByEmail(string $email): bool
    {
        if (!$this->supportsLoginColumns()) {
            return false;
        }

        $row = $this->db->table('doctors')
            ->select('doctor_id, username')
            ->where('deleted', 0)
            ->where('active', 1)
            ->where('email', $email)
            ->get()
            ->getRow();

        if ($row) {
            session()->set('doctor_id', $row->doctor_id);
            session()->set('user_type', 'doctor');
            session()->remove('person_id');
            return true;
        }
        return false;
    }

    /**
     * Verifica si el doctor está logueado.
     */
    public function isDoctorLoggedIn(): bool
    {
        return session()->has('doctor_id') && session()->get('doctor_id') !== null;
    }

    /**
     * Verifica si el username ya existe (para otro doctor).
     */
    public function usernameExists(string $username, ?int $excludeDoctorId = null): bool
    {
        if (!$this->hasColumn('username')) return false;
        if (trim($username) === '') return false;
        $builder = $this->db->table('doctors')
            ->where('username', $username)
            ->where('deleted', 0);
        if ($excludeDoctorId) {
            $builder->where('doctor_id !=', $excludeDoctorId);
        }
        return $builder->countAllResults() > 0;
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
