<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserAgentToAuditoria extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('auditoria')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('auditoria'));
        if (!in_array('user_agent', $fields, true)) {
            $this->forge->addColumn('auditoria', [
                'user_agent' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                    'after' => 'ip',
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('auditoria')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('auditoria'));
        if (in_array('user_agent', $fields, true)) {
            $this->forge->dropColumn('auditoria', 'user_agent');
        }
    }
}
