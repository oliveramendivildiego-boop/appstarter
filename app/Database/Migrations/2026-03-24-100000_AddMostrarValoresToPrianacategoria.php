<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMostrarValoresToPrianacategoria extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (!$this->db->tableExists($table)) {
            return;
        }
        if ($this->db->fieldExists('mostrar_valores', $table)) {
            return;
        }

        $this->forge->addColumn('prianacategoria', [
            'mostrar_valores' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => 'compleja',
            ],
        ]);
    }

    public function down()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (!$this->db->tableExists($table)) {
            return;
        }
        if (!$this->db->fieldExists('mostrar_valores', $table)) {
            return;
        }
        $this->forge->dropColumn('prianacategoria', 'mostrar_valores');
    }
}

