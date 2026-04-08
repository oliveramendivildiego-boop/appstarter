<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TenantSubscriptionPayments extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tenant_subscription_payments')) {
            return;
        }
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_config_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'period_start' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'period_end' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'default'    => 'Bs',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'voucher_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
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
        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_config_id');
        $this->forge->createTable('tenant_subscription_payments', true);
    }

    public function down()
    {
        if ($this->db->tableExists('tenant_subscription_payments')) {
            $this->forge->dropTable('tenant_subscription_payments', true);
        }
    }
}
