<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeneroTable extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('genero')) {
            $this->forge->addField([
                'genero_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => false,
                ],
                'orden' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'null'       => false,
                ],
                'deleted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
            ]);
            $this->forge->addKey('genero_id', true);
            $this->forge->createTable('genero', true);

            $this->db->table('genero')->insertBatch([
                ['genero_id' => 1, 'nombre' => 'Masculino', 'orden' => 1, 'deleted' => 0],
                ['genero_id' => 2, 'nombre' => 'Femenino', 'orden' => 2, 'deleted' => 0],
            ]);
        } else {
            $fields = $this->db->getFieldNames($this->db->prefixTable('genero'));
            if (!in_array('deleted', $fields, true)) {
                $this->forge->addColumn('genero', [
                    'deleted' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                        'null'       => false,
                    ],
                ]);
            }
            if (!in_array('orden', $fields, true)) {
                $this->forge->addColumn('genero', [
                    'orden' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'default'    => 0,
                        'null'       => false,
                    ],
                ]);
            }
            $count = (int) $this->db->table('genero')->countAllResults();
            if ($count === 0) {
                $this->db->table('genero')->insertBatch([
                    ['genero_id' => 1, 'nombre' => 'Masculino', 'orden' => 1, 'deleted' => 0],
                    ['genero_id' => 2, 'nombre' => 'Femenino', 'orden' => 2, 'deleted' => 0],
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('genero')) {
            $this->forge->dropTable('genero', true);
        }
    }
}
