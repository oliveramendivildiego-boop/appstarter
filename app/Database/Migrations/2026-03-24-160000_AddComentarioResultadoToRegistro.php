<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComentarioResultadoToRegistro extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('registro')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
        if (!in_array('comentario_resultado', $fields, true)) {
            $this->forge->addColumn('registro', [
                'comentario_resultado' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('registro')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('registro'));
        if (in_array('comentario_resultado', $fields, true)) {
            $this->forge->dropColumn('registro', 'comentario_resultado');
        }
    }
}

