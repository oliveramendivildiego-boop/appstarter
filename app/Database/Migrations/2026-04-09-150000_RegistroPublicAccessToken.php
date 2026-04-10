<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Token opaco para visor público de resultados (/resultados/{token}) sin login.
 */
class RegistroPublicAccessToken extends Migration
{
    private const INDEX_NAME = 'idx_registro_public_access_token';

    public function up()
    {
        if (! $this->db->tableExists('registro')) {
            return;
        }

        $tablePrefixed = $this->db->prefixTable('registro');
        $fields        = $this->db->getFieldNames($tablePrefixed);
        if (in_array('public_access_token', $fields, true)) {
            $this->ensureUniqueIndex($tablePrefixed);

            return;
        }

        $this->forge->addColumn('registro', [
            'public_access_token' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
                'comment'    => 'Token opaco para ver/descargar resultados sin credenciales',
            ],
        ]);

        $this->ensureUniqueIndex($tablePrefixed);
    }

    public function down()
    {
        if (! $this->db->tableExists('registro')) {
            return;
        }

        $tablePrefixed = $this->db->prefixTable('registro');

        if ($this->indexExistsOnTable($tablePrefixed, self::INDEX_NAME)) {
            $this->db->query('ALTER TABLE ' . $this->db->escapeIdentifiers($tablePrefixed)
                . ' DROP INDEX ' . $this->db->escapeIdentifiers(self::INDEX_NAME));
        }

        if ($this->db->fieldExists('public_access_token', 'registro')) {
            $this->forge->dropColumn('registro', 'public_access_token');
        }
    }

    private function ensureUniqueIndex(string $tablePrefixed): void
    {
        if ($this->indexExistsOnTable($tablePrefixed, self::INDEX_NAME)) {
            return;
        }

        $sql = 'CREATE UNIQUE INDEX ' . $this->db->escapeIdentifiers(self::INDEX_NAME)
            . ' ON ' . $this->db->escapeIdentifiers($tablePrefixed)
            . ' (' . $this->db->escapeIdentifiers('public_access_token') . ')';

        $this->db->query($sql);
    }

    private function indexExistsOnTable(string $tablePrefixed, string $indexName): bool
    {
        $dbName = (string) (config('Database')->default['database'] ?? '');
        if ($dbName === '') {
            return false;
        }

        $row = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1',
            [$dbName, $tablePrefixed, $indexName]
        )->getRow();

        return $row !== null;
    }
}
