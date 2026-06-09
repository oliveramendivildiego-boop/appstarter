<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'person_id';
    protected $useAutoIncrement = false;
    protected $allowedFields    = ['person_id', 'account_number', 'taxable', 'seguro', 'institucion', 'deleted'];

    private function usesSplitNames(): bool
    {
        return (bool) env('people.useSplitNames', true);
    }

    private function peopleTable(): string
    {
        return $this->db->prefixTable('people');
    }

    private function customersTable(): string
    {
        return $this->db->prefixTable('customers');
    }

    /**
     * Verifica si ya existe otro paciente (customer) con el mismo CI.
     * @param string $ci Cédula de identidad
     * @param int|null $excludePersonId Excluir este person_id (para edición)
     * @return bool true si el CI ya existe en otro paciente
     */
    public function ciExistsForOtherCustomer(string $ci, ?int $excludePersonId = null): bool
    {
        $ci = trim($ci);
        if ($ci === '') return false;
        $p = $this->peopleTable();
        $c = $this->customersTable();
        $qb = $this->db->table('customers')
            ->join('people', "{$p}.person_id = {$c}.person_id")
            ->where("{$p}.ci", $ci)
            ->where("{$c}.deleted", 0);
        if ($excludePersonId) {
            $qb->where("{$p}.person_id !=", $excludePersonId);
        }
        return $qb->countAllResults() > 0;
    }

    /**
     * Verifica si el customer existe
     */
    public function exists(int $person_id): bool
    {
        $p = $this->peopleTable();
        $c = $this->customersTable();
        return $this->db->table('customers')
            ->join('people', "{$p}.person_id = {$c}.person_id")
            ->where("{$c}.person_id", $person_id)
            ->countAllResults() === 1;
    }

    /**
     * Obtiene todos los clientes (usa people join)
     */
    public function getAll(int $limit = 10000, int $offset = 0): array
    {
        $split = $this->usesSplitNames();
        $p = $this->peopleTable();
        $c = $this->customersTable();
        $qb = $this->db->table('customers')
            ->select($split ? "{$c}.*, {$p}.*, TRIM(CONCAT(COALESCE({$p}.last_name_fa,\"\"), \" \", COALESCE({$p}.last_name_mom,\"\"))) as last_name" : "{$c}.*, {$p}.*")
            ->join('people', "{$p}.person_id = {$c}.person_id")
            ->where("{$c}.deleted", 0)
            ->orderBy($split ? "{$p}.last_name_fa" : "{$p}.last_name", 'ASC')
            ->limit($limit, $offset);
        return $qb->get()->getResult();
    }

    /**
     * Cuenta todos los clientes
     */
    public function countAll(): int
    {
        return $this->db->table('customers')
            ->where('deleted', 0)
            ->countAllResults();
    }

    /**
     * Obtiene información de un cliente
     */
    public function getInfo(int $customer_id)
    {
        $split = $this->usesSplitNames();
        $p = $this->peopleTable();
        $c = $this->customersTable();
        $select = $split ? "{$c}.*, {$p}.*, TRIM(CONCAT(COALESCE({$p}.last_name_fa,\"\"), \" \", COALESCE({$p}.last_name_mom,\"\"))) as last_name" : "{$c}.*, {$p}.*";
        $row = $this->db->table('customers')
            ->select($select)
            ->join('people', "{$p}.person_id = {$c}.person_id")
            ->where("{$c}.person_id", $customer_id)
            ->get()
            ->getRow();

        if ($row) {
            return $row;
        }

        $personModel = model(PersonModel::class);
        $person_obj = $personModel->getInfo(-1);
        $fields = $this->db->getFieldNames('customers');
        foreach ($fields as $field) {
            $person_obj->$field = '';
        }
        return $person_obj;
    }

    /**
     * Inserta o actualiza un customer (con transacción).
     * Retorna person_id en éxito, false en fallo.
     * @return int|bool
     */
    public function saveCustomer(array $person_data, array $customer_data, ?int $customer_id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $personModel = model(PersonModel::class);
        $result = $personModel->savePerson($person_data, $customer_id);
        if ($result === false) {
            $db->transRollback();
            return false;
        }

        $person_id = is_int($result) ? $result : $customer_id;

        if (!$customer_id || !$this->exists($customer_id)) {
            $customer_data['person_id'] = $person_id;
            $success = $this->db->table('customers')->insert($customer_data);
        } else {
            $success = $this->db->table('customers')
                ->where('person_id', $customer_id)
                ->update($customer_data);
        }

        $db->transComplete();
        return $success ? $person_id : false;
    }

    /**
     * Soft delete de un customer
     */
    public function deleteCustomer(int $customer_id): bool
    {
        return $this->db->table('customers')
            ->where('person_id', $customer_id)
            ->update(['deleted' => 1]);
    }

    /**
     * Buscar clientes
     */
    public function search(string $search): array
    {
        $escaped = $this->db->escapeLikeString($search);
        $split = $this->usesSplitNames();
        $p = $this->peopleTable();
        $c = $this->customersTable();
        $sel = $split ? "{$c}.*, {$p}.*, TRIM(CONCAT(COALESCE({$p}.last_name_fa,\"\"), \" \", COALESCE({$p}.last_name_mom,\"\"))) as last_name" : "{$c}.*, {$p}.*";
        $qb = $this->db->table('customers')->select($sel)->join('people', "{$p}.person_id = {$c}.person_id")
            ->groupStart()->like("{$p}.first_name", $escaped, 'both')
            ->orLike($split ? "{$p}.last_name_fa" : "{$p}.last_name", $escaped, 'both');
        if ($split) {
            $qb->orLike("{$p}.last_name_mom", $escaped, 'both')
                ->orLike("CONCAT({$p}.first_name,' ',{$p}.last_name_fa,' ',{$p}.last_name_mom)", $escaped, 'both');
        } else {
            $qb->orLike("CONCAT({$p}.first_name,' ',{$p}.last_name)", $escaped, 'both');
        }
        $qb->orLike("{$p}.email", $escaped, 'both')->orLike("{$p}.phone_number", $escaped, 'both')
            ->orLike("{$c}.account_number", $escaped, 'both')->groupEnd()
            ->where("{$c}.deleted", 0)->orderBy($split ? "{$p}.last_name_fa" : "{$p}.last_name", 'ASC');
        return $qb->get()->getResult();
    }

    /**
     * Sugerencias de búsqueda para autocompletado
     */
    public function getSearchSuggestions(string $search, int $limit = 25): array
    {
        $suggestions = [];
        $search = trim($search);
        if ($search === '') {
            return [];
        }
        $escaped = $this->db->escapeLikeString($search);

        $split = $this->usesSplitNames();
        $p = $this->peopleTable();
        $c = $this->customersTable();
        $select = $split ? "{$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom, TRIM(CONCAT(COALESCE({$p}.last_name_fa,\"\"), \" \", COALESCE({$p}.last_name_mom,\"\"))) as last_name" : "{$p}.first_name, {$p}.last_name";
        $qb = $this->db->table('customers')->select($select)->join('people', "{$p}.person_id = {$c}.person_id")
            ->groupStart()->like("{$p}.first_name", $escaped, 'both');
        if ($split) {
            $qb->orLike("{$p}.last_name_fa", $escaped, 'both')->orLike("{$p}.last_name_mom", $escaped, 'both')
                ->orLike("CONCAT({$p}.first_name,' ',{$p}.last_name_fa,' ',{$p}.last_name_mom)", $escaped, 'both');
        } else {
            $qb->orLike("{$p}.last_name", $escaped, 'both')->orLike("CONCAT({$p}.first_name,' ',{$p}.last_name)", $escaped, 'both');
        }
        $by_name = $qb->groupEnd()->where("{$c}.deleted", 0)->orderBy($split ? "{$p}.last_name_fa" : "{$p}.last_name", 'ASC')->limit($limit)->get()->getResult();

        foreach ($by_name as $row) {
            $suggestions[] = trim($row->first_name . ' ' . $row->last_name);
        }

        $c = $this->customersTable();
        $by_email = $this->db->table('customers')->select("{$p}.email")->join('people', "{$p}.person_id = {$c}.person_id")
            ->where("{$c}.deleted", 0)->like("{$p}.email", $escaped, 'both')->limit($limit)->get()->getResult();

        foreach ($by_email as $row) {
            $suggestions[] = $row->email;
        }

        $by_phone = $this->db->table('customers')->select("{$p}.phone_number")->join('people', "{$p}.person_id = {$c}.person_id")
            ->where("{$c}.deleted", 0)->like("{$p}.phone_number", $escaped, 'both')->limit($limit)->get()->getResult();

        foreach ($by_phone as $row) {
            $suggestions[] = $row->phone_number;
        }

        return array_slice(array_unique($suggestions), 0, $limit);
    }

    /**
     * Sugerencias para dropdown de customer (formato: id|nombre)
     */
    public function getCustomerSearchSuggestions(string $search, int $limit = 25): array
    {
        $suggestions = [];
        $escaped = $this->db->escapeLikeString($search);

        $split = $this->usesSplitNames();
        $p = $this->peopleTable();
        $c = $this->customersTable();
        $select = $split ? "{$c}.person_id, {$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom, TRIM(CONCAT(COALESCE({$p}.last_name_fa,\"\"), \" \", COALESCE({$p}.last_name_mom,\"\"))) as last_name, {$c}.account_number" : "{$c}.person_id, {$p}.first_name, {$p}.last_name, {$c}.account_number";
        $qb = $this->db->table('customers')->select($select)->join('people', "{$p}.person_id = {$c}.person_id")
            ->groupStart()->like("{$p}.first_name", $escaped, 'both');
        if ($split) {
            $qb->orLike("{$p}.last_name_fa", $escaped, 'both')->orLike("{$p}.last_name_mom", $escaped, 'both')
                ->orLike("CONCAT({$p}.first_name,' ',{$p}.last_name_fa,' ',{$p}.last_name_mom)", $escaped, 'both');
        } else {
            $qb->orLike("{$p}.last_name", $escaped, 'both')->orLike("CONCAT({$p}.first_name,' ',{$p}.last_name)", $escaped, 'both');
        }
        $rows = $qb->orLike("{$c}.person_id", $escaped, 'both')->orLike("{$c}.account_number", $escaped, 'both')
            ->groupEnd()->where("{$c}.deleted", 0)->orderBy($split ? "{$p}.last_name_fa" : "{$p}.last_name", 'ASC')->limit($limit)->get()->getResult();

        foreach ($rows as $row) {
            $nombre = trim($row->first_name . ' ' . $row->last_name);
            $suggestions[] = $row->person_id . '|' . $nombre . ' ( ' . $row->person_id . ' )';
        }

        return $suggestions;
    }

    /**
     * Lista instituciones/procedencias usadas por pacientes (sin duplicados).
     *
     * @return list<string>
     */
    public function getInstituciones(int $limit = 500): array
    {
        $c = $this->customersTable();
        $rows = $this->db->table('customers')
            ->select("{$c}.institucion")
            ->where("{$c}.deleted", 0)
            ->where("{$c}.institucion IS NOT NULL", null, false)
            ->orderBy("{$c}.institucion", 'ASC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();

        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['institucion'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $name;
        }

        usort($out, static fn (string $a, string $b): int => strcasecmp($a, $b));
        return $out;
    }

    /**
     * Aplica una transformación de texto a nombre y apellidos de todos los pacientes activos.
     *
     * @return array{success: bool, message: string, patients_updated: int, unchanged: int}
     */
    public function transformAllNames(string $mode): array
    {
        $serviceClass = \App\Services\LabotestNameTransformService::class;
        $allowedModes = [
            $serviceClass::MODE_UPPERCASE,
            $serviceClass::MODE_TITLE,
        ];
        if (! in_array($mode, $allowedModes, true)) {
            return [
                'success'          => false,
                'message'          => 'Modo de transformación inválido',
                'patients_updated' => 0,
                'unchanged'        => 0,
            ];
        }

        $split = $this->usesSplitNames();
        $p = $this->peopleTable();
        $c = $this->customersTable();
        $select = $split
            ? "{$p}.person_id, {$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom"
            : "{$p}.person_id, {$p}.first_name, {$p}.last_name";

        $rows = $this->db->table('customers')
            ->select($select)
            ->join('people', "{$p}.person_id = {$c}.person_id")
            ->where("{$c}.deleted", 0)
            ->get()
            ->getResultArray();

        $patientsUpdated = 0;
        $unchanged       = 0;

        foreach ($rows as $row) {
            $personId = (int) ($row['person_id'] ?? 0);
            if ($personId < 1) {
                continue;
            }

            $updates = [];
            $firstName = trim((string) ($row['first_name'] ?? ''));
            if ($firstName !== '') {
                $transformed = $serviceClass::transform($firstName, $mode);
                if ($transformed !== $firstName) {
                    $updates['first_name'] = $transformed;
                }
            }

            if ($split) {
                $lastNameFa = trim((string) ($row['last_name_fa'] ?? ''));
                if ($lastNameFa !== '') {
                    $transformed = $serviceClass::transform($lastNameFa, $mode);
                    if ($transformed !== $lastNameFa) {
                        $updates['last_name_fa'] = $transformed;
                    }
                }
                $lastNameMom = trim((string) ($row['last_name_mom'] ?? ''));
                if ($lastNameMom !== '') {
                    $transformed = $serviceClass::transform($lastNameMom, $mode);
                    if ($transformed !== $lastNameMom) {
                        $updates['last_name_mom'] = $transformed;
                    }
                }
            } else {
                $lastName = trim((string) ($row['last_name'] ?? ''));
                if ($lastName !== '') {
                    $transformed = $serviceClass::transform($lastName, $mode);
                    if ($transformed !== $lastName) {
                        $updates['last_name'] = $transformed;
                    }
                }
            }

            if ($updates === []) {
                $unchanged++;
                continue;
            }

            $this->db->table('people')->where('person_id', $personId)->update($updates);
            $patientsUpdated++;
        }

        $modeLabels = [
            $serviceClass::MODE_UPPERCASE => 'MAYÚSCULAS',
            $serviceClass::MODE_TITLE     => 'título',
        ];
        $label = $modeLabels[$mode] ?? $mode;

        return [
            'success'          => true,
            'message'          => $patientsUpdated > 0
                ? "Se actualizaron {$patientsUpdated} paciente(s) (formato {$label})."
                : 'No hubo cambios: los nombres ya cumplen el formato seleccionado.',
            'patients_updated' => $patientsUpdated,
            'unchanged'        => $unchanged,
        ];
    }
}
