<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCategoriaToLeyendasCultivo extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('leyendas_cultivo')) {
            return;
        }

        if ($this->db->fieldExists('leyenda_cultivo_categoria_id', 'leyendas_cultivo')) {
            return;
        }

        $this->forge->addColumn('leyendas_cultivo', [
            'leyenda_cultivo_categoria_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'titulo',
            ],
        ]);

        $this->forge->addKey('leyenda_cultivo_categoria_id');
    }

    public function down(): void
    {
        if ($this->db->tableExists('leyendas_cultivo')
            && $this->db->fieldExists('leyenda_cultivo_categoria_id', 'leyendas_cultivo')) {
            $this->forge->dropColumn('leyendas_cultivo', 'leyenda_cultivo_categoria_id');
        }
    }
}
