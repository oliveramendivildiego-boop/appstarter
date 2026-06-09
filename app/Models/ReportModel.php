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
     * Excluye órdenes anuladas: no se facturan ni cuentan en cobros (devolución).
     * Usar en todo agregado de importes (reportes, ingresos, pagos por doctor, etc.).
     *
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
            ->where("{$ptbl}.deleted", 0)
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
            $ids     = array_filter(array_map('intval', explode(',', trim((string) ($row['pruebas'] ?? '')))));
            $nombres = [];
            foreach ($ids as $id) {
                $nombres[] = $pruebasMap[$id] ?? '#' . $id;
            }
            $row['pruebas_nombres'] = implode(', ', $nombres);
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
            ->select("{$ana}.name as categoria, {$pri}.name as prueba,
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

        return $builder
            ->orderBy("{$ana}.order", 'ASC')
            ->orderBy("{$pri}.order", 'ASC')
            ->get()
            ->getResultArray();
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
     * @param 'pendiente'|'pagado'|null $saldoFiltro
     *
     * @return list<array<string, mixed>>
     */
    protected function fetchCobrosAbonoPorFecha(string $startDate, string $endDate, ?string $saldoFiltro = null, ?string $tipopago = null): array
    {
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
                {$r}.registro_id, {$r}.numero_orden, {$r}.ingreso,
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
    protected function fetchCobrosLegacySinAbono(string $startDate, string $endDate, ?string $saldoFiltro = null, ?string $tipopago = null): array
    {
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
                {$r}.registro_id, {$r}.numero_orden, {$r}.ingreso,
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
                {$r}.registro_id, {$r}.numero_orden, {$r}.ingreso,
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
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
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
     * Reporte detallado de pruebas: código de recepción, paciente, usuario de recepción y resumen de trazabilidad.
     *
     * @return list<array<string, mixed>>
     */
    public function getPruebasDetalladoPorFecha(string $startDate, string $endDate): array
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
                CAST({$pa}.total AS DECIMAL(12,2)) as total", false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->join($pu, "pu.person_id = {$r}.id_session", 'left', false);
        $b = $this->applySinRegistrosAnulados($b, $r);
        $b = $this->applySinRegistrosEliminados($b, $r);
        $b->where("(SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) > 0", null, false);
        $rows = RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL")
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();

        $rows = $this->attachPruebasNombresListado($rows);
        $rows = $this->attachCodigoRecepcionListado($rows);

        return $this->attachTrazabilidadPruebasResumen($rows);
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
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $rv = $this->db->prefixTable('regvalues');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
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
        $p = $this->db->prefixTable('people');
        $d = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        $motivoCol = $this->registroTieneCampoMotivoAnulacion()
            ? "{$r}.motivo_anulacion"
            : "'' AS motivo_anulacion";

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                {$motivoCol},
                CAST({$pa}.total AS DECIMAL(12,2)) as total", false)
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("COALESCE({$r}.anulado, 0) <> 0", null, false);
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
        $b->where("(SELECT COUNT(*) FROM {$rv} WHERE {$rv}.registro_id = {$r}.registro_id) > 0", null, false);

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
