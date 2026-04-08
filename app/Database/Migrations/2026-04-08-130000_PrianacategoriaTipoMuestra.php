<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PrianacategoriaTipoMuestra extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('prianacategoria')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('prianacategoria'));
        if (in_array('tipo_muestra_id', $fields, true)) {
            return;
        }
        $this->forge->addColumn('prianacategoria', [
            'tipo_muestra_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        if (!$this->db->tableExists('prianacategoria')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('prianacategoria'));
        if (in_array('tipo_muestra_id', $fields, true)) {
            $this->forge->dropColumn('prianacategoria', 'tipo_muestra_id');
        }
    }
}
