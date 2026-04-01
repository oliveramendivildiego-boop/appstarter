<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSesgoToControlCalidad extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('control_calidad')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('control_calidad'));
        if (!in_array('sesgo', $fields, true)) {
            $this->forge->addColumn('control_calidad', [
                'sesgo' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,4',
                    'null'       => true,
                    'comment'    => 'Sesgo asignado de referencia (opcional)',
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('control_calidad')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('control_calidad'));
        if (in_array('sesgo', $fields, true)) {
            $this->forge->dropColumn('control_calidad', 'sesgo');
        }
    }
}
