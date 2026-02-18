<?php

namespace App\Models;

use CodeIgniter\Model;

class ReactivoModel extends Model
{
    protected $table = 'reactivo';
    protected $primaryKey = 'reactivo_id';

    public const TIPO_REACTIVO = 1;
    public const TIPO_KIT = 2;
    public const TIPO_INSUMO = 3;

    public const UNIDADES_BASE = ['ml' => 'ml', 'g' => 'g', 'mg' => 'mg', 'unidad' => 'unidad', 'prueba' => 'prueba', 'caja' => 'caja'];

    public function getAll(?string $grupo = null): array
    {
        $q = $this->db->table('reactivo')
            ->where('(deleted = 0 OR deleted IS NULL)');
        if ($grupo) {
            $q->where('grupo', $grupo);
        }
        return $q->orderBy('grupo')->orderBy('nombre')->get()->getResultArray();
    }

    public function getGrupos(): array
    {
        return $this->db->table('reactivo')
            ->select('grupo')
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->where('grupo IS NOT NULL')
            ->where("grupo != ''")
            ->groupBy('grupo')
            ->orderBy('grupo')
            ->get()
            ->getResultArray();
    }

    public function getLotes(int $reactivoId): array
    {
        return $this->db->table('reactivo_lote')
            ->where('reactivo_id', $reactivoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('fecha_vencimiento', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getStockTotal(int $reactivoId): int
    {
        $r = $this->db->table('reactivo_lote')
            ->selectSum('cantidad')
            ->where('reactivo_id', $reactivoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRow();
        return (int) ($r->cantidad ?? 0);
    }

    public function getMovimientos(int $reactivoId, int $limit = 50): array
    {
        return $this->db->table('reactivo_movimiento')
            ->select('reactivo_movimiento.*, people.first_name, people.last_name_fa')
            ->join('people', 'people.person_id = reactivo_movimiento.person_id', 'left')
            ->where('reactivo_movimiento.reactivo_id', $reactivoId)
            ->orderBy('reactivo_movimiento.fecha', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getAlertas(): array
    {
        $hoy = date('Y-m-d');
        $en30 = date('Y-m-d', strtotime('+30 days'));
        $alertas = [];
        foreach ($this->getAll() as $r) {
            $stock = $this->getStockTotal($r['reactivo_id']);
            $min = (int) ($r['stock_minimo'] ?? 0);
            if ($min > 0 && $stock < $min) {
                $alertas[] = ['tipo' => 'stock', 'reactivo' => $r, 'stock' => $stock, 'minimo' => $min];
            }
            foreach ($this->getLotes($r['reactivo_id']) as $lote) {
                if (!empty($lote['fecha_vencimiento']) && $lote['fecha_vencimiento'] <= $hoy) {
                    $alertas[] = ['tipo' => 'vencido', 'reactivo' => $r, 'lote' => $lote];
                } elseif (!empty($lote['fecha_vencimiento']) && $lote['fecha_vencimiento'] <= $en30) {
                    $alertas[] = ['tipo' => 'pronto', 'reactivo' => $r, 'lote' => $lote];
                }
            }
        }
        return $alertas;
    }

    public function getNombreTipo(int $tipo): string
    {
        $t = (int) $tipo;
        return $t === self::TIPO_KIT ? 'Kit' : ($t === self::TIPO_INSUMO ? 'Insumo' : 'Reactivo');
    }

    public function saveReactivo(array $data, ?int $id = null): bool
    {
        $save = [
            'nombre'                    => trim($data['nombre'] ?? ''),
            'unidad'                    => trim($data['unidad'] ?? '') ?: null,
            'unidad_base'               => trim($data['unidad_base'] ?? $data['unidad'] ?? '') ?: null,
            'stock_minimo'              => (int) ($data['stock_minimo'] ?? 0),
            'grupo'                     => trim($data['grupo'] ?? '') ?: null,
            'subgrupo'                  => trim($data['subgrupo'] ?? '') ?: null,
            'contenido_por_presentacion'=> (int) ($data['contenido_por_presentacion'] ?? 1) ?: 1,
            'tipo'                      => (int) ($data['tipo'] ?? self::TIPO_REACTIVO),
            'deleted'                   => 0,
        ];
        if ($id) {
            return $this->db->table('reactivo')->where('reactivo_id', $id)->update($save);
        }
        return $this->db->table('reactivo')->insert($save) !== false;
    }

    public function deleteReactivo(int $reactivoId): bool
    {
        return $this->db->table('reactivo')->where('reactivo_id', $reactivoId)->update(['deleted' => 1]);
    }

    public function saveLote(array $data, ?int $id = null): bool
    {
        $save = [
            'reactivo_id'        => (int) ($data['reactivo_id'] ?? 0),
            'codigo_lote'        => trim($data['codigo_lote'] ?? ''),
            'cantidad'           => (int) ($data['cantidad'] ?? 0),
            'fecha_vencimiento'  => $data['fecha_vencimiento'] ?? null,
            'fecha_ingreso'      => $data['fecha_ingreso'] ?? date('Y-m-d'),
            'deleted'            => 0,
        ];
        if ($id) {
            return $this->db->table('reactivo_lote')->where('lote_id', $id)->update($save);
        }
        return $this->db->table('reactivo_lote')->insert($save) !== false;
    }

    /** Registra entrada: crea lote nuevo y movimiento (con responsable) */
    public function registrarEntrada(int $reactivoId, string $codigoLote, int $cantidad, ?string $vencimiento, ?int $personId = null): bool
    {
        $db = $this->db;
        $db->transStart();
        try {
            $db->table('reactivo_lote')->insert([
                'reactivo_id'        => $reactivoId,
                'codigo_lote'        => $codigoLote,
                'cantidad'           => $cantidad,
                'fecha_vencimiento' => $vencimiento,
                'fecha_ingreso'     => date('Y-m-d'),
                'deleted'           => 0,
            ]);
            $loteId = $db->insertID();
            $db->table('reactivo_movimiento')->insert([
                'reactivo_id' => $reactivoId,
                'tipo'        => 'entrada',
                'cantidad'    => $cantidad,
                'person_id'   => $personId,
                'lote_id'     => $loteId,
            ]);
            $db->transComplete();
            return $db->transStatus();
        } catch (\Throwable $e) {
            $db->transRollback();
            return false;
        }
    }

    /** Registra salida (consumo): descuenta de lotes FIFO y crea un movimiento con responsable */
    public function registrarSalida(int $reactivoId, int $cantidad, ?int $personId = null, ?string $observaciones = null, ?int $registroId = null): bool
    {
        $db = $this->db;
        $stock = $this->getStockTotal($reactivoId);
        if ($stock < $cantidad) {
            return false;
        }
        $db->transStart();
        try {
            $restante = $cantidad;
            $lotes = $this->getLotes($reactivoId);
            foreach ($lotes as $l) {
                if ($restante <= 0) break;
                $disponible = (int) ($l['cantidad'] ?? 0);
                if ($disponible <= 0) continue;
                $descontar = min($restante, $disponible);
                $db->table('reactivo_lote')->where('lote_id', $l['lote_id'])->update(['cantidad' => $disponible - $descontar]);
                $restante -= $descontar;
            }
            $db->table('reactivo_movimiento')->insert([
                'reactivo_id'   => $reactivoId,
                'tipo'          => 'salida',
                'cantidad'      => $cantidad,
                'person_id'     => $personId,
                'observaciones' => $observaciones,
                'registro_id'   => $registroId,
            ]);
            $db->transComplete();
            return $db->transStatus();
        } catch (\Throwable $e) {
            $db->transRollback();
            return false;
        }
    }
}
