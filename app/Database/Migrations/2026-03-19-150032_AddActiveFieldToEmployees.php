<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActiveFieldToEmployees extends Migration
{
    public function up()
    {
        // Agregar campo 'active' para distinguir inactivo (toggle) de borrado (delete)
        // active = 1: empleado activo (mostrar con toggle activado)
        // active = 0: empleado inactivo (mostrar con toggle desactivado)
        // deleted = 1: empleado borrado (no mostrar en tabla)
        $this->forge->addColumn('employees', [
            'active' => [
                'type'       => 'INT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'comment'    => 'Estado activo/inactivo del empleado',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employees', 'active');
    }
}
