<?php

namespace App\Models;

use App\Libraries\LabNaiveDateRange;
use App\Libraries\RegistroIngresoDateRange;
use App\Services\RegisterService;
use CodeIgniter\Model;

/**
 * Modelo independiente para los 10 reportes analíticos nuevos (módulo /reports).
 *
 * REGLAS DE DISEÑO:
 *  - Solo consultas de LECTURA (SELECT). Nunca UPDATE/DELETE/ALTER.
 *  - No modifica ni depende de cambios en tablas existentes.
 *  - Reutiliza los helpers de rango de fechas del sistema (LabNaiveDateRange /
 *    RegistroIngresoDateRange) para aprovechar los índices existentes
 *    (idx_registro_ingreso, idx_auditoria_modulo_fecha, idx_regvalues_registro_ord).
 *  - Las agregaciones sobre el campo CSV `registro.pruebas` se hacen en PHP en
 *    una sola pasada (O(n)) para evitar FIND_IN_SET por fila.
 */
class ReportAnalyticsModel extends Model
{
    protected $table = 'registro';

    /** Expresión regular SQL para valores numéricos (acepta coma o punto decimal). */
    private const SQL_NUMERIC_REGEXP = "REGEXP '^-?[0-9]+([.,][0-9]+)?$'";

    /** Convierte un varchar numérico (con coma o punto) a DECIMAL para comparar. */
    private function sqlNum(string $expr): string
    {
        return "CAST(REPLACE({$expr}, ',', '.') AS DECIMAL(15,4))";
    }

    /** Condición SQL: la expresión contiene un valor numérico. */
    private function sqlIsNumeric(string $expr): string
    {
        return "TRIM({$expr}) " . self::SQL_NUMERIC_REGEXP;
    }

    /** Excluye órdenes anuladas (mismo criterio que ReportModel). */
    private function sqlSinAnulados(string $r): string
    {
        return "COALESCE({$r}.anulado, 0) = 0";
    }

    /** Nombre de paciente concatenado. */
    private function sqlPaciente(string $p): string
    {
        return "TRIM(CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', COALESCE({$p}.last_name_mom, '')))";
    }

    // =====================================================================
    // Catálogos para filtros (lectura)
    // =====================================================================

    /** @return list<array{anacategoria_id:int|string, name:string}> */
    public function getGruposAnalisis(): array
    {
        $a = $this->db->prefixTable('anacategoria');

        return $this->db->table('anacategoria')
            ->select("{$a}.anacategoria_id, {$a}.name")
            ->where("{$a}.deleted", 0)
            ->orderBy("{$a}.name", 'ASC')
            ->get()
            ->getResultArray();
    }

    /** @return list<array{doctor_id:int|string, name:string}> */
    public function getDoctores(): array
    {
        $d = $this->db->prefixTable('doctors');

        return $this->db->table('doctors')
            ->select("{$d}.doctor_id, {$d}.name")
            ->where("{$d}.deleted", 0)
            ->orderBy("{$d}.name", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Catálogo de pruebas con su grupo y costo.
     * Devuelve mapa prianacategoria_id => [name, grupo_id, grupo, cost].
     *
     * @return array<int, array{name:string, grupo_id:int, grupo:string, cost:float}>
     */
    public function getPruebasCatalogoMap(): array
    {
        $pri = $this->db->prefixTable('prianacategoria');
        $ana = $this->db->prefixTable('anacategoria');

        $rows = $this->db->table('prianacategoria')
            ->select("{$pri}.prianacategoria_id, {$pri}.name, {$pri}.cost, {$pri}.anacategoria_id, {$ana}.name AS grupo")
            ->join('anacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['prianacategoria_id']] = [
                'name'     => (string) ($row['name'] ?? ''),
                'grupo_id' => (int) ($row['anacategoria_id'] ?? 0),
                'grupo'    => (string) ($row['grupo'] ?? ''),
                'cost'     => (float) ($row['cost'] ?? 0),
            ];
        }

        return $map;
    }

    /** @return list<array{prianacategoria_id:int|string, name:string, grupo:string}> */
    public function getPruebasCatalogoListado(): array
    {
        $pri = $this->db->prefixTable('prianacategoria');
        $ana = $this->db->prefixTable('anacategoria');

        return $this->db->table('prianacategoria')
            ->select("{$pri}.prianacategoria_id, {$pri}.name, COALESCE({$ana}.name, '') AS grupo")
            ->join('anacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->where("{$pri}.deleted", 0)
            ->orderBy('grupo', 'ASC')
            ->orderBy("{$pri}.name", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Busca pacientes con órdenes registradas (para el filtro de paciente).
     *
     * @return list<array<string, mixed>>
     */
    public function searchPacientes(string $q, int $limit = 20): array
    {
        $p = $this->db->prefixTable('people');
        $r = $this->db->prefixTable('registro');
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $b = $this->db->table('people')
            ->select("{$p}.person_id, {$p}.ci, {$p}.birthday, {$this->sqlPaciente($p)} AS paciente,
                (SELECT COUNT(*) FROM {$r} WHERE {$r}.person_id = {$p}.person_id) AS total_ordenes", false)
            ->groupStart()
                ->like("{$p}.first_name", $q)
                ->orLike("{$p}.last_name_fa", $q)
                ->orLike("{$p}.last_name_mom", $q)
                ->orLike("{$p}.ci", $q)
            ->groupEnd()
            ->orderBy("{$p}.last_name_fa", 'ASC')
            ->limit($limit);

        return $b->get()->getResultArray();
    }

    /** Datos básicos de un paciente. */
    public function getPaciente(int $personId): ?array
    {
        $p = $this->db->prefixTable('people');

        $row = $this->db->table('people')
            ->select("{$p}.person_id, {$p}.ci, {$p}.birthday, {$p}.gender, {$this->sqlPaciente($p)} AS paciente", false)
            ->where("{$p}.person_id", $personId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Busca pruebas del catálogo por nombre o grupo (para el reporte historial por prueba).
     *
     * @return list<array<string, mixed>>
     */
    public function searchPruebas(string $q, int $limit = 25): array
    {
        $pri = $this->db->prefixTable('prianacategoria');
        $ana = $this->db->prefixTable('anacategoria');
        $r   = $this->db->prefixTable('registro');
        $q   = trim($q);
        if ($q === '') {
            return [];
        }

        return $this->db->table('prianacategoria')
            ->select("{$pri}.prianacategoria_id, {$pri}.name, COALESCE({$ana}.name, '') AS grupo,
                (SELECT COUNT(*) FROM {$r}
                    WHERE COALESCE({$r}.anulado, 0) = 0
                      AND CONCAT(',', {$r}.pruebas, ',') LIKE CONCAT('%,', {$pri}.prianacategoria_id, ',%')
                ) AS total_ordenes", false)
            ->join('anacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->where("{$pri}.deleted", 0)
            ->groupStart()
                ->like("{$pri}.name", $q)
                ->orLike("{$ana}.name", $q)
            ->groupEnd()
            ->orderBy("{$pri}.name", 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /** Datos básicos de una prueba del catálogo. */
    public function getPrueba(int $pruebaId): ?array
    {
        $pri = $this->db->prefixTable('prianacategoria');
        $ana = $this->db->prefixTable('anacategoria');
        $r   = $this->db->prefixTable('registro');

        $row = $this->db->table('prianacategoria')
            ->select("{$pri}.prianacategoria_id, {$pri}.name, COALESCE({$ana}.name, '') AS grupo,
                (SELECT COUNT(*) FROM {$r}
                    WHERE COALESCE({$r}.anulado, 0) = 0
                      AND CONCAT(',', {$r}.pruebas, ',') LIKE CONCAT('%,', {$pri}.prianacategoria_id, ',%')
                ) AS total_ordenes", false)
            ->join('anacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->where("{$pri}.prianacategoria_id", $pruebaId)
            ->where("{$pri}.deleted", 0)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    // =====================================================================
    // 1. PRUEBAS MÁS SOLICITADAS
    // =====================================================================

    /**
     * Agrega el CSV registro.pruebas en una sola pasada PHP.
     *
     * @return array{rows: list<array<string,mixed>>, total_pruebas: int, total_ingresos: float, total_ordenes: int}
     */
    public function getPruebasMasSolicitadas(string $startDate, string $endDate, int $grupoId = 0, int $doctorId = 0): array
    {
        $r = $this->db->prefixTable('registro');

        $b = $this->db->table('registro')
            ->select("{$r}.pruebas", false)
            ->where($this->sqlSinAnulados($r), null, false);
        if ($doctorId > 0) {
            $b->where("{$r}.doctor_id", $doctorId);
        }
        $registros = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->get()
            ->getResultArray();

        $catalogo = $this->getPruebasCatalogoMap();

        $conteo       = [];
        $totalOrdenes = count($registros);
        foreach ($registros as $reg) {
            $csv = trim((string) ($reg['pruebas'] ?? ''));
            if ($csv === '') {
                continue;
            }
            foreach (explode(',', $csv) as $idStr) {
                $id = (int) trim($idStr);
                if ($id <= 0) {
                    continue;
                }
                $conteo[$id] = ($conteo[$id] ?? 0) + 1;
            }
        }

        $rows          = [];
        $totalPruebas  = 0;
        $totalIngresos = 0.0;
        foreach ($conteo as $id => $cantidad) {
            $cat = $catalogo[$id] ?? null;
            if ($grupoId > 0 && (int) ($cat['grupo_id'] ?? 0) !== $grupoId) {
                continue;
            }
            $ingreso        = $cantidad * (float) ($cat['cost'] ?? 0);
            $totalPruebas  += $cantidad;
            $totalIngresos += $ingreso;
            $rows[] = [
                'codigo'   => $id,
                'prueba'   => $cat['name'] ?? ('Prueba #' . $id . ' (eliminada)'),
                'grupo'    => $cat['grupo'] ?? '',
                'cantidad' => $cantidad,
                'ingreso'  => $ingreso,
            ];
        }

        usort($rows, static fn ($a, $b2) => $b2['cantidad'] <=> $a['cantidad']);

        $rank = 0;
        foreach ($rows as &$row) {
            $rank++;
            $row['ranking']    = $rank;
            $row['porcentaje'] = $totalPruebas > 0 ? round($row['cantidad'] * 100 / $totalPruebas, 2) : 0.0;
        }
        unset($row);

        return [
            'rows'           => $rows,
            'total_pruebas'  => $totalPruebas,
            'total_ingresos' => $totalIngresos,
            'total_ordenes'  => $totalOrdenes,
        ];
    }

    // =====================================================================
    // 2. TENDENCIA HISTÓRICA POR PACIENTE
    // 3. VALORES CRÍTICOS / FUERA DE RANGO
    //    (ambos leen dom_regvalues unido al catálogo vía la convención de
    //     claves: c_{secanacategoria_id} y noc_{priresultados_id})
    // =====================================================================

    /**
     * SQL base (UNION ALL) que resuelve cada valor guardado en regvalues contra
     * el catálogo: parámetros de pruebas complejas (c_*) y pruebas simples (noc_*).
     *
     * El join con registro usa un predicado redundante en ambas direcciones:
     * r.registro_id = CAST(rv.registro_id AS UNSIGNED) permite búsquedas por la
     * clave primaria de registro, y rv.registro_id = CAST(... CHARACTER SET utf8mb3)
     * permite usar el índice de regvalues.registro_id (mismo charset de la columna)
     * cuando el filtro restringe primero los registros (p. ej. por paciente).
     *
     * @param string $extraWhere  Condición adicional (ya saneada) sobre alias internos (r, pri)
     * @param bool   $withPersona Incluir joins de paciente y médico (omitir para agregados)
     */
    private function buildRegvaluesResolvedSql(string $extraWhere = '', bool $withPersona = true): string
    {
        $rv  = $this->db->prefixTable('regvalues');
        $r   = $this->db->prefixTable('registro');
        $p   = $this->db->prefixTable('people');
        $d   = $this->db->prefixTable('doctors');
        $sec = $this->db->prefixTable('secanacategoria');
        $pri = $this->db->prefixTable('prianacategoria');
        $ana = $this->db->prefixTable('anacategoria');
        $prr = $this->db->prefixTable('priresultados');

        // Dentro del SQL las tablas usan alias cortos (r, p, d, ...)
        $sinAnulados = $this->sqlSinAnulados('r');

        if ($withPersona) {
            $selPersona  = "{$this->sqlPaciente('p')} AS paciente, COALESCE(p.ci, '') AS paciente_ci, COALESCE(d.name, '') AS doctor,";
            $joinPersona = "INNER JOIN {$p} p ON p.person_id = r.person_id
            LEFT JOIN {$d} d ON d.doctor_id = r.doctor_id";
        } else {
            $selPersona  = "'' AS paciente, '' AS paciente_ci, '' AS doctor,";
            $joinPersona = '';
        }

        // Parámetros de pruebas complejas: name = 'c_{secanacategoria_id}'
        $sqlComplejas = "SELECT r.registro_id, r.numero_orden, r.ingreso, r.person_id,
                {$selPersona}
                COALESCE(ana.name, '') AS grupo,
                pri.prianacategoria_id,
                pri.name AS prueba,
                sec.nombre AS parametro,
                rv.regvalues AS valor,
                sec.umedida AS unidad,
                sec.valor_min, sec.valor_max,
                sec.critico_min, sec.critico_max
            FROM {$rv} rv
            INNER JOIN {$r} r ON r.registro_id = CAST(rv.registro_id AS UNSIGNED)
                AND rv.registro_id = CAST(r.registro_id AS CHAR CHARACTER SET utf8mb3)
            {$joinPersona}
            INNER JOIN {$sec} sec ON sec.secanacategoria_id = CAST(SUBSTRING(rv.name, 3) AS UNSIGNED)
            INNER JOIN {$pri} pri ON pri.prianacategoria_id = sec.prianacategoria_id
            LEFT JOIN {$ana} ana ON ana.anacategoria_id = pri.anacategoria_id
            WHERE rv.name LIKE 'c\\_%' AND rv.name REGEXP '^c_[0-9]+$'
              AND COALESCE(sec.es_separador, 0) = 0
              AND {$sinAnulados}";

        // Pruebas simples: name = 'noc_{priresultados_id}'
        $sqlSimples = "SELECT r.registro_id, r.numero_orden, r.ingreso, r.person_id,
                {$selPersona}
                COALESCE(ana.name, '') AS grupo,
                pri.prianacategoria_id,
                pri.name AS prueba,
                pri.name AS parametro,
                rv.regvalues AS valor,
                prr.umedida AS unidad,
                prr.valor_min, prr.valor_max,
                prr.critico_min, prr.critico_max
            FROM {$rv} rv
            INNER JOIN {$r} r ON r.registro_id = CAST(rv.registro_id AS UNSIGNED)
                AND rv.registro_id = CAST(r.registro_id AS CHAR CHARACTER SET utf8mb3)
            {$joinPersona}
            INNER JOIN {$prr} prr ON prr.priresultados_id = CAST(SUBSTRING(rv.name, 5) AS UNSIGNED)
            INNER JOIN {$pri} pri ON pri.prianacategoria_id = prr.prianacategoria_id
            LEFT JOIN {$ana} ana ON ana.anacategoria_id = pri.anacategoria_id
            WHERE rv.name LIKE 'noc\\_%' AND rv.name REGEXP '^noc_[0-9]+$'
              AND {$sinAnulados}";

        if ($extraWhere !== '') {
            $sqlComplejas .= ' AND ' . $extraWhere;
            $sqlSimples   .= ' AND ' . $extraWhere;
        }

        return "({$sqlComplejas}) UNION ALL ({$sqlSimples})";
    }

    /** Límites de fechas como condición SQL segura sobre r.ingreso. */
    private function sqlRangoIngreso(string $startDate, string $endDate): string
    {
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);

        return "r.ingreso >= " . $this->db->escape($ini) . " AND r.ingreso < " . $this->db->escape($fin);
    }

    /**
     * Historial de resultados de un paciente (con referencia y médico).
     *
     * @return list<array<string, mixed>>
     */
    public function getTendenciaPaciente(int $personId, string $startDate, string $endDate, int $pruebaId = 0, int $limit = 2000): array
    {
        $extra = 'r.person_id = ' . (int) $personId . ' AND ' . $this->sqlRangoIngreso($startDate, $endDate);
        if ($pruebaId > 0) {
            $extra .= ' AND pri.prianacategoria_id = ' . (int) $pruebaId;
        }

        $sql = 'SELECT t.* FROM (' . $this->buildRegvaluesResolvedSql($extra) . ') t
            ORDER BY t.prueba ASC, t.parametro ASC, t.ingreso ASC
            LIMIT ' . max(1, $limit);

        $rows = $this->db->query($sql)->getResultArray();

        foreach ($rows as &$row) {
            $row['estado'] = $this->clasificarValor(
                (string) ($row['valor'] ?? ''),
                (string) ($row['valor_min'] ?? ''),
                (string) ($row['valor_max'] ?? ''),
                (string) ($row['critico_min'] ?? ''),
                (string) ($row['critico_max'] ?? '')
            );
        }
        unset($row);

        return $rows;
    }

    /**
     * Historial de resultados de una prueba (todos los pacientes en el período).
     *
     * @return list<array<string, mixed>>
     */
    public function getHistorialPorPrueba(int $pruebaId, string $startDate, string $endDate, int $limit = 3000): array
    {
        if ($pruebaId < 1) {
            return [];
        }

        $extra = 'pri.prianacategoria_id = ' . (int) $pruebaId . ' AND ' . $this->sqlRangoIngreso($startDate, $endDate);

        $sql = 'SELECT t.* FROM (' . $this->buildRegvaluesResolvedSql($extra) . ') t
            ORDER BY t.ingreso DESC, t.paciente ASC, t.parametro ASC
            LIMIT ' . max(1, $limit);

        $rows = $this->db->query($sql)->getResultArray();

        foreach ($rows as &$row) {
            $row['estado'] = $this->clasificarValor(
                (string) ($row['valor'] ?? ''),
                (string) ($row['valor_min'] ?? ''),
                (string) ($row['valor_max'] ?? ''),
                (string) ($row['critico_min'] ?? ''),
                (string) ($row['critico_max'] ?? '')
            );
        }
        unset($row);

        return $rows;
    }

    /**
     * Condición SQL "fuera de rango" + filtros de período/grupo/prueba para
     * la subconsulta de regvalues resuelta contra el catálogo.
     */
    private function buildValoresFueraDeRangoFromSql(string $startDate, string $endDate, int $grupoId, int $pruebaId, bool $withPersona = true): string
    {
        $extra = $this->sqlRangoIngreso($startDate, $endDate);
        if ($pruebaId > 0) {
            $extra .= ' AND pri.prianacategoria_id = ' . (int) $pruebaId;
        }
        if ($grupoId > 0) {
            $extra .= ' AND pri.anacategoria_id = ' . (int) $grupoId;
        }

        $numValor = $this->sqlNum('t.valor');
        $numMin   = $this->sqlNum('t.valor_min');
        $numMax   = $this->sqlNum('t.valor_max');

        return 'FROM (' . $this->buildRegvaluesResolvedSql($extra, $withPersona) . ') t
            WHERE ' . $this->sqlIsNumeric('t.valor') . '
              AND ' . $this->sqlIsNumeric('t.valor_min') . '
              AND ' . $this->sqlIsNumeric('t.valor_max') . "
              AND ({$numValor} < {$numMin} OR {$numValor} > {$numMax})";
    }

    /** Expresión SQL del estado (bajo/alto/crítico) para valores fuera de rango. */
    private function sqlEstadoValor(): string
    {
        $numValor = $this->sqlNum('t.valor');
        $numMin   = $this->sqlNum('t.valor_min');
        $numCMin  = $this->sqlNum('t.critico_min');
        $numCMax  = $this->sqlNum('t.critico_max');
        $cMinOk   = "(t.critico_min IS NOT NULL AND " . $this->sqlIsNumeric('t.critico_min') . ")";
        $cMaxOk   = "(t.critico_max IS NOT NULL AND " . $this->sqlIsNumeric('t.critico_max') . ")";

        return "CASE
            WHEN {$cMinOk} AND {$numValor} < {$numCMin} THEN 'critico_bajo'
            WHEN {$cMaxOk} AND {$numValor} > {$numCMax} THEN 'critico_alto'
            WHEN {$numValor} < {$numMin} THEN 'bajo'
            ELSE 'alto'
        END";
    }

    /**
     * Valores fuera de rango / críticos en un período.
     * Detalle limitado a $limit filas; los indicadores se calculan en SQL
     * sobre el conjunto completo (apto para volúmenes de 100k+ registros).
     *
     * @return array{rows: list<array<string,mixed>>, porEstado: array<string,int>, porPrueba: array<string,int>, porGrupo: array<string,int>, total: int}
     */
    public function getValoresCriticos(string $startDate, string $endDate, int $grupoId = 0, int $pruebaId = 0, int $limit = 3000): array
    {
        $estadoSql = $this->sqlEstadoValor();

        // Detalle (con paciente/médico), limitado
        $fromDetalle = $this->buildValoresFueraDeRangoFromSql($startDate, $endDate, $grupoId, $pruebaId, true);
        $rows = $this->db->query(
            "SELECT t.*, {$estadoSql} AS estado {$fromDetalle} ORDER BY t.ingreso DESC LIMIT " . max(1, $limit)
        )->getResultArray();

        // Indicadores en UNA sola pasada sobre el conjunto completo (sin joins de persona)
        $fromAgg = $this->buildValoresFueraDeRangoFromSql($startDate, $endDate, $grupoId, $pruebaId, false);
        $agg = $this->db->query(
            "SELECT {$estadoSql} AS estado, t.prueba, t.grupo, COUNT(*) AS cnt {$fromAgg} GROUP BY estado, t.prueba, t.grupo"
        )->getResultArray();

        $porEstado = ['bajo' => 0, 'alto' => 0, 'critico_bajo' => 0, 'critico_alto' => 0];
        $porPrueba = [];
        $porGrupo  = [];
        $total     = 0;
        foreach ($agg as $r) {
            $cnt    = (int) $r['cnt'];
            $estado = (string) $r['estado'];
            $total += $cnt;
            if (isset($porEstado[$estado])) {
                $porEstado[$estado] += $cnt;
            }
            $porPrueba[(string) $r['prueba']] = ($porPrueba[(string) $r['prueba']] ?? 0) + $cnt;
            $porGrupo[(string) $r['grupo']]   = ($porGrupo[(string) $r['grupo']] ?? 0) + $cnt;
        }
        arsort($porPrueba);
        arsort($porGrupo);

        return [
            'rows'      => $rows,
            'porEstado' => $porEstado,
            'porPrueba' => array_slice($porPrueba, 0, 50, true),
            'porGrupo'  => array_slice($porGrupo, 0, 50, true),
            'total'     => $total,
        ];
    }

    /**
     * Clasifica un valor: normal | bajo | alto | critico_bajo | critico_alto | no_numerico.
     */
    public function clasificarValor(string $valor, string $min, string $max, string $criticoMin = '', string $criticoMax = ''): string
    {
        $v = $this->toFloat($valor);
        if ($v === null) {
            return 'no_numerico';
        }
        $cMin = $this->toFloat($criticoMin);
        $cMax = $this->toFloat($criticoMax);
        if ($cMin !== null && $v < $cMin) {
            return 'critico_bajo';
        }
        if ($cMax !== null && $v > $cMax) {
            return 'critico_alto';
        }
        $nMin = $this->toFloat($min);
        $nMax = $this->toFloat($max);
        if ($nMin !== null && $v < $nMin) {
            return 'bajo';
        }
        if ($nMax !== null && $v > $nMax) {
            return 'alto';
        }
        if ($nMin === null && $nMax === null) {
            return 'sin_referencia';
        }

        return 'normal';
    }

    private function toFloat(string $value): ?float
    {
        $value = str_replace(',', '.', trim($value));
        if ($value === '' || ! preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $value)) {
            return null;
        }

        return (float) $value;
    }

    // =====================================================================
    // 4. TIEMPO DE ENTREGA (TAT)
    // =====================================================================

    /**
     * Cláusulas FROM/WHERE comunes del reporte TAT: órdenes del período con
     * primer resultado y primera validación tomados de dom_auditoria.
     *
     * @param list<int> $pruebaIds Limitar a órdenes que contengan alguna de estas pruebas (CSV registro.pruebas)
     */
    private function buildTatFromSql(string $startDate, string $endDate, array $pruebaIds = []): string
    {
        $r = $this->db->prefixTable('registro');
        $p = $this->db->prefixTable('people');
        $a = $this->db->prefixTable('auditoria');
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);

        $filtroPruebas = '';
        $ids = array_values(array_filter(array_map('intval', $pruebaIds), static fn ($id) => $id > 0));
        if ($ids !== []) {
            $filtroPruebas = " AND CONCAT(',', r.pruebas, ',') REGEXP " . $this->db->escape(',(' . implode('|', $ids) . '),');
        }

        return "FROM {$r} r
            INNER JOIN {$p} p ON p.person_id = r.person_id
            LEFT JOIN (
                SELECT a.registro_id,
                    MIN(CASE WHEN a.accion = 'guardar_resultados' THEN a.fecha END) AS fecha_resultado,
                    MIN(CASE WHEN a.accion IN ('validar_tecnico','validar_medico') THEN a.fecha END) AS fecha_validacion
                FROM {$a} a
                WHERE a.modulo = 'registers'
                  AND a.accion IN ('guardar_resultados','validar_tecnico','validar_medico')
                  AND a.fecha >= " . $this->db->escape($ini) . "
                GROUP BY a.registro_id
            ) e ON e.registro_id = CAST(r.registro_id AS CHAR)
            WHERE COALESCE(r.anulado, 0) = 0
              AND r.ingreso >= " . $this->db->escape($ini) . '
              AND r.ingreso < ' . $this->db->escape($fin) . $filtroPruebas;
    }

    /**
     * Detalle TAT (limitado) con horas a resultado y a validación calculadas en SQL.
     *
     * @param list<int> $pruebaIds
     * @return list<array<string, mixed>>
     */
    public function getTiempoEntrega(string $startDate, string $endDate, array $pruebaIds = [], int $limit = 3000): array
    {
        $fromSql = $this->buildTatFromSql($startDate, $endDate, $pruebaIds);

        $sql = "SELECT r.registro_id, r.numero_orden, r.ingreso,
                {$this->sqlPaciente('p')} AS paciente,
                e.fecha_resultado, e.fecha_validacion,
                ROUND(TIMESTAMPDIFF(SECOND, r.ingreso, e.fecha_resultado) / 3600, 2) AS horas_resultado,
                ROUND(TIMESTAMPDIFF(SECOND, r.ingreso, e.fecha_validacion) / 3600, 2) AS horas_validacion
            {$fromSql}
            ORDER BY r.ingreso DESC
            LIMIT " . max(1, $limit);

        $rows = $this->db->query($sql)->getResultArray();

        foreach ($rows as &$row) {
            $row['horas_resultado']  = ($row['horas_resultado'] !== null && (float) $row['horas_resultado'] >= 0) ? (float) $row['horas_resultado'] : null;
            $row['horas_validacion'] = ($row['horas_validacion'] !== null && (float) $row['horas_validacion'] >= 0) ? (float) $row['horas_validacion'] : null;
        }
        unset($row);

        return $rows;
    }

    /**
     * Totales TAT calculados en SQL sobre el conjunto completo del período.
     *
     * @param list<int> $pruebaIds
     * @return array<string, mixed>
     */
    public function getTiempoEntregaResumen(string $startDate, string $endDate, int $slaHoras, array $pruebaIds = []): array
    {
        $fromSql  = $this->buildTatFromSql($startDate, $endDate, $pruebaIds);
        $segRes   = 'TIMESTAMPDIFF(SECOND, r.ingreso, e.fecha_resultado)';
        $segVal   = 'TIMESTAMPDIFF(SECOND, r.ingreso, e.fecha_validacion)';
        $slaSeg   = max(1, $slaHoras) * 3600;

        $row = $this->db->query("SELECT COUNT(*) AS ordenes,
                SUM(CASE WHEN {$segRes} >= 0 THEN 1 ELSE 0 END) AS con_resultado,
                ROUND(AVG(CASE WHEN {$segRes} >= 0 THEN {$segRes} END) / 3600, 2) AS promedio_resultado,
                ROUND(MAX(CASE WHEN {$segRes} >= 0 THEN {$segRes} END) / 3600, 2) AS max_resultado,
                ROUND(MIN(CASE WHEN {$segRes} >= 0 THEN {$segRes} END) / 3600, 2) AS min_resultado,
                ROUND(AVG(CASE WHEN {$segVal} >= 0 THEN {$segVal} END) / 3600, 2) AS promedio_validacion,
                ROUND(MAX(CASE WHEN {$segVal} >= 0 THEN {$segVal} END) / 3600, 2) AS max_validacion,
                ROUND(MIN(CASE WHEN {$segVal} >= 0 THEN {$segVal} END) / 3600, 2) AS min_validacion,
                SUM(CASE WHEN {$segRes} BETWEEN 0 AND {$slaSeg} THEN 1 ELSE 0 END) AS dentro_sla
            {$fromSql}")->getRowArray() ?: [];

        $ordenes      = (int) ($row['ordenes'] ?? 0);
        $conResultado = (int) ($row['con_resultado'] ?? 0);
        $dentroSla    = (int) ($row['dentro_sla'] ?? 0);

        return [
            'ordenes'             => $ordenes,
            'con_resultado'       => $conResultado,
            'sin_resultado'       => $ordenes - $conResultado,
            'promedio_resultado'  => $row['promedio_resultado'] !== null ? (float) $row['promedio_resultado'] : null,
            'max_resultado'       => $row['max_resultado'] !== null ? (float) $row['max_resultado'] : null,
            'min_resultado'       => $row['min_resultado'] !== null ? (float) $row['min_resultado'] : null,
            'promedio_validacion' => $row['promedio_validacion'] !== null ? (float) $row['promedio_validacion'] : null,
            'max_validacion'      => $row['max_validacion'] !== null ? (float) $row['max_validacion'] : null,
            'min_validacion'      => $row['min_validacion'] !== null ? (float) $row['min_validacion'] : null,
            'sla_horas'           => $slaHoras,
            'dentro_sla'          => $dentroSla,
            'fuera_sla'           => $conResultado - $dentroSla,
            'cumplimiento_sla'    => $conResultado > 0 ? round($dentroSla * 100 / $conResultado, 2) : null,
        ];
    }

    /**
     * Primer guardado de resultados y primera validación por orden, leídos de
     * dom_auditoria (usa idx_auditoria_modulo_fecha).
     *
     * @return array<string, array{fecha_resultado: ?string, fecha_validacion: ?string, person_resultado: ?int}>
     */
    public function getAuditoriaEventosPorRegistro(string $desdeFecha): array
    {
        $a = $this->db->prefixTable('auditoria');
        [$ini] = RegisterService::labDateRangeToStorageBounds($desdeFecha, $desdeFecha);

        $sql = "SELECT a.registro_id,
                MIN(CASE WHEN a.accion = 'guardar_resultados' THEN a.fecha END) AS fecha_resultado,
                MIN(CASE WHEN a.accion IN ('validar_tecnico','validar_medico') THEN a.fecha END) AS fecha_validacion,
                MIN(CASE WHEN a.accion = 'guardar_resultados' THEN a.person_id END) AS person_resultado
            FROM {$a} a
            WHERE a.modulo = 'registers'
              AND a.accion IN ('guardar_resultados','validar_tecnico','validar_medico')
              AND a.fecha >= " . $this->db->escape($ini) . '
            GROUP BY a.registro_id';

        $map = [];
        foreach ($this->db->query($sql)->getResultArray() as $row) {
            $map[(string) ($row['registro_id'] ?? '')] = [
                'fecha_resultado'  => $row['fecha_resultado'] ?? null,
                'fecha_validacion' => $row['fecha_validacion'] ?? null,
                'person_resultado' => isset($row['person_resultado']) ? (int) $row['person_resultado'] : null,
            ];
        }

        return $map;
    }

    /** Horas (decimales) entre dos datetime; null si falta alguno. */
    public function horasEntre(string $desde, string $hasta): ?float
    {
        if (trim($desde) === '' || trim($hasta) === '') {
            return null;
        }
        try {
            $d1 = new \DateTimeImmutable($desde);
            $d2 = new \DateTimeImmutable($hasta);
        } catch (\Throwable $e) {
            return null;
        }
        $segundos = $d2->getTimestamp() - $d1->getTimestamp();

        return $segundos >= 0 ? round($segundos / 3600, 2) : null;
    }

    // =====================================================================
    // 5. PRODUCTIVIDAD POR USUARIO
    // =====================================================================

    /**
     * Métricas de actividad por usuario, combinando dom_registro (recepciones)
     * y dom_auditoria (resultados, validaciones, ediciones, envíos).
     *
     * @return list<array<string, mixed>>
     */
    public function getProductividadUsuarios(string $startDate, string $endDate, int $personId = 0): array
    {
        $r = $this->db->prefixTable('registro');
        $a = $this->db->prefixTable('auditoria');
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);

        // Recepciones desde la tabla de órdenes (fuente primaria, indexada por ingreso)
        $b = $this->db->table('registro')
            ->select("{$r}.id_session AS person_id, COUNT(*) AS recepciones", false)
            ->where($this->sqlSinAnulados($r), null, false)
            ->groupBy("{$r}.id_session");
        $recepciones = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->get()
            ->getResultArray();

        // Actividad desde la bitácora de auditoría
        $sql = "SELECT a.person_id,
                SUM(CASE WHEN a.accion = 'guardar_resultados'
                         AND JSON_VALID(a.datos)
                         AND JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.es_primera_carga')) = 'true'
                    THEN 1 ELSE 0 END) AS resultados_cargados,
                SUM(CASE WHEN a.accion = 'guardar_resultados'
                         AND (NOT JSON_VALID(a.datos)
                              OR JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.es_primera_carga')) IS NULL
                              OR JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.es_primera_carga')) <> 'true')
                    THEN 1 ELSE 0 END) AS modificaciones,
                SUM(CASE WHEN a.accion IN ('validar_tecnico','validar_medico') THEN 1 ELSE 0 END) AS validaciones,
                SUM(CASE WHEN a.accion = 'imprimir' THEN 1 ELSE 0 END) AS impresiones,
                SUM(CASE WHEN a.accion = 'enviar_whatsapp' THEN 1 ELSE 0 END) AS envios_whatsapp
            FROM {$a} a
            WHERE a.modulo = 'registers'
              AND a.fecha >= " . $this->db->escape($ini) . '
              AND a.fecha < ' . $this->db->escape($fin) . '
              AND a.person_id IS NOT NULL
            GROUP BY a.person_id';

        $actividad = $this->db->query($sql)->getResultArray();

        $porUsuario = [];
        foreach ($recepciones as $row) {
            $pid = (int) ($row['person_id'] ?? 0);
            $porUsuario[$pid]['recepciones'] = (int) ($row['recepciones'] ?? 0);
        }
        foreach ($actividad as $row) {
            $pid = (int) ($row['person_id'] ?? 0);
            $porUsuario[$pid]['resultados_cargados'] = (int) ($row['resultados_cargados'] ?? 0);
            $porUsuario[$pid]['modificaciones']      = (int) ($row['modificaciones'] ?? 0);
            $porUsuario[$pid]['validaciones']        = (int) ($row['validaciones'] ?? 0);
            $porUsuario[$pid]['impresiones']         = (int) ($row['impresiones'] ?? 0);
            $porUsuario[$pid]['envios_whatsapp']     = (int) ($row['envios_whatsapp'] ?? 0);
        }

        if ($personId > 0) {
            $porUsuario = array_intersect_key($porUsuario, [$personId => true]);
        }
        if ($porUsuario === []) {
            return [];
        }

        $nombres = $this->getNombresPersonas(array_keys($porUsuario));

        $rows = [];
        foreach ($porUsuario as $pid => $metricas) {
            $rows[] = [
                'person_id'           => $pid,
                'usuario'             => $nombres[$pid] ?? ('Usuario #' . $pid),
                'recepciones'         => (int) ($metricas['recepciones'] ?? 0),
                'resultados_cargados' => (int) ($metricas['resultados_cargados'] ?? 0),
                'modificaciones'      => (int) ($metricas['modificaciones'] ?? 0),
                'validaciones'        => (int) ($metricas['validaciones'] ?? 0),
                'impresiones'         => (int) ($metricas['impresiones'] ?? 0),
                'envios_whatsapp'     => (int) ($metricas['envios_whatsapp'] ?? 0),
            ];
        }

        usort($rows, static function ($x, $y) {
            $sx = $x['recepciones'] + $x['resultados_cargados'] + $x['validaciones'] + $x['modificaciones'];
            $sy = $y['recepciones'] + $y['resultados_cargados'] + $y['validaciones'] + $y['modificaciones'];

            return $sy <=> $sx;
        });

        $rank = 0;
        foreach ($rows as &$row) {
            $row['ranking'] = ++$rank;
            $row['total_actividad'] = $row['recepciones'] + $row['resultados_cargados']
                + $row['modificaciones'] + $row['validaciones'];
        }
        unset($row);

        return $rows;
    }

    /** Lista de empleados activos para el filtro de usuario. */
    public function getUsuariosEmpleados(): array
    {
        $e = $this->db->prefixTable('employees');
        $p = $this->db->prefixTable('people');

        return $this->db->table('employees')
            ->select("{$e}.person_id, {$this->sqlPaciente($p)} AS nombre, {$e}.username", false)
            ->join('people', "{$p}.person_id = {$e}.person_id")
            ->where("{$e}.deleted", 0)
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @param list<int> $personIds
     * @return array<int, string>
     */
    private function getNombresPersonas(array $personIds): array
    {
        $personIds = array_values(array_filter(array_map('intval', $personIds), static fn ($id) => $id > 0));
        if ($personIds === []) {
            return [];
        }
        $p = $this->db->prefixTable('people');

        $rows = $this->db->table('people')
            ->select("{$p}.person_id, {$this->sqlPaciente($p)} AS nombre", false)
            ->whereIn("{$p}.person_id", $personIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['person_id']] = (string) $row['nombre'];
        }

        return $map;
    }

    // =====================================================================
    // 6. RESULTADOS CORREGIDOS (AUDITORÍA)
    // =====================================================================

    /**
     * Correcciones de resultados: eventos guardar_resultados de dom_auditoria
     * cuyo detalle JSON contiene cambios (valor_anterior → valor_nuevo).
     * La trazabilidad ya existe (AuditoriaModel); este reporte solo la lee.
     *
     * @return list<array<string, mixed>>
     */
    public function getResultadosCorregidos(string $startDate, string $endDate, int $personId = 0, int $limit = 3000): array
    {
        $a = $this->db->prefixTable('auditoria');
        $p = $this->db->prefixTable('people');
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);

        $filtroUsuario = $personId > 0 ? ' AND a.person_id = ' . (int) $personId : '';

        $sql = "SELECT a.auditoria_id, a.registro_id, a.person_id, a.fecha, a.datos,
                {$this->sqlPaciente('p')} AS usuario
            FROM {$a} a
            LEFT JOIN {$p} p ON p.person_id = a.person_id
            WHERE a.modulo = 'registers'
              AND a.accion = 'guardar_resultados'
              AND a.fecha >= " . $this->db->escape($ini) . '
              AND a.fecha < ' . $this->db->escape($fin) . "
              AND JSON_VALID(a.datos)
              AND JSON_LENGTH(JSON_EXTRACT(a.datos, '$.cambios')) > 0
              {$filtroUsuario}
            ORDER BY a.fecha DESC
            LIMIT " . max(1, $limit);

        $eventos = $this->db->query($sql)->getResultArray();
        if ($eventos === []) {
            return [];
        }

        // Pacientes de las órdenes implicadas (una sola consulta)
        $rids = array_values(array_unique(array_map(
            static fn ($e) => (int) ($e['registro_id'] ?? 0),
            $eventos
        )));
        $pacientes = $this->getPacientesPorRegistro($rids);

        $rows = [];
        foreach ($eventos as $evento) {
            $datos   = json_decode((string) ($evento['datos'] ?? ''), true) ?: [];
            $cambios = is_array($datos['cambios'] ?? null) ? $datos['cambios'] : [];
            foreach ($cambios as $cambio) {
                $rows[] = [
                    'auditoria_id'   => (int) ($evento['auditoria_id'] ?? 0),
                    'registro_id'    => (string) ($evento['registro_id'] ?? ''),
                    'numero_orden'   => $pacientes[(int) ($evento['registro_id'] ?? 0)]['numero_orden'] ?? '',
                    'paciente'       => $pacientes[(int) ($evento['registro_id'] ?? 0)]['paciente'] ?? '',
                    'fecha'          => (string) ($evento['fecha'] ?? ''),
                    'usuario'        => (string) ($evento['usuario'] ?? ''),
                    'prueba'         => (string) ($cambio['prueba'] ?? ''),
                    'campo'          => (string) ($cambio['campo'] ?? ''),
                    'valor_anterior' => (string) ($cambio['valor_anterior'] ?? ''),
                    'valor_nuevo'    => (string) ($cambio['valor_nuevo'] ?? ''),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param list<int> $registroIds
     * @return array<int, array{paciente: string, numero_orden: string}>
     */
    private function getPacientesPorRegistro(array $registroIds): array
    {
        $registroIds = array_values(array_filter($registroIds, static fn ($id) => $id > 0));
        if ($registroIds === []) {
            return [];
        }
        $r = $this->db->prefixTable('registro');
        $p = $this->db->prefixTable('people');

        $rows = $this->db->table('registro')
            ->select("{$r}.registro_id, COALESCE({$r}.numero_orden, '') AS numero_orden, {$this->sqlPaciente($p)} AS paciente", false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->whereIn("{$r}.registro_id", $registroIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['registro_id']] = [
                'paciente'     => (string) ($row['paciente'] ?? ''),
                'numero_orden' => (string) ($row['numero_orden'] ?? ''),
            ];
        }

        return $map;
    }

    // =====================================================================
    // 7. RESULTADOS PENDIENTES DE VALIDAR
    // =====================================================================

    /**
     * Cláusulas FROM/WHERE comunes del reporte de pendientes de validar:
     * órdenes con resultados (dom_regvalues) sin validación técnica ni médica
     * registrada en dom_auditoria ni en dom_resulanalisis.
     */
    private function buildPendientesFromSql(string $startDate, string $endDate): string
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $rv = $this->db->prefixTable('regvalues');
        $ra = $this->db->prefixTable('resulanalisis');
        $a  = $this->db->prefixTable('auditoria');
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);
        $iniEsc = $this->db->escape($ini);

        // Tablas derivadas (hash join): evita subconsultas correlacionadas que no pueden
        // usar índice por diferencia de colación entre CAST(int AS CHAR) y los varchar utf8mb3.
        // La bitácora se agrega una sola vez ya filtrada por fecha.
        return "FROM {$r} r
            INNER JOIN {$p} p ON p.person_id = r.person_id
            INNER JOIN (
                SELECT rv.registro_id, MIN(rv.id_session) AS usuario_cargo_id
                FROM {$rv} rv
                GROUP BY rv.registro_id
            ) cargas ON cargas.registro_id = CAST(r.registro_id AS CHAR)
            LEFT JOIN (
                SELECT a.registro_id,
                    MIN(CASE WHEN a.accion = 'guardar_resultados' THEN a.fecha END) AS fecha_resultado,
                    MIN(CASE WHEN a.accion IN ('validar_tecnico','validar_medico') THEN a.fecha END) AS fecha_validacion
                FROM {$a} a
                WHERE a.modulo = 'registers'
                  AND a.accion IN ('guardar_resultados','validar_tecnico','validar_medico')
                  AND a.fecha >= {$iniEsc}
                GROUP BY a.registro_id
            ) e ON e.registro_id = CAST(r.registro_id AS CHAR)
            LEFT JOIN (
                SELECT ra.registro_id
                FROM {$ra} ra
                WHERE COALESCE(ra.validado_tecnico, 0) = 1 OR COALESCE(ra.validado_medico, 0) = 1
                GROUP BY ra.registro_id
            ) val ON val.registro_id = CAST(r.registro_id AS CHAR)
            WHERE COALESCE(r.anulado, 0) = 0
              AND r.ingreso >= {$iniEsc}
              AND r.ingreso < " . $this->db->escape($fin) . '
              AND e.fecha_validacion IS NULL
              AND val.registro_id IS NULL';
    }

    /**
     * Detalle (limitado, más antiguas primero) de órdenes pendientes de validar.
     *
     * @return array{rows: list<array<string,mixed>>, total: int, porUsuario: array<string,int>}
     */
    public function getPendientesValidacion(string $startDate, string $endDate, int $limit = 3000): array
    {
        $fromSql  = $this->buildPendientesFromSql($startDate, $endDate);
        $ahoraEsc = $this->db->escape(RegisterService::mysqlNowForReport());

        $rows = $this->db->query("SELECT r.registro_id, r.numero_orden, r.ingreso, r.pruebas,
                {$this->sqlPaciente('p')} AS paciente,
                cargas.usuario_cargo_id,
                e.fecha_resultado,
                ROUND(TIMESTAMPDIFF(SECOND, r.ingreso, {$ahoraEsc}) / 3600, 2) AS horas_pendiente
            {$fromSql}
            ORDER BY r.ingreso ASC
            LIMIT " . max(1, $limit))->getResultArray();

        // Total e indicador por usuario en una sola pasada sobre el conjunto completo
        $total         = 0;
        $porUsuarioIds = [];
        foreach ($this->db->query("SELECT cargas.usuario_cargo_id, COUNT(*) AS cnt {$fromSql} GROUP BY cargas.usuario_cargo_id ORDER BY cnt DESC")->getResultArray() as $r) {
            $cnt    = (int) $r['cnt'];
            $total += $cnt;
            if (count($porUsuarioIds) < 50) {
                $porUsuarioIds[(int) ($r['usuario_cargo_id'] ?? 0)] = $cnt;
            }
        }

        $nombres = $this->getNombresPersonas(array_merge(
            array_keys($porUsuarioIds),
            array_map(static fn ($row) => (int) ($row['usuario_cargo_id'] ?? 0), $rows)
        ));

        $porUsuario = [];
        foreach ($porUsuarioIds as $uid => $cnt) {
            $nombre = $uid > 0 ? ($nombres[$uid] ?? ('Usuario #' . $uid)) : '—';
            $porUsuario[$nombre] = ($porUsuario[$nombre] ?? 0) + $cnt;
        }

        foreach ($rows as &$row) {
            $uid = (int) ($row['usuario_cargo_id'] ?? 0);
            $row['usuario_cargo']   = $uid > 0 ? ($nombres[$uid] ?? ('Usuario #' . $uid)) : '—';
            $row['horas_pendiente'] = max(0.0, (float) ($row['horas_pendiente'] ?? 0));
        }
        unset($row);

        return [
            'rows'       => $rows,
            'total'      => $total,
            'porUsuario' => $porUsuario,
        ];
    }

    // =====================================================================
    // 8. CONSUMO DE INSUMOS POR PRUEBA
    // =====================================================================

    /**
     * Consumo automático por prueba/reactivo (dom_reactivo_consumo_auto).
     *
     * @return list<array<string, mixed>>
     */
    public function getConsumoPorPrueba(string $startDate, string $endDate): array
    {
        $ca  = $this->db->prefixTable('reactivo_consumo_auto');
        $pri = $this->db->prefixTable('prianacategoria');
        $re  = $this->db->prefixTable('reactivo');

        $b = $this->db->table('reactivo_consumo_auto')
            ->select("COALESCE({$pri}.name, CONCAT('Prueba #', {$ca}.prianacategoria_id)) AS prueba,
                COALESCE({$re}.nombre, CONCAT('Reactivo #', {$ca}.reactivo_id)) AS reactivo,
                COALESCE({$re}.unidad, '') AS unidad,
                COUNT(*) AS eventos,
                SUM({$ca}.cantidad) AS cantidad_total", false)
            ->join('prianacategoria', "{$pri}.prianacategoria_id = {$ca}.prianacategoria_id", 'left')
            ->join('reactivo', "{$re}.reactivo_id = {$ca}.reactivo_id", 'left')
            ->where("{$ca}.estado", 'aplicado')
            ->groupBy("{$ca}.prianacategoria_id, {$ca}.reactivo_id");
        $b = LabNaiveDateRange::apply($b, $ca, 'created_at', $startDate, $endDate);

        return $b->orderBy('cantidad_total', 'DESC')->get()->getResultArray();
    }

    /**
     * Salidas de inventario por reactivo (dom_reactivo_movimiento tipo=salida).
     *
     * @return list<array<string, mixed>>
     */
    public function getConsumoPorReactivo(string $startDate, string $endDate): array
    {
        $m  = $this->db->prefixTable('reactivo_movimiento');
        $re = $this->db->prefixTable('reactivo');

        $b = $this->db->table('reactivo_movimiento')
            ->select("{$re}.reactivo_id, {$re}.nombre AS reactivo, COALESCE({$re}.unidad, '') AS unidad,
                COUNT(*) AS movimientos, SUM({$m}.cantidad) AS cantidad_total", false)
            ->join('reactivo', "{$re}.reactivo_id = {$m}.reactivo_id")
            ->where("{$m}.tipo", 'salida')
            ->groupBy("{$m}.reactivo_id");
        $b = LabNaiveDateRange::apply($b, $m, 'fecha', $startDate, $endDate);

        return $b->orderBy('cantidad_total', 'DESC')->get()->getResultArray();
    }

    /**
     * Consumo agrupado por día o por mes.
     *
     * @param 'dia'|'mes' $agrupacion
     * @return list<array<string, mixed>>
     */
    public function getConsumoPorPeriodo(string $startDate, string $endDate, string $agrupacion = 'dia'): array
    {
        $m  = $this->db->prefixTable('reactivo_movimiento');
        $re = $this->db->prefixTable('reactivo');

        $expr = $agrupacion === 'mes'
            ? "DATE_FORMAT({$m}.fecha, '%Y-%m')"
            : "DATE({$m}.fecha)";

        $b = $this->db->table('reactivo_movimiento')
            ->select("{$expr} AS periodo, {$re}.nombre AS reactivo, COALESCE({$re}.unidad, '') AS unidad,
                SUM({$m}.cantidad) AS cantidad_total", false)
            ->join('reactivo', "{$re}.reactivo_id = {$m}.reactivo_id")
            ->where("{$m}.tipo", 'salida')
            ->groupBy("periodo, {$m}.reactivo_id");
        $b = LabNaiveDateRange::apply($b, $m, 'fecha', $startDate, $endDate);

        return $b->orderBy('periodo', 'ASC')->get()->getResultArray();
    }

    // =====================================================================
    // 9. PROYECCIÓN DE AGOTAMIENTO DE INSUMOS
    // =====================================================================

    /**
     * Stock actual (lotes activos) + consumo promedio de los últimos N días.
     * Clasificación: critico (≤ diasCritico o bajo stock mínimo),
     * advertencia (≤ diasAdvertencia), normal.
     *
     * @return list<array<string, mixed>>
     */
    public function getProyeccionInsumos(int $diasVentana = 30, int $diasCritico = 7, int $diasAdvertencia = 30): array
    {
        $re = $this->db->prefixTable('reactivo');
        $rl = $this->db->prefixTable('reactivo_lote');
        $m  = $this->db->prefixTable('reactivo_movimiento');

        $desde = RegisterService::reportDateFromModifier('-' . max(1, $diasVentana) . ' days');
        [$ini] = RegisterService::labDateRangeToStorageBounds($desde, $desde);

        $sql = "SELECT re.reactivo_id, re.nombre, COALESCE(re.unidad, '') AS unidad, COALESCE(re.stock_minimo, 0) AS stock_minimo,
                COALESCE(st.stock, 0) AS stock_actual,
                COALESCE(cs.consumido, 0) AS consumido_ventana
            FROM {$re} re
            LEFT JOIN (
                SELECT rl.reactivo_id, SUM(rl.cantidad) AS stock
                FROM {$rl} rl
                WHERE COALESCE(rl.deleted, 0) = 0
                GROUP BY rl.reactivo_id
            ) st ON st.reactivo_id = re.reactivo_id
            LEFT JOIN (
                SELECT m.reactivo_id, SUM(m.cantidad) AS consumido
                FROM {$m} m
                WHERE m.tipo = 'salida' AND m.fecha >= " . $this->db->escape($ini) . "
                GROUP BY m.reactivo_id
            ) cs ON cs.reactivo_id = re.reactivo_id
            WHERE COALESCE(re.deleted, 0) = 0
            ORDER BY re.nombre ASC";

        $rows = $this->db->query($sql)->getResultArray();

        $hoy = new \DateTimeImmutable(RegisterService::todayForReport());
        foreach ($rows as &$row) {
            $stock    = (float) ($row['stock_actual'] ?? 0);
            $ventana  = max(1, $diasVentana);
            $promedio = (float) ($row['consumido_ventana'] ?? 0) / $ventana;
            $row['consumo_promedio_dia'] = round($promedio, 4);

            if ($promedio <= 0) {
                $row['dias_restantes']    = null;
                $row['fecha_agotamiento'] = null;
                $row['estado'] = $stock <= 0 ? 'sin_stock' : 'sin_consumo';
            } else {
                $dias = (int) floor($stock / $promedio);
                $row['dias_restantes']    = $dias;
                $row['fecha_agotamiento'] = $hoy->modify('+' . $dias . ' days')->format('Y-m-d');
                if ($dias <= $diasCritico || ($row['stock_minimo'] > 0 && $stock < (float) $row['stock_minimo'])) {
                    $row['estado'] = 'critico';
                } elseif ($dias <= $diasAdvertencia) {
                    $row['estado'] = 'advertencia';
                } else {
                    $row['estado'] = 'normal';
                }
            }
        }
        unset($row);

        return $rows;
    }

    // =====================================================================
    // 10. COMPARATIVO MENSUAL DE INGRESOS Y PACIENTES
    // =====================================================================

    /**
     * Métricas por mes: pacientes únicos, órdenes, pruebas y facturación.
     * El conteo de pruebas usa el CSV registro.pruebas (comas + 1).
     *
     * @return list<array<string, mixed>>
     */
    public function getComparativoMensual(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $pg = $this->db->prefixTable('pago');
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);

        $sql = "SELECT DATE_FORMAT(r.ingreso, '%Y-%m') AS mes,
                COUNT(*) AS ordenes,
                COUNT(DISTINCT r.person_id) AS pacientes,
                SUM(CASE WHEN TRIM(COALESCE(r.pruebas, '')) = '' THEN 0
                    ELSE LENGTH(r.pruebas) - LENGTH(REPLACE(r.pruebas, ',', '')) + 1 END) AS pruebas,
                SUM(CAST(COALESCE(pg.total, '0') AS DECIMAL(15,2))) AS facturado,
                SUM(CAST(COALESCE(pg.monto_pagar, '0') AS DECIMAL(15,2))) AS cobrado
            FROM {$r} r
            LEFT JOIN {$pg} pg ON pg.registro_id = r.registro_id
            WHERE COALESCE(r.anulado, 0) = 0
              AND r.ingreso >= " . $this->db->escape($ini) . '
              AND r.ingreso < ' . $this->db->escape($fin) . '
            GROUP BY mes
            ORDER BY mes ASC';

        $rows = $this->db->query($sql)->getResultArray();

        // Variaciones vs mes anterior y vs mismo mes del año anterior + acumulados
        $porMes = [];
        foreach ($rows as $row) {
            $porMes[(string) $row['mes']] = $row;
        }

        $acumFacturado = 0.0;
        $acumOrdenes   = 0;
        foreach ($rows as $i => &$row) {
            $mes  = (string) $row['mes'];
            $prev = $i > 0 ? $rows[$i - 1] : null;
            try {
                $anioAnterior = (new \DateTimeImmutable($mes . '-01'))->modify('-1 year')->format('Y-m');
            } catch (\Throwable $e) {
                $anioAnterior = '';
            }
            $mismoMesAnterior = $porMes[$anioAnterior] ?? null;

            $row['var_mes_ordenes']     = $this->variacionPct((float) ($prev['ordenes'] ?? 0), (float) $row['ordenes'], $prev !== null);
            $row['var_mes_facturado']   = $this->variacionPct((float) ($prev['facturado'] ?? 0), (float) $row['facturado'], $prev !== null);
            $row['var_anio_ordenes']    = $this->variacionPct((float) ($mismoMesAnterior['ordenes'] ?? 0), (float) $row['ordenes'], $mismoMesAnterior !== null);
            $row['var_anio_facturado']  = $this->variacionPct((float) ($mismoMesAnterior['facturado'] ?? 0), (float) $row['facturado'], $mismoMesAnterior !== null);

            $acumFacturado += (float) $row['facturado'];
            $acumOrdenes   += (int) $row['ordenes'];
            $row['acumulado_facturado'] = $acumFacturado;
            $row['acumulado_ordenes']   = $acumOrdenes;
        }
        unset($row);

        return $rows;
    }

    private function variacionPct(float $anterior, float $actual, bool $hayBase): ?float
    {
        if (! $hayBase) {
            return null;
        }
        if ($anterior == 0.0) {
            return $actual > 0 ? 100.0 : 0.0;
        }

        return round(($actual - $anterior) * 100 / $anterior, 2);
    }

    // =====================================================================
    // NOTIFICACIONES DE ENTREGA
    // =====================================================================

    /**
     * Entregas confirmadas con el botón «Notificar».
     * Combina dom_analisis_delivery_notification (attended) y dom_auditoria.
     *
     * @return list<array<string, mixed>>
     */
    public function getNotificacionesEntregaDetalle(
        string $startDate,
        string $endDate,
        int $personId = 0,
        int $registroId = 0,
        int $pruebaId = 0,
        int $limit = 5000
    ): array {
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);
        $rows        = [];

        if ($this->db->tableExists('analisis_delivery_notification')) {
            $n   = $this->db->prefixTable('analisis_delivery_notification');
            $r   = $this->db->prefixTable('registro');
            $p   = $this->db->prefixTable('people');
            $pri = $this->db->prefixTable('prianacategoria');

            $builder = $this->db->table("{$n} nd")
                ->select("nd.notification_id, nd.registro_id, nd.prianacategoria_id, nd.validated_at,
                    nd.attended_at AS fecha_entrega, nd.attended_by AS person_id, nd.status,
                    r.numero_orden, r.ingreso,
                    {$this->sqlPaciente('p')} AS paciente,
                    pri.name AS analisis_nombre,
                    {$this->sqlPaciente('pu')} AS usuario", false)
                ->join("{$r} r", 'r.registro_id = nd.registro_id', 'inner')
                ->join("{$p} p", 'p.person_id = r.person_id', 'left')
                ->join("{$pri} pri", 'pri.prianacategoria_id = nd.prianacategoria_id', 'left')
                ->join("{$p} pu", 'pu.person_id = nd.attended_by', 'left')
                ->where('nd.status', 'attended')
                ->where('nd.attended_at >=', $ini)
                ->where('nd.attended_at <', $fin)
                ->where($this->sqlSinAnulados('r'), null, false);

            if ($personId > 0) {
                $builder->where('nd.attended_by', $personId);
            }
            if ($registroId > 0) {
                $builder->where('nd.registro_id', $registroId);
            }
            if ($pruebaId > 0) {
                $builder->where('nd.prianacategoria_id', $pruebaId);
            }

            foreach ($builder->orderBy('nd.attended_at', 'DESC')->limit($limit)->get()->getResultArray() as $row) {
                $row['fuente'] = 'analisis';
                $rows[]        = $this->enrichNotificacionEntregaRow($row);
            }
        }

        if ($pruebaId < 1) {
            $covered = [];
            foreach ($rows as $row) {
                $covered[($row['registro_id'] ?? 0) . '|' . substr((string) ($row['fecha_entrega'] ?? ''), 0, 10)] = true;
            }
            foreach ($this->getNotificacionesEntregaDesdeAuditoria($ini, $fin, $personId, $registroId, $limit) as $audRow) {
                $key = ($audRow['registro_id'] ?? 0) . '|' . substr((string) ($audRow['fecha_entrega'] ?? ''), 0, 10);
                if (isset($covered[$key])) {
                    continue;
                }
                $rows[] = $this->enrichNotificacionEntregaRow($audRow);
            }
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($b['fecha_entrega'] ?? ''), (string) ($a['fecha_entrega'] ?? ''));
        });

        return array_slice($rows, 0, $limit);
    }

    /** Total de confirmaciones registradas en auditoría (clics en Notificar). */
    public function countNotificacionesEntregaAuditoria(
        string $startDate,
        string $endDate,
        int $personId = 0,
        int $registroId = 0
    ): int {
        [$ini, $fin] = RegisterService::labDateRangeToStorageBounds($startDate, $endDate);
        $a           = $this->db->prefixTable('auditoria');

        $builder = $this->db->table('auditoria')
            ->where("{$a}.modulo", 'registers')
            ->where("{$a}.accion", 'notificar_entrega_analisis')
            ->where("{$a}.fecha >=", $ini)
            ->where("{$a}.fecha <", $fin);

        if ($personId > 0) {
            $builder->where("{$a}.person_id", $personId);
        }
        if ($registroId > 0) {
            $builder->where("{$a}.registro_id", $registroId);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getNotificacionesEntregaDesdeAuditoria(
        string $ini,
        string $fin,
        int $personId,
        int $registroId,
        int $limit
    ): array {
        $a = $this->db->prefixTable('auditoria');
        $p = $this->db->prefixTable('people');
        $r = $this->db->prefixTable('registro');

        $filtroUsuario  = $personId > 0 ? ' AND a.person_id = ' . (int) $personId : '';
        $filtroRegistro = $registroId > 0 ? ' AND a.registro_id = ' . (int) $registroId : '';

        $sql = "SELECT a.auditoria_id, a.registro_id, a.person_id, a.fecha AS fecha_entrega,
                r.numero_orden, r.ingreso,
                {$this->sqlPaciente('p')} AS usuario,
                {$this->sqlPaciente('pac')} AS paciente
            FROM {$a} a
            LEFT JOIN {$p} p ON p.person_id = a.person_id
            LEFT JOIN {$r} r ON r.registro_id = a.registro_id
            LEFT JOIN {$p} pac ON pac.person_id = r.person_id
            WHERE a.modulo = 'registers'
              AND a.accion = 'notificar_entrega_analisis'
              AND a.fecha >= " . $this->db->escape($ini) . '
              AND a.fecha < ' . $this->db->escape($fin) . "
              {$filtroUsuario}{$filtroRegistro}
            ORDER BY a.fecha DESC
            LIMIT " . max(1, $limit);

        $out = [];
        foreach ($this->db->query($sql)->getResultArray() as $row) {
            $row['fuente']              = 'auditoria';
            $row['prianacategoria_id']  = 0;
            $row['validated_at']        = null;
            $row['analisis_nombre']     = 'Recepción completa';
            $row['status']              = 'attended';
            $out[]                      = $row;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function enrichNotificacionEntregaRow(array $row): array
    {
        $validated = trim((string) ($row['validated_at'] ?? ''));
        $entrega   = trim((string) ($row['fecha_entrega'] ?? ''));
        $horas     = ($validated !== '' && $entrega !== '')
            ? $this->diffHoursMysql($validated, $entrega)
            : null;

        $bucket = 'sin_validacion';
        if ($horas !== null) {
            if ($horas <= 12) {
                $bucket = 'dentro_12';
            } elseif ($horas <= 24) {
                $bucket = 'entre_12_24';
            } else {
                $bucket = 'mayor_24';
            }
        }

        $priaId = (int) ($row['prianacategoria_id'] ?? 0);
        $nombre = trim((string) ($row['analisis_nombre'] ?? ''));

        return array_merge($row, [
            'horas_transcurridas' => $horas,
            'bucket_tiempo'       => $bucket,
            'estado'              => 'Entregado',
            'analisis_codigo'     => $nombre !== '' ? $nombre : ($priaId > 0 ? ('Análisis #' . $priaId) : 'Recepción completa'),
            'registro_codigo'     => trim((string) ($row['numero_orden'] ?? '')) !== ''
                ? trim((string) $row['numero_orden'])
                : (string) (int) ($row['registro_id'] ?? 0),
        ]);
    }

    private function diffHoursMysql(string $from, string $to): ?float
    {
        try {
            $dtFrom = new \DateTimeImmutable($from);
            $dtTo   = new \DateTimeImmutable($to);
            $sec    = $dtTo->getTimestamp() - $dtFrom->getTimestamp();

            return $sec >= 0 ? round($sec / 3600, 2) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
