<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRegistroFichaClinicaTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('registro_ficha_clinica')) {
            return;
        }

        $this->forge->addField([
            'registro_ficha_clinica_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'registro_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'prianacategoria_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ficha_clinica_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'valores_json' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
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
        $this->forge->addKey('registro_ficha_clinica_id', true);
        $this->forge->addKey('registro_id');
        $this->forge->addKey('prianacategoria_id');
        $this->forge->addKey('ficha_clinica_id');
        $this->forge->addUniqueKey(['registro_id', 'prianacategoria_id']);
        $this->forge->createTable('registro_ficha_clinica', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('registro_ficha_clinica')) {
            $this->forge->dropTable('registro_ficha_clinica', true);
        }
    }
}
