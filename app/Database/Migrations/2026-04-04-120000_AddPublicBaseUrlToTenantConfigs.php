<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPublicBaseUrlToTenantConfigs extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tenant_configs')) {
            return;
        }
        if ($this->db->fieldExists('public_base_url', 'tenant_configs')) {
            return;
        }
        $this->forge->addColumn('tenant_configs', [
            'public_base_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'tenant_name',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('tenant_configs') && $this->db->fieldExists('public_base_url', 'tenant_configs')) {
            $this->forge->dropColumn('tenant_configs', 'public_base_url');
        }
    }
}
