<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGraficarToPrianacategoria extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (!$this->db->tableExists($table)) {
            return;
        }
        if ($this->db->fieldExists('graficar', $table)) {
            return;
        }

        $after = 'compleja';
        if ($this->db->fieldExists('mostrar_valores', $table)) {
            $after = 'mostrar_valores';
        }

        $this->forge->addColumn('prianacategoria', [
            'graficar' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => $after,
            ],
        ]);
    }

    public function down()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (!$this->db->tableExists($table)) {
            return;
        }
        if (!$this->db->fieldExists('graficar', $table)) {
            return;
        }
        $this->forge->dropColumn('prianacategoria', 'graficar');
    }
}
