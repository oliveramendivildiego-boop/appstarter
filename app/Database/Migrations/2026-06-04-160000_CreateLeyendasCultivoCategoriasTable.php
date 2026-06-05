<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeyendasCultivoCategoriasTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('leyendas_cultivo_categorias')) {
            return;
        }

        $this->forge->addField([
            'leyenda_cultivo_categoria_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'activo' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'deleted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('leyenda_cultivo_categoria_id', true);
        $this->forge->addKey('deleted');
        $this->forge->createTable('leyendas_cultivo_categorias', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('leyendas_cultivo_categorias')) {
            $this->forge->dropTable('leyendas_cultivo_categorias', true);
        }
    }
}
