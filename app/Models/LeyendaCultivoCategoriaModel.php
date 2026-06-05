<?php

namespace App\Models;

use CodeIgniter\Model;

class LeyendaCultivoCategoriaModel extends Model
{
    protected $table            = 'leyendas_cultivo_categorias';
    protected $primaryKey       = 'leyenda_cultivo_categoria_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['nombre', 'activo', 'deleted', 'created_at', 'updated_at'];

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

    public function getActivas(): array
    {
        if (! $this->ensureTable()) {
            return [];
        }

        return $this->db->table($this->table)
            ->where('activo', 1)
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
            ->where('leyenda_cultivo_categoria_id', $id)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function saveCategoria(array $data, ?int $id = null): bool
    {
        if (! $this->ensureTable()) {
            return false;
        }

        $now = \App\Services\RegisterService::mysqlNowForReport();
        $save = [
            'nombre'     => trim((string) ($data['nombre'] ?? '')),
            'activo'     => (int) ($data['activo'] ?? 1) ? 1 : 0,
            'updated_at' => $now,
            'deleted'    => 0,
        ];

        if ($save['nombre'] === '') {
            return false;
        }

        if ($id !== null && $id > 0) {
            return $this->db->table($this->table)
                ->where('leyenda_cultivo_categoria_id', $id)
                ->update($save) !== false;
        }

        $save['created_at'] = $now;

        return $this->db->table($this->table)->insert($save) !== false;
    }

    public function softDelete(int $id): bool
    {
        if ($id < 1 || ! $this->ensureTable()) {
            return false;
        }

        return $this->db->table($this->table)
            ->where('leyenda_cultivo_categoria_id', $id)
            ->update([
                'deleted'    => 1,
                'updated_at' => \App\Services\RegisterService::mysqlNowForReport(),
            ]) !== false;
    }

    public function countLeyendasEnCategoria(int $categoriaId): int
    {
        if ($categoriaId < 1 || ! $this->ensureTable()) {
            return 0;
        }

        if (! $this->db->tableExists($this->db->prefixTable('leyendas_cultivo'))) {
            return 0;
        }

        return (int) $this->db->table('leyendas_cultivo')
            ->where('leyenda_cultivo_categoria_id', $categoriaId)
            ->where('(deleted = 0 OR deleted IS NULL)')
            ->countAllResults();
    }

    /**
     * Crea la tabla leyendas_cultivo_categorias si la migración aún no corrió.
     */
    public function ensureTable(): bool
    {
        if ($this->db->tableExists($this->db->prefixTable($this->table))) {
            return true;
        }

        try {
            $forge = \Config\Database::forge($this->db);
            $forge->addField([
                'leyenda_cultivo_categoria_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
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
            $forge->addKey('leyenda_cultivo_categoria_id', true);
            $forge->addKey('deleted');
            $forge->createTable($this->table, true);

            return $this->db->tableExists($this->db->prefixTable($this->table));
        } catch (\Throwable $e) {
            log_message('error', 'LeyendaCultivoCategoriaModel::ensureTable: {err}', ['err' => $e->getMessage()]);

            return false;
        }
    }
}
