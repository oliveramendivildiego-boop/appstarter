<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsDefaultToTenantConfigs extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tenant_configs')) {
            return;
        }

        $fields = $this->db->getFieldNames($this->db->prefixTable('tenant_configs'));
        if (!in_array('is_default', $fields, true)) {
            $this->forge->addColumn('tenant_configs', [
                'is_default' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'is_active',
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('tenant_configs')) {
            return;
        }

        $fields = $this->db->getFieldNames($this->db->prefixTable('tenant_configs'));
        if (in_array('is_default', $fields, true)) {
            $this->forge->dropColumn('tenant_configs', 'is_default');
        }
    }
}
