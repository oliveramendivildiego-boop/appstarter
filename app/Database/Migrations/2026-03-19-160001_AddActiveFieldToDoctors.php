<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActiveFieldToDoctors extends Migration
{
    public function up()
    {
        // Agregar campo 'active' a doctors
        if (!$this->db->fieldExists('active', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'active' => [
                    'type'       => 'INT',
                    'constraint' => 1,
                    'default'    => 1,
                    'null'       => false,
                    'comment'    => 'Estado: 1=puede loguearse, 0=deshabilitado (sin acceso)',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('active', 'doctors')) {
            $this->forge->dropColumn('doctors', 'active');
        }
    }
}
