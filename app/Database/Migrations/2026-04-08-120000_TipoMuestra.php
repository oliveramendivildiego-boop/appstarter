<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TipoMuestra extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tipo_muestra')) {
            $this->forge->addField([
                'tipo_muestra_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => false,
                ],
                'deleted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
            ]);
            $this->forge->addKey('tipo_muestra_id', true);
            $this->forge->createTable('tipo_muestra', true);

            $this->db->table('tipo_muestra')->insertBatch([
                ['nombre' => 'Sangre total', 'deleted' => 0],
                ['nombre' => 'Suero', 'deleted' => 0],
                ['nombre' => 'Plasma', 'deleted' => 0],
                ['nombre' => 'Orina', 'deleted' => 0],
            ]);
        } else {
            $fields = $this->db->getFieldNames($this->db->prefixTable('tipo_muestra'));
            if (!in_array('deleted', $fields, true)) {
                $this->forge->addColumn('tipo_muestra', [
                    'deleted' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                        'null'       => false,
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tipo_muestra')) {
            $this->forge->dropTable('tipo_muestra', true);
        }
    }
}
