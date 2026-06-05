<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeyendasCultivoTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('leyendas_cultivo')) {
            return;
        }

        $this->forge->addField([
            'leyenda_cultivo_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'mensaje' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
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
        $this->forge->addKey('leyenda_cultivo_id', true);
        $this->forge->addKey('deleted');
        $this->forge->createTable('leyendas_cultivo', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('leyendas_cultivo')) {
            $this->forge->dropTable('leyendas_cultivo', true);
        }
    }
}
