<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActiveFieldToEmployees extends Migration
{
    public function up()
    {
        // Agregar campo 'active' SOLO si no existe
        if (!$this->db->fieldExists('active', 'employees')) {
            $this->forge->addColumn('employees', [
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
        if ($this->db->fieldExists('active', 'employees')) {
            $this->forge->dropColumn('employees', 'active');
        }
    }
}
