<?php

namespace App\Models;

use CodeIgniter\Model;

class ReactivoModel extends Model
{
    protected $table = 'reactivo';
    protected $primaryKey = 'reactivo_id';

    const TIPO_REACTIVO = 1;
    const TIPO_KIT = 2;
    const TIPO_INSUMO = 3;

    const UNIDADES_BASE = ['ml' => 'ml', 'g' => 'g', 'mg' => 'mg', 'unidad' => 'unidad', 'prueba' => 'prueba', 'caja' => 'caja'];

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

    /**
     * Todos los lotes para reporte de vencimientos (con nombre del insumo).
     * Ordenados por fecha_vencimiento ASC (próximos a vencer primero).
     */
    public function getTodosLotesParaReporte(): array
    {
        $rl = $this->db->prefixTable('reactivo_lote');
        $r  = $this->db->prefixTable('reactivo');

        return $this->db->table('reactivo_lote')
            ->select("{$rl}.*, {$r}.nombre AS reactivo_nombre, {$r}.unidad_base, {$r}.unidad")
            ->join('reactivo', "{$r}.reactivo_id = {$rl}.reactivo_id")
            ->where("({$rl}.deleted = 0 OR {$rl}.deleted IS NULL)")
            ->where("({$r}.deleted = 0 OR {$r}.deleted IS NULL)")
            ->orderBy("{$rl}.fecha_vencimiento", 'ASC')
            ->orderBy("{$rl}.fecha_ingreso", 'ASC')
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

    /**
     * Obtiene insumos consumidos asociados a una orden (registro).
     * Solo movimientos tipo salida con registro_id = $registroId.
     */
    public function getInsumosPorRegistro(int $registroId): array
    {
        $rm = $this->db->prefixTable('reactivo_movimiento');
        $r  = $this->db->prefixTable('reactivo');
        $p  = $this->db->prefixTable('people');

        $rows = $this->db->table('reactivo_movimiento')
            ->select("{$rm}.movimiento_id, {$rm}.fecha, {$rm}.cantidad, {$rm}.observaciones,
                {$r}.nombre AS reactivo_nombre, {$r}.unidad_base, {$r}.unidad,
                CONCAT({$p}.first_name, ' ', {$p}.last_name_fa) AS responsable")
            ->join('reactivo', "{$r}.reactivo_id = {$rm}.reactivo_id")
            ->join('people', "{$p}.person_id = {$rm}.person_id", 'left')
            ->where("{$rm}.registro_id", $registroId)
            ->where("{$rm}.tipo", 'salida')
            ->orderBy("{$rm}.fecha", 'ASC')
            ->get()
            ->getResultArray();

        return $rows;
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

    /**
     * Kardex: lista de movimientos con filtros.
     */
    public function getKardexMovimientos(
        string $startDate,
        string $endDate,
        ?int $personId = null,
        ?int $reactivoId = null,
        ?string $tipo = null,
        int $limit = 2500
    ): array {
        $rm = $this->db->prefixTable('reactivo_movimiento');
        $r  = $this->db->prefixTable('reactivo');
        $rl = $this->db->prefixTable('reactivo_lote');
        $p  = $this->db->prefixTable('people');
        $re = $this->db->prefixTable('registro');

        $builder = $this->db->table('reactivo_movimiento')
            ->select("
                {$rm}.movimiento_id,
                {$rm}.fecha,
                {$rm}.reactivo_id,
                {$rm}.tipo,
                {$rm}.cantidad,
                {$rm}.person_id,
                {$rm}.lote_id,
                {$rm}.registro_id,
                {$rm}.observaciones,
                {$r}.nombre AS reactivo_nombre,
                {$rl}.codigo_lote,
                CONCAT(COALESCE({$p}.first_name, ''), ' ', COALESCE({$p}.last_name_fa, '')) AS usuario_nombre,
                {$re}.numero_orden
            ")
            ->join('reactivo', "{$r}.reactivo_id = {$rm}.reactivo_id", 'left')
            ->join('reactivo_lote', "{$rl}.lote_id = {$rm}.lote_id", 'left')
            ->join('people', "{$p}.person_id = {$rm}.person_id", 'left')
            ->join('registro', "{$re}.registro_id = {$rm}.registro_id", 'left')
            ->where("DATE({$rm}.fecha) >=", $startDate)
            ->where("DATE({$rm}.fecha) <=", $endDate);

        if (($personId ?? 0) > 0) {
            $builder->where("{$rm}.person_id", (int) $personId);
        }
        if (($reactivoId ?? 0) > 0) {
            $builder->where("{$rm}.reactivo_id", (int) $reactivoId);
        }
        if (in_array($tipo, ['entrada', 'salida'], true)) {
            $builder->where("{$rm}.tipo", $tipo);
        }

        $rows = $builder
            ->orderBy("{$rm}.fecha", 'DESC')
            ->orderBy("{$rm}.movimiento_id", 'DESC')
            ->limit(max(1, (int) $limit))
            ->get()
            ->getResultArray();

        // Saldo acumulado solo cuando se filtra un insumo específico.
        if (($reactivoId ?? 0) > 0) {
            $saldo = 0;
            for ($i = count($rows) - 1; $i >= 0; $i--) {
                $cant = (int) ($rows[$i]['cantidad'] ?? 0);
                $esEntrada = (($rows[$i]['tipo'] ?? '') === 'entrada');
                $saldo += $esEntrada ? $cant : -$cant;
                $rows[$i]['saldo_acumulado'] = $saldo;
            }
        }

        return $rows;
    }

    /**
     * Kardex: resumen por usuario (cantidad de movimientos).
     */
    public function getKardexResumenPorUsuario(
        string $startDate,
        string $endDate,
        ?int $reactivoId = null,
        ?string $tipo = null
    ): array {
        $rm = $this->db->prefixTable('reactivo_movimiento');
        $p  = $this->db->prefixTable('people');

        $builder = $this->db->table('reactivo_movimiento')
            ->select("
                {$rm}.person_id,
                CONCAT(COALESCE({$p}.first_name, ''), ' ', COALESCE({$p}.last_name_fa, '')) AS usuario_nombre,
                COUNT(*) AS movimientos,
                SUM(CASE WHEN {$rm}.tipo = 'entrada' THEN {$rm}.cantidad ELSE 0 END) AS total_entrada,
                SUM(CASE WHEN {$rm}.tipo = 'salida' THEN {$rm}.cantidad ELSE 0 END) AS total_salida
            ")
            ->join('people', "{$p}.person_id = {$rm}.person_id", 'left')
            ->where("DATE({$rm}.fecha) >=", $startDate)
            ->where("DATE({$rm}.fecha) <=", $endDate);

        if (($reactivoId ?? 0) > 0) {
            $builder->where("{$rm}.reactivo_id", (int) $reactivoId);
        }
        if (in_array($tipo, ['entrada', 'salida'], true)) {
            $builder->where("{$rm}.tipo", $tipo);
        }

        return $builder
            ->groupBy("{$rm}.person_id")
            ->orderBy('movimientos', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Kardex: total de movimientos para paginación/indicadores.
     */
    public function countKardexMovimientos(
        string $startDate,
        string $endDate,
        ?int $personId = null,
        ?int $reactivoId = null,
        ?string $tipo = null
    ): int {
        $rm = $this->db->prefixTable('reactivo_movimiento');

        $builder = $this->db->table('reactivo_movimiento')
            ->where("DATE({$rm}.fecha) >=", $startDate)
            ->where("DATE({$rm}.fecha) <=", $endDate);

        if (($personId ?? 0) > 0) {
            $builder->where("{$rm}.person_id", (int) $personId);
        }
        if (($reactivoId ?? 0) > 0) {
            $builder->where("{$rm}.reactivo_id", (int) $reactivoId);
        }
        if (in_array($tipo, ['entrada', 'salida'], true)) {
            $builder->where("{$rm}.tipo", $tipo);
        }

        return (int) $builder->countAllResults();
    }

    public function getAlertas(): array
    {
        $hoy = date('Y-m-d');
        $en30 = date('Y-m-d', strtotime('+30 days'));
        $alertas = [];

        $reactivos = $this->getAll();
        if (empty($reactivos)) {
            return $alertas;
        }

        $ids = array_column($reactivos, 'reactivo_id');
        $stockMap = $this->getStockTotalBatch($ids);
        $lotesPorReactivo = $this->getLotesPorReactivosBatch($ids, $en30);

        foreach ($reactivos as $r) {
            $rid = (int) ($r['reactivo_id'] ?? 0);
            $stock = $stockMap[$rid] ?? 0;
            $min = (int) ($r['stock_minimo'] ?? 0);
            if ($min > 0 && $stock < $min) {
                $alertas[] = ['tipo' => 'stock', 'reactivo' => $r, 'stock' => $stock, 'minimo' => $min];
            }
            foreach ($lotesPorReactivo[$rid] ?? [] as $lote) {
                if (!empty($lote['fecha_vencimiento']) && $lote['fecha_vencimiento'] <= $hoy) {
                    $alertas[] = ['tipo' => 'vencido', 'reactivo' => $r, 'lote' => $lote];
                } elseif (!empty($lote['fecha_vencimiento']) && $lote['fecha_vencimiento'] <= $en30) {
                    $alertas[] = ['tipo' => 'pronto', 'reactivo' => $r, 'lote' => $lote];
                }
            }
        }
        return $alertas;
    }

    /**
     * Obtiene stock total para múltiples reactivos en una sola consulta.
     */
    private function getStockTotalBatch(array $reactivoIds): array
    {
        if (empty($reactivoIds)) {
            return [];
        }
        $ids = array_map('intval', $reactivoIds);
        $ids = array_filter($ids, fn($x) => $x > 0);
        if (empty($ids)) {
            return [];
        }
        $rows = $this->db->table('reactivo_lote')
            ->select('reactivo_id, SUM(cantidad) as total')
            ->whereIn('reactivo_id', $ids)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->groupBy('reactivo_id')
            ->get()
            ->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['reactivo_id']] = (int) ($row['total'] ?? 0);
        }
        foreach ($ids as $id) {
            if (!isset($map[$id])) {
                $map[$id] = 0;
            }
        }
        return $map;
    }

    /**
     * Obtiene lotes con vencimiento <= $fechaLimite para múltiples reactivos.
     */
    private function getLotesPorReactivosBatch(array $reactivoIds, string $fechaLimite): array
    {
        if (empty($reactivoIds)) {
            return [];
        }
        $ids = array_map('intval', array_filter($reactivoIds, fn($x) => (int) $x > 0));
        if (empty($ids)) {
            return [];
        }
        $rows = $this->db->table('reactivo_lote')
            ->whereIn('reactivo_id', $ids)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->where('fecha_vencimiento IS NOT NULL')
            ->where('fecha_vencimiento <=', $fechaLimite)
            ->orderBy('fecha_vencimiento', 'ASC')
            ->get()
            ->getResultArray();
        $porReactivo = [];
        foreach ($ids as $id) {
            $porReactivo[$id] = [];
        }
        foreach ($rows as $lote) {
            $rid = (int) ($lote['reactivo_id'] ?? 0);
            $porReactivo[$rid][] = $lote;
        }
        return $porReactivo;
    }

    /**
     * Devuelve reactivos con stock_actual y tipo_nombre en una sola pasada (evita N+1).
     */
    public function getAllWithStockAndTipo(?string $grupo = null): array
    {
        $rl = $this->db->prefixTable('reactivo_lote');
        $r = $this->db->prefixTable('reactivo');
        $builder = $this->db->table($r)
            ->select("{$r}.*, COALESCE(SUM({$rl}.cantidad), 0) AS stock_actual")
            ->join($rl, "{$rl}.reactivo_id = {$r}.reactivo_id AND ({$rl}.deleted = 0 OR {$rl}.deleted IS NULL)", 'left')
            ->where("({$r}.deleted = 0 OR {$r}.deleted IS NULL)")
            ->groupBy("{$r}.reactivo_id")
            ->orderBy("{$r}.grupo")
            ->orderBy("{$r}.nombre");
        if ($grupo) {
            $builder->where("{$r}.grupo", $grupo);
        }
        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['stock_actual'] = (int) ($row['stock_actual'] ?? 0);
            $row['tipo_nombre'] = $this->getNombreTipo((int) ($row['tipo'] ?? 1));
        }
        unset($row);
        return $rows;
    }

    /**
     * Obtiene un reactivo por ID.
     */
    public function getReactivo(int $reactivoId): ?array
    {
        $row = $this->db->table('reactivo')
            ->where('reactivo_id', $reactivoId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();
        return $row ?: null;
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

    /**
     * Normaliza fecha a Y-m-d para MySQL. Acepta Y-m-d, d/m/Y o d-m-Y.
     */
    public static function normalizarFecha(?string $fecha): ?string
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }
        $fecha = trim($fecha);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha)) {
            return $fecha;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $fecha, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        $dt = date_create($fecha);
        return $dt ? $dt->format('Y-m-d') : null;
    }

    /** Registra entrada: crea lote nuevo y movimiento (con responsable) */
    public function registrarEntrada(int $reactivoId, string $codigoLote, int $cantidad, ?string $vencimiento, ?int $personId = null, ?string $fechaIngreso = null): bool
    {
        $db = $this->db;
        $fechaIngreso = self::normalizarFecha($fechaIngreso) ?: date('Y-m-d');
        $vencimiento = self::normalizarFecha($vencimiento);
        $db->transStart();
        try {
            $db->table('reactivo_lote')->insert([
                'reactivo_id'        => $reactivoId,
                'codigo_lote'        => $codigoLote,
                'cantidad'           => $cantidad,
                'fecha_vencimiento'  => $vencimiento,
                'fecha_ingreso'      => $fechaIngreso,
                'deleted'            => 0,
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
