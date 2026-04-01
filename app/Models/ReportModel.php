<?php

namespace App\Models;

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
                      {$pri}.cost as precio, {$pri}.cost_deriv as precio_derivado,
                      {$pri}.order as orden_prueba, {$ana}.order as orden_categoria")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id AND ({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)");

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

        // Obtener pruebas compuestas (secanacategoria) - más inclusivo
        $builder1 = $this->db->table('anacategoria')
            ->select("{$ana}.name as categoria, {$pri}.name as prueba,
                      {$sec}.nombre as analisis, {$sec}.valor_min, {$sec}.valor_max,
                      {$sec}.umedida, {$sec}.paciente_id as poblacion, {$sec}.sexo,
                      'compuesto' as tipo_prueba")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->join('secanacategoria', "{$pri}.prianacategoria_id = {$sec}.prianacategoria_id", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->where("({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)")
            ->where("({$sec}.deleted = 0 OR {$sec}.deleted IS NULL)")
            ->where("{$sec}.nombre IS NOT NULL");

        // Obtener pruebas no compuestas (priresultados) - más inclusivo
        $builder2 = $this->db->table('anacategoria')
            ->select("{$ana}.name as categoria, {$pri}.name as prueba,
                      {$pri}.name as analisis, {$prires}.valor_min, {$prires}.valor_max,
                      {$prires}.umedida, {$prires}.id_poblacion as poblacion, {$prires}.sexo,
                      'simple' as tipo_prueba")
            ->join('prianacategoria', "{$ana}.anacategoria_id = {$pri}.anacategoria_id", 'left')
            ->join('priresultados', "{$pri}.prianacategoria_id = {$prires}.prianacategoria_id", 'left')
            ->where("({$ana}.deleted = 0 OR {$ana}.deleted IS NULL)")
            ->where("({$pri}.deleted = 0 OR {$pri}.deleted IS NULL)")
            ->where("({$prires}.deleted = 0 OR {$prires}.deleted IS NULL)")
            ->where("{$pri}.name IS NOT NULL");

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

        return array_merge($data1, $data2);
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
     * Reporte de pagos: todos los registros con datos de pago en rango de fechas
     */
    public function getReportePagos(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                CAST({$pa}.saldo AS DECIMAL(12,2)) as saldo,
                {$pa}.tipopago")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->orderBy("{$r}.ingreso", 'DESC')
            ->get()
            ->getResultArray();
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

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                CAST({$pa}.saldo AS DECIMAL(12,2)) as saldo")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("CAST({$pa}.saldo AS DECIMAL(12,2)) >", 0)
            ->orderBy('saldo', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Totales para reporte de pagos
     */
    public function getTotalesPagos(string $startDate, string $endDate): object
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("COUNT(*) as total_registros,
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
     * Resumen de pagos por tipo (Efectivo/QR/Transferencia/Pendiente).
     */
    public function getResumenPagosPorTipo(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

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
     * Detalle de pagos ya cobrados (saldo <= 0).
     */
    public function getPagosPagadosDetalle(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                CAST({$pa}.saldo AS DECIMAL(12,2)) as saldo,
                {$pa}.tipopago")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return RegistroIngresoDateRange::apply($b, $r, $startDate, $endDate)
            ->where("CAST({$pa}.saldo AS DECIMAL(12,2)) <= 0")
            ->orderBy("{$r}.ingreso", 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Resumen diario de pagos.
     */
    public function getResumenPagosPorDia(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

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
     * Resumen por doctor (facturado/cobrado/pendiente) en rango de fechas.
     */
    public function getResumenPagosPorDoctor(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

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
