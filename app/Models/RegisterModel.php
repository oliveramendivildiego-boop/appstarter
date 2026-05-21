<?php

namespace App\Models;

use App\Libraries\RegistroIngresoDateRange;
use App\Services\RegistroFolioService;
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

    /**
     * Resuelve el texto que el usuario ingresa como orden (folio en numero_orden o ID numérico interno).
     * No usar (int) sobre folios con guiones/guiones bajos (p. ej. 04_2026_5).
     */
    public function resolveRegistroIdFromOrdenInput(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $row = $this->db->table('registro')
            ->select('registro_id')
            ->where('numero_orden', $raw)
            ->limit(1)
            ->get()
            ->getRow();
        if ($row !== null) {
            return (int) $row->registro_id;
        }

        if (ctype_digit($raw)) {
            $id = (int) $raw;
            if ($id > 0 && $this->existsRegistro($id)) {
                return $id;
            }
        }

        return null;
    }

    /** @var bool|null */
    private static $registroAnuladoColumnExists = null;

    private function registroTieneColumnaAnulado(): bool
    {
        if (self::$registroAnuladoColumnExists === null) {
            self::$registroAnuladoColumnExists = $this->hasColumn('registro', 'anulado');
        }

        return self::$registroAnuladoColumnExists;
    }

    public function isRegistroAnulado(int $registroId): bool
    {
        if (!$this->registroTieneColumnaAnulado()) {
            return false;
        }
        $row = $this->db->table('registro')->select('anulado')->where('registro_id', $registroId)->get()->getRow();

        return $row !== null && (int) ($row->anulado ?? 0) === 1;
    }

    /** @var bool solo se guarda true: si la columna aún no existía y se crea después, no quedar “cacheado” en false. */
    private static bool $registroFechaHoraReporteFijadaColumnKnown = false;

    private function registroTieneColumnaFechaHoraReporteFijada(): bool
    {
        if (self::$registroFechaHoraReporteFijadaColumnKnown) {
            return true;
        }
        if ($this->hasColumn('registro', 'fecha_hora_reporte_fijada')) {
            self::$registroFechaHoraReporteFijadaColumnKnown = true;

            return true;
        }

        return false;
    }

    /**
     * Valor almacenado de fecha/hora de reporte (Y-m-d H:i:s), o null si no hay columna o valor.
     */
    public function getReporteFechaHoraFijadaMysql(int $registroId): ?string
    {
        if (! $this->registroTieneColumnaFechaHoraReporteFijada()) {
            return null;
        }
        $row = $this->db->table('registro')
            ->select('fecha_hora_reporte_fijada')
            ->where('registro_id', $registroId)
            ->get()
            ->getRow();
        if ($row === null) {
            return null;
        }
        $v = $row->fecha_hora_reporte_fijada ?? null;
        if ($v === null || trim((string) $v) === '') {
            return null;
        }

        return (string) $v;
    }

    /**
     * Si aún no hay fecha fijada, la guarda con $mysqlNow. Devuelve el valor definitivo en BD.
     *
     * @return string|null Y-m-d H:i:s, o null si la columna no existe en la tabla
     */
    public function lockReporteFechaHoraFijada(int $registroId, string $mysqlNow): ?string
    {
        if (! $this->registroTieneColumnaFechaHoraReporteFijada()) {
            return null;
        }
        $existing = $this->getReporteFechaHoraFijadaMysql($registroId);
        if ($existing !== null) {
            return $existing;
        }
        $builder = $this->db->table('registro');
        $builder->where('registro_id', $registroId);
        $builder->where('fecha_hora_reporte_fijada', null);
        $builder->update(['fecha_hora_reporte_fijada' => $mysqlNow]);

        $final = $this->getReporteFechaHoraFijadaMysql($registroId);

        return $final !== null ? $final : $mysqlNow;
    }

    /** @var bool|null */
    private static $registroPublicAccessTokenColumnExists = null;

    private function registroTieneColumnaPublicAccessToken(): bool
    {
        if (self::$registroPublicAccessTokenColumnExists === null) {
            self::$registroPublicAccessTokenColumnExists = $this->hasColumn('registro', 'public_access_token');
        }

        return self::$registroPublicAccessTokenColumnExists;
    }

    /**
     * Token opaco para ver resultados sin iniciar sesión (enlace tipo resultados/{token}).
     * Devuelve null si la columna no existe en BD (ejecute la migración SQL).
     */
    public function ensurePublicAccessToken(int $registroId): ?string
    {
        if ($registroId < 1 || ! $this->registroTieneColumnaPublicAccessToken()) {
            return null;
        }

        $row = $this->db->table('registro')
            ->select('public_access_token')
            ->where('registro_id', $registroId)
            ->get()
            ->getRow();
        if ($row === null) {
            return null;
        }
        $existing = trim((string) ($row->public_access_token ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        for ($i = 0; $i < 8; $i++) {
            $token = bin2hex(random_bytes(32));
            $dup   = $this->db->table('registro')
                ->where('public_access_token', $token)
                ->countAllResults();
            if ($dup > 0) {
                continue;
            }
            $updated = $this->db->table('registro')
                ->where('registro_id', $registroId)
                ->where('public_access_token', null)
                ->update(['public_access_token' => $token]);
            if ($updated) {
                return $token;
            }
            $row2 = $this->db->table('registro')
                ->select('public_access_token')
                ->where('registro_id', $registroId)
                ->get()
                ->getRow();
            if ($row2 !== null && trim((string) ($row2->public_access_token ?? '')) !== '') {
                return trim((string) $row2->public_access_token);
            }
        }

        return null;
    }

    public function findRegistroIdByPublicToken(string $token): ?int
    {
        $token = strtolower(preg_replace('/[^a-f0-9]/', '', $token) ?? '');
        if (strlen($token) !== 64 || ! $this->registroTieneColumnaPublicAccessToken()) {
            return null;
        }

        $row = $this->db->table('registro')
            ->select('registro_id')
            ->where('public_access_token', $token)
            ->get()
            ->getRow();

        return $row !== null ? (int) $row->registro_id : null;
    }

    /**
     * Anulación lógica: el registro permanece en BD y no puede usarse en flujo operativo.
     */
    public function anularRegistro(int $registroId, string $motivo, ?int $personIdAnulo): bool
    {
        if (!$this->registroTieneColumnaAnulado()) {
            return false;
        }
        if ($this->isRegistroAnulado($registroId)) {
            return false;
        }
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 5) {
            return false;
        }

        return $this->db->table('registro')->where('registro_id', $registroId)->update([
            'anulado'            => 1,
            'motivo_anulacion'   => $motivo,
            'fecha_anulacion'    => date('Y-m-d H:i:s'),
            'person_id_anulo'    => $personIdAnulo,
        ]) !== false;
    }

    public function getPersonShortDisplay(int $personId): string
    {
        $row = $this->db->table('people')
            ->select("CONCAT(TRIM(COALESCE(first_name,'')), ' ', TRIM(COALESCE(last_name_fa,''))) AS n")
            ->where('person_id', $personId)
            ->get()
            ->getRow();

        return $row ? trim(preg_replace('/\s+/', ' ', (string) $row->n)) : '';
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
     * Nombre del paciente para listados: apellido paterno, materno, nombres.
     */
    private function sqlPacienteNombreLista(string $peopleAlias): string
    {
        return "TRIM(CONCAT_WS(' ', NULLIF(TRIM({$peopleAlias}.last_name_fa), ''), NULLIF(TRIM({$peopleAlias}.last_name_mom), ''), NULLIF(TRIM({$peopleAlias}.first_name), '')))";
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
        $pacienteSql = $this->sqlPacienteNombreLista($p);

        $builder = $this->db->table('registro')
            ->select("{$r}.*, {$pacienteSql} AS paciente,
                {$p}.phone_number as paciente_phone,
                {$d}.name as doctor, {$d}.phone_number as doctor_phone,
                {$pa}.total as total, {$pa}.monto_pagar as monto_pagar, {$pa}.tipopago as tipopago, {$pa}.saldo as saldo,
                (SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) AS regvalues_count")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
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
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
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
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
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
        $pacienteSql = $this->sqlPacienteNombreLista($p);

        $builder = $this->db->table('registro')
            ->select("{$r}.*, {$pacienteSql} AS paciente,
                {$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom, {$p}.ci, {$p}.phone_number as paciente_phone,
                {$d}.name as doctor, {$d}.phone_number as doctor_phone,
                {$pa}.total as total, {$pa}.monto_pagar as monto_pagar, {$pa}.tipopago as tipopago, {$pa}.saldo as saldo,
                (SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) AS regvalues_count")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
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
            $builder->orWhere("{$r}.registro_id", $idPrueba);
        }
        $builder->orLike("{$r}.numero_orden", $esc, 'both');
        $builder->groupEnd();

        return $builder;
    }

    /**
     * Filtra por estado: completo / incompleto / anulado / activo (no anulado)
     */
    private function applyEstadoFilter($builder, string $estado, string $r, string $rv)
    {
        $estado = trim($estado);
        $hasAnul = $this->registroTieneColumnaAnulado();
        $notAnuladoSql = "COALESCE({$r}.anulado, 0) = 0";

        if ($hasAnul && $estado === 'anulado') {
            $builder->where("{$r}.anulado", 1);

            return $builder;
        }
        if ($hasAnul && $estado === 'activo') {
            $builder->where($notAnuladoSql, null, false);

            return $builder;
        }
        if ($estado === 'completo') {
            if ($hasAnul) {
                $builder->where($notAnuladoSql, null, false);
            }
            $builder->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false);
        } elseif ($estado === 'incompleto') {
            if ($hasAnul) {
                $builder->where($notAnuladoSql, null, false);
            }
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
        $b = RegistroIngresoDateRange::apply($this->db->table('registro'), $r, $dateFrom, $dateTo);
        if ($this->registroTieneColumnaAnulado()) {
            $b->where("COALESCE({$r}.anulado, 0) = 0", null, false);
        }

        return $b->countAllResults();
    }

    /**
     * Obtiene info para rellenar formulario (registro + people + doctor)
     */
    public function getInfoRefill($id)
    {
        $r = $this->getRegistroTable();
        $p = $this->db->prefixTable('people');
        $d = $this->db->prefixTable('doctors');
        $c = $this->db->prefixTable('customers');

        return $this->db->table('registro')
            ->select("{$r}.*, {$p}.*, {$d}.doctor_id AS doctor_doctor_id, {$d}.name AS doctor_name, {$d}.gender AS doctor_gender,
                {$d}.address AS doctor_address, {$d}.comments AS doctor_comments, {$d}.speciality AS doctor_specialty, {$d}.phone_number AS doctor_phone,
                {$c}.institucion AS customer_institucion")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('customers', "{$c}.person_id = {$r}.person_id", 'left')
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

    /**
     * Nombre corto de la prueba (prianacategoria) para reportes.
     */
    public function getPrianacategoriaNombre(int $prianacategoriaId): string
    {
        if ($prianacategoriaId < 1) {
            return '';
        }
        $row = $this->db->table('prianacategoria')
            ->select('name')
            ->where('prianacategoria_id', $prianacategoriaId)
            ->get()
            ->getRow();

        return trim((string) ($row->name ?? ''));
    }

    /**
     * Resuelve prianacategoria_id desde regvalues.name (formato id|nombre, c_*, noc_*).
     * Caches opcionales reducen consultas al procesar muchas filas.
     *
     * @param array<int,int> $nocCache
     * @param array<int,int> $cCache
     */
    public function resolvePrianacategoriaIdFromRegvalueName(string $name, array &$nocCache = [], array &$cCache = []): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        if (preg_match('/^lab_(val|app)_pri_(\d+)$/', $name, $m) === 1) {
            return (int) $m[2];
        }
        if (strpos($name, '|') !== false) {
            [$priaStr] = explode('|', $name, 2);

            return (int) trim($priaStr);
        }
        if (strpos($name, '_') === false) {
            return 0;
        }
        [$tipo, $idStr] = explode('_', $name, 2);
        $id = (int) $idStr;
        if ($id < 1) {
            return 0;
        }
        if ($tipo === 'noc') {
            if (!array_key_exists($id, $nocCache)) {
                $item = $this->getAnalisisNocompleja($id);
                $nocCache[$id] = $item ? (int) ($item->prianacategoria_id ?? 0) : 0;
            }

            return $nocCache[$id];
        }
        if ($tipo === 'c') {
            if (!array_key_exists($id, $cCache)) {
                $item = $this->getAnalisisCompleja($id);
                $cCache[$id] = $item ? (int) ($item->prianacategoria_id ?? 0) : 0;
            }

            return $cCache[$id];
        }

        return 0;
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

    /**
     * Dado un secanacategoria_id, obtiene la fila de la misma sub-clase (por nombre)
     * que mejor coincide con población y sexo del paciente. Si no hay filtros, devuelve la fila original.
     */
    public function getAnalisisComplejaConFiltros(int $id, array $matchingPoblacionIds = [], ?int $gender = null)
    {
        $original = $this->getAnalisisCompleja($id);
        if (!$original || empty($matchingPoblacionIds)) {
            return $original;
        }
        $nombre = trim((string) ($original->nombre ?? ''));
        $priaId = (int) ($original->prianacategoria_id ?? 0);
        if ($nombre === '' || $priaId < 1) {
            return $original;
        }
        $best = $this->getSecItemByPrianacategoriaYNombre($priaId, $nombre, $matchingPoblacionIds, $gender);
        if (!$best) {
            return $original;
        }
        $best->padre = $original->padre ?? ($best->padre ?? '');
        $best->hijo = $original->hijo ?? ($best->hijo ?? '');
        return $best;
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
     * @return array<int,array{prianacategoria_id:int,compleja:int,mostrar_valores:int,name:string,anacategoria_id:int,tipo_muestra_id?:int|null,metodo_id?:int|null}>
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
        if ($this->hasColumn('prianacategoria', 'tipo_muestra_id')) {
            $select .= ", {$pt}.tipo_muestra_id";
        } else {
            $select .= ", NULL as tipo_muestra_id";
        }
        if ($this->hasColumn('prianacategoria', 'metodo_id')) {
            $select .= ", {$pt}.metodo_id";
        } else {
            $select .= ", NULL as metodo_id";
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
    public function getAllSecItemsByPrianacategoriaForReport(int $prianacategoriaId, array $matchingPoblacionIds = [], ?int $gender = null): array
    {
        $s = $this->db->prefixTable('secanacategoria');
        $p = $this->db->prefixTable('prianacategoria');
        $a = $this->db->prefixTable('anacategoria');
        $mostrarValoresSql = $this->hasColumn('prianacategoria', 'mostrar_valores')
            ? "{$p}.mostrar_valores as mostrar_valores"
            : "0 as mostrar_valores";

        $rows = $this->db->table('secanacategoria')
            ->select("{$s}.*, {$a}.name as padre, {$p}.name as hijo, {$mostrarValoresSql}")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$s}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$s}.prianacategoria_id", $prianacategoriaId)
            ->where("({$s}.deleted = 0 OR {$s}.deleted IS NULL)")
            ->orderBy("{$s}.orden", 'ASC')
            ->orderBy("{$s}.secanacategoria_id", 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows) || empty($matchingPoblacionIds)) {
            return $rows;
        }

        $grouped = [];
        foreach ($rows as $row) {
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $esSep = (int) ($row['es_separador'] ?? 0) === 1;
            $groupKey = $esSep ? ('__sep:' . (int) ($row['secanacategoria_id'] ?? 0)) : $nombre;
            $grouped[$groupKey][] = $row;
        }

        $result = [];
        foreach ($grouped as $nombre => $candidates) {
            if (count($candidates) === 1) {
                $result[] = $candidates[0];
                continue;
            }
            $filtered = $this->filterSecanacategoriaCandidatesBySexo($candidates, $gender);
            $best = $this->pickBestSecanacategoriaRow($filtered, $matchingPoblacionIds, $gender);
            $result[] = $best;
        }

        usort($result, static function ($a, $b) {
            $aOrd = (int) ($a['orden'] ?? 0);
            $bOrd = (int) ($b['orden'] ?? 0);
            if ($aOrd !== $bOrd) {
                return $aOrd <=> $bOrd;
            }
            return ((int) ($a['secanacategoria_id'] ?? 0)) <=> ((int) ($b['secanacategoria_id'] ?? 0));
        });

        return $result;
    }

    /**
     * Todas las filas de referencia de sub-análisis (secanacategoria) de una prueba compuesta,
     * sin colapsar por población: para tabla consolidada en reporte cuando hay varios grupos poblacionales.
     *
     * @return list<array<string,mixed>>
     */
    public function getSecReferenciasConsolidadasSinColapsar(int $prianacategoriaId): array
    {
        if ($prianacategoriaId < 1) {
            return [];
        }
        $s  = $this->db->prefixTable('secanacategoria');
        $p  = $this->db->prefixTable('prianacategoria');
        $a  = $this->db->prefixTable('anacategoria');
        $pb = $this->db->prefixTable('poblacion');

        $select = "{$s}.secanacategoria_id, {$s}.prianacategoria_id, {$s}.nombre, {$s}.paciente_id, {$s}.valor_min, {$s}.valor_max, {$s}.umedida, {$pb}.name AS poblacion_nombre, {$pb}.orden AS poblacion_orden";
        if ($this->hasColumn('secanacategoria', 'sexo')) {
            $select .= ", {$s}.sexo";
        }

        $builder = $this->db->table('secanacategoria')
            ->select($select)
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$s}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->join('poblacion', "{$pb}.id_poblacion = {$s}.paciente_id", 'left')
            ->where("{$s}.prianacategoria_id", $prianacategoriaId)
            ->where("({$s}.deleted = 0 OR {$s}.deleted IS NULL)");

        if ($this->hasColumn('secanacategoria', 'es_separador')) {
            $builder->groupStart()
                ->where("{$s}.es_separador", 0)
                ->orWhere("{$s}.es_separador IS NULL", null, false)
                ->groupEnd();
        }

        if ($this->hasColumn('secanacategoria', 'orden')) {
            $builder->orderBy("{$s}.orden", 'ASC')
                ->orderBy("{$pb}.orden", 'ASC');
        } else {
            $builder->orderBy("{$pb}.orden", 'ASC');
        }

        $rows = $builder->orderBy("{$s}.secanacategoria_id", 'ASC')->get()->getResultArray();

        foreach ($rows as &$r) {
            $nomPob = (string) ($r['poblacion_nombre'] ?? '');
            $r['poblacion_nombre'] = html_entity_decode($nomPob, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (trim($r['poblacion_nombre']) === '') {
                $r['poblacion_nombre'] = (string) ($r['paciente_id'] ?? '');
            }
        }
        unset($r);

        return $rows;
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

    public function registroTieneDoctorAsignado(int $registroId): bool
    {
        if ($registroId < 1) {
            return false;
        }

        $row = $this->db->table('registro')
            ->select('doctor_id')
            ->where('registro_id', $registroId)
            ->limit(1)
            ->get()
            ->getRow();

        return $row !== null && (int) ($row->doctor_id ?? 0) > 0;
    }

    public function getInfoPaciente(int $id)
    {
        return $this->db->table('registro')
            ->select('people.*, customers.institucion AS paciente_institucion')
            ->join('people', 'people.person_id = registro.person_id')
            ->join('customers', 'customers.person_id = people.person_id', 'left')
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
     * Una fila por nombre; la referencia usa id_poblacion guardado en paciente_id (catálogo /config población)
     * y sexo, igual que en labotests/detail.
     *
     * @param int[] $matchingPoblacionIds ids dom_poblacion que aplican (edad al ingreso + sexo según RegisterService)
     */
    public function getValoresCompleja(int $prianacategoriaId, array $matchingPoblacionIds, ?int $gender = null): array
    {
        $rows = $this->fetchSecanacategoriaRowsForCompleja($prianacategoriaId, $matchingPoblacionIds, $gender, true);
        return $this->reduceSecanacategoriaRowsPorNombre($rows, $matchingPoblacionIds, false, $gender);
    }

    /**
     * Todas las sub-clases de una prueba compuesta (una fila por nombre de sub-clase).
     * Elige valores de referencia según grupos de población configurados (paciente_id = id_poblacion),
     * edad calculada con fecha de nacimiento e ingreso del registro, y sexo.
     */
    public function getValoresComplejaSiempre(int $prianacategoriaId, array $matchingPoblacionIds, ?int $gender = null): array
    {
        $rows = $this->fetchSecanacategoriaRowsForCompleja($prianacategoriaId, $matchingPoblacionIds, $gender, false);
        return $this->reduceSecanacategoriaRowsPorNombre($rows, $matchingPoblacionIds, true, $gender);
    }

    /**
     * @param int[] $matchingPoblacionIds
     * @return array<int,array<string,mixed>>
     */
    private function fetchSecanacategoriaRowsForCompleja(int $prianacategoriaId, array $matchingPoblacionIds, ?int $gender, bool $strictPoblacion): array
    {
        $sec = $this->db->prefixTable('secanacategoria');
        $f = $this->db->prefixTable('formulas');
        $pobIds = array_values(array_unique(array_map('intval', $matchingPoblacionIds)));
        if ($pobIds === []) {
            $pobIds = [15];
        }

        $makeBuilder = function () use ($sec, $f, $prianacategoriaId) {
            return $this->db->table('secanacategoria')
                ->select("{$sec}.*, {$f}.formula_expresion AS formula_expresion_desde_formulas")
                ->join('formulas', "{$f}.formulas_id = {$sec}.formulas_id", 'left')
                ->where("{$sec}.prianacategoria_id", $prianacategoriaId)
                ->where("({$sec}.deleted = 0 OR {$sec}.deleted IS NULL)");
        };

        $applySexo = function ($builder) use ($sec, $gender) {
            if ($gender !== null && ($gender === 1 || $gender === 2) && $this->hasColumn('secanacategoria', 'sexo')) {
                $sexoVal = $gender === 1 ? 'masculino' : 'femenino';
                $builder->groupStart()
                    ->where("LOWER(TRIM({$sec}.sexo)) = 'ambos'", null, false)
                    ->orWhere("LOWER(TRIM({$sec}.sexo)) = '" . $this->db->escapeString($sexoVal) . "'", null, false)
                    ->orWhere("{$sec}.sexo IS NULL", null, false)
                    ->orWhere("TRIM({$sec}.sexo) = ''", null, false)
                    ->groupEnd();
            }
        };

        $builder = $makeBuilder();
        $builder->groupStart()
            ->whereIn("{$sec}.paciente_id", $pobIds)
            ->orWhere("{$sec}.paciente_id", 15);
        if ($this->hasColumn('secanacategoria', 'es_separador')) {
            $builder->orWhere("{$sec}.es_separador", 1);
        }
        $builder->groupEnd();
        $applySexo($builder);
        if ($this->hasColumn('secanacategoria', 'orden')) {
            $builder->orderBy("{$sec}.orden", 'ASC');
        }
        $rows = $builder->orderBy("{$sec}.nombre", 'ASC')->orderBy("{$sec}.paciente_id", 'ASC')->get()->getResultArray();

        return $rows;
    }

    /**
     * Mantiene todas las filas que aplican (incluyendo nombres repetidos).
     *
     * @param int[] $matchingPoblacionIds
     * @return array<int,array<string,mixed>>
     */
    private function reduceSecanacategoriaRowsPorNombre(array $rows, array $matchingPoblacionIds, bool $mergeFormulaDesdeFormulas, ?int $gender = null): array
    {
        $rows = $this->filterSecanacategoriaCandidatesBySexo($rows, $gender);

        $seen = [];
        $result = [];
        foreach ($rows as $row) {
            $nombre = trim((string) ($row['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            // Evita duplicar la misma fila por joins, pero conserva nombres repetidos.
            $secId = (int) ($row['secanacategoria_id'] ?? 0);
            $dedupeKey = $secId > 0 ? (string) $secId : md5(json_encode($row, JSON_UNESCAPED_UNICODE));
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            if ($mergeFormulaDesdeFormulas) {
                $exprFromFormulas = trim((string) ($row['formula_expresion_desde_formulas'] ?? ''));
                if ($exprFromFormulas !== '' && (int) ($row['formulas_id'] ?? 0) > 1) {
                    $row['formula_expresion'] = $exprFromFormulas;
                }
            }

            unset($row['formula_expresion_desde_formulas']);
            $result[] = $row;
        }

        if ($this->hasColumn('secanacategoria', 'orden')) {
            usort($result, static function ($a, $b) {
                $cmp = ((int) ($a['orden'] ?? 0)) <=> ((int) ($b['orden'] ?? 0));
                if ($cmp !== 0) {
                    return $cmp;
                }
                return ((int) ($a['secanacategoria_id'] ?? 0)) <=> ((int) ($b['secanacategoria_id'] ?? 0));
            });
        }

        return $result;
    }

    /**
     * Obtiene una sub-clase (secanacategoria) por prianacategoria_id y nombre.
     * Usado en reporte cuando regvalues.name viene como "prianacategoria_id|nombre".
     */
    /**
     * Obtiene una sub-clase por prianacategoria_id y nombre.
     * Si se proporcionan $matchingPoblacionIds y $gender, elige la fila que mejor
     * coincide con la población/sexo del paciente (misma lógica que getValoresComplejaSiempre).
     */
    public function getSecItemByPrianacategoriaYNombre(int $prianacategoriaId, string $nombre, array $matchingPoblacionIds = [], ?int $gender = null)
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

        $builder = $this->db->table('secanacategoria')
            ->select("{$s}.*, {$a}.name as padre, {$p}.name as hijo, {$mostrarValoresSql}")
            ->join('prianacategoria', "{$p}.prianacategoria_id = {$s}.prianacategoria_id")
            ->join('anacategoria', "{$a}.anacategoria_id = {$p}.anacategoria_id")
            ->where("{$s}.prianacategoria_id", $prianacategoriaId)
            ->where("{$s}.nombre", $nombre)
            ->where("{$s}.deleted", 0);

        if (empty($matchingPoblacionIds)) {
            return $builder->limit(1)->get()->getRow();
        }

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return null;
        }
        if (count($rows) === 1) {
            return (object) $rows[0];
        }

        $filtered = $this->filterSecanacategoriaCandidatesBySexo($rows, $gender);
        $chosen = $this->pickBestSecanacategoriaRow($filtered, $matchingPoblacionIds, $gender);
        return (object) $chosen;
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

    public function hasRegistroColumn(string $column): bool
    {
        return $this->hasColumn('registro', $column);
    }

    /**
     * Elige la fila cuyo id de población coincide con el paciente: prioridad según orden en $matchingPoblacionIds,
     * desempate por id numérico (coherente con labotests/detail). $poblacionField = id_poblacion (priresultados) o paciente_id (secanacategoria, almacena id_poblacion).
     * @param array<int,array<string,mixed>> $candidates
     */
    private function pickBestRowByPoblacionId(array $candidates, array $matchingPoblacionIds, string $poblacionField = 'id_poblacion'): array
    {
        $priority = [];
        foreach ($matchingPoblacionIds as $idx => $id) {
            $priority[(int) $id] = $idx;
        }
        $allowed = array_map('intval', $matchingPoblacionIds);
        $best = null;
        $bestKey = null;
        foreach ($candidates as $r) {
            $pop = (int) ($r[$poblacionField] ?? 0);
            if (! in_array($pop, $allowed, true)) {
                continue;
            }
            $p = $priority[$pop] ?? 9999;
            $key = [$p, $pop];
            if ($bestKey === null || $key < $bestKey) {
                $bestKey = $key;
                $best = $r;
            }
        }

        return $best ?? $candidates[0];
    }

    private function pickBestPriresultadoRow(array $candidates, array $matchingPoblacionIds): array
    {
        return $this->pickBestRowByPoblacionId($candidates, $matchingPoblacionIds, 'id_poblacion');
    }

    /**
     * Quita filas cuyo sexo contradice al paciente (defensa ante mayúsculas / datos inconsistentes).
     * @param array<int,array<string,mixed>> $cands
     * @return array<int,array<string,mixed>>
     */
    private function filterSecanacategoriaCandidatesBySexo(array $cands, ?int $gender): array
    {
        if ($gender !== 1 && $gender !== 2) {
            return $cands;
        }
        $want = $gender === 1 ? 'masculino' : 'femenino';
        $ok = [];
        foreach ($cands as $r) {
            $sx = strtolower(trim((string) ($r['sexo'] ?? '')));
            if ($sx === '' || $sx === 'ambos' || $sx === $want) {
                $ok[] = $r;
            }
        }

        return $ok !== [] ? $ok : $cands;
    }

    /**
     * Elige sub-clase (secanacategoria): primero sexo específico del paciente sobre "ambos"/vacío,
     * luego prioridad de id_poblacion en $matchingPoblacionIds, desempate paciente_id.
     * @param array<int,array<string,mixed>> $candidates
     */
    private function pickBestSecanacategoriaRow(array $candidates, array $matchingPoblacionIds, ?int $gender = null): array
    {
        if ($candidates !== []) {
            $allSep = true;
            foreach ($candidates as $r) {
                if ((int) ($r['es_separador'] ?? 0) !== 1) {
                    $allSep = false;
                    break;
                }
            }
            if ($allSep) {
                return $candidates[0];
            }
        }

        $priority = [];
        foreach ($matchingPoblacionIds as $idx => $id) {
            $priority[(int) $id] = $idx;
        }
        $allowed = array_map('intval', $matchingPoblacionIds);
        $sexoPaciente = null;
        if ($gender === 1) {
            $sexoPaciente = 'masculino';
        } elseif ($gender === 2) {
            $sexoPaciente = 'femenino';
        }

        $best = null;
        $bestKey = null;
        foreach ($candidates as $r) {
            $pop = (int) ($r['paciente_id'] ?? 0);
            if (! in_array($pop, $allowed, true)) {
                continue;
            }
            $pPrio = $priority[$pop] ?? 9999;
            $sx = strtolower(trim((string) ($r['sexo'] ?? '')));
            $sexTier = 0;
            if ($sexoPaciente !== null) {
                if ($sx === $sexoPaciente) {
                    $sexTier = 0;
                } elseif ($sx === 'ambos' || $sx === '') {
                    $sexTier = 1;
                } else {
                    $sexTier = 2;
                }
            }
            $key = [$sexTier, $pPrio, $pop];
            if ($bestKey === null || $key < $bestKey) {
                $bestKey = $key;
                $best = $r;
            }
        }

        if ($best === null) {
            foreach ($candidates as $r) {
                if ((int) ($r['es_separador'] ?? 0) === 1) {
                    return $r;
                }
            }
        }

        return $best ?? $this->pickBestRowByPoblacionId($candidates, $matchingPoblacionIds, 'paciente_id');
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
        $f  = $this->db->prefixTable('formulas');
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
                pr.formulas_id, f.formula_expresion AS formula_expresion,
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
                 ORDER BY CASE WHEN prff.id_poblacion = 15 THEN 1 ELSE 0 END
                 LIMIT 1) AS priresultados_id_filtered
                FROM {$pt} pt
                LEFT JOIN {$ac} ac ON ac.anacategoria_id = pt.anacategoria_id
                LEFT JOIN {$pr} pr ON pr.prianacategoria_id = pt.prianacategoria_id
                    AND pt.compleja = 0 AND (pr.deleted = 0 OR pr.deleted IS NULL) AND pr.id_poblacion IN {$poblacionIn}{$sexoCond}
                LEFT JOIN {$f} f ON f.formulas_id = pr.formulas_id
                WHERE (pt.deleted = 0 OR pt.deleted IS NULL)
                AND (ac.deleted = 0 OR ac.deleted IS NULL)
                AND pt.prianacategoria_id IN (" . implode(',', array_map('intval', $ids)) . ")
                ORDER BY ac.order, pt.order, pr.id_poblacion ASC";
        $rows = empty($bindParams) ? $this->db->query($sql)->getResultArray() : $this->db->query($sql, $bindParams)->getResultArray();

        $needFallbackData = [];
        $byPria = [];

        $grouped = [];
        foreach ($rows as $r) {
            $pid = (int) ($r['prianacategoria_id'] ?? 0);
            if ($pid < 1) {
                continue;
            }
            $grouped[$pid][] = $r;
        }

        foreach ($grouped as $pid => $groupRows) {
            $r0 = $groupRows[0];
            $compleja = (int) ($r0['compleja'] ?? 0);

            if ($compleja === 1) {
                $r = $r0;
                if (((int) ($r['opcion_id'] ?? 0)) <= 0 && ((int) ($r['opcion_id_fallback'] ?? 0)) > 0) {
                    $r['opcion_id'] = $r['opcion_id_fallback'];
                }
                if (((int) ($r['priresultados_id'] ?? 0)) <= 0 && ((int) ($r['priresultados_id_fallback'] ?? 0)) > 0) {
                    $r['priresultados_id'] = $r['priresultados_id_fallback'];
                }
                unset($r['opcion_id_fallback'], $r['priresultados_id_fallback'], $r['priresultados_id_filtered']);
                $byPria[$pid] = $r;
                continue;
            }

            $candidates = [];
            foreach ($groupRows as $r) {
                if (! empty($r['priresultados_id'])) {
                    $candidates[] = $r;
                }
            }

            if ($candidates === []) {
                $filteredId = (int) ($r0['priresultados_id_filtered'] ?? 0);
                if ($filteredId > 0) {
                    $needFallbackData[$pid] = ['row' => $r0, 'priresultados_id' => $filteredId];
                }
                continue;
            }

            $r = $this->pickBestPriresultadoRow($candidates, $matchingPoblacionIds);
            if (((int) ($r['opcion_id'] ?? 0)) <= 0 && ((int) ($r['opcion_id_fallback'] ?? 0)) > 0) {
                $r['opcion_id'] = $r['opcion_id_fallback'];
            }
            if (((int) ($r['priresultados_id'] ?? 0)) <= 0 && ((int) ($r['priresultados_id_fallback'] ?? 0)) > 0) {
                $r['priresultados_id'] = $r['priresultados_id_fallback'];
            }
            unset($r['opcion_id_fallback'], $r['priresultados_id_fallback'], $r['priresultados_id_filtered']);
            $byPria[$pid] = $r;
        }

        if (!empty($needFallbackData)) {
            $prIds = array_unique(array_column($needFallbackData, 'priresultados_id'));
            $f = $this->db->prefixTable('formulas');
            $fallbackRows = $this->db->table('priresultados')
                ->select("priresultados.priresultados_id, priresultados.prianacategoria_id, priresultados.opcion_id, priresultados.valor_min, priresultados.valor_max, priresultados.umedida, priresultados.id_poblacion, priresultados.formulas_id, {$f}.formula_expresion AS formula_expresion")
                ->join('formulas', "{$f}.formulas_id = priresultados.formulas_id", 'left')
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
                $r['formulas_id'] = $fr['formulas_id'] ?? 1;
                $r['formula_expresion'] = $fr['formula_expresion'] ?? '';
                $r['id_poblacion'] = $fr['id_poblacion'] ?? 15;
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
        $p = $this->db->prefixTable('people');
        $c = $this->db->prefixTable('customers');
        $rows = $this->db->table('people')
            ->select("{$p}.person_id, {$p}.first_name, {$p}.last_name_fa, {$p}.last_name_mom, {$p}.ci, {$c}.institucion")
            ->join('customers', "{$c}.person_id = {$p}.person_id AND (COALESCE({$c}.deleted, 0) = 0)")
            ->where("{$p}.first_name != '' AND {$p}.first_name != '0'")
            ->where("{$p}.last_name_fa != '' AND {$p}.last_name_fa != '0'")
            ->groupStart()
            ->like("{$p}.first_name", $esc, 'both')
            ->orLike("{$p}.last_name_fa", $esc, 'both')
            ->orLike("{$p}.ci", $esc, 'both')
            ->orWhere("CONCAT({$p}.first_name, ' ', {$p}.last_name_fa) LIKE", $pat)
            ->groupEnd()
            ->orderBy("{$p}.last_name_fa", 'ASC')
            ->limit($limit)
            ->get()
            ->getResult();

        $suggestions = [];
        foreach ($rows as $r) {
            $full = trim(($r->first_name ?? '') . ' ' . ($r->last_name_fa ?? '') . ' ' . ($r->last_name_mom ?? ''));
            if ($full !== '') {
                $ci = trim($r->ci ?? '');
                $display = $ci !== '' ? $full . ' (CI: ' . $ci . ')' : $full;
                $suggestions[] = [
                    'value' => $display,
                    'data' => $r->person_id,
                    'institucion' => trim((string) ($r->institucion ?? '')),
                ];
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
     * Cuenta registros del paciente con resultados (misma lógica que getRegistrosByPersonId).
     */
    public function countRegistrosByPersonId(int $personId): int
    {
        $r  = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');

        return (int) $this->db->table('registro')
            ->where("{$r}.person_id", $personId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->countAllResults();
    }

    /**
     * Obtiene todos los registros de un paciente ordenados por fecha
     */
    public function getRegistrosByPersonId(int $personId, int $limit = 200, int $offset = 0): array
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
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    /**
     * Obtiene antecedentes: resultados previos del mismo paciente para comparar (solo completos/con regvalues)
     * @param int $personId ID del paciente
     * @param int $currentRegistroId Excluir este registro
     * @param int $limit Máximo de registros anteriores a incluir
     * @param int|null $doctorId Si se provee, solo registros de ese doctor (para portal doctor)
     * @param int $offset Desplazamiento para paginación
     */
    public function getAntecedentesPaciente(int $personId, int $currentRegistroId = 0, int $limit = 10, ?int $doctorId = null, int $offset = 0): array
    {
        $r  = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');
        $builder = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas, {$r}.doctor_id")
            ->where("{$r}.person_id", $personId)
            ->where("{$r}.registro_id !=", $currentRegistroId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit, $offset);
        if ($doctorId !== null && $doctorId > 0) {
            $builder->where("{$r}.doctor_id", $doctorId);
        }
        return $builder->get()->getResult();
    }

    /**
     * Cuenta antecedentes (mismos filtros que getAntecedentesPaciente).
     */
    public function countAntecedentesPaciente(int $personId, int $currentRegistroId = 0, ?int $doctorId = null): int
    {
        $r  = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');
        $builder = $this->db->table('registro')
            ->where("{$r}.person_id", $personId)
            ->where("{$r}.registro_id !=", $currentRegistroId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false);
        if ($doctorId !== null && $doctorId > 0) {
            $builder->where("{$r}.doctor_id", $doctorId);
        }

        return (int) $builder->countAllResults();
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
     * Búsqueda avanzada para el portal del doctor: nombre/CI/orden, rango de fechas y texto de parámetro.
     * Los filtros clínicos finos (alterado, glucosa > 126) se completan en el servicio del controlador.
     *
     * @param array<string, mixed> $filters
     */
    public function getRegistrosByDoctorAdvanced(int $doctorId, array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $r  = $this->getRegistroTable();
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $builder = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.person_id, {$r}.numero_orden,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$p}.ci, {$d}.name as doctor, {$pa}.total as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id", 'left')
            ->where("{$r}.doctor_id", $doctorId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false);

        if ($this->registroTieneColumnaAnulado()) {
            $builder->where("COALESCE({$r}.anulado, 0) = 0", null, false);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $esc = $this->db->escapeLikeString($q);
            $pat = '%' . $esc . '%';
            $builder->groupStart()
                ->like("{$p}.first_name", $esc, 'both')
                ->orLike("{$p}.last_name_fa", $esc, 'both')
                ->orLike("{$p}.last_name_mom", $esc, 'both')
                ->orLike("{$p}.ci", $esc, 'both')
                ->orLike("{$r}.numero_orden", $esc, 'both')
                ->orWhere("CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) LIKE", $pat)
                ->groupEnd();
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $builder->where("DATE({$r}.ingreso) >=", $dateFrom);
        }
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $builder->where("DATE({$r}.ingreso) <=", $dateTo);
        }

        $parameter = trim((string) ($filters['parameter'] ?? ''));
        if ($parameter !== '') {
            $esc = $this->db->escapeLikeString($parameter);
            $like = $this->db->escape('%' . $esc . '%');
            $builder->where("EXISTS (
                SELECT 1 FROM {$rv} rv_param
                WHERE rv_param.registro_id = {$r}.registro_id
                AND rv_param.name LIKE {$like} ESCAPE '!'
            )", null, false);
        }

        return $builder
            ->orderBy("{$r}.ingreso", 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    /**
     * Cuenta registros con resultados del paciente para un doctor.
     */
    public function countRegistrosByPersonAndDoctor(int $personId, int $doctorId): int
    {
        $r  = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');

        return (int) $this->db->table('registro')
            ->where("{$r}.person_id", $personId)
            ->where("{$r}.doctor_id", $doctorId)
            ->where("EXISTS (SELECT 1 FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id)", null, false)
            ->countAllResults();
    }

    /**
     * Registros de un paciente vistos por un doctor específico
     */
    public function getRegistrosByPersonAndDoctor(int $personId, int $doctorId, int $limit = 200, int $offset = 0): array
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
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    /**
     * Lista de pruebas del historial (desde regvalues) para graficar.
     * Devuelve: [['key' => raw_name, 'label' => nombre legible], ...]
     */
    public function getPruebasByPersonAndDoctor(int $personId, int $doctorId): array
    {
        return $this->getPruebasByPersonScope($personId, $doctorId);
    }

    /**
     * Lista de pruebas del historial completo del paciente.
     */
    public function getPruebasByPersonId(int $personId): array
    {
        return $this->getPruebasByPersonScope($personId, null);
    }

    /**
     * Serie histórica por prueba (clave raw de regvalues.name) para paciente+doctor.
     */
    public function getSeriePruebaByPersonAndDoctor(int $personId, int $doctorId, string $pruebaKey): array
    {
        return $this->getSeriePruebaByPersonScope($personId, $doctorId, $pruebaKey);
    }

    /**
     * Serie histórica por prueba para todo el historial del paciente.
     */
    public function getSeriePruebaByPersonId(int $personId, string $pruebaKey): array
    {
        return $this->getSeriePruebaByPersonScope($personId, null, $pruebaKey);
    }

    private function getPruebasByPersonScope(int $personId, ?int $doctorId): array
    {
        $r = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');

        $builder = $this->db->table('regvalues')
            ->select("TRIM({$rv}.name) AS prueba_key")
            ->join('registro', "{$r}.registro_id = {$rv}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->where("{$rv}.name !=", '')
            ->groupBy("TRIM({$rv}.name)")
            ->orderBy("TRIM({$rv}.name)", 'ASC');

        if ($doctorId !== null) {
            $builder->where("{$r}.doctor_id", $doctorId);
        }

        $rows = $builder->get()->getResultArray();

        $pruebas = [];
        $seenLabels = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['prueba_key'] ?? ''));
            if (!$this->isResultadoPruebaRegvalueKey($key)) {
                continue;
            }
            $label = $this->getPruebaLabelFromRawKey($key);
            $labelKey = mb_strtolower((string) preg_replace('/\s+/', ' ', trim($label)));
            if ($labelKey !== '' && isset($seenLabels[$labelKey])) {
                continue;
            }
            $seenLabels[$labelKey] = true;
            $pruebas[] = ['key' => $key, 'label' => $label];
        }

        usort($pruebas, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return $pruebas;
    }

    private function getSeriePruebaByPersonScope(int $personId, ?int $doctorId, string $pruebaKey): array
    {
        $pruebaKey = trim($pruebaKey);
        if (!$this->isResultadoPruebaRegvalueKey($pruebaKey)) {
            return [];
        }

        $r = $this->getRegistroTable();
        $rv = $this->db->prefixTable('regvalues');

        $builder = $this->db->table('regvalues')
            ->select("{$r}.registro_id, {$r}.ingreso, {$rv}.name AS prueba_key, {$rv}.regvalues AS valor")
            ->join('registro', "{$r}.registro_id = {$rv}.registro_id")
            ->where("{$r}.person_id", $personId)
            ->where("{$rv}.name", $pruebaKey)
            ->orderBy("{$r}.ingreso", 'ASC');

        if ($doctorId !== null) {
            $builder->where("{$r}.doctor_id", $doctorId);
        }

        $rows = $builder->get()->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $refs = $this->getReferenceRangeFromRawKey($pruebaKey);
        foreach ($rows as &$row) {
            $row['minimo'] = $refs['minimo'];
            $row['maximo'] = $refs['maximo'];
            $row['critico_min'] = $refs['critico_min'];
            $row['critico_max'] = $refs['critico_max'];
            $row['label'] = $this->getPruebaLabelFromRawKey($pruebaKey);
        }
        unset($row);

        return $rows;
    }

    private function isResultadoPruebaRegvalueKey(string $rawKey): bool
    {
        $rawKey = trim($rawKey);
        if ($rawKey === '') {
            return false;
        }

        if (str_starts_with($rawKey, 'lab_')) {
            return false;
        }

        return str_starts_with($rawKey, 'c_')
            || str_starts_with($rawKey, 'noc_')
            || strpos($rawKey, '|') !== false;
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
     * Obtiene referencia mínima/máxima y límites críticos según clave raw de regvalues.
     * @return array{minimo: string|null, maximo: string|null, critico_min: string|null, critico_max: string|null}
     */
    private function getReferenceRangeFromRawKey(string $rawKey): array
    {
        $min = null;
        $max = null;
        $criticalMin = null;
        $criticalMax = null;

        if (strpos($rawKey, '|') !== false) {
            [$priaStr, $nombre] = explode('|', $rawKey, 2);
            $priaId = (int) trim($priaStr);
            $nombre = trim($nombre);
            if ($priaId > 0 && $nombre !== '') {
                $item = $this->getSecItemByPrianacategoriaYNombre($priaId, $nombre);
                if ($item) {
                    $min = (string) ($item->valor_min ?? '');
                    $max = (string) ($item->valor_max ?? '');
                    $criticalMin = (string) ($item->critico_min ?? '');
                    $criticalMax = (string) ($item->critico_max ?? '');
                }
            }
        } elseif (str_starts_with($rawKey, 'c_')) {
            $id = (int) substr($rawKey, 2);
            if ($id > 0) {
                $item = $this->getAnalisisCompleja($id);
                if ($item) {
                    $min = (string) ($item->valor_min ?? '');
                    $max = (string) ($item->valor_max ?? '');
                    $criticalMin = (string) ($item->critico_min ?? '');
                    $criticalMax = (string) ($item->critico_max ?? '');
                }
            }
        } elseif (str_starts_with($rawKey, 'noc_')) {
            $id = (int) substr($rawKey, 4);
            if ($id > 0) {
                $item = $this->getAnalisisNocompleja($id);
                if ($item) {
                    $min = (string) ($item->valor_min ?? '');
                    $max = (string) ($item->valor_max ?? '');
                    $criticalMin = (string) ($item->critico_min ?? '');
                    $criticalMax = (string) ($item->critico_max ?? '');
                }
            }
        }

        return [
            'minimo' => ($min !== null && trim($min) !== '') ? $min : null,
            'maximo' => ($max !== null && trim($max) !== '') ? $max : null,
            'critico_min' => ($criticalMin !== null && trim($criticalMin) !== '') ? $criticalMin : null,
            'critico_max' => ($criticalMax !== null && trim($criticalMax) !== '') ? $criticalMax : null,
        ];
    }

    /**
     * Líneas de detalle comercial (nombre y costo catálogo) para recibo/factura PDF.
     *
     * @return list<array{descripcion: string, importe: float}>
     */
    public function getPruebasLineasComerciales(?string $pruebasCsv): array
    {
        $csv = trim((string) $pruebasCsv);
        if ($csv === '') {
            return [];
        }
        $ids = array_values(array_filter(array_map('intval', explode(',', $csv)), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }
        $pt = $this->db->prefixTable('prianacategoria');
        $rows = $this->db->table('prianacategoria')
            ->select("{$pt}.prianacategoria_id, {$pt}.name, {$pt}.cost")
            ->whereIn("{$pt}.prianacategoria_id", $ids)
            ->where("{$pt}.deleted", 0)
            ->get()
            ->getResult();
        $byId = [];
        foreach ($rows as $r) {
            $pid = (int) ($r->prianacategoria_id ?? 0);
            if ($pid < 1) {
                continue;
            }
            $nombre = trim((string) ($r->name ?? ''));
            $byId[$pid] = [
                'descripcion' => $nombre !== '' ? $nombre : ('Prueba #' . $pid),
                'importe'     => (float) ($r->cost ?? 0),
            ];
        }
        $ordered = [];
        foreach ($ids as $pid) {
            if (isset($byId[$pid])) {
                $ordered[] = $byId[$pid];
            }
        }

        return $ordered;
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
            $needFolio = !array_key_exists('numero_orden', $data)
                || $data['numero_orden'] === null
                || trim((string) $data['numero_orden']) === '';
            if ($needFolio) {
                try {
                    $folio = (new RegistroFolioService())->generateNextFolio();
                    if ($folio !== null) {
                        $data['numero_orden'] = $folio;
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'RegisterModel::saveRegistro folio: ' . $e->getMessage());
                }
            }
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
        if (array_key_exists('total_reco', $data)) $upd['total_reco'] = $data['total_reco'];
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
     * Convierte montos guardados en BD o formulario (punto/coma, espacios) a float.
     */
    private function parseDecimalMoney(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return 0.0;
        }
        $s = str_replace(["\xc2\xa0", ' '], '', $s);
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    /**
     * Indica si la orden tiene fila de pago y el monto de la orden está cubierto.
     * Usa saldo en BD y, si hiciera falta, total vs monto_pagar (por redondeos o datos desincronizados).
     */
    public function isPagoCompletoPorRegistroId(int $registroId): bool
    {
        $pago = $this->getPagoByRegistroId($registroId);
        if (!$pago) {
            return false;
        }
        $saldo = $this->parseDecimalMoney($pago->saldo ?? 0);
        // Tolerancia por DECIMAL/float y distintos formatos al leer desde MySQL
        if ($saldo <= 0.02) {
            return true;
        }
        $total = $this->parseDecimalMoney($pago->total ?? 0);
        $monto = $this->parseDecimalMoney($pago->monto_pagar ?? 0);
        if ($total <= 0.0) {
            return $saldo <= 0.02;
        }

        return ($monto + 0.02) >= $total;
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

        $pacienteSql = $this->sqlPacienteNombreLista($p);
        $reg = $this->db->table('registro')
            ->select("{$r}.*, {$pacienteSql} AS paciente, {$d}.name as doctor")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
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
