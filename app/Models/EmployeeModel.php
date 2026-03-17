<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table            = 'employees';
    protected $primaryKey       = 'person_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';

    private function peopleTable(): string
    {
        return $this->db->prefixTable('people');
    }

    private function employeesTable(): string
    {
        return $this->db->prefixTable('employees');
    }

    public function exists(int $person_id): bool
    {
        $p = $this->peopleTable();
        $e = $this->employeesTable();
        return $this->db->table('employees')
            ->join($p, "{$p}.person_id = {$e}.person_id")
            ->where("{$e}.person_id", $person_id)
            ->countAllResults() === 1;
    }

    public function getInfo(int $employee_id)
    {
        $p = $this->peopleTable();
        $e = $this->employeesTable();
        $row = $this->db->table('employees')
            ->select("{$e}.*, {$p}.*")
            ->join($p, "{$p}.person_id = {$e}.person_id")
            ->where("{$e}.person_id", $employee_id)
            ->get()
            ->getRow();

        if ($row) {
            if (property_exists($row, 'last_name_fa') || property_exists($row, 'last_name_mom')) {
                $row->last_name = trim(($row->last_name_fa ?? '') . ' ' . ($row->last_name_mom ?? ''));
            }
            return $row;
        }

        $personModel = model(PersonModel::class);
        $obj = $personModel->getInfo(-1);
        foreach ($this->db->getFieldNames('employees') as $field) {
            $obj->$field = '';
        }
        return $obj;
    }

    public function getAllowedModules(int $person_id): array
    {
        return $this->db->table('modules')
            ->select('modules.*')
            ->join('permissions', 'permissions.module_id = modules.module_id')
            ->where('permissions.person_id', $person_id)
            ->orderBy('modules.sort', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Login por username o por email. Si el valor contiene @ se intenta por email.
     */
    public function login(string $usernameOrEmail, string $password): bool
    {
        $hash = md5($password);

        // Intentar por username
        $row = $this->db->table($this->table)
            ->where('username', $usernameOrEmail)
            ->where('password', $hash)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        if ($row) {
            session()->set('person_id', $row->person_id);
            session()->remove('doctor_id');
            session()->remove('user_type');
            return true;
        }

        // Si no funcionó y el input parece email, intentar por email (people)
        if (str_contains($usernameOrEmail, '@')) {
            return $this->loginByEmailWithPassword($usernameOrEmail, $hash);
        }

        return false;
    }

    /**
     * Login por email verificando además la contraseña.
     */
    private function loginByEmailWithPassword(string $email, string $passwordHash): bool
    {
        $row = $this->db->table('employees')
            ->select('employees.person_id')
            ->join('people', 'people.person_id = employees.person_id')
            ->where('people.email', $email)
            ->where('employees.password', $passwordHash)
            ->where('employees.deleted', 0)
            ->get()
            ->getRow();

        if ($row) {
            session()->set('person_id', $row->person_id);
            session()->remove('doctor_id');
            session()->remove('user_type');
            return true;
        }
        return false;
    }

    /**
     * Restablece la contraseña de admin a 'password'. Solo para desarrollo.
     */
    public function resetAdminPassword(): bool
    {
        $hash = md5('password');
        $this->db->table($this->table)
            ->where('username', 'admin')
            ->where('deleted', 0)
            ->set('password', $hash)
            ->update();
        return $this->db->affectedRows() > 0;
    }

    /**
     * Verifica si existe un empleado activo con ese username (para mensajes de error).
     */
    public function usernameExists(string $username): bool
    {
        return $this->db->table($this->table)
            ->where('username', $username)
            ->where('deleted', 0)
            ->countAllResults() > 0;
    }

    /**
     * Login por email (para OAuth Google). Busca empleado por email en people.
     */
    public function loginByEmail(string $email): bool
    {
        $row = $this->db->table('employees')
            ->select('employees.person_id')
            ->join('people', 'people.person_id = employees.person_id')
            ->where('people.email', $email)
            ->where('employees.deleted', 0)
            ->get()
            ->getRow();

        if ($row) {
            session()->set('person_id', $row->person_id);
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        $personId = session()->get('person_id');
        if ($personId !== null) {
            session()->remove('user_info_' . $personId);
            session()->remove('allowed_modules_' . $personId);
        }
        session()->remove('doctor_id');
        session()->remove('user_type');
        session()->destroy();
    }

    public function isLoggedIn(): bool
    {
        return (session()->has('person_id') && session()->get('person_id') !== null)
            || (session()->has('doctor_id') && session()->get('doctor_id') !== null);
    }

    public function getLoggedInEmployeeInfo()
    {
        if ($this->isLoggedIn()) {
            return $this->getInfo((int) session()->get('person_id'));
        }
        return null;
    }

    public function getAll(int $limit = 10000, int $offset = 0): array
    {
        $p = $this->peopleTable();
        $e = $this->employeesTable();
        return $this->db->table('employees')
            ->select("{$e}.*, {$p}.*, TRIM(CONCAT(COALESCE({$p}.last_name_fa,\"\"), \" \", COALESCE({$p}.last_name_mom,\"\"))) as last_name")
            ->join('people', "{$p}.person_id = {$e}.person_id")
            ->where("{$e}.deleted", 0)
            ->orderBy("{$p}.last_name_fa", 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    public function search(string $search, int $limit = 100): array
    {
        $escaped = $this->db->escapeLikeString($search);
        $p = $this->peopleTable();
        $e = $this->employeesTable();
        return $this->db->table('employees')
            ->select("{$e}.*, {$p}.*, TRIM(CONCAT(COALESCE({$p}.last_name_fa,\"\"), \" \", COALESCE({$p}.last_name_mom,\"\"))) as last_name")
            ->join('people', "{$p}.person_id = {$e}.person_id")
            ->groupStart()
            ->like("{$p}.first_name", $escaped, 'both')
            ->orLike("{$p}.last_name_fa", $escaped, 'both')
            ->orLike("{$p}.last_name_mom", $escaped, 'both')
            ->orLike("{$p}.email", $escaped, 'both')
            ->orLike("{$p}.phone_number", $escaped, 'both')
            ->orLike("{$e}.username", $escaped, 'both')
            ->orLike("CONCAT(COALESCE({$p}.first_name,''), ' ', COALESCE({$p}.last_name_fa,''), ' ', COALESCE({$p}.last_name_mom,''))", $escaped, 'both')
            ->groupEnd()
            ->where("{$e}.deleted", 0)
            ->orderBy("{$p}.last_name_fa", 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    public function getSearchSuggestions(string $search, int $limit = 25): array
    {
        $search = trim($search);
        if ($search === '') return [];
        $escaped = $this->db->escapeLikeString($search);
        $p = $this->peopleTable();
        $e = $this->employeesTable();
        $rows = $this->db->table('employees')
            ->select("{$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom")
            ->join('people', "{$p}.person_id = {$e}.person_id")
            ->groupStart()
            ->like("{$p}.first_name", $escaped, 'both')
            ->orLike("{$p}.last_name_fa", $escaped, 'both')
            ->orLike("{$p}.last_name_mom", $escaped, 'both')
            ->orLike("{$e}.username", $escaped, 'both')
            ->groupEnd()
            ->where("{$e}.deleted", 0)
            ->orderBy("{$p}.last_name_fa", 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();
        return array_map(fn ($r) => trim(($r->first_name ?? '') . ' ' . ($r->last_name_fa ?? '') . ' ' . ($r->last_name_mom ?? '')), $rows);
    }

    public function deleteEmployee(int $person_id): bool
    {
        return $this->db->table($this->table)->where('person_id', $person_id)->update(['deleted' => 1]);
    }

    public function hasPermission(?string $module_id, int $person_id): bool
    {
        if ($module_id === null) {
            return true;
        }
        $count = $this->db->table('permissions')
            ->where('person_id', $person_id)
            ->where('module_id', $module_id)
            ->countAllResults();
        return $count === 1;
    }

    public function countAll(): int
    {
        return $this->db->table($this->employeesTable())->where('deleted', 0)->countAllResults();
    }

    /**
     * Guarda empleado (persona + credenciales). Retorna person_id o false.
     * @return int|bool
     */
    public function saveEmployee(array $person_data, array $employee_data, ?int $employee_id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $personModel = model(PersonModel::class);
        $result = $personModel->savePerson($person_data, $employee_id);
        if ($result === false) {
            $db->transRollback();
            return false;
        }

        $person_id = is_int($result) ? $result : $employee_id;
        $username = trim($employee_data['username'] ?? '');
        $password = $employee_data['password'] ?? '';

        if (!$employee_id || !$this->exists($employee_id)) {
            if (empty($username)) {
                $db->transRollback();
                return false;
            }
            if ($this->usernameExists($username)) {
                $db->transRollback();
                return false;
            }
            $hash = md5($password ?: 'password');
            $rolId = isset($employee_data['rol_id']) && $employee_data['rol_id'] ? (int) $employee_data['rol_id'] : null;
            $success = $this->db->table('employees')->insert([
                'username'   => $username,
                'password'   => $hash,
                'person_id'  => $person_id,
                'rol_id'     => $rolId,
                'deleted'    => 0,
            ]);
        } else {
            $upd = ['username' => $username];
            if ($password !== '') $upd['password'] = md5($password);
            if (array_key_exists('rol_id', $employee_data)) $upd['rol_id'] = $employee_data['rol_id'] ? (int) $employee_data['rol_id'] : null;
            $success = $this->db->table('employees')
                ->where('person_id', $employee_id)
                ->update($upd);
        }

        $db->transComplete();
        return $success ? $person_id : false;
    }
}
