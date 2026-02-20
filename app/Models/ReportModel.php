<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'registro';

    /**
     * Registros de análisis por rango de fechas
     */
    public function getRegistrosByDateRange(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $p  = $this->db->prefixTable('people');
        $d  = $this->db->prefixTable('doctors');
        $pa = $this->db->prefixTable('pago');

        return $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor, {$pa}.total as total, {$pa}.monto_pagar")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
            ->orderBy("{$r}.ingreso", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Resumen de ingresos por rango de fechas
     */
    public function getIngresosByDateRange(string $startDate, string $endDate): array
    {
        $r  = $this->db->prefixTable('registro');
        $pa = $this->db->prefixTable('pago');

        $rows = $this->db->table('registro')
            ->select("DATE({$r}.ingreso) as fecha, COUNT(*) as cantidad, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
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

        return $this->db->table('registro')
            ->select("COUNT(*) as total_registros, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado, SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
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

        return $this->db->table('registro')
            ->select("{$d}.name as doctor, COUNT(*) as cantidad, SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
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

        return $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                CAST({$pa}.saldo AS DECIMAL(12,2)) as saldo,
                {$pa}.tipopago")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
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

        return $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total,
                CAST({$pa}.monto_pagar AS DECIMAL(12,2)) as monto_pagado,
                CAST({$pa}.saldo AS DECIMAL(12,2)) as saldo")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
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

        return $this->db->table('registro')
            ->select("COUNT(*) as total_registros,
                SUM(CAST({$pa}.total AS DECIMAL(12,2))) as total_facturado,
                SUM(CAST({$pa}.monto_pagar AS DECIMAL(12,2))) as total_cobrado,
                SUM(CAST({$pa}.saldo AS DECIMAL(12,2))) as total_pendiente")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
            ->where("DATE({$r}.ingreso) <=", $endDate)
            ->get()
            ->getRow();
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

        $rows = $this->db->table('registro')
            ->select("{$r}.registro_id, {$r}.ingreso, {$r}.pruebas,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa, ' ', {$p}.last_name_mom) AS paciente,
                {$d}.name as doctor,
                CAST({$pa}.total AS DECIMAL(12,2)) as total")
            ->join('people', "{$p}.person_id = {$r}.person_id")
            ->join('doctors', "{$d}.doctor_id = {$r}.doctor_id")
            ->join('pago', "{$r}.registro_id = {$pa}.registro_id")
            ->where("DATE({$r}.ingreso) >=", $startDate)
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
}
