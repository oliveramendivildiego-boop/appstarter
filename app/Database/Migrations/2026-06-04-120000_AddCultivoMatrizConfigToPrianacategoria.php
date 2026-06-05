<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCultivoMatrizConfigToPrianacategoria extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (! $this->db->tableExists($table)) {
            return;
        }
        if ($this->db->fieldExists('cultivo_matriz_config', $table)) {
            return;
        }

        $this->forge->addColumn('prianacategoria', [
            'cultivo_matriz_config' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
                'after' => 'compleja',
            ],
        ]);
    }

    public function down()
    {
        $table = $this->db->prefixTable('prianacategoria');
        if (! $this->db->tableExists($table)) {
            return;
        }
        if (! $this->db->fieldExists('cultivo_matriz_config', $table)) {
            return;
        }
        $this->forge->dropColumn('prianacategoria', 'cultivo_matriz_config');
    }
}
