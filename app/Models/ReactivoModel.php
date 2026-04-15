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

    /**
     * Por cada lote del insumo: cantidad ingresada (movimientos entrada), saldo actual y fechas.
     * Si no hay movimiento de entrada registrado, se estima ingreso como saldo + salidas del lote.
     *
     * @return list<array<string, mixed>>
     */
    public function getLotesResumenKardexPorReactivo(int $reactivoId): array
    {
        if ($reactivoId <= 0) {
            return [];
        }
        $lotes = $this->getLotes($reactivoId);
        $out   = [];
        foreach ($lotes as $l) {
            $lid = (int) ($l['lote_id'] ?? 0);
            if ($lid <= 0) {
                continue;
            }
            $sumEntrada = $this->sumCantidadMovimientosPorLote($lid, 'entrada');
            $sumSalida  = $this->sumCantidadMovimientosPorLote($lid, 'salida');
            $queda      = (int) round((float) ($l['cantidad'] ?? 0));
            $ingreso    = $sumEntrada > 0 ? (int) round($sumEntrada) : (int) round($queda + $sumSalida);

            $out[] = [
                'lote_id'             => $lid,
                'codigo_lote'         => (string) ($l['codigo_lote'] ?? ''),
                'ingreso'             => $ingreso,
                'queda'               => $queda,
                'fecha_vencimiento'   => $l['fecha_vencimiento'] ?? null,
                'fecha_ingreso'       => $l['fecha_ingreso'] ?? null,
            ];
        }

        return $out;
    }

    private function sumCantidadMovimientosPorLote(int $loteId, string $tipo): float
    {
        if (! in_array($tipo, ['entrada', 'salida'], true)) {
            return 0.0;
        }
        $row = $this->db->table('reactivo_movimiento')
            ->selectSum('cantidad')
            ->where('lote_id', $loteId)
            ->where('tipo', $tipo)
            ->get()
            ->getRow();

        return (float) ($row->cantidad ?? 0);
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
        $re = $this->db->prefixTable('registro');
        $rl = $this->db->prefixTable('reactivo_lote');

        return $this->db->table('reactivo_movimiento')
            ->select("reactivo_movimiento.*, people.first_name, people.last_name_fa, {$re}.numero_orden, {$rl}.codigo_lote")
            ->join('people', 'people.person_id = reactivo_movimiento.person_id', 'left')
            ->join('registro', "{$re}.registro_id = reactivo_movimiento.registro_id", 'left')
            ->join('reactivo_lote', "{$rl}.lote_id = reactivo_movimiento.lote_id", 'left')
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

        return $builder
            ->orderBy("{$rm}.fecha", 'DESC')
            ->orderBy("{$rm}.movimiento_id", 'DESC')
            ->limit(max(1, (int) $limit))
            ->get()
            ->getResultArray();
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

    /**
     * Lotes del insumo con cantidad disponible mayor que cero (selector de consumo).
     *
     * @return list<array<string, mixed>>
     */
    public function getLotesConStock(int $reactivoId): array
    {
        return array_values(array_filter(
            $this->getLotes($reactivoId),
            static fn (array $l): bool => (int) ($l['cantidad'] ?? 0) > 0
        ));
    }

    /**
     * Selecciona lote para salida automática según política:
     * - fefo: primero en vencer
     * - fifo: primero en entrar
     * - lifo: último en entrar
     */
    public function getLoteIdForAutoSalida(int $reactivoId, string $policy = 'fefo'): ?int
    {
        $lotes = $this->getLotesConStock($reactivoId);
        if (empty($lotes)) {
            return null;
        }

        $policy = strtolower(trim($policy));
        if (! in_array($policy, ['fefo', 'fifo', 'lifo'], true)) {
            $policy = 'fefo';
        }

        usort($lotes, static function (array $a, array $b) use ($policy): int {
            $va = $a['fecha_vencimiento'] ?? null;
            $vb = $b['fecha_vencimiento'] ?? null;
            $ia = $a['fecha_ingreso'] ?? null;
            $ib = $b['fecha_ingreso'] ?? null;
            $la = (int) ($a['lote_id'] ?? 0);
            $lb = (int) ($b['lote_id'] ?? 0);

            if ($policy === 'lifo') {
                if ($ia !== $ib) {
                    return strcmp((string) $ib, (string) $ia);
                }
                return $lb <=> $la;
            }

            if ($policy === 'fifo') {
                if ($ia !== $ib) {
                    return strcmp((string) $ia, (string) $ib);
                }
                return $la <=> $lb;
            }

            $aNoVence = empty($va);
            $bNoVence = empty($vb);
            if ($aNoVence !== $bNoVence) {
                return $aNoVence ? 1 : -1;
            }
            if ($va !== $vb) {
                return strcmp((string) $va, (string) $vb);
            }
            if ($ia !== $ib) {
                return strcmp((string) $ia, (string) $ib);
            }
            return $la <=> $lb;
        });

        $selected = $lotes[0] ?? null;
        return $selected ? (int) ($selected['lote_id'] ?? 0) : null;
    }

    /**
     * Registra salida (consumo) desde un lote concreto.
     * Si $loteId es null y solo hay un lote con stock, usa ese. Si hay varios, debe indicarse lote_id.
     */
    public function registrarSalida(
        int $reactivoId,
        int $cantidad,
        ?int $personId = null,
        ?string $observaciones = null,
        ?int $registroId = null,
        ?int $loteId = null
    ): bool {
        $db = $this->db;
        if ($cantidad <= 0) {
            return false;
        }

        $lotesConStock = $this->getLotesConStock($reactivoId);
        if ($loteId === null || $loteId <= 0) {
            if (count($lotesConStock) === 1) {
                $loteId = (int) ($lotesConStock[0]['lote_id'] ?? 0);
            } else {
                return false;
            }
        }

        $lote = $db->table('reactivo_lote')
            ->where('lote_id', $loteId)
            ->where('reactivo_id', $reactivoId)
            ->where('(deleted = 0 OR deleted IS NULL)', null, false)
            ->get()
            ->getRowArray();

        if ($lote === null) {
            return false;
        }

        $disponible = (int) ($lote['cantidad'] ?? 0);
        if ($disponible < $cantidad) {
            return false;
        }

        $db->transStart();
        try {
            $db->table('reactivo_lote')->where('lote_id', $loteId)->update(['cantidad' => $disponible - $cantidad]);
            $db->table('reactivo_movimiento')->insert([
                'reactivo_id'   => $reactivoId,
                'tipo'          => 'salida',
                'cantidad'      => $cantidad,
                'person_id'     => $personId,
                'observaciones' => $observaciones,
                'registro_id'   => $registroId,
                'lote_id'       => $loteId,
            ]);
            $db->transComplete();

            return $db->transStatus();
        } catch (\Throwable $e) {
            $db->transRollback();

            return false;
        }
    }

    /**
     * Anula un movimiento de salida: devuelve la cantidad al lote y elimina el registro de movimiento.
     *
     * @return 'ok'|'not_found'|'not_salida'|'sin_lote'|'lote_invalido'
     */
    public function revertirSalida(int $movimientoId, int $reactivoId): string
    {
        $db = $this->db;
        $row = $db->table('reactivo_movimiento')
            ->where('movimiento_id', $movimientoId)
            ->where('reactivo_id', $reactivoId)
            ->get()
            ->getRowArray();

        if ($row === null) {
            return 'not_found';
        }
        if (($row['tipo'] ?? '') !== 'salida') {
            return 'not_salida';
        }

        $loteId = (int) ($row['lote_id'] ?? 0);
        if ($loteId <= 0) {
            return 'sin_lote';
        }

        $cant = (int) round((float) ($row['cantidad'] ?? 0));
        if ($cant <= 0) {
            return 'not_found';
        }

        $lote = $db->table('reactivo_lote')
            ->where('lote_id', $loteId)
            ->where('reactivo_id', $reactivoId)
            ->where('(deleted = 0 OR deleted IS NULL)', null, false)
            ->get()
            ->getRowArray();

        if ($lote === null) {
            return 'lote_invalido';
        }

        $db->transStart();
        try {
            $nuevo = (int) ($lote['cantidad'] ?? 0) + $cant;
            $db->table('reactivo_lote')->where('lote_id', $loteId)->update(['cantidad' => $nuevo]);
            $db->table('reactivo_movimiento')->where('movimiento_id', $movimientoId)->delete();
            $db->transComplete();

            return $db->transStatus() ? 'ok' : 'not_found';
        } catch (\Throwable $e) {
            $db->transRollback();

            return 'not_found';
        }
    }
}
