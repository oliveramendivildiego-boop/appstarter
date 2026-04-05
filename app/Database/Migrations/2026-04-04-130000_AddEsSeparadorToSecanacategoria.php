<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEsSeparadorToSecanacategoria extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('secanacategoria');
        if (!$this->db->tableExists($table)) {
            return;
        }
        if ($this->db->fieldExists('es_separador', $table)) {
            return;
        }

        $this->forge->addColumn('secanacategoria', [
            'es_separador' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
    }

    public function down()
    {
        $table = $this->db->prefixTable('secanacategoria');
        if (!$this->db->tableExists($table)) {
            return;
        }
        if (!$this->db->fieldExists('es_separador', $table)) {
            return;
        }
        $this->forge->dropColumn('secanacategoria', 'es_separador');
    }
}
