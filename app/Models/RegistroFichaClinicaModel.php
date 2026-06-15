<?php

namespace App\Models;

use CodeIgniter\Model;

class RegistroFichaClinicaModel extends Model
{
    protected $table            = 'registro_ficha_clinica';
    protected $primaryKey       = 'registro_ficha_clinica_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'registro_id',
        'prianacategoria_id',
        'ficha_clinica_id',
        'valores_json',
        'created_at',
        'updated_at',
    ];

    public function ensureTable(): bool
    {
        if ($this->db->tableExists($this->db->prefixTable($this->table))) {
            return true;
        }

        try {
            $forge = \Config\Database::forge($this->db);
            $forge->addField([
                'registro_ficha_clinica_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'registro_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'prianacategoria_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'ficha_clinica_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'valores_json' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
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
            $forge->addKey('registro_ficha_clinica_id', true);
            $forge->addKey('registro_id');
            $forge->addKey('prianacategoria_id');
            $forge->addKey('ficha_clinica_id');
            $forge->addUniqueKey(['registro_id', 'prianacategoria_id']);
            $forge->createTable($this->table, true);

            return $this->db->tableExists($this->db->prefixTable($this->table));
        } catch (\Throwable $e) {
            log_message('error', 'RegistroFichaClinicaModel::ensureTable ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @return array<int, array{ficha_clinica_id: int, valores: array<string, string>, has_data: bool}>
     */
    public function getAllDataByRegistro(int $registroId): array
    {
        if ($registroId < 1 || ! $this->ensureTable()) {
            return [];
        }

        $rows = $this->db->table($this->table)
            ->select('prianacategoria_id, ficha_clinica_id, valores_json')
            ->where('registro_id', $registroId)
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $priaId = (int) ($row['prianacategoria_id'] ?? 0);
            if ($priaId < 1) {
                continue;
            }
            $valores = $this->valoresToFlatStrings($this->decodeValores((string) ($row['valores_json'] ?? '')));
            $out[$priaId] = [
                'ficha_clinica_id' => (int) ($row['ficha_clinica_id'] ?? 0),
                'valores'          => $valores,
                'has_data'         => $this->valoresTienenDatos($valores),
            ];
        }

        return $out;
    }

    public function getByRegistroAndPrueba(int $registroId, int $prianacategoriaId): ?array
    {
        if ($registroId < 1 || $prianacategoriaId < 1 || ! $this->ensureTable()) {
            return null;
        }

        $row = $this->db->table($this->table)
            ->where('registro_id', $registroId)
            ->where('prianacategoria_id', $prianacategoriaId)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int, array{ficha_clinica_id: int, has_data: bool}>
     */
    public function getFilledMapByRegistro(int $registroId): array
    {
        if ($registroId < 1 || ! $this->ensureTable()) {
            return [];
        }

        $rows = $this->db->table($this->table)
            ->select('prianacategoria_id, ficha_clinica_id, valores_json')
            ->where('registro_id', $registroId)
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $priaId = (int) ($row['prianacategoria_id'] ?? 0);
            if ($priaId < 1) {
                continue;
            }
            $valores = $this->decodeValores((string) ($row['valores_json'] ?? ''));
            $out[$priaId] = [
                'ficha_clinica_id' => (int) ($row['ficha_clinica_id'] ?? 0),
                'has_data'         => $this->valoresTienenDatos($valores),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public function getExistentesFlat(int $registroId, int $prianacategoriaId): array
    {
        $row = $this->getByRegistroAndPrueba($registroId, $prianacategoriaId);
        if ($row === null) {
            return [];
        }

        $valores = $this->decodeValores((string) ($row['valores_json'] ?? ''));

        return $this->valoresToFlatStrings($valores);
    }

    /**
     * @param array<string, mixed> $valores
     */
    public function saveFill(int $registroId, int $prianacategoriaId, int $fichaClinicaId, array $valores): bool
    {
        if ($registroId < 1 || $prianacategoriaId < 1 || $fichaClinicaId < 1 || ! $this->ensureTable()) {
            return false;
        }

        $json = json_encode($valores, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $now = \App\Services\RegisterService::mysqlNowForReport();
        $existing = $this->getByRegistroAndPrueba($registroId, $prianacategoriaId);

        if ($existing !== null) {
            return $this->db->table($this->table)
                ->where('registro_ficha_clinica_id', (int) ($existing['registro_ficha_clinica_id'] ?? 0))
                ->update([
                    'ficha_clinica_id' => $fichaClinicaId,
                    'valores_json'     => $json,
                    'updated_at'       => $now,
                ]) !== false;
        }

        return $this->db->table($this->table)->insert([
            'registro_id'          => $registroId,
            'prianacategoria_id'   => $prianacategoriaId,
            'ficha_clinica_id'     => $fichaClinicaId,
            'valores_json'         => $json,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]) !== false;
    }

    /**
     * @param list<array{prianacategoria_id: int, ficha_clinica_id: int, valores: array<string, mixed>}> $items
     */
    public function saveBatch(int $registroId, array $items): void
    {
        if ($registroId < 1 || $items === []) {
            return;
        }

        foreach ($items as $item) {
            $priaId = (int) ($item['prianacategoria_id'] ?? 0);
            $fichaId = (int) ($item['ficha_clinica_id'] ?? 0);
            $valores = is_array($item['valores'] ?? null) ? $item['valores'] : [];
            if ($priaId < 1 || $fichaId < 1) {
                continue;
            }
            $this->saveFill($registroId, $priaId, $fichaId, $valores);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeValores(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $valores
     */
    private function valoresTienenDatos(array $valores): bool
    {
        foreach ($valores as $v) {
            if (is_scalar($v) && trim((string) $v) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $valores
     * @return array<string, string>
     */
    private function valoresToFlatStrings(array $valores): array
    {
        $out = [];
        foreach ($valores as $k => $v) {
            if (! is_string($k)) {
                continue;
            }
            $out[$k] = is_scalar($v) ? (string) $v : '';
        }

        return $out;
    }

    /**
     * @param list<int> $prianacategoriaIds
     */
    public function deleteByRegistroAndPruebas(int $registroId, array $prianacategoriaIds): void
    {
        if ($registroId < 1 || $prianacategoriaIds === [] || ! $this->ensureTable()) {
            return;
        }

        $ids = [];
        foreach ($prianacategoriaIds as $rawId) {
            $id = (int) $rawId;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return;
        }

        $this->db->table($this->table)
            ->where('registro_id', $registroId)
            ->whereIn('prianacategoria_id', $ids)
            ->delete();
    }
}
