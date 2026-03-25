<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAnuladoToRegistro extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('registro')) {
            return;
        }
        $t      = $this->db->prefixTable('registro');
        $fields = $this->db->getFieldNames($t);

        if (!in_array('anulado', $fields, true)) {
            $this->forge->addColumn('registro', [
                'anulado' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 0,
                    'null'       => false,
                ],
            ]);
        }
        if (!in_array('motivo_anulacion', $fields, true)) {
            $this->forge->addColumn('registro', [
                'motivo_anulacion' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
        }
        if (!in_array('fecha_anulacion', $fields, true)) {
            $this->forge->addColumn('registro', [
                'fecha_anulacion' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }
        if (!in_array('person_id_anulo', $fields, true)) {
            $this->forge->addColumn('registro', [
                'person_id_anulo' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('registro')) {
            return;
        }
        $t      = $this->db->prefixTable('registro');
        $fields = $this->db->getFieldNames($t);
        foreach (['person_id_anulo', 'fecha_anulacion', 'motivo_anulacion', 'anulado'] as $col) {
            if (in_array($col, $fields, true)) {
                $this->forge->dropColumn('registro', $col);
            }
        }
    }
}
