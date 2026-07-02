<?php

namespace App\Models;

use CodeIgniter\Model;

class FichaClinicaModel extends Model
{
    protected $table            = 'fichas_clinicas';
    protected $primaryKey       = 'ficha_clinica_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'nombre',
        'matriz_config',
        'activo',
        'deleted',
        'created_at',
        'updated_at',
    ];

    public function getAll(): array
    {
        if (! $this->ensureTable()) {
            return [];
        }

        return $this->db->table($this->table)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        if ($id < 1 || ! $this->ensureTable()) {
            return null;
        }

        $row = $this->db->table($this->table)
            ->where('ficha_clinica_id', $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveFicha(array $data, ?int $id = null): int|false
    {
        if (! $this->ensureTable()) {
            return false;
        }

        $now = \App\Services\RegisterService::mysqlNowForReport();
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return false;
        }

        $save = [
            'nombre'     => $nombre,
            'activo'     => (int) ($data['activo'] ?? 1) ? 1 : 0,
            'updated_at' => $now,
            'deleted'    => 0,
        ];

        if ($id !== null && $id > 0) {
            $ok = $this->db->table($this->table)
                ->where('ficha_clinica_id', $id)
                ->update($save) !== false;

            return $ok ? $id : false;
        }

        $save['created_at'] = $now;
        $ok = $this->db->table($this->table)->insert($save);
        if (! $ok) {
            return false;
        }

        return (int) $this->db->insertID();
    }

    public function softDelete(int $id): bool
    {
        if ($id < 1 || ! $this->ensureTable()) {
            return false;
        }

        return $this->db->table($this->table)
            ->where('ficha_clinica_id', $id)
            ->update([
                'deleted'    => 1,
                'updated_at' => \App\Services\RegisterService::mysqlNowForReport(),
            ]) !== false;
    }

    /**
     * @return array{version: int, bloques: list<array<string, mixed>>}
     */
    public function getMatrizConfig(int $fichaClinicaId): array
    {
        $labotestModel = model(LabotestModel::class);
        $row = $this->getById($fichaClinicaId);
        if ($row === null) {
            return $labotestModel->getDefaultCultivoMatrizConfig();
        }

        $raw = null;
        if (! empty($row['matriz_config'])) {
            $decoded = json_decode((string) $row['matriz_config'], true);
            $raw = is_array($decoded) ? $decoded : null;
        }

        return $labotestModel->normalizePersonalizadoMatrizConfig(
            $raw ?? $labotestModel->getDefaultCultivoMatrizConfig()
        );
    }

    /**
     * Indica si la matriz de la ficha tiene al menos una celda que el usuario debe capturar al crear la orden.
     *
     * @param array<string, mixed> $matriz
     */
    public function matrizRequiereCapturaUsuario(array $matriz): bool
    {
        $bloques = LabotestModel::resolvePersonalizadoMatrizBloques($matriz);
        foreach ($bloques as $bloque) {
            if (! is_array($bloque)) {
                continue;
            }
            if ($this->bloqueMatrizRequiereCapturaUsuario($bloque)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $bloque
     */
    private function bloqueMatrizRequiereCapturaUsuario(array $bloque): bool
    {
        $columnas = max(1, (int) ($bloque['columnas'] ?? 1));
        $filas = max(0, (int) ($bloque['filas'] ?? 0));
        $celdas = is_array($bloque['celdas'] ?? null) ? $bloque['celdas'] : [];
        $skipCols = [];
        $coveredRowspan = [];

        for ($r = 0; $r < $filas; $r++) {
            for ($c = 0; $c < $columnas; $c++) {
                if (isset($skipCols[$r . ',' . $c]) || isset($coveredRowspan[$r . ',' . $c])) {
                    continue;
                }

                $raw = $celdas[$r][$c] ?? ['modo' => 'texto'];
                if ($this->celdaMatrizRequiereCapturaUsuario($raw)) {
                    return true;
                }

                if (! is_array($raw)) {
                    continue;
                }

                $colspan = max(1, min(20, (int) ($raw['colspan'] ?? 1)));
                $rowspan = max(1, min(50, (int) ($raw['rowspan'] ?? 1)));
                $maxColspan = max(1, $columnas - $c);
                if ($colspan > $maxColspan) {
                    $colspan = $maxColspan;
                }
                $maxRowspan = max(1, $filas - $r);
                if ($rowspan > $maxRowspan) {
                    $rowspan = $maxRowspan;
                }

                if ($colspan > 1) {
                    for ($cc = $c + 1; $cc < $c + $colspan; $cc++) {
                        $skipCols[$r . ',' . $cc] = true;
                    }
                }
                if ($rowspan > 1) {
                    for ($rr = $r + 1; $rr < $r + $rowspan; $rr++) {
                        for ($cc = $c; $cc < $c + $colspan; $cc++) {
                            $coveredRowspan[$rr . ',' . $cc] = true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function fichaRequiereCapturaUsuario(int $fichaClinicaId): bool
    {
        if ($fichaClinicaId < 1) {
            return false;
        }

        return $this->matrizRequiereCapturaUsuario($this->getMatrizConfig($fichaClinicaId));
    }

    /**
     * Mapa prueba_id => si alguna ficha enlazada requiere captura en la orden.
     *
     * @return array<int, bool>
     */
    public function getRequiereCapturaMapForRegisters(): array
    {
        $map = $this->getFichasMapForRegisters();
        $out = [];
        foreach ($map as $priaId => $fichas) {
            $requiere = false;
            foreach ($fichas as $ficha) {
                $fichaId = (int) ($ficha['ficha_clinica_id'] ?? 0);
                if ($fichaId > 0 && $this->fichaRequiereCapturaUsuario($fichaId)) {
                    $requiere = true;
                    break;
                }
            }
            $out[(int) $priaId] = $requiere;
        }

        return $out;
    }

    /**
     * @param mixed $raw
     */
    private function celdaMatrizRequiereCapturaUsuario(mixed $raw): bool
    {
        if (! is_array($raw)) {
            return is_numeric($raw) && (int) $raw > 0;
        }

        $modo = (string) ($raw['modo'] ?? 'texto');
        if (in_array($modo, ['vacio', 'texto_fijo'], true)) {
            return false;
        }

        if (! in_array($modo, ['texto', 'texto_rico', 'opcion', 'leyenda'], true)) {
            return false;
        }

        $rol = (string) ($raw['rol'] ?? 'input');

        return ! in_array($rol, ['titulo', 'etiqueta'], true);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function saveMatrizConfig(int $fichaClinicaId, array $config): bool
    {
        if ($fichaClinicaId < 1 || ! $this->ensureTable() || $this->getById($fichaClinicaId) === null) {
            return false;
        }

        $labotestModel = model(LabotestModel::class);
        $normalized = $labotestModel->normalizePersonalizadoMatrizConfig($config);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        return $this->db->table($this->table)
            ->where('ficha_clinica_id', $fichaClinicaId)
            ->update([
                'matriz_config' => $json,
                'updated_at'    => \App\Services\RegisterService::mysqlNowForReport(),
            ]) !== false;
    }

    /**
     * Payload exportable de una ficha clínica completa.
     *
     * @return array<string, mixed>|null
     */
    public function buildExportPayload(int $fichaClinicaId): ?array
    {
        $row = $this->getById($fichaClinicaId);
        if ($row === null) {
            return null;
        }

        $matriz = $this->getMatrizConfig($fichaClinicaId);
        $pruebas = $this->getLinkedPruebas($fichaClinicaId);

        return [
            'schema_version'       => 1,
            'tipo'                 => 'ficha_clinica',
            'exported_at'          => date('c'),
            'ficha_clinica_id'     => $fichaClinicaId,
            'nombre'               => (string) ($row['nombre'] ?? ''),
            'activo'               => (int) ($row['activo'] ?? 1) === 1 ? 1 : 0,
            'matriz_tipo'          => 'personalizado',
            'personalizado_matriz' => $matriz,
            'cultivo_matriz'       => $matriz,
            'pruebas'              => array_map(static function (array $p): array {
                return [
                    'prianacategoria_id' => (int) ($p['prianacategoria_id'] ?? 0),
                    'prueba_nombre'      => (string) ($p['prueba_nombre'] ?? ''),
                    'categoria_nombre'   => (string) ($p['categoria_nombre'] ?? ''),
                ];
            }, $pruebas),
        ];
    }

    /**
     * Payload exportable solo con la matriz (compatible con importación de pruebas personalizadas).
     *
     * @return array<string, mixed>|null
     */
    public function buildMatrizExportPayload(int $fichaClinicaId): ?array
    {
        $row = $this->getById($fichaClinicaId);
        if ($row === null) {
            return null;
        }

        $matriz = $this->getMatrizConfig($fichaClinicaId);

        return [
            'schema_version'       => 1,
            'matriz_tipo'          => 'personalizado',
            'compleja'             => LabotestModel::COMPLEJA_PERSONALIZADO,
            'personalizado_matriz' => $matriz,
            'cultivo_matriz'       => $matriz,
        ];
    }

    /**
     * Importa matriz en una ficha existente desde JSON de ficha o de prueba personalizada.
     *
     * @return array{success: bool, message: string}
     */
    public function importMatrizFromPayload(int $fichaClinicaId, array $payload): array
    {
        if ($fichaClinicaId < 1 || $this->getById($fichaClinicaId) === null) {
            return ['success' => false, 'message' => 'Ficha clínica no encontrada'];
        }

        $matrizRaw = LabotestModel::extractMatrizFromDetailImportPayload(
            $payload,
            LabotestModel::COMPLEJA_PERSONALIZADO
        );
        if ($matrizRaw === null) {
            return ['success' => false, 'message' => 'El archivo no contiene una matriz personalizada para importar'];
        }

        if (! $this->saveMatrizConfig($fichaClinicaId, $matrizRaw)) {
            return ['success' => false, 'message' => 'No se pudo guardar la matriz de la ficha clínica'];
        }

        return ['success' => true, 'message' => 'Matriz de ficha clínica importada correctamente'];
    }

    /**
     * Crea una ficha clínica nueva desde JSON exportado.
     *
     * @return array{success: bool, message: string, ficha_clinica_id?: int}
     */
    public function importFichaFromPayload(array $payload): array
    {
        if (! $this->ensureTable()) {
            return ['success' => false, 'message' => 'No se pudo preparar la tabla fichas_clinicas'];
        }

        $nombre = trim((string) ($payload['nombre'] ?? ''));
        if ($nombre === '') {
            $nombre = 'Ficha importada';
        }

        $activo = (int) ($payload['activo'] ?? 1) ? 1 : 0;
        $savedId = $this->saveFicha([
            'nombre' => $nombre,
            'activo' => $activo,
        ]);
        if ($savedId === false) {
            return ['success' => false, 'message' => 'No se pudo crear la ficha clínica'];
        }

        $fichaId = (int) $savedId;
        $matrizResult = $this->importMatrizFromPayload($fichaId, $payload);
        if (! ($matrizResult['success'] ?? false)) {
            $this->softDelete($fichaId);

            return [
                'success' => false,
                'message' => (string) ($matrizResult['message'] ?? 'No se pudo importar la matriz'),
            ];
        }

        $pruebaIds = [];
        if (isset($payload['pruebas']) && is_array($payload['pruebas'])) {
            foreach ($payload['pruebas'] as $pruebaRow) {
                if (! is_array($pruebaRow)) {
                    continue;
                }
                $pid = (int) ($pruebaRow['prianacategoria_id'] ?? 0);
                if ($pid > 0) {
                    $pruebaIds[] = $pid;
                }
            }
        }
        if ($pruebaIds !== []) {
            $this->saveLinkedPruebas($fichaId, $pruebaIds);
        }

        return [
            'success'          => true,
            'message'          => 'Ficha clínica importada correctamente',
            'ficha_clinica_id' => $fichaId,
        ];
    }

    public static function slugifyNombre(string $nombre): string
    {
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9]+/i', '_', $slug) ?? '';
        $slug = trim($slug, '_');

        return $slug !== '' ? $slug : 'ficha';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLinkedPruebas(int $fichaClinicaId): array
    {
        if ($fichaClinicaId < 1 || ! $this->ensurePruebasTable()) {
            return [];
        }

        $link = $this->db->prefixTable('ficha_clinica_pruebas');
        $pri = $this->db->prefixTable('prianacategoria');
        $ana = $this->db->prefixTable('anacategoria');

        return $this->db->table('ficha_clinica_pruebas fcp')
            ->select('fcp.prianacategoria_id, fcp.orden, pri.name AS prueba_nombre, ana.name AS categoria_nombre, pri.compleja')
            ->join($pri . ' pri', 'pri.prianacategoria_id = fcp.prianacategoria_id', 'inner')
            ->join($ana . ' ana', 'ana.anacategoria_id = pri.anacategoria_id', 'left')
            ->where('fcp.ficha_clinica_id', $fichaClinicaId)
            ->where('(pri.deleted = 0 OR pri.deleted IS NULL)')
            ->orderBy('fcp.orden', 'ASC')
            ->orderBy('ana.name', 'ASC')
            ->orderBy('pri.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<int>
     */
    public function getLinkedPruebaIds(int $fichaClinicaId): array
    {
        $rows = $this->getLinkedPruebas($fichaClinicaId);

        return array_values(array_map(static fn (array $r): int => (int) ($r['prianacategoria_id'] ?? 0), $rows));
    }

    /**
     * @param list<int> $prianacategoriaIds
     */
    public function saveLinkedPruebas(int $fichaClinicaId, array $prianacategoriaIds): bool
    {
        if ($fichaClinicaId < 1 || $this->getById($fichaClinicaId) === null || ! $this->ensurePruebasTable()) {
            return false;
        }

        $ids = [];
        foreach ($prianacategoriaIds as $rawId) {
            $id = (int) $rawId;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        $ids = array_values($ids);

        if ($ids !== []) {
            $validRows = $this->db->table('prianacategoria')
                ->select('prianacategoria_id')
                ->whereIn('prianacategoria_id', $ids)
                ->where('(deleted = 0 OR deleted IS NULL)')
                ->get()
                ->getResultArray();
            $validIds = [];
            foreach ($validRows as $row) {
                $vid = (int) ($row['prianacategoria_id'] ?? 0);
                if ($vid > 0) {
                    $validIds[$vid] = $vid;
                }
            }
            $ids = array_values(array_intersect($ids, array_values($validIds)));
        }

        $this->db->transStart();
        $this->db->table('ficha_clinica_pruebas')
            ->where('ficha_clinica_id', $fichaClinicaId)
            ->delete();

        $now = \App\Services\RegisterService::mysqlNowForReport();
        foreach ($ids as $orden => $priaId) {
            $this->db->table('ficha_clinica_pruebas')->insert([
                'ficha_clinica_id'     => $fichaClinicaId,
                'prianacategoria_id'   => $priaId,
                'orden'                => (int) $orden,
                'created_at'           => $now,
            ]);
        }
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * @return array<int, int> ficha_clinica_id => count
     */
    public function getPruebasCountByFicha(): array
    {
        if (! $this->ensurePruebasTable()) {
            return [];
        }

        $rows = $this->db->table('ficha_clinica_pruebas')
            ->select('ficha_clinica_id, COUNT(*) AS total', false)
            ->groupBy('ficha_clinica_id')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $fid = (int) ($row['ficha_clinica_id'] ?? 0);
            if ($fid > 0) {
                $out[$fid] = (int) ($row['total'] ?? 0);
            }
        }

        return $out;
    }

    /**
     * Mapa prueba_id => listado de fichas activas enlazadas (para /registers).
     *
     * @return array<int, list<array{ficha_clinica_id: int, nombre: string}>>
     */
    public function getFichasMapForRegisters(): array
    {
        if (! $this->ensureTable() || ! $this->ensurePruebasTable()) {
            return [];
        }

        $link = $this->db->prefixTable('ficha_clinica_pruebas');
        $ficha = $this->db->prefixTable('fichas_clinicas');

        $rows = $this->db->table('ficha_clinica_pruebas fcp')
            ->select('fcp.prianacategoria_id, fcp.orden, fc.ficha_clinica_id, fc.nombre')
            ->join($ficha . ' fc', 'fc.ficha_clinica_id = fcp.ficha_clinica_id', 'inner')
            ->where('(fc.deleted = 0 OR fc.deleted IS NULL)')
            ->where('fc.activo', 1)
            ->orderBy('fcp.prianacategoria_id', 'ASC')
            ->orderBy('fcp.orden', 'ASC')
            ->orderBy('fc.nombre', 'ASC')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $priaId = (int) ($row['prianacategoria_id'] ?? 0);
            $fichaId = (int) ($row['ficha_clinica_id'] ?? 0);
            if ($priaId < 1 || $fichaId < 1) {
                continue;
            }
            if (! isset($out[$priaId])) {
                $out[$priaId] = [];
            }
            $out[$priaId][] = [
                'ficha_clinica_id' => $fichaId,
                'nombre'           => (string) ($row['nombre'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{ficha_clinica_id: int, nombre: string}>
     */
    public function getFichasByPruebaId(int $prianacategoriaId): array
    {
        $map = $this->getFichasMapForRegisters();

        return $map[$prianacategoriaId] ?? [];
    }

    public function pruebaTieneFichaActiva(int $prianacategoriaId): bool
    {
        return $this->getFichasByPruebaId($prianacategoriaId) !== [];
    }

    public function ensurePruebasTable(): bool
    {
        if ($this->db->tableExists($this->db->prefixTable('ficha_clinica_pruebas'))) {
            return true;
        }

        try {
            $forge = \Config\Database::forge($this->db);
            $forge->addField([
                'ficha_clinica_prueba_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'ficha_clinica_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'prianacategoria_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'orden' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('ficha_clinica_prueba_id', true);
            $forge->addKey(['ficha_clinica_id', 'prianacategoria_id'], false, true);
            $forge->addKey('prianacategoria_id');
            $forge->createTable('ficha_clinica_pruebas', true);

            return $this->db->tableExists($this->db->prefixTable('ficha_clinica_pruebas'));
        } catch (\Throwable $e) {
            log_message('error', 'FichaClinicaModel::ensurePruebasTable: {err}', ['err' => $e->getMessage()]);

            return false;
        }
    }

    public function ensureTable(): bool
    {
        if ($this->db->tableExists($this->db->prefixTable($this->table))) {
            return true;
        }

        try {
            $forge = \Config\Database::forge($this->db);
            $forge->addField([
                'ficha_clinica_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'matriz_config' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
                ],
                'activo' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'deleted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('ficha_clinica_id', true);
            $forge->addKey('deleted');
            $forge->addKey('activo');
            $forge->createTable($this->table, true);

            return $this->db->tableExists($this->db->prefixTable($this->table));
        } catch (\Throwable $e) {
            log_message('error', 'FichaClinicaModel::ensureTable: {err}', ['err' => $e->getMessage()]);

            return false;
        }
    }
}
