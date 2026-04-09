<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportePagosCierreModel extends Model
{
    protected $table            = 'reporte_pagos_cierre';
    protected $primaryKey       = 'cierre_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fecha_desde',
        'fecha_hasta',
        'snapshot_json',
        'person_id',
        'elaborado_nombre',
        'created_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function getListado(int $limit = 500): array
    {
        return $this->orderBy('cierre_id', 'DESC')
            ->limit(max(1, $limit))
            ->findAll();
    }

    public function findCierre(int $cierreId): ?array
    {
        $row = $this->find($cierreId);

        return $row ?: null;
    }

    /**
     * Agrupa cierres por fecha de registro (día de `created_at`) y suma montos del snapshot.
     *
     * @return array<string, array{cierres: int, cobrado: float, facturado: float}>
     */
    public function getAgregadoPorDiaRegistro(string $desdeFechaYmd): array
    {
        try {
            $rows = $this->where('created_at >=', $desdeFechaYmd . ' 00:00:00')
                ->orderBy('created_at', 'ASC')
                ->findAll();
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            $created = (string) ($r['created_at'] ?? '');
            if ($created === '') {
                continue;
            }
            $day = substr($created, 0, 10);
            if (! isset($map[$day])) {
                $map[$day] = ['cierres' => 0, 'cobrado' => 0.0, 'facturado' => 0.0];
            }
            $map[$day]['cierres']++;
            $snap = json_decode($r['snapshot_json'] ?? '', true);
            if (is_array($snap) && isset($snap['totales']) && is_array($snap['totales'])) {
                $map[$day]['cobrado']   += (float) ($snap['totales']['total_cobrado'] ?? 0);
                $map[$day]['facturado'] += (float) ($snap['totales']['total_facturado'] ?? 0);
            }
        }

        return $map;
    }
}
