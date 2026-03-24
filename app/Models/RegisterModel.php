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

    /**
     * Elimina todos los regvalues de un registro (antes de guardar los nuevos desde formfill).
     */
    public function deleteRegvaluesByRegistroId(int $registroId): bool
    {
        return $this->db->table('regvalues')
            ->where('registro_id', $registroId)
            ->delete() !== false;
    }

    /**
     * Elimina un registro y sus datos relacionados (pago, regvalues, resulanalisis, muestra)
     */
    public function deleteRegistro(int $registroId): bool
    {
        $this->db->table('regvalues')->where('registro_id', $registroId)->delete();
        $this->db->table('resulanalisis')->where('registro_id', $registroId)->delete();
        $this->db->table('muestra')->where('registro_id', $registroId)->delete();
        if ($this->db->tableExists('pago_abono')) {
            $this->db->table('pago_abono')->where('registro_id', $registroId)->delete();
        }
        $this->db->table('pago')->where('registro_id', $registroId)->delete();
        return $this->db->table('registro')->where('registro_id', $registroId)->delete() !== false;
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
    public function getAllAnalisis(int $limit = 10000, int $offset = 0, string $estado = ''): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $builder = $this->db->table('registro')
            ->select("{$r}.*, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$p}.phone_number as paciente_phone,
                {$d}.name as doctor, {$d}.phone_number as doctor_phone,
                {$pa}.total as total, {$pa}.monto_pagar as monto_pagar, {$pa}.tipopago as tipopago, {$pa}.saldo as saldo,
                (SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) AS regvalues_count")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->orderBy("{$r}.registro_id", 'DESC');
        $builder = $this->applyEstadoFilter($builder, $estado, $r, $rv);
        return $builder->limit($limit, $offset)->get()->getResult();
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

    public function countAll(string $estado = ''): int
    {
        $r  = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');
        $builder = $this->db->table('registro');
        $builder = $this->applyEstadoFilter($builder, $estado, $r, $rv);
        return $builder->countAllResults();
    }

    /**
     * Cuenta registros con filtro de búsqueda (código prueba, nombre, apellidos, CI)
     */
    public function countWithSearch(string $q, string $estado = ''): int
    {
        $q = trim($q);
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');
        $builder = $this->db->table('registro')
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $builder = $this->applySearchBuilder($builder, $q);
        $builder = $this->applyEstadoFilter($builder, $estado, $r, $rv);
        return $builder->countAllResults();
    }

    /**
     * Obtiene registros con búsqueda (código prueba, nombre, apellidos, CI) y paginación
     */
    public function getAllAnalisisWithSearch(string $q, int $limit = 50, int $offset = 0, string $estado = ''): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $builder = $this->db->table('registro')
            ->select("{$r}.*, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom, {$p}.ci, {$p}.phone_number as paciente_phone,
                {$d}.name as doctor, {$d}.phone_number as doctor_phone,
                {$pa}.total as total, {$pa}.monto_pagar as monto_pagar, {$pa}.tipopago as tipopago, {$pa}.saldo as saldo,
                (SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) AS regvalues_count")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->orderBy("{$r}.registro_id", 'DESC');

        $builder = $this->applySearchBuilder($builder, $q);
        $builder = $this->applyEstadoFilter($builder, $estado, $r, $rv);
        return $builder->limit($limit, $offset)->get()->getResult();
    }

    private function applySearchBuilder($builder, string $q)
    {
        $q = trim($q);
        if ($q === '') return $builder;

        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $esc = $this->db->escapeLikeString($q);

        $builder->groupStart()
            ->like("{$p}.first_name", $esc, 'both')
            ->orLike("{$p}.last_name_fa", $esc, 'both')
            ->orLike("{$p}.last_name_mom", $esc, 'both')
            ->orLike("{$p}.ci", $esc, 'both');
        // Búsqueda por código de prueba (prianacategoria_id): si q es numérico, buscar por FIND_IN_SET
        if (ctype_digit($q)) {
            $idPrueba = (int) $q;
            $builder->orWhere("FIND_IN_SET(" . $this->db->escape($idPrueba) . ", {$r}.pruebas) > 0", null, false);
        }
        $builder->groupEnd();

        return $builder;
    }

    /**
     * Filtra por estado: completo (tiene regvalues) o incompleto (sin regvalues)
     */
    private function applyEstadoFilter($builder, string $estado, string $r, string $rv)
    {
        $estado = trim($estado);
        if ($estado === 'completo') {
            $builder->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false);
        } elseif ($estado === 'incompleto') {
            $builder->where("NOT EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false);
        }
        return $builder;
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

        $mostrarValoresSql = $this->hasColumn('prianacategoria', 'mostrar_valores')
            ? "{$p}.mostrar_valores as mostrar_valores"
            : "0 as mostrar_valores";

        return $this->db->table('priresultados')
            ->select("{$pr}.*, {$a}.name as padre, {$p}.name as hijo, {$p}.name as nombre, {$mostrarValoresSql}")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$pr}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$pr}.priresultados_id", $id)
            ->get()
            ->getRow();
    }

    /**
     * Obtiene todos los valores de referencia (priresultados) de una prueba no compuesta
     * para mostrarlos en el reporte aunque no tengan resultado cargado.
     */
    public function getAllPriResultadosByPrianacategoriaForReport(int $prianacategoriaId): array
    {
        $pr = $this->db->prefixTable('priresultados');
        $p  = $this->db->prefixTable('prianacategoria');
        $a  = $this->db->prefixTable('anacategoria');
        $mostrarValoresSql = $this->hasColumn('prianacategoria', 'mostrar_valores')
            ? "{$p}.mostrar_valores as mostrar_valores"
            : "0 as mostrar_valores";

        return $this->db->table('priresultados')
            ->select("{$pr}.*, {$a}.name as padre, {$p}.name as hijo, {$p}.name as nombre, {$mostrarValoresSql}")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$pr}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$pr}.prianacategoria_id", $prianacategoriaId)
            ->where("({$pr}.deleted = 0 OR {$pr}.deleted IS NULL)")
            ->orderBy("{$pr}.id_poblacion", 'ASC')
            ->orderBy("{$pr}.priresultados_id", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Retorna ids de prianacategoria con mostrar_valores = 1 para un conjunto dado.
     */
    public function getPrianacategoriasMostrarValoresIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($x) => $x > 0)));
        if (empty($ids) || !$this->hasColumn('prianacategoria', 'mostrar_valores')) {
            return [];
        }
        $pt = $this->db->prefixTable('prianacategoria');
        $rows = $this->db->table('prianacategoria')
            ->select("{$pt}.prianacategoria_id")
            ->whereIn("{$pt}.prianacategoria_id", $ids)
            ->where("{$pt}.mostrar_valores", 1)
            ->where("({$pt}.deleted = 0 OR {$pt}.deleted IS NULL)")
            ->get()
            ->getResultArray();
        return array_values(array_unique(array_map(static fn($r) => (int)($r['prianacategoria_id'] ?? 0), $rows)));
    }

    /**
     * Retorna configuración básica de prianacategoria por ids.
     * @return array<int,array{prianacategoria_id:int,compleja:int,mostrar_valores:int,name:string,anacategoria_id:int}>
     */
    public function getPrianacategoriaConfigByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($x) => $x > 0)));
        if (empty($ids)) {
            return [];
        }
        $pt = $this->db->prefixTable('prianacategoria');
        $select = "{$pt}.prianacategoria_id, {$pt}.anacategoria_id, {$pt}.name, {$pt}.compleja";
        if ($this->hasColumn('prianacategoria', 'mostrar_valores')) {
            $select .= ", {$pt}.mostrar_valores";
        } else {
            $select .= ", 0 as mostrar_valores";
        }
        return $this->db->table('prianacategoria')
            ->select($select)
            ->whereIn("{$pt}.prianacategoria_id", $ids)
            ->where("({$pt}.deleted = 0 OR {$pt}.deleted IS NULL)")
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene TODAS las sub-pruebas (secanacategoria) de una prueba compuesta
     * para reporte, sin filtrar por población/sexo.
     */
    public function getAllSecItemsByPrianacategoriaForReport(int $prianacategoriaId): array
    {
        $s = $this->db->prefixTable('secanacategoria');
        $p = $this->db->prefixTable('prianacategoria');
        $a = $this->db->prefixTable('anacategoria');
        $mostrarValoresSql = $this->hasColumn('prianacategoria', 'mostrar_valores')
            ? "{$p}.mostrar_valores as mostrar_valores"
            : "0 as mostrar_valores";

        return $this->db->table('secanacategoria')
            ->select("{$s}.*, {$a}.name as padre, {$p}.name as hijo, {$mostrarValoresSql}")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$s}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$s}.prianacategoria_id", $prianacategoriaId)
            ->where("({$s}.deleted = 0 OR {$s}.deleted IS NULL)")
            ->orderBy("{$s}.orden", 'ASC')
            ->orderBy("{$s}.secanacategoria_id", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene la fila del registro (para reporte: person_id, doctor_id, ingreso).
     * No hace JOIN con regvalues para que el reporte muestre paciente/doctor aunque aún no haya valores guardados.
     */
    public function getInforeport(int $id)
    {
        return $this->db->table('registro')
            ->where('registro_id', $id)
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
     * según la población del paciente (paciente_id = edad/sexo) y opcionalmente sexo.
     *
     * @param int $prianacategoriaId
     * @param int $paciente Tipo de paciente (0=Niños, 1=Masculino, 2=Femenino, 3=Todos, 4=RN, 5=Lactante)
     * @param int|null $gender Género del paciente (1=masculino, 2=femenino) para filtrar por sexo si existe columna
     */
    public function getValoresCompleja(int $prianacategoriaId, int $paciente, ?int $gender = null): array
    {
        $paciente = (int) $paciente;
        $builder = $this->db->table('secanacategoria')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->groupStart()
            ->where('paciente_id', $paciente)
            ->orWhere('paciente_id', 3)
            ->groupEnd()
            ->where('deleted', 0);

        if ($gender !== null && ($gender === 1 || $gender === 2) && $this->hasColumn('secanacategoria', 'sexo')) {
            $sexoVal = $gender === 1 ? 'masculino' : 'femenino';
            $builder->groupStart()
                ->where('sexo', 'ambos')
                ->orWhere('sexo', $sexoVal)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy("CASE WHEN paciente_id = {$paciente} THEN 0 ELSE 1 END", 'ASC', false)
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
     * Siempre devuelve todas las sub-clases de una prueba compuesta.
     * Identificación por nombre: una fila por nombre (ej. Eritrocitos = Eritrocitos).
     * Se elige la fila que coincida con edad/sexo del paciente: paciente_id (Adulto Masculino=1,
     * Femenino=2, Todos=3, etc.) y si existe columna sexo se filtra por masculino/femenino/ambos.
     * Así para paciente adulto femenino se usa la fila con valor_min/max y fórmula de esa fila
     * (ej. Eritrocitos Adulto Femenino 4500000-6000000, Formula Eritrocitos).
     */
    public function getValoresComplejaSiempre(int $prianacategoriaId, int $paciente, ?int $gender = null): array
    {
        $paciente = (int) $paciente;
        $sec = $this->db->prefixTable('secanacategoria');
        $f = $this->db->prefixTable('formulas');
        $builder = $this->db->table('secanacategoria')
            ->select("{$sec}.*, {$f}.formula_expresion AS formula_expresion_desde_formulas")
            ->join('formulas', "{$f}.formulas_id = {$sec}.formulas_id", 'left')
            ->where("{$sec}.prianacategoria_id", $prianacategoriaId)
            ->where("{$sec}.deleted", 0)
            ->groupStart()
            ->where("{$sec}.paciente_id", $paciente)
            ->orWhere("{$sec}.paciente_id", 3);
        if ($paciente === 3) {
            $builder->orWhere("{$sec}.paciente_id", 1)->orWhere("{$sec}.paciente_id", 2);
        }
        $builder->groupEnd();

        if ($gender !== null && ($gender === 1 || $gender === 2) && $this->hasColumn('secanacategoria', 'sexo')) {
            $sexoVal = $gender === 1 ? 'masculino' : 'femenino';
            $builder->groupStart()
                ->where("{$sec}.sexo", 'ambos')
                ->orWhere("{$sec}.sexo", $sexoVal)
                ->orWhere("{$sec}.sexo IS NULL", null, false)
                ->orWhere("{$sec}.sexo = ''", null, false)
                ->groupEnd();
        }

        if ($this->hasColumn('secanacategoria', 'orden')) {
            $builder->orderBy("{$sec}.orden", 'ASC');
        }
        $rows = $builder
            ->orderBy("CASE WHEN {$sec}.paciente_id = {$paciente} THEN 0 WHEN {$sec}.paciente_id = 3 THEN 1 ELSE 2 END", 'ASC', false)
            ->orderBy("{$sec}.nombre", 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            $builder2 = $this->db->table('secanacategoria')
                ->select("{$sec}.*, {$f}.formula_expresion AS formula_expresion_desde_formulas")
                ->join('formulas', "{$f}.formulas_id = {$sec}.formulas_id", 'left')
                ->where("{$sec}.prianacategoria_id", $prianacategoriaId)
                ->where("{$sec}.deleted", 0);
            if ($this->hasColumn('secanacategoria', 'orden')) {
                $builder2->orderBy("{$sec}.orden", 'ASC');
            }
            $rows = $builder2->orderBy("{$sec}.nombre", 'ASC')->get()->getResultArray();
        }

        $porNombre = [];
        foreach ($rows as $r) {
            $nombre = trim($r['nombre'] ?? '');
            if ($nombre === '') {
                continue;
            }
            if (!isset($porNombre[$nombre])) {
                $exprFromFormulas = trim($r['formula_expresion_desde_formulas'] ?? '');
                if ($exprFromFormulas !== '' && (int)($r['formulas_id'] ?? 0) > 1) {
                    $r['formula_expresion'] = $exprFromFormulas;
                }
                unset($r['formula_expresion_desde_formulas']);
                $porNombre[$nombre] = $r;
            }
        }
        $result = array_values($porNombre);
        if ($this->hasColumn('secanacategoria', 'orden')) {
            usort($result, static function ($a, $b) {
                return ((int) ($a['orden'] ?? 0)) <=> ((int) ($b['orden'] ?? 0));
            });
        }
        return $result;
    }

    /**
     * Obtiene una sub-clase (secanacategoria) por prianacategoria_id y nombre.
     * Usado en reporte cuando regvalues.name viene como "prianacategoria_id|nombre".
     */
    public function getSecItemByPrianacategoriaYNombre(int $prianacategoriaId, string $nombre)
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return null;
        }
        $s = $this->db->prefixTable('secanacategoria');
        $p = $this->db->prefixTable('prianacategoria');
        $a = $this->db->prefixTable('anacategoria');

        $mostrarValoresSql = $this->hasColumn('prianacategoria', 'mostrar_valores')
            ? "{$p}.mostrar_valores as mostrar_valores"
            : "0 as mostrar_valores";

        return $this->db->table('secanacategoria')
            ->select("{$s}.*, {$a}.name as padre, {$p}.name as hijo, {$mostrarValoresSql}")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$s}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$s}.prianacategoria_id", $prianacategoriaId)
            ->where("{$s}.nombre", $nombre)
            ->where("{$s}.deleted", 0)
            ->limit(1)
            ->get()
            ->getRow();
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            $full = $this->db->prefixTable($table);
            return in_array($column, $this->db->getFieldNames($full), true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Obtiene pruebas con sus inputs para el formfill.
     * Filtra por valores de referencia: poblacion (edad config) y sexo.
     * @param int[] $matchingPoblacionIds ids de poblacion que aplican al paciente (edad según config)
     * @param int|null $gender Género del paciente (1=masculino, 2=femenino) para filtrar por sexo en priresultados
     */
    public function getPruebasInput(string $valores, array $matchingPoblacionIds, ?int $gender = null): array
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
        $mostrarValoresSelect = $this->hasColumn('prianacategoria', 'mostrar_valores')
            ? 'pt.mostrar_valores'
            : '0 AS mostrar_valores';

        $poblacionIn = empty($matchingPoblacionIds) ? "(-1)" : "(" . implode(",", array_map('intval', $matchingPoblacionIds)) . ")";

        $sexoCond = '';
        $sexoCondFallback = '';
        $bindParams = [];
        if ($gender !== null && ($gender === 1 || $gender === 2) && $this->hasColumn('priresultados', 'sexo')) {
            $sexoVal = $gender === 1 ? 'masculino' : 'femenino';
            $sexoCond = " AND (pr.sexo = 'ambos' OR pr.sexo = ? OR pr.sexo IS NULL OR pr.sexo = '')";
            $sexoCondFallback = " AND (prff.sexo = 'ambos' OR prff.sexo = ? OR prff.sexo IS NULL OR prff.sexo = '')";
            $bindParams = [$sexoVal, $sexoVal];
        }

        $sql = "SELECT pt.name as hijo, pt.compleja, pt.prianacategoria_id, {$mostrarValoresSelect}, ac.name as padre,
                pr.opcion_id, pr.priresultados_id, pr.id_poblacion, pr.valor_min, pr.valor_max, pr.umedida,
                (SELECT prfb.opcion_id FROM {$pr} prfb
                 WHERE prfb.prianacategoria_id = pt.prianacategoria_id
                   AND pt.compleja = 0 AND (prfb.deleted = 0 OR prfb.deleted IS NULL)
                 LIMIT 1) AS opcion_id_fallback,
                (SELECT prfb2.priresultados_id FROM {$pr} prfb2
                 WHERE prfb2.prianacategoria_id = pt.prianacategoria_id
                   AND pt.compleja = 0 AND (prfb2.deleted = 0 OR prfb2.deleted IS NULL)
                 LIMIT 1) AS priresultados_id_fallback,
                (SELECT prff.priresultados_id FROM {$pr} prff
                 WHERE prff.prianacategoria_id = pt.prianacategoria_id
                   AND pt.compleja = 0 AND (prff.deleted = 0 OR prff.deleted IS NULL)
                   AND prff.id_poblacion IN {$poblacionIn}{$sexoCondFallback}
                 ORDER BY CASE WHEN prff.id_poblacion = 3 THEN 1 ELSE 0 END
                 LIMIT 1) AS priresultados_id_filtered
                FROM {$pt} pt
                LEFT JOIN {$ac} ac ON ac.anacategoria_id = pt.anacategoria_id
                LEFT JOIN {$pr} pr ON pr.prianacategoria_id = pt.prianacategoria_id
                    AND pt.compleja = 0 AND (pr.deleted = 0 OR pr.deleted IS NULL) AND pr.id_poblacion IN {$poblacionIn}{$sexoCond}
                WHERE (pt.deleted = 0 OR pt.deleted IS NULL)
                AND (ac.deleted = 0 OR ac.deleted IS NULL)
                AND pt.prianacategoria_id IN (" . implode(',', array_map('intval', $ids)) . ")
                ORDER BY ac.order, pt.order, CASE WHEN pr.id_poblacion = 3 THEN 1 ELSE 0 END";
        $rows = empty($bindParams) ? $this->db->query($sql)->getResultArray() : $this->db->query($sql, $bindParams)->getResultArray();

        $needFallbackData = [];
        $byPria = [];
        $especificos = array_values(array_filter($matchingPoblacionIds, fn($x) => $x !== 3));
        $preferPoblacion = $especificos[0] ?? 3;
        foreach ($rows as $r) {
            $compleja = (int) ($r['compleja'] ?? 0);
            $pid = (int) ($r['prianacategoria_id'] ?? 0);
            if ($compleja === 0 && empty($r['priresultados_id'])) {
                $filteredId = (int) ($r['priresultados_id_filtered'] ?? 0);
                if ($filteredId > 0) {
                    $needFallbackData[$pid] = ['row' => $r, 'priresultados_id' => $filteredId];
                }
                continue;
            }
            if ($compleja === 1 || !isset($byPria[$pid]) || (int)($r['id_poblacion'] ?? 0) === ($preferPoblacion ?? -1)) {
                if (((int) ($r['opcion_id'] ?? 0)) <= 0 && ((int) ($r['opcion_id_fallback'] ?? 0)) > 0) {
                    $r['opcion_id'] = $r['opcion_id_fallback'];
                }
                if (((int) ($r['priresultados_id'] ?? 0)) <= 0 && ((int) ($r['priresultados_id_fallback'] ?? 0)) > 0) {
                    $r['priresultados_id'] = $r['priresultados_id_fallback'];
                }
                unset($r['opcion_id_fallback'], $r['priresultados_id_fallback'], $r['priresultados_id_filtered']);
                $byPria[$pid] = $r;
            }
        }

        if (!empty($needFallbackData)) {
            $prIds = array_unique(array_column($needFallbackData, 'priresultados_id'));
            $fallbackRows = $this->db->table('priresultados')
                ->select('priresultados_id, prianacategoria_id, opcion_id, valor_min, valor_max, umedida, id_poblacion')
                ->whereIn('priresultados_id', $prIds)
                ->get()
                ->getResultArray();
            $fallbackById = [];
            foreach ($fallbackRows as $fr) {
                $fallbackById[(int) $fr['priresultados_id']] = $fr;
            }
            foreach ($needFallbackData as $pid => $data) {
                $prId = $data['priresultados_id'];
                $fr = $fallbackById[$prId] ?? null;
                if (!$fr) continue;
                $r = $data['row'];
                $r['priresultados_id'] = $fr['priresultados_id'];
                $r['opcion_id'] = $fr['opcion_id'] ?? $r['opcion_id_fallback'] ?? 0;
                $r['valor_min'] = $fr['valor_min'] ?? '';
                $r['valor_max'] = $fr['valor_max'] ?? '';
                $r['umedida'] = $fr['umedida'] ?? '';
                $r['id_poblacion'] = $fr['id_poblacion'] ?? 3;
                unset($r['opcion_id_fallback'], $r['priresultados_id_fallback'], $r['priresultados_id_filtered']);
                $byPria[$pid] = $r;
            }
        }

        return array_values($byPria);
    }

    public function getOpciones(int $id): array
    {
        $row = $this->db->table('opciones')
            ->select('tabla, opciones')
            ->where('opciones_id', $id)
            ->get()
            ->getRow();
        if (!$row) {
            return [];
        }
        $opcionesName = trim($row->opciones ?? '');
        $tabla = trim($row->tabla ?? '');
        $options = [];

        if ($tabla === 'opcion_valores') {
            $options = $this->getOpcionesValoresGenericos($id);
        } elseif ($tabla !== '') {
            $options = $this->getOpcionesValores($tabla);
        }

        if (empty($options)) {
            if (stripos($opcionesName, 'positivo') !== false) {
                return ['Positivo' => 'Positivo', 'Negativo' => 'Negativo'];
            }
            if (stripos($opcionesName, 'reactivo') !== false) {
                $fromTable = $this->getOpcionesValores('opcreactivo');
                return !empty($fromTable) ? $fromTable : ['Reactivo' => 'Reactivo', 'No reactivo' => 'No reactivo'];
            }
        }
        return $options;
    }

    /**
     * Obtiene valores desde la tabla genérica opcion_valores
     */
    private function getOpcionesValoresGenericos(int $opcionesId): array
    {
        try {
            $rows = $this->db->table('opcion_valores')
                ->where('opciones_id', $opcionesId)
                ->orderBy('orden', 'ASC')
                ->orderBy('valor', 'ASC')
                ->get()
                ->getResult();
            $options = [];
            foreach ($rows as $r) {
                $v = trim($r->valor ?? '');
                if ($v !== '') {
                    $options[$v] = $v;
                }
            }
            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getOpcionesValores(string $nombreTabla): array
    {
        try {
            $rows = $this->db->table($nombreTabla)->get()->getResult();
            $options = [];
            $colName = $nombreTabla;
            foreach ($rows as $r) {
                $v = $r->{$colName} ?? '';
                if ($v !== '') {
                    $options[$v] = $v;
                }
            }
            return $options;
        } catch (\Throwable $e) {
            return [];
        }
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
        $rv = $this->db->prefixTable('regvalues');

        return $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas, {$r}.person_id,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor, {$pa}.total as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    /**
     * Obtiene antecedentes: resultados previos del mismo paciente para comparar (solo completos/con regvalues)
     * @param int $personId ID del paciente
     * @param int $currentRegistroId Excluir este registro
     * @param int $limit Máximo de registros anteriores a incluir
     * @param int|null $doctorId Si se provee, solo registros de ese doctor (para portal doctor)
     */
    public function getAntecedentesPaciente(int $personId, int $currentRegistroId = 0, int $limit = 10, ?int $doctorId = null): array
    {
        $r  = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');
        $builder = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas, {$r}.doctor_id")
            ->where("{$r}.person_id", $personId)
            ->where("{$r}.registro_id !=", $currentRegistroId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit);
        if ($doctorId !== null && $doctorId > 0) {
            $builder->where("{$r}.doctor_id", $doctorId);
        }
        return $builder->get()->getResult();
    }

    /**
     * Busca pacientes para autocompletado (por nombre)
     */
    public function searchPacienteForExpediente(string $search, int $limit = 20): array
    {
        return $this->searchPaciente($search, $limit);
    }

    /**
     * Busca pacientes para doctor (solo los que tienen registros con resultados de ese doctor)
     */
    public function searchPacienteForDoctor(string $search, int $doctorId, int $limit = 20): array
    {
        $search = trim($search);
        if ($search === '') return [];
        $esc = $this->db->escapeLikeString($search);
        $pat = '%' . $esc . '%';
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $rv = $this->db->prefixTable('regvalues');

        $rows = $this->db->table('people')
            ->select("{$p}.person_id, {$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom, {$p}.ci")
            ->join($r, "{$r}.person_id = {$p}.person_id")
            ->where("{$r}.doctor_id", $doctorId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->where("{$p}.first_name != '' AND {$p}.first_name != '0'")
            ->where("{$p}.last_name_fa != '' AND {$p}.last_name_fa != '0'")
            ->groupStart()
            ->like("{$p}.first_name", $esc, 'both')
            ->orLike("{$p}.last_name_fa", $esc, 'both')
            ->orLike("{$p}.ci", $esc, 'both')
            ->orWhere("CONCAT({$p}.first_name, ' ', {$p}.last_name_fa) LIKE", $pat)
            ->groupEnd()
            ->groupBy("{$p}.person_id")
            ->orderBy("{$p}.last_name_fa", 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();

        $suggestions = [];
        foreach ($rows as $row) {
            $full = trim(($row->first_name ?? '') . ' ' . ($row->last_name_fa ?? '') . ' ' . ($row->last_name_mom ?? ''));
            if ($full !== '') {
                $ci = trim($row->ci ?? '');
                $display = $ci !== '' ? $full . ' (CI: ' . $ci . ')' : $full;
                $suggestions[] = ['value' => $display, 'data' => $row->person_id];
            }
        }
        return $suggestions;
    }

    /**
     * Lista de pacientes únicos que tienen registros con resultados (regvalues) de un doctor
     */
    public function getPacientesByDoctorId(int $doctorId, int $limit = 500): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $rv = $this->db->prefixTable('regvalues');
        return $this->db->table('registro')
            ->select("{$p}.person_id, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) as nombre, COUNT(*) as total_registros")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->where("{$r}.doctor_id", $doctorId)
            ->where("{$r}.person_id >", 0)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->groupBy("{$r}.person_id")
            ->orderBy('nombre', 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    /**
     * Registros recientes de un doctor (solo con resultados llenados/regvalues)
     */
    public function getRegistrosByDoctorId(int $doctorId, int $limit = 20, int $offset = 0): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');
        return $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.person_id, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente, {$d}.name as doctor, {$pa}.total as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$r}.doctor_id", $doctorId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    /**
     * Cuenta registros de un doctor (solo con resultados llenados/regvalues).
     */
    public function countRegistrosByDoctorId(int $doctorId): int
    {
        $r  = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');
        return $this->db->table('registro')
            ->where("{$r}.doctor_id", $doctorId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->countAllResults();
    }

    /**
     * Registros de un paciente vistos por un doctor específico
     */
    public function getRegistrosByPersonAndDoctor(int $personId, int $doctorId, int $limit = 200): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');
        return $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas, {$r}.person_id, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente, {$d}.name as doctor, {$pa}.total as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->where("{$r}.doctor_id", $doctorId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    /**
     * Lista de pruebas del historial (desde regvalues) para graficar.
     * Devuelve: [['key' => raw_name, 'label' => nombre legible], ...]
     */
    public function getPruebasByPersonAndDoctor(int $personId, int $doctorId): array
    {
        $r = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');

        $rows = $this->db->table('regvalues')
            ->select("TRIM({$rv}.name) AS prueba_key")
            ->join('registro', "{$r}.registro_id = {$rv}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->where("{$r}.doctor_id", $doctorId)
            ->where("{$rv}.name !=", '')
            ->groupBy("TRIM({$rv}.name)")
            ->get()
            ->getResultArray();

        $pruebas = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['prueba_key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $label = $this->getPruebaLabelFromRawKey($key);
            $pruebas[] = ['key' => $key, 'label' => $label];
        }

        usort($pruebas, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return $pruebas;
    }

    /**
     * Serie histórica por prueba (clave raw de regvalues.name) para paciente+doctor.
     */
    public function getSeriePruebaByPersonAndDoctor(int $personId, int $doctorId, string $pruebaKey): array
    {
        $pruebaKey = trim($pruebaKey);
        if ($pruebaKey === '') {
            return [];
        }

        $r = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');

        $rows = $this->db->table('regvalues')
            ->select("{$r}.registro_id, {$r}.ingreso, {$rv}.name AS prueba_key, {$rv}.regvalues AS valor")
            ->join('registro', "{$r}.registro_id = {$rv}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->where("{$r}.doctor_id", $doctorId)
            ->where("{$rv}.name", $pruebaKey)
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $refs = $this->getReferenceRangeFromRawKey($pruebaKey);
        foreach ($rows as &$row) {
            $row['minimo'] = $refs['minimo'];
            $row['maximo'] = $refs['maximo'];
            $row['label'] = $this->getPruebaLabelFromRawKey($pruebaKey);
        }
        unset($row);

        return $rows;
    }

    private function getPruebaLabelFromRawKey(string $rawKey): string
    {
        if (strpos($rawKey, '|') !== false) {
            [, $nombre] = explode('|', $rawKey, 2);
            $nombre = trim($nombre);
            return $nombre !== '' ? $nombre : $rawKey;
        }

        if (str_starts_with($rawKey, 'c_')) {
            $id = (int) substr($rawKey, 2);
            if ($id > 0) {
                $item = $this->getAnalisisCompleja($id);
                $nombre = trim((string) ($item->nombre ?? ''));
                if ($nombre !== '') {
                    return $nombre;
                }
            }
        }

        if (str_starts_with($rawKey, 'noc_')) {
            $id = (int) substr($rawKey, 4);
            if ($id > 0) {
                $item = $this->getAnalisisNocompleja($id);
                $nombre = trim((string) ($item->hijo ?? $item->nombre ?? ''));
                if ($nombre !== '') {
                    return $nombre;
                }
            }
        }

        return $rawKey;
    }

    /**
     * Obtiene referencia mínima/máxima según clave raw de regvalues.
     * @return array{minimo: string|null, maximo: string|null}
     */
    private function getReferenceRangeFromRawKey(string $rawKey): array
    {
        $min = null;
        $max = null;

        if (strpos($rawKey, '|') !== false) {
            [$priaStr, $nombre] = explode('|', $rawKey, 2);
            $priaId = (int) trim($priaStr);
            $nombre = trim($nombre);
            if ($priaId > 0 && $nombre !== '') {
                $item = $this->getSecItemByPrianacategoriaYNombre($priaId, $nombre);
                if ($item) {
                    $min = (string) ($item->valor_min ?? '');
                    $max = (string) ($item->valor_max ?? '');
                }
            }
        } elseif (str_starts_with($rawKey, 'c_')) {
            $id = (int) substr($rawKey, 2);
            if ($id > 0) {
                $item = $this->getAnalisisCompleja($id);
                if ($item) {
                    $min = (string) ($item->valor_min ?? '');
                    $max = (string) ($item->valor_max ?? '');
                }
            }
        } elseif (str_starts_with($rawKey, 'noc_')) {
            $id = (int) substr($rawKey, 4);
            if ($id > 0) {
                $item = $this->getAnalisisNocompleja($id);
                if ($item) {
                    $min = (string) ($item->valor_min ?? '');
                    $max = (string) ($item->valor_max ?? '');
                }
            }
        }

        return [
            'minimo' => ($min !== null && trim($min) !== '') ? $min : null,
            'maximo' => ($max !== null && trim($max) !== '') ? $max : null,
        ];
    }

    public function searchPrueba(string $search, int $limit = 25): array
    {
        $search = trim($search);
        if ($search === '') return [];
        $esc = $this->db->escapeLikeString($search);

        $pri = $this->db->prefixTable('prianacategoria');
        $ana = $this->db->prefixTable('anacategoria');
        $rows = $this->db->table('prianacategoria')
            ->select("{$pri}.prianacategoria_id, {$pri}.name, {$pri}.cost, {$pri}.cost_deriv, {$ana}.name as padre")
            ->join('anacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->where("{$pri}.name != '' AND {$pri}.name != '0'")
            ->where("{$pri}.deleted", 0)
            ->like("{$pri}.name", $esc, 'both')
            ->orderBy("{$pri}.name", 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();

        $suggestions = [];
        foreach ($rows as $r) {
            $name = trim($r->name ?? '');
            if ($name !== '') {
                $padre = trim($r->padre ?? '');
                $suggestions[] = [
                    'value' => $name,
                    'padre' => $padre,
                    'data'  => $r->prianacategoria_id,
                    'cost'  => (float) ($r->cost ?? 0),
                    'refe'  => (float) ($r->cost_deriv ?? 0),
                ];
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

    /**
     * Actualiza pago por registro_id (para agregar pago desde lista)
     */
    public function updatePagoByRegistroId(int $registroId, array $data): bool
    {
        $row = $this->db->table('pago')->where('registro_id', $registroId)->get()->getRow();
        if (!$row) {
            return false;
        }
        $upd = [];
        if (array_key_exists('monto_pagar', $data)) $upd['monto_pagar'] = $data['monto_pagar'];
        if (array_key_exists('tipopago', $data)) $upd['tipopago'] = $data['tipopago'];
        if (array_key_exists('saldo', $data)) $upd['saldo'] = $data['saldo'];
        if (array_key_exists('total', $data)) $upd['total'] = $data['total'];
        if (empty($upd)) return true;
        return $this->db->table('pago')->where('registro_id', $registroId)->update($upd);
    }

    /**
     * Obtiene pago por registro_id
     */
    public function getPagoByRegistroId(int $registroId): ?object
    {
        return $this->db->table('pago')->where('registro_id', $registroId)->get()->getRow();
    }

    /**
     * Inserta un abono (nuevo pago) y actualiza pago.monto_pagar y saldo
     */
    public function insertAbono(int $registroId, float $monto, string $tipopago): bool
    {
        $pago = $this->getPagoByRegistroId($registroId);
        if (!$pago) return false;

        $this->db->table('pago_abono')->insert([
            'registro_id' => $registroId,
            'monto'       => $monto,
            'tipopago'    => $tipopago ?: '1',
        ]);
        $montoActual = (float) ($pago->monto_pagar ?? 0);
        $nuevoMontoPagado = $montoActual + $monto;
        $total = (float) ($pago->total ?? 0);
        $nuevoSaldo = $total - $nuevoMontoPagado;

        return $this->db->table('pago')->where('registro_id', $registroId)->update([
            'monto_pagar' => number_format($nuevoMontoPagado, 2, '.', ''),
            'saldo'       => number_format($nuevoSaldo, 2, '.', ''),
            'tipopago'    => $tipopago ?: $pago->tipopago,
        ]);
    }

    /**
     * Registra el pago inicial en pago_abono (cuando se crea el registro con pago)
     */
    public function insertAbonoInicial(int $registroId, float $monto, string $tipopago): void
    {
        if ($monto <= 0 || !$this->db->tableExists('pago_abono')) {
            return;
        }
        $this->db->table('pago_abono')->insert([
            'registro_id' => $registroId,
            'monto'       => $monto,
            'tipopago'    => $tipopago ?: '1',
        ]);
    }

    /**
     * Obtiene todos los abonos de un registro ordenados por fecha
     */
    public function getAbonosByRegistroId(int $registroId): array
    {
        if (!$this->db->tableExists('pago_abono')) {
            return [];
        }
        return $this->db->table('pago_abono')
            ->where('registro_id', $registroId)
            ->orderBy('fecha_abono', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene historial: pago + pruebas realizadas para un registro
     */
    public function getHistorialRegistro(int $registroId): ?array
    {
        $r = $this->getRegistroTable();
        $p = $this->db->prefixTable('people');
        $d = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');
        $pt = $this->db->prefixTable('prianacategoria');
        $a = $this->db->prefixTable('anacategoria');

        $reg = $this->db->table('registro')
            ->select("{$r}.*, CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente, {$d}.name as doctor")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->where("{$r}.registro_id", $registroId)
            ->get()->getRow();
        if (!$reg) return null;

        $pago = $this->getPagoByRegistroId($registroId);
        $regvaluesCount = (int) $this->db->table('regvalues')->where('registro_id', $registroId)->countAllResults();
        $pruebasStr = trim($reg->pruebas ?? '');
        $pruebaIds = $pruebasStr !== '' ? array_filter(array_map('intval', explode(',', $pruebasStr))) : [];
        $pruebas = [];
        if (!empty($pruebaIds)) {
            $rows = $this->db->table('prianacategoria')
                ->select("{$pt}.prianacategoria_id, {$pt}.name, {$a}.name as categoria")
                ->join('anacategoria', "{$a}.anacategoria_id = {$pt}.anacategoria_id", 'left')
                ->whereIn("{$pt}.prianacategoria_id", array_values($pruebaIds))
                ->get()->getResult();
            foreach ($rows as $row) {
                $pruebas[] = ['id' => $row->prianacategoria_id, 'nombre' => $row->name, 'categoria' => $row->categoria ?? ''];
            }
        }
        $tipoPagoMap = ['1' => 'Efectivo', '2' => 'QR', '3' => 'Transferencia', '4' => 'Pendiente'];
        $abonos = $this->getAbonosByRegistroId($registroId);
        if (empty($abonos) && $pago && (float) ($pago->monto_pagar ?? 0) > 0) {
            $abonos = [[
                'monto' => $pago->monto_pagar,
                'tipopago' => $pago->tipopago ?? '1',
                'fecha_abono' => $reg->ingreso ?? date('Y-m-d H:i:s'),
            ]];
        }
        foreach ($abonos as $i => $a) {
            $abonos[$i]['tipo_nombre'] = $tipoPagoMap[$a['tipopago'] ?? ''] ?? ($a['tipopago'] ?? '-');
        }
        return [
            'registro' => $reg,
            'pago' => $pago,
            'tipo_pago_nombre' => $pago ? ($tipoPagoMap[$pago->tipopago ?? ''] ?? ($pago->tipopago ?? '-')) : '-',
            'abonos' => $abonos,
            'pruebas' => $pruebas,
            'tiene_resultados' => $regvaluesCount > 0,
            'regvalues_count' => $regvaluesCount,
        ];
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
