<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFichasClinicasTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('fichas_clinicas')) {
            return;
        }

        $this->forge->addField([
            'ficha_clinica_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'matriz_config' => [
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
        $this->forge->addKey('ficha_clinica_id', true);
        $this->forge->addKey('deleted');
        $this->forge->addKey('activo');
        $this->forge->createTable('fichas_clinicas', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('fichas_clinicas')) {
            $this->forge->dropTable('fichas_clinicas', true);
        }
    }
}
