<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRecomendacionesPreviasToPrianacategoria extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (! $this->db->tableExists($table)) {
            return;
        }
        if ($this->db->fieldExists('recomendaciones_previas', $table)) {
            return;
        }

        $this->forge->addColumn('prianacategoria', [
            'recomendaciones_previas' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (! $this->db->tableExists($table)) {
            return;
        }
        if (! $this->db->fieldExists('recomendaciones_previas', $table)) {
            return;
        }
        $this->forge->dropColumn('prianacategoria', 'recomendaciones_previas');
    }
}
