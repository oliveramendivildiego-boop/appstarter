<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDisplayModeToDoctors extends Migration
{
    public function up()
    {
        // Añadir columna solo si no existe (evita errores si ya fue creada manualmente)
        if (! $this->db->fieldExists('display_mode', 'doctors')) {
            $fields = [
                'display_mode' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => false,
                    'default'    => 'clinico',
                    'after'      => 'hide_commission_details',
                ],
            ];

            $this->forge->addColumn('doctors', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('display_mode', 'doctors')) {
            $this->forge->dropColumn('doctors', 'display_mode');
        }
    }
}
