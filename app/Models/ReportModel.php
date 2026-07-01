<?php

namespace App\Models;

use App\Libraries\LabNaiveDateRange;
use App\Libraries\RegistroIngresoDateRange;
use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'registro';

    /** @var bool|null */
    private static $registroTieneCampoAnulado = null;

    /** @var bool|null */
    private static $registroTieneCampoDeleted = null;

    /** @var bool|null */
    private static $registroTieneCampoMotivoAnulacion = null;
    /** @var bool|null */
    private static $registroTieneCampoPrioridad = null;
    /** @var bool|null */
    private static $registroTieneCampoOrigenPrueba = null;
    /** @var bool|null */
    private static $egresosTieneTipoMovimiento = null;

    private function registroTieneCampoAnulado(): bool
    {
        if (self::$registroTieneCampoAnulado === null) {
            try {
                $t = $this->db->prefixTable('registro');
                self::$registroTieneCampoAnulado = in_array('anulado', $this->db->getFieldNames($t), true);
            } catch (\Throwable $e) {
                self::$registroTieneCampoAnulado = false;
            }
        }

        return self::$registroTieneCampoAnulado;
    }

    private function registroTieneCampoDeleted(): bool
    {
        if (self::$registroTieneCampoDeleted === null) {
            try {
                $t = $this->db->prefixTable('registro');
                self::$registroTieneCampoDeleted = in_array('deleted', $this->db->getFieldNames($t), true);
            } catch (\Throwable $e) {
                self::$registroTieneCampoDeleted = false;
            }
        }

        return self::$registroTieneCampoDeleted;
    }

    private function registroTieneCampoMotivoAnulacion(): bool
    {
        if (self::$registroTieneCampoMotivoAnulacion === null) {
            try {
                $t = $this->db->prefixTable('registro');
                self::$registroTieneCampoMotivoAnulacion = in_array('motivo_anulacion', $this->db->getFieldNames($t), true);
            } catch (\Throwable $e) {
                self::$registroTieneCampoMotivoAnulacion = false;
            }
        }

        return self::$registroTieneCampoMotivoAnulacion;
    }

    private function registroTieneCampoPrioridad(): bool
    {
        if (self::$registroTieneCampoPrioridad === null) {
            try {
                $t = $this->db->prefixTable('registro');
                self::$registroTieneCampoPrioridad = in_array('prioridad', $this->db->getFieldNames($t), true);
            } catch (\Throwable $e) {
                self::$registroTieneCampoPrioridad = false;
            }
        }

        return self::$registroTieneCampoPrioridad;
    }

    private function registroTieneCampoOrigenPrueba(): bool
    {
        if (self::$registroTieneCampoOrigenPrueba === null) {
            try {
                $t = $this->db->prefixTable('registro');
                self::$registroTieneCampoOrigenPrueba = in_array('origen_prueba', $this->db->getFieldNames($t), true);
            } catch (\Throwable $e) {
                self::$registroTieneCampoOrigenPrueba = false;
            }
        }

        return self::$registroTieneCampoOrigenPrueba;
    }

    /**
     * Clave de procesamiento (rutina|urgente|derivacion) según columnas de dom_registro.
     */
    private function sqlProcesamientoRegistroExpr(string $rAlias): string
    {
        $hasOrigen = $this->registroTieneCampoOrigenPrueba();
        $hasPrioridad = $this->registroTieneCampoPrioridad();

        if ($hasOrigen && $hasPrioridad) {
            return "CASE WHEN COALESCE({$rAlias}.origen_prueba, 0) = 1 THEN 'derivacion' "
                . "WHEN COALESCE({$rAlias}.prioridad, 0) = 1 THEN 'urgente' "
                . "ELSE 'rutina' END";
        }
        if ($hasOrigen) {
            return "CASE WHEN COALESCE({$rAlias}.origen_prueba, 0) = 1 THEN 'derivacion' ELSE 'rutina' END";
        }
        if ($hasPrioridad) {
            return "CASE WHEN COALESCE({$rAlias}.prioridad, 0) = 1 THEN 'urgente' ELSE 'rutina' END";
        }

        return "'rutina'";
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function emptyResumenPagosPorProcesamiento(): array
    {
        $resumen = [];
        foreach (['rutina', 'urgente', 'derivacion'] as $key) {
            $resumen[$key] = [
                'procesamiento'   => $key,
                'cantidad'        => 0,
                'total_facturado' => 0.0,
                'total_cobrado'   => 0.0,
                'total_pendiente' => 0.0,
            ];
        }

        return $resumen;
    }

    private function egresosTieneTipoMovimiento(): bool
    {
        if (self::$egresosTieneTipoMovimiento === null) {
            try {
                $t = $this->db->prefixTable('egresos');
                self::$egresosTieneTipoMovimiento = in_array('tipo_movimiento', $this->db->getFieldNames($t), true);
            } catch (\Throwable $e) {
                self::$egresosTieneTipoMovimiento = false;
            }
        }

        return self::$egresosTieneTipoMovimiento;
    }

    /**
     * Filtra órdenes por procesamiento (rutina|urgente|derivacion).
     *
     * @param \CodeIgniter\Database\BaseBuilder $builder
     */
    private function applyFiltroProcesamientoRegistro($builder, string $r, string $procesamiento): void
    {
        if (! in_array($procesamiento, ['rutina', 'urgente', 'derivacion'], true)) {
            return;
        }

        $hasOrigen = $this->registroTieneCampoOrigenPrueba();
        $hasPrioridad = $this->registroTieneCampoPrioridad();

        if ($procesamiento === 'derivacion') {
            if ($hasOrigen) {
                $builder->where("COALESCE({$r}.origen_prueba, 0) = 1", null, false);
            } else {
                $builder->where('1 = 0', null, false);
            }

            return;
        }

        if ($hasOrigen) {
            $builder->where("COALESCE({$r}.origen_prueba, 0) = 0", null, false);
        }

        if ($procesamiento === 'urgente') {
            if ($hasPrioridad) {
                $builder->where("COALESCE({$r}.prioridad, 0) = 1", null, false);
            } else {
                $builder->where('1 = 0', null, false);
            }

            return;
        }

        if ($hasPrioridad) {
            $builder->where("COALESCE({$r}.prioridad, 0) = 0", null, false);
        }
    }

    /**
     * @param \CodeIgniter\Database\BaseBuilder $builder
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function applySinRegistrosAnulados($builder, string $r)
    {
        if (!$this->registroTieneCampoAnulado()) {
            return $builder;
        }

        return $builder->where("COALESCE({$r}.anulado, 0) = 0", null, false);
    }

    /**
     * Excluye órdenes eliminadas (soft delete en registro).
     *
     * @param \CodeIgniter\Database\BaseBuilder $builder
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function applySinRegistrosEliminados($builder, string $r)
    {
        if (!$this->registroTieneCampoDeleted()) {
            return $builder;
        }

        return $builder->where("COALESCE({$r}.deleted, 0) = 0", null, false);
    }

    public function tieneCampoAnuladoEnRegistro(): bool
    {
        return $this->registroTieneCampoAnulado();
    }

    /**
     * Nombres de prueba para listados (catálogo activo deleted=0).
     *
     * @return array<int, string>
     */
    private function buildPruebasNombreMapListado(): array
    {
        $pruebasMap = [];
        $ptbl       = $this->db->prefixTable('prianacategoria');
        $atbl       = $this->db->prefixTable('anacategoria');
        $allPrias   = $this->db->table('prianacategoria')
            ->select("{$ptbl}.prianacategoria_id, {$ptbl}.name, {$atbl}.name as categoria")
            ->join('anacategoria', "{$atbl}.anacategoria_id = {$ptbl}.anacategoria_id", 'left')
            ->where("({$ptbl}.deleted = 0 OR {$ptbl}.deleted IS NULL)")
            ->get()
            ->getResultArray();
        foreach ($allPrias as $pr) {
            $pruebasMap[(int) $pr['prianacategoria_id']] = $pr['name'] . (($pr['categoria'] ?? '') !== '' ? ' (' . $pr['categoria'] . ')' : '');
        }

        return $pruebasMap;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function attachPruebasNombresListado(array $rows): array
    {
        $pruebasMap = $this->buildPruebasNombreMapListado();
        foreach ($rows as &$row) {
            $ids     = array_values(array_unique($this->parsePruebasCsvIds((string) ($row['pruebas'] ?? ''))));
            $nombres = [];
            foreach ($ids as $id) {
                $nombres[] = $pruebasMap[$id] ?? ('#' . $id);
            }
            $row['pruebas_nombres'] = $nombres !== [] ? implode(', ', $nombres) : '—';
        }
        unset($row);

        return $rows;
    }

    /**
     * Registros de análisis por rango de fechas (incluye anuladas para poder mostrar estado).
     * Campos extra: estado_anulado, estado_eliminado, regvalues_cnt.
     */
    public function getRegistrosByDateRange(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $anulSql = $this->registroTieneCampoAnulado() ? "COALESCE({$r}.anulado, 0)" : '0';
        $delSql  = $this->registroTieneCampoDeleted() ? "COALESCE({$r}.deleted, 0)" : '0';

        $select = "{$r}.registro_id, {$r}.ingreso,
            CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
            {$d}.name as doctor, {$pa}.total as total, {$pa}.monto_pagar,
            ({$anulSql}) AS estado_anulado,
            ({$delSql}) AS estado_eliminado,
            (SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) AS regvalues_cnt";

        $b = $this->db->table('registro')
            ->select($select, false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener costos de todas las pruebas con búsqueda
     */
    public function getCostosPruebas(string $busqueda = ''): array
    {
        $ana = $this->db->prefixTable('anacategoria');
        $pri = $this->db->prefixTable('prianacategoria');

        $builder = $this->db->table('anacategoria')
            ->select("{$ana}.anacategoria_id, {$ana}.name as categoria, {$pri}.name as prueba,
                      {$pri}.prianacategoria_id,
                      {$pri}.cost as precio, {$pri}.cost_deriv as precio_derivado,
                      {$pri}.order as orden_prueba, {$ana}.order as orden_categoria")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id AND ({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->where("{$pri}.prianacategoria_id IS NOT NULL");

        if (!empty($busqueda)) {
            $builder->groupStart()
                ->like("{$ana}.name", $busqueda)
                ->orLike("{$pri}.name", $busqueda)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy("{$ana}.order", 'ASC')
            ->orderBy("{$ana}.anacategoria_id", 'ASC')
            ->orderBy("{$pri}.order", 'ASC')
            ->orderBy("{$pri}.prianacategoria_id", 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['anacategoria_id'] = (int) ($row['anacategoria_id'] ?? 0);
            $row['categoria']       = trim((string) ($row['categoria'] ?? ''));
            $row['prueba']          = trim((string) ($row['prueba'] ?? ''));
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            $cmp = ((int) ($a['orden_categoria'] ?? 0)) <=> ((int) ($b['orden_categoria'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcasecmp((string) ($a['categoria'] ?? ''), (string) ($b['categoria'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = ((int) ($a['anacategoria_id'] ?? 0)) <=> ((int) ($b['anacategoria_id'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = ((int) ($a['orden_prueba'] ?? 0)) <=> ((int) ($b['orden_prueba'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }

            return ((int) ($a['prianacategoria_id'] ?? 0)) <=> ((int) ($b['prianacategoria_id'] ?? 0));
        });

        return $rows;
    }

    /**
     * Obtener valores de referencia de todas las pruebas con búsqueda
     */
    public function getValoresReferencia(string $busqueda = ''): array
    {
        $ana = $this->db->prefixTable('anacategoria');
        $pri = $this->db->prefixTable('prianacategoria');
        $sec = $this->db->prefixTable('secanacategoria');
        $prires = $this->db->prefixTable('priresultados');

        // Pruebas compuestas (sub-análisis en secanacategoria)
        $builder1 = $this->db->table('anacategoria')
            ->select("{$ana}.name as categoria, {$pri}.name as prueba,
                      {$pri}.prianacategoria_id, {$sec}.secanacategoria_id,
                      {$sec}.nombre as analisis, {$sec}.valor_min, {$sec}.valor_max,
                      {$sec}.umedida, {$sec}.paciente_id as poblacion, {$sec}.sexo,
                      {$sec}.opcion_id,
                      'compuesto' as tipo_prueba")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->join('secanacategoria', "{$pri}.prianacategoria_id = {$sec}.prianacategoria_id", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->where("({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)")
            ->where("({$sec}.deleted = 0 OR {$sec}.deleted IS NULL)")
            ->where("{$sec}.nombre IS NOT NULL")
            ->where("{$pri}.compleja", 1);

        // Pruebas no compuestas (valores en priresultados)
        $builder2 = $this->db->table('anacategoria')
            ->select("{$ana}.name as categoria, {$pri}.name as prueba,
                      {$pri}.prianacategoria_id, {$prires}.priresultados_id,
                      {$pri}.name as analisis, {$prires}.valor_min, {$prires}.valor_max,
                      {$prires}.umedida, {$prires}.id_poblacion as poblacion, {$prires}.sexo,
                      {$prires}.opcion_id,
                      'simple' as tipo_prueba")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->join('priresultados', "{$pri}.prianacategoria_id = {$prires}.prianacategoria_id", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->where("({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)")
            ->where("({$prires}.deleted = 0 OR {$prires}.deleted IS NULL)")
            ->where("{$pri}.name IS NOT NULL")
            ->where("({$pri}.compleja = 0 OR {$pri}.compleja IS NULL)");

        // Aplicar búsqueda a ambas consultas
        if (!empty($busqueda)) {
            $builder1->groupStart()
                ->like("{$ana}.name", $busqueda)
                ->orLike("{$pri}.name", $busqueda)
                ->orLike("{$sec}.nombre", $busqueda)
                ->groupEnd();

            $builder2->groupStart()
                ->like("{$ana}.name", $busqueda)
                ->orLike("{$pri}.name", $busqueda)
                ->groupEnd();
        }

        // Ordenamiento para ambas consultas
        $builder1->orderBy("{$ana}.name", 'ASC')
                  ->orderBy("{$pri}.name", 'ASC')
                  ->orderBy("{$sec}.nombre", 'ASC');

        $builder2->orderBy("{$ana}.name", 'ASC')
                  ->orderBy("{$pri}.name", 'ASC')
                  ->orderBy("{$prires}.id_poblacion", 'ASC');

        // Ejecutar ambas consultas y combinar resultados
        $data1 = $builder1->get()->getResultArray();
        $data2 = $builder2->get()->getResultArray();

        $rows = array_merge($data1, $data2);
        usort($rows, static function (array $a, array $b): int {
            $cmp = strcasecmp((string) ($a['categoria'] ?? ''), (string) ($b['categoria'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcasecmp((string) ($a['prueba'] ?? ''), (string) ($b['prueba'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcasecmp((string) ($a['analisis'] ?? ''), (string) ($b['analisis'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int) ($a['poblacion'] ?? 0)) <=> ((int) ($b['poblacion'] ?? 0));
        });

        return $rows;
    }

    /**
     * Resumen de ingresos por rango de fechas
     */
    public function getIngresosByDateRange(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("DATE({$r}.ingreso) as fecha, COUNT(*) as cantidad, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);
        $rows = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy("DATE({$r}.ingreso)")
            ->orderBy('fecha', 'ASC')
            ->get()
            ->getResultArray();

        return $rows;
    }

    /**
     * Ingresos agrupados por día solo para órdenes anuladas (referencia; no se suman a facturación).
     */
    public function getIngresosAnuladosPorDia(string $startDate, string $endDate): array
    {
        if (!$this->registroTieneCampoAnulado()) {
            return [];
        }
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("DATE({$r}.ingreso) as fecha, COUNT(*) as cantidad, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$r}.anulado", 1);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy("DATE({$r}.ingreso)")
            ->orderBy('fecha', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Totales del período solo órdenes anuladas (importes del pago en BD, solo informativo).
     */
    public function getTotalesAnuladosByDateRange(string $startDate, string $endDate): object
    {
        if (!$this->registroTieneCampoAnulado()) {
            return (object) [
                'total_registros'   => 0,
                'total_facturado'   => null,
                'total_cobrado'     => null,
            ];
        }
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("COUNT(*) as total_registros, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$r}.anulado", 1);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->get()
            ->getRow();
    }

    /**
     * Totales del período (solo órdenes facturables: excluye anuladas).
     */
    public function getTotalesByDateRange(string $startDate, string $endDate): object
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("COUNT(*) as total_registros, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->get()
            ->getRow();
    }

    /**
     * Registros por doctor en rango de fechas
     */
    public function getRegistrosByDoctor(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("{$d}.name as doctor, COUNT(*) as cantidad, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy("{$r}.doctor_id")
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * SQL: orden con monto cubierto (misma regla que RegisterModel::isPagoCompletoPorRegistroId).
     */
    private function sqlOrdenPagoSaldada(string $pa): string
    {
        return "(CAST({$pa}.saldo AS DECIMAL(12,2)) <= 0.02"
            . " OR (CAST({$pa}.total AS DECIMAL(12,2)) > 0"
            . " AND CAST({$pa}.monto_pagar AS DECIMAL(12,2)) + 0.02 >= CAST({$pa}.total AS DECIMAL(12,2))))";
    }

    /**
     * Saldo pendiente calculado (máximo entre saldo en BD y total − pagado).
     */
    private function sqlSaldoPendienteCalculado(string $pa): string
    {
        return "GREATEST(CAST({$pa}.saldo AS DECIMAL(12,2)), CAST({$pa}.total AS DECIMAL(12,2)) - CAST({$pa}.monto_pagar AS DECIMAL(12,2)))";
    }

    /**
     * Saldo a mostrar en reportes de cobros (0 si la orden ya está cubierta).
     */
    private function sqlSaldoPendienteEfectivo(string $pa): string
    {
        $saldada = $this->sqlOrdenPagoSaldada($pa);
        $calc    = $this->sqlSaldoPendienteCalculado($pa);

        return "CASE WHEN {$saldada} THEN 0 ELSE {$calc} END";
    }

    /**
     * Nombre de paciente para tablas de cobros (misma convención que lista de órdenes).
     */
    private function sqlPacienteNombreReporte(string $p): string
    {
        return "TRIM(CONCAT_WS(' ', NULLIF(TRIM({$p}.last_name_fa), ''), NULLIF(TRIM({$p}.last_name_mom), ''), NULLIF(TRIM({$p}.first_name), '')))";
    }

    /**
     * Detalle de cada cobro en el período (fecha_abono). Incluye contexto de la orden.
     *
     * @param 'pendiente'|'pagado'|null $saldoFiltro Filtra por saldo actual de la orden tras el cobro.
     *
     * @return list<array<string, mixed>>
     */
    public function getCobrosDetallePorFecha(string $startDate, string $endDate, ?string $saldoFiltro = null, ?string $tipopago = null): array
    {
        if ($tipopago === '4') {
            return $this->fetchOrdenesPendienteTipoDetalle($startDate, $endDate, $saldoFiltro);
        }

        if ($this->db->tableExists('pago_abono')) {
            $rows = $this->fetchCobrosAbonoPorFecha($startDate, $endDate, $saldoFiltro, $tipopago);
            $legacy = $this->fetchCobrosLegacySinAbono($startDate, $endDate, $saldoFiltro, $tipopago);
            if ($legacy !== []) {
                $rows = array_merge($rows, $legacy);
                usort($rows, static function (array $a, array $b): int {
                    return strcmp((string) ($b['fecha_cobro'] ?? ''), (string) ($a['fecha_cobro'] ?? ''));
                });
            }

            return $rows;
        }

        return $this->fetchCobrosLegacySinAbono($startDate, $endDate, $saldoFiltro, $tipopago);
    }

    /**
     * Cobros del período filtrados por procesamiento de la orden.
     *
     * @return list<array<string, mixed>>
     */
    public function getCobrosDetallePorProcesamiento(string $startDate, string $endDate, string $procesamiento): array
    {
        if (! in_array($procesamiento, ['rutina', 'urgente', 'derivacion'], true)) {
            return [];
        }

        if ($this->db->tableExists('pago_abono')) {
            $rows = $this->fetchCobrosAbonoPorFecha($startDate, $endDate, null, null, $procesamiento);
            $legacy = $this->fetchCobrosLegacySinAbono($startDate, $endDate, null, null, $procesamiento);
            $pending = $this->fetchOrdenesPendienteProcesamientoDetalle($startDate, $endDate, null, $procesamiento);
            $merged = array_merge($rows, $legacy, $pending);
            if ($merged !== []) {
                usort($merged, static function (array $a, array $b): int {
                    return strcmp((string) ($b['fecha_cobro'] ?? ''), (string) ($a['fecha_cobro'] ?? ''));
                });
            }

            return $this->attachPruebasNombresListado($merged);
        }

        $rows = $this->fetchCobrosLegacySinAbono($startDate, $endDate, null, null, $procesamiento);
        $pending = $this->fetchOrdenesPendienteProcesamientoDetalle($startDate, $endDate, null, $procesamiento);
        $merged = array_merge($rows, $pending);

        return $this->attachPruebasNombresListado($merged);
    }

    /**
     * @param 'pendiente'|'pagado'|null $saldoFiltro
     *
     * @return list<array<string, mixed>>
     */
    protected function fetchCobrosAbonoPorFecha(
        string $startDate,
        string $endDate,
        ?string $saldoFiltro = null,
        ?string $tipopago = null,
        ?string $procesamiento = null
    ): array {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $ab = $this->db->prefixTable('pago_abono');
        $ordenSaldada       = $this->sqlOrdenPagoSaldada($pa);
        $saldoPendiente     = $this->sqlSaldoPendienteEfectivo($pa);
        $pacienteSql        = $this->sqlPacienteNombreReporte($p);

        $b = $this->db->table('pago_abono')
            ->select("{$ab}.pago_abono_id, {$ab}.fecha_abono as fecha_cobro,
                CAST({$ab}.monto AS DECIMAL(12,2)) as monto_cobro,
                {$ab}.tipopago,
                {$r}.registro_id, {$r}.numero_orden, {$r}.ingreso, {$r}.pruebas,
                {$pacienteSql} AS paciente,
                COALESCE({$d}.name, '') as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                {$saldoPendiente} as saldo", false)
            ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
            ->join('people', "{$p}.person_id = {$r}.person_id", 'left')
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$ab}.tipopago !=", '4');
        $b = $this->applySinRegistrosAnulados($b, $r);
        $b = LabNaiveDateRange::apply($b, $ab, 'fecha_abono', $startDate, $endDate);

        if ($tipopago !== null && $tipopago !== '') {
            $b->where("{$ab}.tipopago", $tipopago);
        }

        if ($procesamiento !== null && $procesamiento !== '') {
            $this->applyFiltroProcesamientoRegistro($b, $r, $procesamiento);
        }

        if ($saldoFiltro === 'pendiente') {
            $b->where("NOT {$ordenSaldada}", null, false);
        } elseif ($saldoFiltro === 'pagado') {
            $b->where($ordenSaldada, null, false);
        }

        return $b->orderBy("{$ab}.fecha_abono", 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * @param 'pendiente'|'pagado'|null $saldoFiltro
     *
     * @return list<array<string, mixed>>
     */
    protected function fetchCobrosLegacySinAbono(
        string $startDate,
        string $endDate,
        ?string $saldoFiltro = null,
        ?string $tipopago = null,
        ?string $procesamiento = null
    ): array {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $ab = $this->db->prefixTable('pago_abono');
        $ordenSaldada   = $this->sqlOrdenPagoSaldada($pa);
        $saldoPendiente = $this->sqlSaldoPendienteEfectivo($pa);
        $pacienteSql    = $this->sqlPacienteNombreReporte($p);

        $b = $this->db->table('registro')
            ->select("NULL as pago_abono_id, {$r}.ingreso as fecha_cobro,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_cobro,
                {$pa}.tipopago,
                {$r}.registro_id, {$r}.numero_orden, {$r}.ingreso, {$r}.pruebas,
                {$pacienteSql} AS paciente,
                COALESCE({$d}.name, '') as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                {$saldoPendiente} as saldo", false)
            ->join('people', "{$p}.person_id = {$r}.person_id", 'left')
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");

        if ($this->db->tableExists('pago_abono')) {
            $b->join('pago_abono', "{$ab}.registro_id = {$r}.registro_id", 'left')
                ->where("{$ab}.registro_id IS NULL", null, false);
        }

        $b = $this->applySinRegistrosAnulados($b, $r);
        $b = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$pa}.tipopago !=", '4');

        if ($tipopago !== null && $tipopago !== '') {
            $b->where("{$pa}.tipopago", $tipopago);
        }

        if ($procesamiento !== null && $procesamiento !== '') {
            $this->applyFiltroProcesamientoRegistro($b, $r, $procesamiento);
        }

        if ($saldoFiltro === 'pendiente') {
            $b->where("NOT {$ordenSaldada}", null, false);
        } elseif ($saldoFiltro === 'pagado') {
            $b->where($ordenSaldada, null, false);
        }

        return $b->orderBy("{$r}.ingreso", 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Órdenes Pendiente (tipopago=4) con saldo en el período, filtradas por procesamiento.
     *
     * @param 'pendiente'|'pagado'|null $saldoFiltro
     *
     * @return list<array<string, mixed>>
     */
    protected function fetchOrdenesPendienteProcesamientoDetalle(
        string $startDate,
        string $endDate,
        ?string $saldoFiltro = null,
        ?string $procesamiento = null
    ): array {
        if ($saldoFiltro === 'pagado' || $procesamiento === null || $procesamiento === '') {
            return [];
        }

        $r              = $this->db->prefixTable('registro');
        $p              = $this->db->prefixTable('people');
        $d              = $this->db->prefixTable('doctors');
        $pa             = $this->db->prefixTable('pago');
        $saldoPendiente = $this->sqlSaldoPendienteEfectivo($pa);
        $pacienteSql    = $this->sqlPacienteNombreReporte($p);

        $b = $this->db->table('registro')
            ->select("NULL as pago_abono_id, {$r}.ingreso as fecha_cobro,
                0.00 as monto_cobro,
                {$pa}.tipopago,
                {$r}.registro_id, {$r}.numero_orden, {$r}.ingreso, {$r}.pruebas,
                {$pacienteSql} AS paciente,
                COALESCE({$d}.name, '') as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                {$saldoPendiente} as saldo", false)
            ->join('people', "{$p}.person_id = {$r}.person_id", 'left')
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$pa}.tipopago", '4')
            ->where("{$saldoPendiente} >", 0.02, false);
        $b = $this->applySinRegistrosAnulados($b, $r);
        $this->applyFiltroProcesamientoRegistro($b, $r, $procesamiento);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Órdenes registradas como Pendiente (tipopago=4) con saldo en el período (fecha de ingreso).
     *
     * @param 'pendiente'|'pagado'|null $saldoFiltro
     *
     * @return list<array<string, mixed>>
     */
    protected function fetchOrdenesPendienteTipoDetalle(string $startDate, string $endDate, ?string $saldoFiltro = null): array
    {
        if ($saldoFiltro === 'pagado') {
            return [];
        }

        $r              = $this->db->prefixTable('registro');
        $p              = $this->db->prefixTable('people');
        $d              = $this->db->prefixTable('doctors');
        $pa             = $this->db->prefixTable('pago');
        $saldoPendiente = $this->sqlSaldoPendienteEfectivo($pa);
        $pacienteSql    = $this->sqlPacienteNombreReporte($p);

        $b = $this->db->table('registro')
            ->select("NULL as pago_abono_id, {$r}.ingreso as fecha_cobro,
                0.00 as monto_cobro,
                {$pa}.tipopago,
                {$r}.registro_id, {$r}.numero_orden, {$r}.ingreso, {$r}.pruebas,
                {$pacienteSql} AS paciente,
                COALESCE({$d}.name, '') as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                {$saldoPendiente} as saldo", false)
            ->join('people', "{$p}.person_id = {$r}.person_id", 'left')
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$pa}.tipopago", '4')
            ->where("{$saldoPendiente} >", 0.02, false);
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Resumen agregado de órdenes con tipopago Pendiente (4) ingresadas en el período.
     *
     * @return array<string, mixed>|null
     */
    private function fetchResumenTipoPendienteRegistro(string $startDate, string $endDate): ?array
    {
        $r              = $this->db->prefixTable('registro');
        $pa             = $this->db->prefixTable('pago');
        $saldoEfectivo  = $this->sqlSaldoPendienteEfectivo($pa);

        $b = $this->db->table('registro')
            ->select("COUNT(*) as cantidad,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM({$saldoEfectivo}) as total_pendiente", false)
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$pa}.tipopago", '4')
            ->where("{$saldoEfectivo} >", 0.02, false);
        $b = $this->applySinRegistrosAnulados($b, $r);
        $row = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->get()
            ->getRow();

        if ($row === null || (int) ($row->cantidad ?? 0) === 0) {
            return null;
        }

        return [
            'tipopago'        => '4',
            'cantidad'        => (int) ($row->cantidad ?? 0),
            'total_facturado' => round((float) ($row->total_facturado ?? 0), 2),
            'total_cobrado'   => round((float) ($row->total_cobrado ?? 0), 2),
            'total_pendiente' => round((float) ($row->total_pendiente ?? 0), 2),
        ];
    }

    /**
     * Reporte de pagos: todos los cobros realizados en el rango (fecha de cancelación).
     */
    public function getReportePagos(string $startDate, string $endDate): array
    {
        return $this->getCobrosDetallePorFecha($startDate, $endDate);
    }

    /**
     * Reporte de pendientes: registros con saldo > 0
     */
    public function getPendientesPago(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $saldoPendiente = "GREATEST(CAST({$pa}.saldo AS DECIMAL(12,2)), CAST({$pa}.total AS DECIMAL(12,2)) - CAST({$pa}.monto_pagar AS DECIMAL(12,2)))";

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                {$saldoPendiente} as saldo", false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$saldoPendiente} >", 0.02, false)
            ->orderBy('saldo', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Totales para reporte de pagos (por fecha de cobro cuando existe pago_abono).
     */
    public function getTotalesPagos(string $startDate, string $endDate): object
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');
        $ab = $this->db->prefixTable('pago_abono');

        if ($this->db->tableExists('pago_abono')) {
            $saldoPendiente = "GREATEST(CAST({$pa}.saldo AS DECIMAL(12,2)), CAST({$pa}.total AS DECIMAL(12,2)) - CAST({$pa}.monto_pagar AS DECIMAL(12,2)))";

            $bCobros = $this->db->table('pago_abono')
                ->select("COUNT(*) as total_registros,
                    COUNT(DISTINCT {$ab}.registro_id) as cantidad_ordenes,
                    SUM(CAST({$ab}.monto AS DECIMAL(12,2))) as total_cobrado", false)
                ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
                ->where("{$ab}.tipopago !=", '4');
            $bCobros = $this->applySinRegistrosAnulados($bCobros, $r);
            $rowCobros = LabNaiveDateRange::apply($bCobros, $ab, 'fecha_abono', $startDate, $endDate)
                ->get()
                ->getRow();

            $idsBuilder = $this->db->table('pago_abono')
                ->select("DISTINCT {$ab}.registro_id", false)
                ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
                ->where("{$ab}.tipopago !=", '4');
            $idsBuilder = $this->applySinRegistrosAnulados($idsBuilder, $r);
            $idsBuilder = LabNaiveDateRange::apply($idsBuilder, $ab, 'fecha_abono', $startDate, $endDate);
            $idsRows = $idsBuilder->get()->getResultArray();
            $registroIds = array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['registro_id'] ?? 0), $idsRows)));

            $totalFacturado = 0.0;
            $totalPendiente = 0.0;
            if ($registroIds !== []) {
                $bFact = $this->db->table('pago')
                    ->select("SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado", false)
                    ->whereIn('registro_id', $registroIds);
                $totalFacturado = (float) ($bFact->get()->getRow()->total_facturado ?? 0);

                $bPend = $this->db->table('pago')
                    ->select("SUM({$saldoPendiente}) as total_pendiente", false)
                    ->whereIn('registro_id', $registroIds)
                    ->where("{$saldoPendiente} >", 0.02, false);
                $totalPendiente = (float) ($bPend->get()->getRow()->total_pendiente ?? 0);
            }

            $totalCobrado = (float) ($rowCobros->total_cobrado ?? 0);

            $bLegacy = $this->db->table('registro')
                ->select("COUNT(*) as total_registros,
                    SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                    SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                    SUM({$saldoPendiente}) as total_pendiente", false)
                ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
                ->join('pago_abono', "{$ab}.registro_id = {$r}.registro_id", 'left')
                ->where("{$ab}.registro_id IS NULL", null, false)
                ->where("{$pa}.tipopago !=", '4');
            $bLegacy = $this->applySinRegistrosAnulados($bLegacy, $r);
            $rowLegacy = RegistroIngresoDateRange::apply($bLegacy, $r, $startDate, $endDate)
                ->get()
                ->getRow();

            if ($rowLegacy && (int) ($rowLegacy->total_registros ?? 0) > 0) {
                $totalCobrado += (float) ($rowLegacy->total_cobrado ?? 0);
                $totalFacturado += (float) ($rowLegacy->total_facturado ?? 0);
                $totalPendiente += (float) ($rowLegacy->total_pendiente ?? 0);
            }

            return (object) [
                'total_registros' => (int) ($rowCobros->total_registros ?? 0) + (int) ($rowLegacy->total_registros ?? 0),
                'cantidad_ordenes' => (int) ($rowCobros->cantidad_ordenes ?? 0) + (int) ($rowLegacy->total_registros ?? 0),
                'total_facturado' => round($totalFacturado, 2),
                'total_cobrado' => round($totalCobrado, 2),
                'total_pendiente' => round($totalPendiente, 2),
            ];
        }

        $b = $this->db->table('registro')
            ->select("COUNT(*) as total_registros,
                COUNT(*) as cantidad_ordenes,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->get()
            ->getRow();
    }

    /**
     * Total cobrado en caja según fecha real del cobro (pago_abono.fecha_abono).
     * Registros legacy sin filas en pago_abono usan fecha de ingreso de la orden.
     */
    public function getTotalesCobrosCaja(string $startDate, string $endDate): object
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');
        $ab = $this->db->prefixTable('pago_abono');

        if (!$this->db->tableExists('pago_abono')) {
            $t = $this->getTotalesPagos($startDate, $endDate);

            return (object) ['total_cobrado' => (float) ($t->total_cobrado ?? 0)];
        }

        $bAbonos = $this->db->table('pago_abono')
            ->select("SUM(CAST({$ab}.monto AS DECIMAL(12,2))) as total_cobrado", false)
            ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
            ->where("{$ab}.tipopago !=", '4');
        $bAbonos = $this->applySinRegistrosAnulados($bAbonos, $r);
        $rowAbonos = LabNaiveDateRange::apply($bAbonos, $ab, 'fecha_abono', $startDate, $endDate)
            ->get()
            ->getRow();
        $total = (float) ($rowAbonos->total_cobrado ?? 0);

        $bLegacy = $this->db->table('registro')
            ->select("SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado", false)
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->join('pago_abono', "{$ab}.registro_id = {$r}.registro_id", 'left')
            ->where("{$ab}.registro_id IS NULL", null, false)
            ->where("{$pa}.tipopago !=", '4');
        $bLegacy = $this->applySinRegistrosAnulados($bLegacy, $r);
        $rowLegacy = RegistroIngresoDateRange::apply($bLegacy, $r, $startDate, $endDate)
            ->get()
            ->getRow();
        $total += (float) ($rowLegacy->total_cobrado ?? 0);

        return (object) ['total_cobrado' => round($total, 2)];
    }

    /**
     * Facturado y saldo pendiente por tipo de pago (órdenes únicas con cobro en el período).
     *
     * @param array<string, array<string, mixed>> $resumen
     */
    private function enrichResumenPagosPorTipoFacturadoPendiente(array &$resumen, string $startDate, string $endDate): void
    {
        if ($resumen === []) {
            return;
        }

        $r             = $this->db->prefixTable('registro');
        $pa            = $this->db->prefixTable('pago');
        $ab            = $this->db->prefixTable('pago_abono');
        $saldoEfectivo = $this->sqlSaldoPendienteEfectivo($pa);

        $b = $this->db->table('pago_abono')
            ->select("{$ab}.tipopago, {$ab}.registro_id,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                {$saldoEfectivo} as saldo", false)
            ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$ab}.tipopago !=", '4');
        $b = $this->applySinRegistrosAnulados($b, $r);
        $rows = LabNaiveDateRange::apply($b, $ab, 'fecha_abono', $startDate, $endDate)
            ->get()
            ->getResultArray();

        $vistos = [];
        foreach ($rows as $row) {
            $tipo = (string) ($row['tipopago'] ?? '');
            $rid  = (int) ($row['registro_id'] ?? 0);
            if ($tipo === '' || $rid === 0 || !isset($resumen[$tipo])) {
                continue;
            }
            if (isset($vistos[$tipo][$rid])) {
                continue;
            }
            $vistos[$tipo][$rid] = true;
            $resumen[$tipo]['total_facturado'] = (float) ($resumen[$tipo]['total_facturado'] ?? 0) + (float) ($row['total'] ?? 0);
            $resumen[$tipo]['total_pendiente'] = (float) ($resumen[$tipo]['total_pendiente'] ?? 0) + (float) ($row['saldo'] ?? 0);
        }

        foreach ($resumen as &$row) {
            $row['total_facturado'] = round((float) ($row['total_facturado'] ?? 0), 2);
            $row['total_pendiente'] = round((float) ($row['total_pendiente'] ?? 0), 2);
        }
        unset($row);
    }

    /**
     * Resumen de pagos por tipo (Efectivo/QR/Transferencia/Pendiente).
     * Con pago_abono: cobros por fecha_abono (dinero que entró en el período).
     */
    public function getResumenPagosPorTipo(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');
        $ab = $this->db->prefixTable('pago_abono');

        if ($this->db->tableExists('pago_abono')) {
            $resumen = [];
            $saldoEfectivo = $this->sqlSaldoPendienteEfectivo($pa);

            // Fuente principal: historial real de abonos por tipo y fecha de cobro.
            $bAbonos = $this->db->table('pago_abono')
                ->select("{$ab}.tipopago,
                    COUNT(DISTINCT {$ab}.registro_id) as cantidad,
                    0.00 as total_facturado,
                    SUM(CAST({$ab}.monto AS DECIMAL(12,2))) as total_cobrado,
                    0.00 as total_pendiente", false)
                ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
                ->where("{$ab}.tipopago !=", '4');
            $bAbonos = $this->applySinRegistrosAnulados($bAbonos, $r);
            $rowsAbonos = LabNaiveDateRange::apply($bAbonos, $ab, 'fecha_abono', $startDate, $endDate)
                ->groupBy("{$ab}.tipopago")
                ->orderBy("{$ab}.tipopago", 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rowsAbonos as $row) {
                $tipo = (string) ($row['tipopago'] ?? '');
                if ($tipo === '') {
                    continue;
                }
                $resumen[$tipo] = [
                    'tipopago' => $tipo,
                    'cantidad' => (int) ($row['cantidad'] ?? 0),
                    'total_facturado' => 0.0,
                    'total_cobrado' => (float) ($row['total_cobrado'] ?? 0),
                    'total_pendiente' => 0.0,
                ];
            }

            $this->enrichResumenPagosPorTipoFacturadoPendiente($resumen, $startDate, $endDate);

            // Compatibilidad: registros antiguos sin filas en pago_abono.
            $bLegacy = $this->db->table('registro')
                ->select("{$pa}.tipopago,
                    COUNT(*) as cantidad,
                    SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                    SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                    SUM({$saldoEfectivo}) as total_pendiente", false)
                ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
                ->join('pago_abono', "{$ab}.registro_id = {$r}.registro_id", 'left')
                ->where("{$ab}.registro_id IS NULL", null, false);
            $bLegacy = $this->applySinRegistrosAnulados($bLegacy, $r);
            $rowsLegacy = RegistroIngresoDateRange::apply($bLegacy, $r, $startDate, $endDate)
                ->groupBy("{$pa}.tipopago")
                ->orderBy("{$pa}.tipopago", 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rowsLegacy as $row) {
                $tipo = (string) ($row['tipopago'] ?? '');
                if ($tipo === '') {
                    continue;
                }
                if (!isset($resumen[$tipo])) {
                    $resumen[$tipo] = [
                        'tipopago' => $tipo,
                        'cantidad' => 0,
                        'total_facturado' => 0.0,
                        'total_cobrado' => 0.0,
                        'total_pendiente' => 0.0,
                    ];
                }
                $resumen[$tipo]['cantidad'] += (int) ($row['cantidad'] ?? 0);
                $resumen[$tipo]['total_facturado'] += (float) ($row['total_facturado'] ?? 0);
                $resumen[$tipo]['total_cobrado'] += (float) ($row['total_cobrado'] ?? 0);
                $resumen[$tipo]['total_pendiente'] += (float) ($row['total_pendiente'] ?? 0);
            }

            $tipoPend = $this->fetchResumenTipoPendienteRegistro($startDate, $endDate);
            if ($tipoPend !== null) {
                $resumen['4'] = $tipoPend;
            } else {
                unset($resumen['4']);
            }

            if ($resumen !== []) {
                ksort($resumen, SORT_NATURAL);
                return array_values($resumen);
            }
        }

        $b = $this->db->table('registro')
            ->select("{$pa}.tipopago,
                COUNT(*) as cantidad,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy("{$pa}.tipopago")
            ->orderBy("{$pa}.tipopago", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Facturado y saldo pendiente por procesamiento (órdenes únicas con cobro en el período).
     *
     * @param array<string, array<string, mixed>> $resumen
     */
    private function enrichResumenPagosPorProcesamientoFacturadoPendiente(array &$resumen, string $startDate, string $endDate): void
    {
        if ($resumen === []) {
            return;
        }

        $r             = $this->db->prefixTable('registro');
        $pa            = $this->db->prefixTable('pago');
        $ab            = $this->db->prefixTable('pago_abono');
        $procExpr      = $this->sqlProcesamientoRegistroExpr($r);
        $saldoEfectivo = $this->sqlSaldoPendienteEfectivo($pa);

        $b = $this->db->table('pago_abono')
            ->select("{$procExpr} as procesamiento, {$ab}.registro_id,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                {$saldoEfectivo} as saldo", false)
            ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$ab}.tipopago !=", '4');
        $b = $this->applySinRegistrosAnulados($b, $r);
        $rows = LabNaiveDateRange::apply($b, $ab, 'fecha_abono', $startDate, $endDate)
            ->get()
            ->getResultArray();

        $vistos = [];
        foreach ($rows as $row) {
            $proc = (string) ($row['procesamiento'] ?? '');
            $rid  = (int) ($row['registro_id'] ?? 0);
            if ($proc === '' || $rid === 0 || !isset($resumen[$proc])) {
                continue;
            }
            if (isset($vistos[$proc][$rid])) {
                continue;
            }
            $vistos[$proc][$rid] = true;
            $resumen[$proc]['total_facturado'] = (float) ($resumen[$proc]['total_facturado'] ?? 0) + (float) ($row['total'] ?? 0);
            $resumen[$proc]['total_pendiente'] = (float) ($resumen[$proc]['total_pendiente'] ?? 0) + (float) ($row['saldo'] ?? 0);
        }

        foreach ($resumen as &$row) {
            $row['total_facturado'] = round((float) ($row['total_facturado'] ?? 0), 2);
            $row['total_pendiente'] = round((float) ($row['total_pendiente'] ?? 0), 2);
        }
        unset($row);
    }

    /**
     * Órdenes con pago pendiente (tipopago=4) en el período, agrupadas por procesamiento.
     *
     * @param array<string, array<string, mixed>> $resumen
     */
    private function mergeResumenProcesamientoPendienteRegistro(array &$resumen, string $startDate, string $endDate): void
    {
        $r             = $this->db->prefixTable('registro');
        $pa            = $this->db->prefixTable('pago');
        $procExpr      = $this->sqlProcesamientoRegistroExpr($r);
        $saldoEfectivo = $this->sqlSaldoPendienteEfectivo($pa);

        $b = $this->db->table('registro')
            ->select("{$procExpr} as procesamiento,
                COUNT(*) as cantidad,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM({$saldoEfectivo}) as total_pendiente", false)
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("{$pa}.tipopago", '4')
            ->where("{$saldoEfectivo} >", 0.02, false);
        $b = $this->applySinRegistrosAnulados($b, $r);
        $rows = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy($procExpr, false)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $proc = (string) ($row['procesamiento'] ?? '');
            if ($proc === '' || !isset($resumen[$proc])) {
                continue;
            }
            $resumen[$proc]['cantidad'] += (int) ($row['cantidad'] ?? 0);
            $resumen[$proc]['total_facturado'] += (float) ($row['total_facturado'] ?? 0);
            $resumen[$proc]['total_cobrado'] += (float) ($row['total_cobrado'] ?? 0);
            $resumen[$proc]['total_pendiente'] += (float) ($row['total_pendiente'] ?? 0);
        }
    }

    /**
     * Resumen de cobros del período por procesamiento (Rutina / Urgente / Derivación).
     *
     * @return list<array<string, mixed>>
     */
    public function getResumenPagosPorProcesamiento(string $startDate, string $endDate): array
    {
        $r        = $this->db->prefixTable('registro');
        $pa       = $this->db->prefixTable('pago');
        $ab       = $this->db->prefixTable('pago_abono');
        $procExpr = $this->sqlProcesamientoRegistroExpr($r);
        $resumen  = $this->emptyResumenPagosPorProcesamiento();
        $saldoEfectivo = $this->sqlSaldoPendienteEfectivo($pa);

        if ($this->db->tableExists('pago_abono')) {
            $bAbonos = $this->db->table('pago_abono')
                ->select("{$procExpr} as procesamiento,
                    COUNT(DISTINCT {$ab}.registro_id) as cantidad,
                    0.00 as total_facturado,
                    SUM(CAST({$ab}.monto AS DECIMAL(12,2))) as total_cobrado,
                    0.00 as total_pendiente", false)
                ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
                ->where("{$ab}.tipopago !=", '4');
            $bAbonos = $this->applySinRegistrosAnulados($bAbonos, $r);
            $rowsAbonos = LabNaiveDateRange::apply($bAbonos, $ab, 'fecha_abono', $startDate, $endDate)
                ->groupBy($procExpr, false)
                ->get()
                ->getResultArray();

            foreach ($rowsAbonos as $row) {
                $proc = (string) ($row['procesamiento'] ?? '');
                if ($proc === '' || !isset($resumen[$proc])) {
                    continue;
                }
                $resumen[$proc]['cantidad'] = (int) ($row['cantidad'] ?? 0);
                $resumen[$proc]['total_cobrado'] = (float) ($row['total_cobrado'] ?? 0);
            }

            $this->enrichResumenPagosPorProcesamientoFacturadoPendiente($resumen, $startDate, $endDate);

            $bLegacy = $this->db->table('registro')
                ->select("{$procExpr} as procesamiento,
                    COUNT(*) as cantidad,
                    SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                    SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                    SUM({$saldoEfectivo}) as total_pendiente", false)
                ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
                ->join('pago_abono', "{$ab}.registro_id = {$r}.registro_id", 'left')
                ->where("{$ab}.registro_id IS NULL", null, false)
                ->where("{$pa}.tipopago !=", '4');
            $bLegacy = $this->applySinRegistrosAnulados($bLegacy, $r);
            $rowsLegacy = RegistroIngresoDateRange::apply($bLegacy, $r, $startDate, $endDate)
                ->groupBy($procExpr, false)
                ->get()
                ->getResultArray();

            foreach ($rowsLegacy as $row) {
                $proc = (string) ($row['procesamiento'] ?? '');
                if ($proc === '' || !isset($resumen[$proc])) {
                    continue;
                }
                $resumen[$proc]['cantidad'] += (int) ($row['cantidad'] ?? 0);
                $resumen[$proc]['total_facturado'] += (float) ($row['total_facturado'] ?? 0);
                $resumen[$proc]['total_cobrado'] += (float) ($row['total_cobrado'] ?? 0);
                $resumen[$proc]['total_pendiente'] += (float) ($row['total_pendiente'] ?? 0);
            }

            $this->mergeResumenProcesamientoPendienteRegistro($resumen, $startDate, $endDate);

            foreach ($resumen as &$row) {
                $row['total_facturado'] = round((float) ($row['total_facturado'] ?? 0), 2);
                $row['total_cobrado'] = round((float) ($row['total_cobrado'] ?? 0), 2);
                $row['total_pendiente'] = round((float) ($row['total_pendiente'] ?? 0), 2);
            }
            unset($row);

            return array_values($resumen);
        }

        $b = $this->db->table('registro')
            ->select("{$procExpr} as procesamiento,
                COUNT(*) as cantidad,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente", false)
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);
        $rows = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy($procExpr, false)
            ->orderBy($procExpr, 'ASC', false)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $proc = (string) ($row['procesamiento'] ?? '');
            if ($proc === '' || !isset($resumen[$proc])) {
                continue;
            }
            $resumen[$proc] = [
                'procesamiento'   => $proc,
                'cantidad'        => (int) ($row['cantidad'] ?? 0),
                'total_facturado' => round((float) ($row['total_facturado'] ?? 0), 2),
                'total_cobrado'   => round((float) ($row['total_cobrado'] ?? 0), 2),
                'total_pendiente' => round((float) ($row['total_pendiente'] ?? 0), 2),
            ];
        }

        return array_values($resumen);
    }

    /**
     * Cobros del período en órdenes ya saldadas (monto cubierto o saldo <= 0).
     */
    public function getPagosPagadosDetalle(string $startDate, string $endDate): array
    {
        return $this->getCobrosDetallePorFecha($startDate, $endDate, 'pagado');
    }

    /**
     * Cobros del período en órdenes que aún tienen saldo pendiente.
     */
    public function getCobrosParcialesEnPeriodo(string $startDate, string $endDate): array
    {
        return $this->getCobrosDetallePorFecha($startDate, $endDate, 'pendiente');
    }

    /**
     * Resumen diario de cobros (por fecha_abono).
     */
    public function getResumenPagosPorDia(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');
        $ab = $this->db->prefixTable('pago_abono');

        if ($this->db->tableExists('pago_abono')) {
            $resumen = [];

            $b = $this->db->table('pago_abono')
                ->select("DATE({$ab}.fecha_abono) as fecha,
                    COUNT(*) as cantidad,
                    0.00 as total_facturado,
                    SUM(CAST({$ab}.monto AS DECIMAL(12,2))) as total_cobrado,
                    0.00 as total_pendiente", false)
                ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
                ->where("{$ab}.tipopago !=", '4');
            $b = $this->applySinRegistrosAnulados($b, $r);
            $rows = LabNaiveDateRange::apply($b, $ab, 'fecha_abono', $startDate, $endDate)
                ->groupBy("DATE({$ab}.fecha_abono)")
                ->orderBy('fecha', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $fecha = (string) ($row['fecha'] ?? '');
                if ($fecha === '') {
                    continue;
                }
                $resumen[$fecha] = $row;
            }

            $bLegacy = $this->db->table('registro')
                ->select("DATE({$r}.ingreso) as fecha,
                    COUNT(*) as cantidad,
                    SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                    SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                    SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente")
                ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
                ->join('pago_abono', "{$ab}.registro_id = {$r}.registro_id", 'left')
                ->where("{$ab}.registro_id IS NULL", null, false)
                ->where("{$pa}.tipopago !=", '4');
            $bLegacy = $this->applySinRegistrosAnulados($bLegacy, $r);
            $rowsLegacy = RegistroIngresoDateRange::apply($bLegacy, $r, $startDate, $endDate)
                ->groupBy("DATE({$r}.ingreso)")
                ->orderBy('fecha', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rowsLegacy as $row) {
                $fecha = (string) ($row['fecha'] ?? '');
                if ($fecha === '') {
                    continue;
                }
                if (!isset($resumen[$fecha])) {
                    $resumen[$fecha] = $row;
                    continue;
                }
                $resumen[$fecha]['cantidad'] += (int) ($row['cantidad'] ?? 0);
                $resumen[$fecha]['total_facturado'] = (float) ($resumen[$fecha]['total_facturado'] ?? 0) + (float) ($row['total_facturado'] ?? 0);
                $resumen[$fecha]['total_cobrado'] = (float) ($resumen[$fecha]['total_cobrado'] ?? 0) + (float) ($row['total_cobrado'] ?? 0);
                $resumen[$fecha]['total_pendiente'] = (float) ($resumen[$fecha]['total_pendiente'] ?? 0) + (float) ($row['total_pendiente'] ?? 0);
            }

            if ($resumen !== []) {
                ksort($resumen, SORT_NATURAL);
                return array_values($resumen);
            }
        }

        $b = $this->db->table('registro')
            ->select("DATE({$r}.ingreso) as fecha,
                COUNT(*) as cantidad,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy("DATE({$r}.ingreso)")
            ->orderBy('fecha', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Resumen por doctor según cobros del período (fecha_abono).
     */
    public function getResumenPagosPorDoctor(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $ab = $this->db->prefixTable('pago_abono');

        if ($this->db->tableExists('pago_abono')) {
            $resumen = [];

            $b = $this->db->table('pago_abono')
                ->select("{$d}.doctor_id,
                    {$d}.name as doctor,
                    COUNT(*) as cantidad,
                    0.00 as total_facturado,
                    SUM(CAST({$ab}.monto AS DECIMAL(12,2))) as total_cobrado,
                    0.00 as total_pendiente", false)
                ->join('registro', "{$r}.registro_id = {$ab}.registro_id")
                ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
                ->where("{$ab}.tipopago !=", '4');
            $b = $this->applySinRegistrosAnulados($b, $r);
            $rows = LabNaiveDateRange::apply($b, $ab, 'fecha_abono', $startDate, $endDate)
                ->groupBy("{$d}.doctor_id, {$d}.name")
                ->orderBy('total_cobrado', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $doctorId = (int) ($row['doctor_id'] ?? 0);
                $resumen[$doctorId] = $row;
            }

            $bLegacy = $this->db->table('registro')
                ->select("{$d}.doctor_id,
                    {$d}.name as doctor,
                    COUNT(*) as cantidad,
                    SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                    SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                    SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente")
                ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
                ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
                ->join('pago_abono', "{$ab}.registro_id = {$r}.registro_id", 'left')
                ->where("{$ab}.registro_id IS NULL", null, false);
            $bLegacy = $this->applySinRegistrosAnulados($bLegacy, $r);
            $rowsLegacy = RegistroIngresoDateRange::apply($bLegacy, $r, $startDate, $endDate)
                ->groupBy("{$d}.doctor_id, {$d}.name")
                ->orderBy('total_facturado', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rowsLegacy as $row) {
                $doctorId = (int) ($row['doctor_id'] ?? 0);
                if (!isset($resumen[$doctorId])) {
                    $resumen[$doctorId] = $row;
                    continue;
                }
                $resumen[$doctorId]['cantidad'] += (int) ($row['cantidad'] ?? 0);
                $resumen[$doctorId]['total_facturado'] = (float) ($resumen[$doctorId]['total_facturado'] ?? 0) + (float) ($row['total_facturado'] ?? 0);
                $resumen[$doctorId]['total_cobrado'] = (float) ($resumen[$doctorId]['total_cobrado'] ?? 0) + (float) ($row['total_cobrado'] ?? 0);
                $resumen[$doctorId]['total_pendiente'] = (float) ($resumen[$doctorId]['total_pendiente'] ?? 0) + (float) ($row['total_pendiente'] ?? 0);
            }

            if ($resumen !== []) {
                usort($resumen, static fn (array $a, array $b): int => ((float) ($b['total_cobrado'] ?? 0)) <=> ((float) ($a['total_cobrado'] ?? 0)));
                return array_values($resumen);
            }
        }

        $b = $this->db->table('registro')
            ->select("{$d}.doctor_id,
                {$d}.name as doctor,
                COUNT(*) as cantidad,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->groupBy("{$d}.doctor_id, {$d}.name")
            ->orderBy('total_facturado', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Movimientos de caja por rango y tipo (egreso|ingreso).
     *
     * @return list<array<string, mixed>>
     */
    private function getMovimientosCajaByDateRange(string $startDate, string $endDate, string $tipoMovimiento): array
    {
        $e = $this->db->prefixTable('egresos');

        $selectTipo = $this->egresosTieneTipoMovimiento()
            ? "{$e}.tipo_movimiento"
            : ($tipoMovimiento === 'ingreso' ? "'ingreso'" : "'egreso'");

        $b = $this->db->table('egresos')
            ->select("{$e}.egreso_id, {$e}.fecha, CAST({$e}.monto AS DECIMAL(12,2)) as monto, {$e}.tipopago, {$selectTipo} as tipo_movimiento, {$e}.desglose", false)
            ->where("COALESCE({$e}.deleted, 0) = 0", null, false);

        if ($this->egresosTieneTipoMovimiento()) {
            $b->where("{$e}.tipo_movimiento", $tipoMovimiento);
        } elseif ($tipoMovimiento === 'ingreso') {
            return [];
        }

        if ($startDate !== '' && $endDate !== '') {
            LabNaiveDateRange::apply($b, $e, 'fecha', $startDate, $endDate);
        } elseif ($startDate !== '' || $endDate !== '') {
            LabNaiveDateRange::applyPartial($b, $e, 'fecha', $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null);
        }

        return $b->orderBy("{$e}.fecha", 'DESC')
            ->orderBy("{$e}.egreso_id", 'DESC')
            ->get()
            ->getResultArray();
    }

    private function getTotalesMovimientosCajaByDateRange(string $startDate, string $endDate, string $tipoMovimiento): object
    {
        $e = $this->db->prefixTable('egresos');
        $cantidadAlias = $tipoMovimiento === 'ingreso' ? 'cantidad_ingresos' : 'cantidad_egresos';
        $totalAlias = $tipoMovimiento === 'ingreso' ? 'total_ingresos' : 'total_egresos';

        $b = $this->db->table('egresos')
            ->select("COUNT(*) as {$cantidadAlias}, SUM(CAST({$e}.monto AS DECIMAL(12,2))) as {$totalAlias}", false)
            ->where("COALESCE({$e}.deleted, 0) = 0", null, false);

        if ($this->egresosTieneTipoMovimiento()) {
            $b->where("{$e}.tipo_movimiento", $tipoMovimiento);
        } elseif ($tipoMovimiento === 'ingreso') {
            return (object) [$cantidadAlias => 0, $totalAlias => 0.0];
        }

        if ($startDate !== '' && $endDate !== '') {
            LabNaiveDateRange::apply($b, $e, 'fecha', $startDate, $endDate);
        } elseif ($startDate !== '' || $endDate !== '') {
            LabNaiveDateRange::applyPartial($b, $e, 'fecha', $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null);
        }

        $row = $b->get()->getRow();
        if ($row !== null) {
            return $row;
        }

        return (object) [$cantidadAlias => 0, $totalAlias => 0.0];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function getResumenMovimientosPorTipoPago(string $startDate, string $endDate, string $tipoMovimiento): array
    {
        $e = $this->db->prefixTable('egresos');
        $totalAlias = $tipoMovimiento === 'ingreso' ? 'total_ingresos' : 'total_egresos';

        $b = $this->db->table('egresos')
            ->select("{$e}.tipopago, COUNT(*) as cantidad, SUM(CAST({$e}.monto AS DECIMAL(12,2))) as {$totalAlias}", false)
            ->where("COALESCE({$e}.deleted, 0) = 0", null, false);

        if ($this->egresosTieneTipoMovimiento()) {
            $b->where("{$e}.tipo_movimiento", $tipoMovimiento);
        } elseif ($tipoMovimiento === 'ingreso') {
            return [];
        }

        if ($startDate !== '' && $endDate !== '') {
            LabNaiveDateRange::apply($b, $e, 'fecha', $startDate, $endDate);
        } elseif ($startDate !== '' || $endDate !== '') {
            LabNaiveDateRange::applyPartial($b, $e, 'fecha', $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null);
        }

        return $b->groupBy("{$e}.tipopago")
            ->orderBy("{$e}.tipopago", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Egresos registrados por rango de fechas.
     */
    public function getEgresosByDateRange(string $startDate, string $endDate): array
    {
        return $this->getMovimientosCajaByDateRange($startDate, $endDate, 'egreso');
    }

    /**
     * Totales de egresos por período.
     */
    public function getTotalesEgresosByDateRange(string $startDate, string $endDate): object
    {
        return $this->getTotalesMovimientosCajaByDateRange($startDate, $endDate, 'egreso');
    }

    /**
     * Resumen de egresos por tipo de pago.
     */
    public function getResumenEgresosPorTipo(string $startDate, string $endDate): array
    {
        return $this->getResumenMovimientosPorTipoPago($startDate, $endDate, 'egreso');
    }

    /**
     * Ingresos de caja manuales por rango (módulo de movimientos).
     */
    public function getIngresosCajaByDateRange(string $startDate, string $endDate): array
    {
        return $this->getMovimientosCajaByDateRange($startDate, $endDate, 'ingreso');
    }

    public function getTotalesIngresosCajaByDateRange(string $startDate, string $endDate): object
    {
        return $this->getTotalesMovimientosCajaByDateRange($startDate, $endDate, 'ingreso');
    }

    public function getResumenIngresosCajaPorTipo(string $startDate, string $endDate): array
    {
        return $this->getResumenMovimientosPorTipoPago($startDate, $endDate, 'ingreso');
    }

    /**
     * Reporte de pruebas realizadas por fecha.
     * Solo órdenes completas (al menos una fila en regvalues), no anuladas ni eliminadas,
     * con paciente y doctor válidos, pruebas no vacías y pago — mismo universo que estadísticas de laboratorio.
     */
    public function getPruebasPorFecha(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $rv = $this->db->prefixTable('regvalues');

        $b = $this->buildRegistroPruebasListadoBuilder();
        $b = $this->applySinRegistrosAnulados($b, $r);
        $b = $this->applySinRegistrosEliminados($b, $r);
        $b->where("(SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) > 0", null, false);
        $rows = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL")
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();

        return $this->attachPruebasNombresListado($rows);
    }

    /**
     * Resumen de una orden para el modal de trazabilidad del reporte detallado.
     *
     * @return array<string, mixed>|null
     */
    public function getRegistroResumenDetallePruebas(int $registroId): ?array
    {
        if ($registroId < 1) {
            return null;
        }

        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $pu = $this->db->prefixTable('people') . ' AS pu';
        $d  = $this->db->prefixTable('doctors');
        $rv = $this->db->prefixTable('regvalues');

        $row = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.numero_orden, {$r}.ingreso, {$r}.pruebas, {$r}.id_session,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CONCAT(pu.first_name, ' ', pu.last_name_fa) AS usuario_recepcion,
                (SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) AS regvalues_cnt", false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join($pu, "pu.person_id = {$r}.id_session", 'left', false)
            ->where("{$r}.registro_id", $registroId)
            ->get()
            ->getRowArray();

        if ($row === null) {
            return null;
        }

        $rows = $this->attachPruebasNombresListado([$row]);
        $rows = $this->attachCodigoRecepcionListado($rows);
        $rows = $this->attachTrazabilidadPruebasResumen($rows);

        return $rows[0] ?? null;
    }

    /**
     * Cuenta órdenes del reporte detallado de pruebas en el rango de fechas.
     */
    public function countPruebasDetalladoPorFecha(string $startDate, string $endDate): int
    {
        $r = $this->db->prefixTable('registro');

        return (int) $this->buildPruebasDetalladoPorFechaBuilder($startDate, $endDate)
            ->countAllResults();
    }

    /**
     * Reporte detallado de pruebas: código de recepción, paciente, usuario de recepción y resumen de trazabilidad.
     *
     * @return list<array<string, mixed>>
     */
    public function getPruebasDetalladoPorFecha(string $startDate, string $endDate, ?int $limit = null, int $offset = 0): array
    {
        $r = $this->db->prefixTable('registro');

        $builder = $this->buildPruebasDetalladoPorFechaBuilder($startDate, $endDate)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->orderBy("{$r}.registro_id", 'DESC');

        if ($limit !== null && $limit > 0) {
            $builder->limit($limit, max(0, $offset));
        }

        $rows = $builder->get()->getResultArray();
        $rows = $this->attachPruebasNombresListado($rows);
        $rows = $this->attachCodigoRecepcionListado($rows);

        return $this->attachTrazabilidadPruebasResumen($rows);
    }

    /**
     * @return \CodeIgniter\Database\BaseBuilder
     */
    protected function buildPruebasDetalladoPorFechaBuilder(string $startDate, string $endDate)
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $pu = $this->db->prefixTable('people') . ' AS pu';
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.numero_orden, {$r}.ingreso, {$r}.pruebas, {$r}.id_session,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CONCAT(pu.first_name, ' ', pu.last_name_fa) AS usuario_recepcion,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                (SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) AS regvalues_cnt", false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id", 'left')
            ->join($pu, "pu.person_id = {$r}.id_session", 'left', false);
        $b = $this->applySinRegistrosAnulados($b, $r);
        $b = $this->applySinRegistrosEliminados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL");
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    protected function attachCodigoRecepcionListado(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        $folioService = new \App\Services\RegistroFolioService();
        foreach ($rows as &$row) {
            $codigo = $folioService->codigoRecepcionDisplay(
                (int) ($row['registro_id'] ?? 0),
                isset($row['numero_orden']) ? (string) $row['numero_orden'] : null,
                isset($row['ingreso']) ? (string) $row['ingreso'] : null,
                true
            );
            $row['codigo_recepcion'] = $codigo;
            if ($codigo !== '') {
                $row['numero_orden'] = $codigo;
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    protected function attachTrazabilidadPruebasResumen(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['registro_id'] ?? 0);
            if ($id > 0) {
                $ids[] = (string) $id;
            }
        }
        if ($ids === []) {
            return $rows;
        }

        $auditRows = $this->db->table('auditoria')
            ->select('auditoria.registro_id, auditoria.accion, auditoria.datos, auditoria.fecha, people.first_name, people.last_name_fa')
            ->join('people', 'people.person_id = auditoria.person_id', 'left')
            ->where('auditoria.modulo', 'registers')
            ->whereIn('auditoria.accion', ['crear', 'guardar_resultados'])
            ->whereIn('auditoria.registro_id', $ids)
            ->orderBy('auditoria.fecha', 'ASC')
            ->get()
            ->getResultArray();

        $resumen = [];
        foreach ($auditRows as $audit) {
            $rid = (string) ($audit['registro_id'] ?? '');
            if ($rid === '') {
                continue;
            }
            if (! isset($resumen[$rid])) {
                $resumen[$rid] = [
                    'usuario_recepcion_audit' => '',
                    'usuario_primera_carga'   => '',
                    'usuario_ultima_edicion'  => '',
                    'veces_guardado'          => 0,
                    'veces_editado'           => 0,
                ];
            }
            $usuario = trim((string) (($audit['first_name'] ?? '') . ' ' . ($audit['last_name_fa'] ?? '')));
            $accion  = (string) ($audit['accion'] ?? '');
            if ($accion === 'crear' && $usuario !== '' && $resumen[$rid]['usuario_recepcion_audit'] === '') {
                $resumen[$rid]['usuario_recepcion_audit'] = $usuario;
            }
            if ($accion !== 'guardar_resultados') {
                continue;
            }
            $resumen[$rid]['veces_guardado']++;
            $datosArr = null;
            $datosRaw = trim((string) ($audit['datos'] ?? ''));
            if ($datosRaw !== '') {
                $decoded = json_decode($datosRaw, true);
                if (is_array($decoded)) {
                    $datosArr = $decoded;
                }
            }
            $esPrimera = is_array($datosArr) && ! empty($datosArr['es_primera_carga']);
            $tieneCambios = is_array($datosArr) && (
                ! empty($datosArr['cambios'])
                || ! empty($datosArr['agregados'])
                || ! empty($datosArr['eliminados'])
            );
            if ($esPrimera && $usuario !== '' && $resumen[$rid]['usuario_primera_carga'] === '') {
                $resumen[$rid]['usuario_primera_carga'] = $usuario;
            } elseif ($usuario !== '' && ($tieneCambios || $resumen[$rid]['veces_guardado'] > 1)) {
                $resumen[$rid]['usuario_ultima_edicion'] = $usuario;
                if (! $esPrimera) {
                    $resumen[$rid]['veces_editado']++;
                }
            }
        }

        foreach ($rows as &$row) {
            $rid = (string) ($row['registro_id'] ?? '');
            $extra = $resumen[$rid] ?? [];
            if (trim((string) ($row['usuario_recepcion'] ?? '')) === '' && ! empty($extra['usuario_recepcion_audit'])) {
                $row['usuario_recepcion'] = $extra['usuario_recepcion_audit'];
            }
            $row['usuario_primera_carga']  = $extra['usuario_primera_carga'] ?? '';
            $row['usuario_ultima_edicion'] = $extra['usuario_ultima_edicion'] ?? '';
            $row['veces_guardado']         = (int) ($extra['veces_guardado'] ?? 0);
            $row['veces_editado']          = (int) ($extra['veces_editado'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    /**
     * Órdenes con pruebas solicitadas pero sin ninguna fila en regvalues (incompletas), no anuladas ni eliminadas.
     *
     * @return list<array<string, mixed>>
     */
    public function getPruebasIncompletasPorFecha(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $rv = $this->db->prefixTable('regvalues');

        $b = $this->buildRegistroPruebasListadoBuilder();
        $b = $this->applySinRegistrosAnulados($b, $r);
        $b = $this->applySinRegistrosEliminados($b, $r);
        $b->where("(SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) = 0", null, false);
        $rows = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL")
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();

        return $this->attachPruebasNombresListado($rows);
    }

    /**
     * Órdenes anuladas con pruebas y pago en el rango (histórico de importes en pago; no facturables).
     *
     * @return list<array<string, mixed>>
     */
    public function getPruebasAnuladasPorFecha(string $startDate, string $endDate): array
    {
        if (!$this->registroTieneCampoAnulado()) {
            return [];
        }
        $r = $this->db->prefixTable('registro');

        $motivoCol = $this->registroTieneCampoMotivoAnulacion()
            ? "{$r}.motivo_anulacion"
            : "'' AS motivo_anulacion";

        $b = $this->buildRegistroPruebasListadoBuilder($motivoCol);
        $b->where("COALESCE({$r}.anulado, 0) <> 0", null, false);
        $b = $this->applySinRegistrosEliminados($b, $r);
        $rows = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL")
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();

        return $this->attachPruebasNombresListado($rows);
    }

    /**
     * Mapa prianacategoria_id => nombre de prueba y categoría (para conteos en reportes).
     *
     * @return array<int, array{prueba: string, categoria: string}>
     */
    public function getPrianacategoriaLabelsMap(): array
    {
        $ptbl = $this->db->prefixTable('prianacategoria');
        $atbl = $this->db->prefixTable('anacategoria');

        $rows = $this->db->table('prianacategoria')
            ->select("{$ptbl}.prianacategoria_id, {$ptbl}.name AS prueba, {$atbl}.name AS categoria")
            ->join('anacategoria', "{$atbl}.anacategoria_id = {$ptbl}.anacategoria_id", 'left')
            ->where("({$ptbl}.deleted = 0 OR {$ptbl}.deleted IS NULL)")
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['prianacategoria_id']] = [
                'prueba'    => (string) ($r['prueba'] ?? ''),
                'categoria' => (string) ($r['categoria'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Mapa prianacategoria_id => anacategoria_id (grupo padre) para reportes por categoría clínica.
     *
     * @return array<int, int>
     */
    public function getPrianacategoriaAnacategoriaMap(): array
    {
        $ptbl = $this->db->prefixTable('prianacategoria');

        $rows = $this->db->table('prianacategoria')
            ->select("{$ptbl}.prianacategoria_id, {$ptbl}.anacategoria_id")
            ->where("({$ptbl}.deleted = 0 OR {$ptbl}.deleted IS NULL)")
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $pid = (int) ($r['prianacategoria_id'] ?? 0);
            if ($pid < 1) {
                continue;
            }
            $out[$pid] = (int) ($r['anacategoria_id'] ?? 0);
        }

        return $out;
    }

    /**
     * Grupos de análisis (padre) activos para filtros de reporte.
     *
     * @return list<array{anacategoria_id: int, name: string, orden: int}>
     */
    public function getAnacategoriasParaReporte(): array
    {
        $atbl = $this->db->prefixTable('anacategoria');

        $rows = $this->db->table('anacategoria')
            ->select("{$atbl}.anacategoria_id, {$atbl}.name, {$atbl}.order as orden")
            ->where("({$atbl}.deleted = 0 OR {$atbl}.deleted IS NULL)")
            ->orderBy("{$atbl}.order", 'ASC')
            ->orderBy("{$atbl}.name", 'ASC')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'anacategoria_id' => (int) ($r['anacategoria_id'] ?? 0),
                'name'            => (string) ($r['name'] ?? ''),
                'orden'           => (int) ($r['orden'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Por registro, prianacategoria_id con al menos un resultado cargado (regvalues no vacío y distinto de "-").
     * Usa la misma convención de name que el formulario (id|nombre, c_*, noc_*).
     *
     * @param list<int|string> $registroIds
     * @return array<int, array<int, true>>
     */
    public function getMapRegistroPriasConResultadoCargado(array $registroIds): array
    {
        $registroIds = array_values(array_unique(array_filter(array_map('intval', $registroIds), static fn (int $id): bool => $id > 0)));
        if ($registroIds === []) {
            return [];
        }

        $registerModel = model(RegisterModel::class);
        $nocCache      = [];
        $cCache        = [];
        $map           = [];

        foreach (array_chunk($registroIds, 800) as $chunk) {
            $rows = $this->db->table('regvalues')
                ->select('registro_id, name, regvalues')
                ->whereIn('registro_id', $chunk)
                ->get()
                ->getResultArray();
            foreach ($rows as $row) {
                $rid = (int) ($row['registro_id'] ?? 0);
                if ($rid < 1) {
                    continue;
                }
                $val = trim((string) ($row['regvalues'] ?? ''));
                if ($val === '' || $val === '-') {
                    continue;
                }
                $priaId = $registerModel->resolvePrianacategoriaIdFromRegvalueName((string) ($row['name'] ?? ''), $nocCache, $cCache);
                if ($priaId > 0) {
                    $map[$rid][$priaId] = true;
                }
            }
        }

        return $map;
    }

    /**
     * Filas para estadísticas de laboratorio: mismo criterio que getPruebasPorFecha (órdenes completas, facturables).
     *
     * @return list<array{registro_id:string|int,person_id:string|int,ingreso:string,pruebas:string,birthday:?string,gender:?string|int}>
     */
    public function getRegistrosEstadisticasLaboratorio(string $startDate, string $endDate): array
    {
        return $this->queryRegistrosPruebasEnPeriodo($startDate, $endDate, true);
    }

    /**
     * Órdenes con pruebas solicitadas en el período (completas o incompletas), no anuladas ni eliminadas.
     *
     * @return list<array{registro_id:string|int,person_id:string|int,ingreso:string,pruebas:string,birthday:?string,gender:?string|int}>
     */
    public function getRegistrosSolicitudesEnPeriodo(string $startDate, string $endDate): array
    {
        return $this->queryRegistrosPruebasEnPeriodo($startDate, $endDate, false);
    }

    /**
     * @return list<array{registro_id:string|int,person_id:string|int,ingreso:string,pruebas:string,birthday:?string,gender:?string|int}>
     */
    private function queryRegistrosPruebasEnPeriodo(string $startDate, string $endDate, bool $soloConResultados): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.person_id, {$r}.ingreso, {$r}.pruebas,
                {$p}.birthday, {$p}.gender")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);
        $b = $this->applySinRegistrosEliminados($b, $r);
        if ($soloConResultados) {
            $b->where("(SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) > 0", null, false);
        }

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL")
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Todos los registros guardados (con paginación)
     */
    public function getAllRegistros(int $perPage, int $offset): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor, {$pa}.total as total, {$pa}.monto_pagar")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return $b->orderBy("{$r}.ingreso", 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Total de registros para el reporte "Todos los registros"
     */
    public function countAllRegistros(): int
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select('COUNT(*) as total')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);
        $row = $b->get()->getRow();

        return (int) ($row->total ?? 0);
    }

    /**
     * Expresión SQL: tipo de procesamiento comercial (interno|derivado) según origen_prueba.
     */
    private function sqlTipoProcesamientoIngresoExpr(string $rAlias): string
    {
        if ($this->registroTieneCampoOrigenPrueba()) {
            return "CASE WHEN COALESCE({$rAlias}.origen_prueba, 0) = 1 THEN 'derivado' ELSE 'interno' END";
        }

        return "'interno'";
    }

    /**
     * @return 'pagado'|'pendiente'|'parcial'
     */
    private function resolveEstadoPagoIngreso(float $total, float $montoPagar, float $saldo): string
    {
        if ($total <= 0.02) {
            return 'pagado';
        }
        $saldoEf = max($saldo, $total - $montoPagar);
        if ($montoPagar <= 0.02 && $saldoEf > 0.02) {
            return 'pendiente';
        }
        if ($montoPagar + 0.02 >= $total) {
            return 'pagado';
        }

        return 'parcial';
    }

    /**
     * @return array<int, array{name: string, cost: float, cost_deriv: float}>
     */
    private function buildPruebasCostosMapIngresos(): array
    {
        $pri = $this->db->prefixTable('prianacategoria');
        $rows = $this->db->table('prianacategoria')
            ->select("{$pri}.prianacategoria_id, {$pri}.name, {$pri}.cost, {$pri}.cost_deriv")
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row['prianacategoria_id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $map[$id] = [
                'name'       => trim((string) ($row['name'] ?? '')) ?: ('Prueba #' . $id),
                'cost'       => (float) ($row['cost'] ?? 0),
                'cost_deriv' => (float) ($row['cost_deriv'] ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    private function parsePruebasCsvIds(?string $csv): array
    {
        $csv = trim((string) $csv);
        if ($csv === '') {
            return [];
        }
        $ids = [];
        foreach (explode(',', $csv) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $id = preg_match('/contador_(\d+)/', $part, $m) ? (int) $m[1] : (int) $part;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Builder base para listados de órdenes con pruebas (paciente/doctor; pago opcional).
     *
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function buildRegistroPruebasListadoBuilder(string $selectExtra = '')
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        $select = "{$r}.registro_id, {$r}.ingreso, {$r}.pruebas,
            CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
            {$d}.name as doctor,
            CAST(COALESCE({$pa}.total, 0) AS DECIMAL(12,2)) as total";
        if ($selectExtra !== '') {
            $select .= ', ' . $selectExtra;
        }

        return $this->db->table('registro')
            ->select($select, false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id", 'left');
    }

    /**
     * Reporte de ingresos por tipo de procesamiento (Interno / Derivado), detalle por prueba.
     *
     * @return array{
     *   detalle: list<array<string,mixed>>,
     *   resumen: array<string,mixed>,
     *   subtotales: array<string,array<string,mixed>>,
     *   top_derivadas: list<array<string,mixed>>,
     *   evolucion_mensual: list<array<string,mixed>>,
     *   chart_ingresos: array{interno: float, derivado: float},
     *   chart_cantidad: array{interno: int, derivado: int},
     * }
     */
    public function getIngresosPorTipoProcesamientoReport(
        string $startDate,
        string $endDate,
        string $tipoProcesamiento = '',
        string $estadoPago = '',
        int $doctorId = 0,
        string $institucion = ''
    ): array {
        $r        = $this->db->prefixTable('registro');
        $pa       = $this->db->prefixTable('pago');
        $p        = $this->db->prefixTable('people');
        $c        = $this->db->prefixTable('customers');
        $saldoEf  = $this->sqlSaldoPendienteEfectivo($pa);
        $paciente = $this->sqlPacienteNombreReporte($p);

        $select = "{$r}.registro_id, {$r}.ingreso, {$r}.numero_orden, {$r}.pruebas,
            {$this->sqlTipoProcesamientoIngresoExpr($r)} AS tipo_procesamiento,
            CAST({$pa}.total AS DECIMAL(12,2)) AS pago_total,
            CAST({$pa}.monto_pagar AS DECIMAL(12,2)) AS monto_pagar,
            {$saldoEf} AS saldo,
            {$paciente} AS paciente,
            COALESCE({$c}.institucion, '') AS institucion";

        $b = $this->db->table('registro')
            ->select($select, false)
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->join('people', "{$p}.person_id = {$r}.person_id", 'left')
            ->join('customers', "{$c}.person_id = {$r}.person_id AND COALESCE({$c}.deleted, 0) = 0", 'left')
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL");
        $b = $this->applySinRegistrosAnulados($b, $r);

        if ($doctorId > 0) {
            $b->where("{$r}.doctor_id", $doctorId);
        }
        if ($institucion !== '') {
            $b->where("{$c}.institucion", $institucion);
        }

        $ordenes = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();

        $catalogo   = $this->buildPruebasCostosMapIngresos();
        $tipoFiltro = in_array($tipoProcesamiento, ['interno', 'derivado'], true) ? $tipoProcesamiento : '';
        $estadoFiltro = in_array($estadoPago, ['pagado', 'pendiente', 'parcial'], true) ? $estadoPago : '';

        $detalle          = [];
        $topDerivadas     = [];
        $evolucion        = [];
        $subtotales       = [
            'interno'  => ['tipo' => 'interno', 'label' => 'Interno', 'cantidad' => 0, 'importe' => 0.0, 'cobrado' => 0.0, 'pendiente' => 0.0],
            'derivado' => ['tipo' => 'derivado', 'label' => 'Derivado', 'cantidad' => 0, 'importe' => 0.0, 'cobrado' => 0.0, 'pendiente' => 0.0],
        ];

        foreach ($ordenes as $orden) {
            $tipoOrden = (string) ($orden['tipo_procesamiento'] ?? 'interno');
            if ($tipoFiltro !== '' && $tipoOrden !== $tipoFiltro) {
                continue;
            }

            $pagoTotal  = (float) ($orden['pago_total'] ?? 0);
            $montoPagar = (float) ($orden['monto_pagar'] ?? 0);
            $saldoOrden = (float) ($orden['saldo'] ?? 0);
            $estado     = $this->resolveEstadoPagoIngreso($pagoTotal, $montoPagar, $saldoOrden);
            if ($estadoFiltro !== '' && $estado !== $estadoFiltro) {
                continue;
            }

            $pruebaIds = $this->parsePruebasCsvIds($orden['pruebas'] ?? '');
            if ($pruebaIds === []) {
                continue;
            }

            $esDerivado   = $tipoOrden === 'derivado';
            $catalogSum   = 0.0;
            $lineCatalog  = [];
            foreach ($pruebaIds as $pid) {
                $cat = $catalogo[$pid] ?? null;
                $importeCat = $cat !== null
                    ? ($esDerivado ? $cat['cost_deriv'] : $cat['cost'])
                    : 0.0;
                $catalogSum += $importeCat;
                $lineCatalog[] = [
                    'prueba_id' => $pid,
                    'prueba'    => $cat['name'] ?? ('Prueba #' . $pid),
                    'importe_cat' => $importeCat,
                ];
            }

            $ratioFact = $catalogSum > 0 ? ($pagoTotal / $catalogSum) : (count($lineCatalog) > 0 ? ($pagoTotal / count($lineCatalog)) : 0);
            $ratioCob  = $pagoTotal > 0 ? ($montoPagar / $pagoTotal) : 0.0;
            $fecha     = substr((string) ($orden['ingreso'] ?? ''), 0, 10);
            $mesKey    = $fecha !== '' ? substr($fecha, 0, 7) : '';

            foreach ($lineCatalog as $line) {
                $importe = round($line['importe_cat'] * $ratioFact, 2);
                $cobrado = round($importe * $ratioCob, 2);
                $saldo   = round(max(0, $importe - $cobrado), 2);

                $detalle[] = [
                    'fecha'                => $fecha,
                    'codigo_orden'         => trim((string) ($orden['numero_orden'] ?? '')),
                    'registro_id'          => (int) ($orden['registro_id'] ?? 0),
                    'paciente'             => trim((string) ($orden['paciente'] ?? '')),
                    'prueba'               => $line['prueba'],
                    'prueba_id'            => $line['prueba_id'],
                    'tipo_procesamiento'   => $tipoOrden,
                    'tipo_label'           => $esDerivado ? 'Derivado' : 'Interno',
                    'laboratorio_derivado' => $esDerivado ? '—' : '—',
                    'importe'              => $importe,
                    'monto_cobrado'        => $cobrado,
                    'saldo_pendiente'      => $saldo,
                    'estado_pago'          => $estado,
                    'estado_label'         => match ($estado) {
                        'pagado'    => 'Pagado',
                        'pendiente' => 'Pendiente',
                        default     => 'Parcial',
                    },
                ];

                $subtotales[$tipoOrden]['cantidad']++;
                $subtotales[$tipoOrden]['importe'] += $importe;
                $subtotales[$tipoOrden]['cobrado'] += $cobrado;
                $subtotales[$tipoOrden]['pendiente'] += $saldo;

                if ($esDerivado) {
                    $pid = $line['prueba_id'];
                    if (!isset($topDerivadas[$pid])) {
                        $topDerivadas[$pid] = [
                            'prueba_id' => $pid,
                            'prueba'    => $line['prueba'],
                            'cantidad'  => 0,
                        ];
                    }
                    $topDerivadas[$pid]['cantidad']++;
                }

                if ($mesKey !== '') {
                    if (!isset($evolucion[$mesKey])) {
                        $evolucion[$mesKey] = [
                            'mes'               => $mesKey,
                            'interno_importe'   => 0.0,
                            'derivado_importe'  => 0.0,
                            'interno_cantidad'  => 0,
                            'derivado_cantidad' => 0,
                        ];
                    }
                    if ($esDerivado) {
                        $evolucion[$mesKey]['derivado_importe'] += $importe;
                        $evolucion[$mesKey]['derivado_cantidad']++;
                    } else {
                        $evolucion[$mesKey]['interno_importe'] += $importe;
                        $evolucion[$mesKey]['interno_cantidad']++;
                    }
                }
            }
        }

        foreach ($subtotales as &$st) {
            $st['importe']   = round((float) $st['importe'], 2);
            $st['cobrado']   = round((float) $st['cobrado'], 2);
            $st['pendiente'] = round((float) $st['pendiente'], 2);
        }
        unset($st);

        $totalPruebas   = count($detalle);
        $totalFacturado = round($subtotales['interno']['importe'] + $subtotales['derivado']['importe'], 2);
        $totalCobrado   = round($subtotales['interno']['cobrado'] + $subtotales['derivado']['cobrado'], 2);
        $totalPendiente = round($subtotales['interno']['pendiente'] + $subtotales['derivado']['pendiente'], 2);
        $cantInternas   = (int) $subtotales['interno']['cantidad'];
        $cantDerivadas  = (int) $subtotales['derivado']['cantidad'];
        $pctDerivadas   = $totalPruebas > 0 ? round(($cantDerivadas / $totalPruebas) * 100, 2) : 0.0;
        $pctIngresoDeriv = $totalFacturado > 0
            ? round(($subtotales['derivado']['importe'] / $totalFacturado) * 100, 2)
            : 0.0;

        usort($topDerivadas, static fn (array $a, array $b): int => ($b['cantidad'] <=> $a['cantidad']) ?: strcmp($a['prueba'], $b['prueba']));
        $topDerivadas = array_slice(array_values($topDerivadas), 0, 10);
        foreach ($topDerivadas as $i => &$row) {
            $row['ranking'] = $i + 1;
            $row['porcentaje'] = $cantDerivadas > 0
                ? round(((int) $row['cantidad'] / $cantDerivadas) * 100, 2)
                : 0.0;
        }
        unset($row);

        ksort($evolucion);
        $evolucionMensual = array_values($evolucion);

        return [
            'detalle' => $detalle,
            'resumen' => [
                'total_pruebas'            => $totalPruebas,
                'total_facturado'          => $totalFacturado,
                'total_cobrado'            => $totalCobrado,
                'total_pendiente'          => $totalPendiente,
                'total_pruebas_internas'   => $cantInternas,
                'total_pruebas_derivadas'  => $cantDerivadas,
                'pct_pruebas_derivadas'    => $pctDerivadas,
                'pct_ingresos_derivados'   => $pctIngresoDeriv,
            ],
            'subtotales'        => $subtotales,
            'top_derivadas'     => $topDerivadas,
            'evolucion_mensual' => $evolucionMensual,
            'chart_ingresos'    => [
                'interno'  => $subtotales['interno']['importe'],
                'derivado' => $subtotales['derivado']['importe'],
            ],
            'chart_cantidad' => [
                'interno'  => $cantInternas,
                'derivado' => $cantDerivadas,
            ],
        ];
    }

    /**
     * Totales generales (todos los registros)
     */
    public function getTotalesGenerales(): object
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("COUNT(*) as total_registros, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return $b->get()->getRow();
    }
}
