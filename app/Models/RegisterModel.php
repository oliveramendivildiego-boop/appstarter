<?php

namespace App\Models;

use CodeIgniter\Model;

class RegisterModel extends Model
{
    protected $table            = 'registro';
    protected $primaryKey       = 'registro_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';

    public function getRegistroTable(): string
    {
        return $this->db->prefixTable('registro');
    }

    public function existsRegistro(int $id): bool
    {
        return $this->db->table('registro')
            ->where('registro_id', $id)
            ->countAllResults() === 1;
    }

    public function existsPago(int $id): bool
    {
        return $this->db->table('pago')
            ->where('pago_id', $id)
            ->countAllResults() === 1;
    }

    public function existsRegvalues(int $id): bool
    {
        return $this->db->table('regvalues')
            ->where('regvalues_id', $id)
            ->countAllResults() === 1;
    }

    public function existsAnalisis(int $id): bool
    {
        return $this->db->table('resulanalisis')
            ->where('resulanalisis_id', $id)
            ->countAllResults() === 1;
    }

    /**
     * Obtiene todos los registros de análisis con paciente, doctor y pago
     */
    public function getAllAnalisis(int $limit = 10000, int $offset = 0): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        return $this->db->table('registro')
            ->select("{$r}.*, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor, {$d}.phone_number as doctor_phone, {$pa}.total as total, {$pa}.total as acuenta, {$pa}.total as saldo")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->orderBy("{$r}.registro_id", 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    /**
     * Obtiene registros recientes (sin requerir pago)
     */
    public function getRecentRegisters(int $limit = 10, int $offset = 0): array
    {
        $r = $this->getRegistroTable();
        $p = $this->db->prefixTable('people');
        $d = $this->db->prefixTable('doctors');

        return $this->db->table('registro')
            ->select("{$r}.*, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente, {$d}.name as doctor")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->orderBy("{$r}.registro_id", 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    public function countAll(): int
    {
        return $this->db->table('registro')->countAllResults();
    }

    /**
     * Cuenta registros por rango de fechas (campo ingreso)
     */
    public function countByDate(string $dateFrom, string $dateTo): int
    {
        $r = $this->getRegistroTable();
        return $this->db->table('registro')
            ->where("DATE({$r}.ingreso) >=", $dateFrom)
            ->where("DATE({$r}.ingreso) <=", $dateTo)
            ->countAllResults();
    }

    /**
     * Obtiene info para rellenar formulario (registro + people + doctor)
     */
    public function getInfoRefill($id)
    {
        $r = $this->getRegistroTable();
        $p = $this->db->prefixTable('people');
        $d = $this->db->prefixTable('doctors');

        return $this->db->table('registro')
            ->select("{$r}.*, {$p}.*, {$d}.doctor_id AS doctor_doctor_id, {$d}.name AS doctor_name, {$d}.gender AS doctor_gender,
                {$d}.address AS doctor_address, {$d}.comments AS doctor_comments, {$d}.speciality AS doctor_specialty, {$d}.phone_number AS doctor_phone")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->where('registro.registro_id', (int) $id)
            ->limit(1)
            ->get()
            ->getRow();
    }

    public function getInfoAnalisis(int $id): array
    {
        return $this->db->table('regvalues')
            ->where('registro_id', $id)
            ->orderBy('regvalues_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getFormula(int $id)
    {
        return $this->db->table('formulas')
            ->select('nombre_fun')
            ->where('formulas_id', $id)
            ->get()
            ->getRow();
    }

    public function getAnalisisCompleja(int $id)
    {
        $s = $this->db->prefixTable('secanacategoria');
        $p = $this->db->prefixTable('prianacategoria');
        $a = $this->db->prefixTable('anacategoria');

        return $this->db->table('secanacategoria')
            ->select("{$s}.*, {$a}.name as padre, {$p}.name as hijo")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$s}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$s}.secanacategoria_id", $id)
            ->get()
            ->getRow();
    }

    public function getAnalisisNocompleja(int $id)
    {
        $pr = $this->db->prefixTable('priresultados');
        $p  = $this->db->prefixTable('prianacategoria');
        $a  = $this->db->prefixTable('anacategoria');

        return $this->db->table('priresultados')
            ->select("{$pr}.*, {$a}.name as padre, {$p}.name as hijo, {$p}.name as nombre")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$pr}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$pr}.priresultados_id", $id)
            ->get()
            ->getRow();
    }

    public function getInforeport(int $id)
    {
        return $this->db->table('registro')
            ->join('regvalues', 'regvalues.registro_id = registro.registro_id')
            ->where('registro.registro_id', $id)
            ->get()
            ->getRow();
    }

    public function getInfoPaciente(int $id)
    {
        return $this->db->table('registro')
            ->select('people.*')
            ->join('people', 'people.person_id = registro.person_id')
            ->where('registro.person_id', $id)
            ->limit(1)
            ->get()
            ->getRow();
    }

    /**
     * Obtiene datos del paciente por person_id (sin necesidad de registro)
     */
    public function getPatientById(int $personId)
    {
        return $this->db->table('people')
            ->where('person_id', $personId)
            ->limit(1)
            ->get()
            ->getRow();
    }

    public function getInfoDoctor(int $id)
    {
        return $this->db->table('registro')
            ->select('doctors.*')
            ->join('doctors', 'doctors.doctor_id = registro.doctor_id')
            ->where('registro.doctor_id', $id)
            ->limit(1)
            ->get()
            ->getRow();
    }

    /**
     * Obtiene sub-clases para una prueba compuesta, agrupadas por nombre.
     * Solo una fila por sub-clase (ej. un solo CHCM), eligiendo la referencia
     * según la población del paciente (paciente_id exacto, o 3=Todos como fallback).
     */
    public function getValoresCompleja(int $prianacategoriaId, int $paciente): array
    {
        $paciente = (int) $paciente;
        $rows = $this->db->table('secanacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->groupStart()
            ->where('paciente_id', $paciente)
            ->orWhere('paciente_id', 3)
            ->groupEnd()
            ->where('deleted', 0)
            ->orderBy("CASE WHEN paciente_id = {$paciente} THEN 0 ELSE 1 END", 'ASC')
            ->get()
            ->getResultArray();

        $porNombre = [];
        foreach ($rows as $r) {
            $nombre = trim($r['nombre'] ?? '');
            if ($nombre === '') {
                continue;
            }
            $pid = (int) ($r['paciente_id'] ?? 3);
            if (!isset($porNombre[$nombre])) {
                $porNombre[$nombre] = $r;
            } elseif ($pid === $paciente) {
                $porNombre[$nombre] = $r;
            }
        }
        return array_values($porNombre);
    }

    /**
     * Obtiene pruebas con sus inputs para el formfill
     */
    public function getPruebasInput(string $valores, int $paciente): array
    {
        $parts = explode(',', $valores);
        $ids = [];
        foreach ($parts as $p) {
            $p = trim($p);
            $id = preg_match('/contador_(\d+)/', $p, $m) ? (int) $m[1] : (int) $p;
            if ($id > 0) $ids[] = $id;
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            return [];
        }

        $pt = $this->db->prefixTable('prianacategoria');
        $ac = $this->db->prefixTable('anacategoria');
        $pr = $this->db->prefixTable('priresultados');

        $sql = "SELECT DISTINCT pt.name as hijo, pt.compleja, pt.prianacategoria_id, ac.name as padre,
                pr.opcion_id, pr.priresultados_id, pr.id_poblacion
                FROM {$pt} pt
                LEFT JOIN {$ac} ac ON ac.anacategoria_id = pt.anacategoria_id
                LEFT JOIN {$pr} pr ON pr.prianacategoria_id = pt.prianacategoria_id
                    AND pt.compleja = 0 AND (pr.id_poblacion = 3 OR pr.id_poblacion = ?)
                WHERE (pt.deleted = 0 OR pt.deleted IS NULL)
                AND (ac.deleted = 0 OR ac.deleted IS NULL)
                AND pt.prianacategoria_id IN (" . implode(',', array_map('intval', $ids)) . ")
                ORDER BY ac.order, pt.order";
        return $this->db->query($sql, [$paciente])->getResultArray();
    }

    public function getOpciones(int $id): array
    {
        $row = $this->db->table('opciones')
            ->select('tabla')
            ->where('opciones_id', $id)
            ->get()
            ->getRow();
        if (!$row) {
            return [];
        }
        return $this->getOpcionesValores($row->tabla);
    }

    private function getOpcionesValores(string $nombreTabla): array
    {
        $rows = $this->db->table($nombreTabla)->get()->getResult();
        $options = [];
        foreach ($rows as $r) {
            $v = $r->{$nombreTabla} ?? '';
            $options[$v] = $v;
        }
        return $options;
    }

    public function searchPaciente(string $search, int $limit = 25): array
    {
        $search = trim($search);
        if ($search === '') return [];
        $esc = $this->db->escapeLikeString($search);

        $pat = '%' . $esc . '%';
        $rows = $this->db->table('people')
            ->where("first_name != '' AND first_name != '0'")
            ->where("last_name_fa != '' AND last_name_fa != '0'")
            ->groupStart()
            ->like('first_name', $esc, 'both')
            ->orLike('last_name_fa', $esc, 'both')
            ->orLike('ci', $esc, 'both')
            ->orWhere("CONCAT(first_name, ' ', last_name_fa) LIKE", $pat)
            ->groupEnd()
            ->orderBy('last_name_fa', 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();

        $suggestions = [];
        foreach ($rows as $r) {
            $full = trim(($r->first_name ?? '') . ' ' . ($r->last_name_fa ?? '') . ' ' . ($r->last_name_mom ?? ''));
            if ($full !== '') {
                $ci = trim($r->ci ?? '');
                $display = $ci !== '' ? $full . ' (CI: ' . $ci . ')' : $full;
                $suggestions[] = ['value' => $display, 'data' => $r->person_id];
            }
        }
        return $suggestions;
    }

    public function searchDoctor(string $search, int $limit = 25): array
    {
        $search = trim($search);
        if ($search === '') return [];
        $esc = $this->db->escapeLikeString($search);

        $rows = $this->db->table('doctors')
            ->where("name != '' AND name != '0'")
            ->like('name', $esc, 'both')
            ->orderBy('name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();

        $suggestions = [];
        foreach ($rows as $r) {
            $name = trim($r->name ?? '');
            if ($name !== '') {
                $suggestions[] = ['value' => $name, 'data' => $r->doctor_id];
            }
        }
        return $suggestions;
    }

    /**
     * Obtiene todos los registros de un paciente ordenados por fecha
     */
    public function getRegistrosByPersonId(int $personId, int $limit = 200): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        return $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas, {$r}.person_id,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor, {$pa}.total as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    /**
     * Obtiene antecedentes: resultados previos del mismo paciente para comparar
     * @param int $personId ID del paciente
     * @param int $currentRegistroId Excluir este registro
     * @param int $limit Máximo de registros anteriores a incluir
     */
    public function getAntecedentesPaciente(int $personId, int $currentRegistroId = 0, int $limit = 10): array
    {
        return $this->db->table('registro')
            ->select('registro_id, ingreso, pruebas, doctor_id')
            ->where('person_id', $personId)
            ->where('registro_id !=', $currentRegistroId)
            ->orderBy('ingreso', 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    /**
     * Busca pacientes para autocompletado (por nombre)
     */
    public function searchPacienteForExpediente(string $search, int $limit = 20): array
    {
        return $this->searchPaciente($search, $limit);
    }

    public function searchPrueba(string $search, int $limit = 25): array
    {
        $search = trim($search);
        if ($search === '') return [];
        $esc = $this->db->escapeLikeString($search);

        $rows = $this->db->table('prianacategoria')
            ->where("name != '' AND name != '0'")
            ->where('deleted', 0)
            ->like('name', $esc, 'both')
            ->orderBy('name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();

        $suggestions = [];
        foreach ($rows as $r) {
            $name = trim($r->name ?? '');
            if ($name !== '') {
                $suggestions[] = ['value' => $name, 'data' => $r->prianacategoria_id];
            }
        }
        return $suggestions;
    }

    public function saveRegistro(array $data, $id = null)
    {
        if ($id === null || !$this->existsRegistro((int) $id)) {
            $this->db->table('registro')->insert($data);
            return (int) $this->db->insertID();
        }
        $this->db->table('registro')->where('registro_id', $id)->update($data);
        return (int) $id;
    }

    public function savePago(array $data, $id = null): bool
    {
        if ($id === null || !$this->existsPago((int) $id)) {
            return $this->db->table('pago')->insert($data) !== false;
        }
        return $this->db->table('pago')->where('pago_id', $id)->update($data);
    }

    public function saveRegvalues(array $data, $id = null): bool
    {
        if ($id === null || !$this->existsRegvalues((int) $id)) {
            return $this->db->table('regvalues')->insert($data) !== false;
        }
        return $this->db->table('regvalues')->where('regvalues_id', $id)->update($data);
    }

    public function saveAnalisis(array $data, $id = null): bool
    {
        if ($id === null || !$this->existsAnalisis((int) $id)) {
            return $this->db->table('resulanalisis')->insert($data) !== false;
        }
        return $this->db->table('resulanalisis')->where('resulanalisis_id', $id)->update($data);
    }

    /**
     * Obtiene resulanalisis por registro_id (para validación)
     */
    public function getResulanalisisByRegistro(int $registroId): array
    {
        return $this->db->table('resulanalisis')
            ->where('registro_id', $registroId)
            ->get()
            ->getResultArray();
    }

    /**
     * Actualiza validación técnica/médica en resulanalisis del registro
     */
    public function validarResultados(int $registroId, string $tipo, ?string $observaciones = null): bool
    {
        $upd = ['fecha_validacion' => date('Y-m-d H:i:s')];
        if ($tipo === 'tecnico') {
            $upd['validado_tecnico'] = 1;
        } elseif ($tipo === 'medico') {
            $upd['validado_medico'] = 1;
        }
        if ($observaciones !== null) {
            $upd['observaciones_clinicas'] = $observaciones;
        }
        return $this->db->table('resulanalisis')
            ->where('registro_id', $registroId)
            ->update($upd);
    }
}
