<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInterpretacionEnabledToDoctors extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('interpretacion_enabled', 'doctors')) {
            $fields = [
                'interpretacion_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                    'after' => 'display_mode',
                ],
            ];
            $this->forge->addColumn('doctors', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('interpretacion_enabled', 'doctors')) {
            $this->forge->dropColumn('doctors', 'interpretacion_enabled');
        }
    }
}
