<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'registro';

    /** @var bool|null */
    private static $registroTieneCampoAnulado = null;

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

    /**
     * Excluye órdenes anuladas de totales e informes operativos.
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
     * Registros de análisis por rango de fechas
     */
    public function getRegistrosByDateRange(string $startDate, string $endDate): array
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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
        $rows = $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
            ->groupBy("DATE({$r}.ingreso)")
            ->orderBy('fecha', 'ASC')
            ->get()
            ->getResultArray();

        return $rows;
    }

    /**
     * Totales del período
     */
    public function getTotalesByDateRange(string $startDate, string $endDate): object
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("COUNT(*) as total_registros, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
            ->groupBy("{$d}.doctor_id, {$d}.name")
            ->orderBy('total_facturado', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Reporte de pruebas realizadas por fecha (registros con pruebas, agrupado por fecha)
     */
    public function getPruebasPorFecha(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');
        $pt = $this->db->prefixTable('prianacategoria');
        $a  = $this->db->prefixTable('anacategoria');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);
        $rows = $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
            ->where("{$r}.pruebas != '' AND {$r}.pruebas IS NOT NULL")
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();

        $pruebasMap = [];
        $ptbl = $this->db->prefixTable('prianacategoria');
        $atbl = $this->db->prefixTable('anacategoria');
        $allPrias = $this->db->table('prianacategoria')
            ->select("{$ptbl}.prianacategoria_id, {$ptbl}.name, {$atbl}.name as categoria")
            ->join('anacategoria', "{$atbl}.anacategoria_id = {$ptbl}.anacategoria_id", 'left')
            ->where("{$ptbl}.deleted", 0)
            ->get()
            ->getResultArray();
        foreach ($allPrias as $pr) {
            $pruebasMap[(int)$pr['prianacategoria_id']] = $pr['name'] . ($pr['categoria'] ? ' (' . $pr['categoria'] . ')' : '');
        }

        foreach ($rows as &$row) {
            $ids = array_filter(array_map('intval', explode(',', trim($row['pruebas'] ?? ''))));
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
     * Filas mínimas para estadísticas de laboratorio (población/género/pruebas) en un rango de fechas.
     *
     * @return list<array{registro_id:string|int,person_id:string|int,ingreso:string,pruebas:string,birthday:?string,gender:?string|int}>
     */
    public function getRegistrosEstadisticasLaboratorio(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $pa = $this->db->prefixTable('pago');

        $b = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.person_id, {$r}.ingreso, {$r}.pruebas,
                {$p}.birthday, {$p}.gender")
            ->join('people', "{$p}.person_id = {$r}.person_id", 'left')
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id");
        $b = $this->applySinRegistrosAnulados($b, $r);

        return $b->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
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
