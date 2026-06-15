<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFichaClinicaPruebasTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('ficha_clinica_pruebas')) {
            return;
        }

        $this->forge->addField([
            'ficha_clinica_prueba_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ficha_clinica_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'prianacategoria_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'orden' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('ficha_clinica_prueba_id', true);
        $this->forge->addKey(['ficha_clinica_id', 'prianacategoria_id'], false, true);
        $this->forge->addKey('prianacategoria_id');
        $this->forge->createTable('ficha_clinica_pruebas', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('ficha_clinica_pruebas')) {
            $this->forge->dropTable('ficha_clinica_pruebas', true);
        }
    }
}
