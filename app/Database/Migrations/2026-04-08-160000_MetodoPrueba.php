<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MetodoPrueba extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('metodo')) {
            $this->forge->addField([
                'metodo_id' => [
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
            $this->forge->addKey('metodo_id', true);
            $this->forge->createTable('metodo', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('metodo')) {
            $this->forge->dropTable('metodo', true);
        }
    }
}
