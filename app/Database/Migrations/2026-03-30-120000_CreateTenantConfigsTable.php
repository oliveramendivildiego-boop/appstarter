<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantConfigsTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tenant_configs')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'tenant_key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                ],
                'tenant_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'db_host' => [
                    'type' => 'VARCHAR',
                    'constraint' => 191,
                    'default' => 'localhost',
                ],
                'db_port' => [
                    'type' => 'INT',
                    'constraint' => 5,
                    'default' => 3306,
                ],
                'db_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'db_user' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'db_pass' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'db_prefix' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'dom_',
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'is_default' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
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
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('tenant_key');
            $this->forge->createTable('tenant_configs', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tenant_configs')) {
            $this->forge->dropTable('tenant_configs', true);
        }
    }
}
