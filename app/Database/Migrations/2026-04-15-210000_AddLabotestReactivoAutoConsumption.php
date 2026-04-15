<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLabotestReactivoAutoConsumption extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('labotest_reactivo_config')) {
            $this->forge->addField([
                'config_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'prianacategoria_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'reactivo_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'consumo_default' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 1,
                    'null'       => false,
                ],
                'lote_policy' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'fefo',
                    'null'       => false,
                ],
                'enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'null'       => false,
                ],
                'deleted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
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
            $this->forge->addKey('config_id', true);
            $this->forge->addKey(['prianacategoria_id', 'reactivo_id'], false, true);
            $this->forge->addKey('reactivo_id');
            $this->forge->createTable('labotest_reactivo_config', true);
        }

        if (! $this->db->tableExists('reactivo_consumo_auto')) {
            $this->forge->addField([
                'auto_consumo_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'registro_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'prianacategoria_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'reactivo_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'cantidad' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'null'       => false,
                ],
                'lote_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'estado' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'aplicado',
                    'null'       => false,
                ],
                'mensaje' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
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
            $this->forge->addKey('auto_consumo_id', true);
            $this->forge->addKey(['registro_id', 'prianacategoria_id', 'reactivo_id'], false, true);
            $this->forge->addKey('registro_id');
            $this->forge->addKey('reactivo_id');
            $this->forge->createTable('reactivo_consumo_auto', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('reactivo_consumo_auto')) {
            $this->forge->dropTable('reactivo_consumo_auto', true);
        }
        if ($this->db->tableExists('labotest_reactivo_config')) {
            $this->forge->dropTable('labotest_reactivo_config', true);
        }
    }
}

