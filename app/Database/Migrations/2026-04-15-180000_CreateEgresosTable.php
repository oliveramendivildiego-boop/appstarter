<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEgresosTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('egresos')) {
            return;
        }

        $this->forge->addField([
            'egreso_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fecha' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'monto' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
                'null'       => false,
            ],
            'tipopago' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '1',
                'null'       => false,
            ],
            'desglose' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'deleted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
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
        $this->forge->addKey('egreso_id', true);
        $this->forge->addKey('fecha');
        $this->forge->createTable('egresos', true);
    }

    public function down()
    {
        if ($this->db->tableExists('egresos')) {
            $this->forge->dropTable('egresos', true);
        }
    }
}

