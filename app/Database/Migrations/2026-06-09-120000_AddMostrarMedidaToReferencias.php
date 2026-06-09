<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMostrarMedidaToReferencias extends Migration
{
    public function up()
    {
        $column = [
            'mostrar_medida' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'comment'    => '1 = unidad solo en rango referencial del reporte, no en resultado',
            ],
        ];

        foreach (['secanacategoria', 'priresultados'] as $tableName) {
            $table = $this->db->prefixTable($tableName);
            if (! $this->db->tableExists($table)) {
                continue;
            }
            if ($this->db->fieldExists('mostrar_medida', $table)) {
                continue;
            }
            $this->forge->addColumn($tableName, $column);
        }
    }

    public function down()
    {
        foreach (['secanacategoria', 'priresultados'] as $tableName) {
            $table = $this->db->prefixTable($tableName);
            if (! $this->db->tableExists($table)) {
                continue;
            }
            if (! $this->db->fieldExists('mostrar_medida', $table)) {
                continue;
            }
            $this->forge->dropColumn($tableName, 'mostrar_medida');
        }
    }
}
