<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportPdfTemplatesTable extends Migration
{
    private function defaultLayoutJson(): string
    {
        return json_encode([
            'version' => 1,
            'blocks'  => [
                ['id' => 'header', 'enabled' => true],
                ['id' => 'patient_doctor', 'enabled' => true],
                ['id' => 'results', 'enabled' => true],
                ['id' => 'notes', 'enabled' => true],
                ['id' => 'footer', 'enabled' => true],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function up()
    {
        if ($this->db->tableExists('report_pdf_templates')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'layout_json' => [
                'type' => 'LONGTEXT',
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
        $this->forge->createTable('report_pdf_templates');

        $now = date('Y-m-d H:i:s');
        $this->db->table('report_pdf_templates')->insert([
            'name'        => 'Predeterminado',
            'layout_json' => $this->defaultLayoutJson(),
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $cfgTable = $this->db->prefixTable('app_config');
        $exists = $this->db->table('app_config')->where('key', 'pdf_result_template_id')->countAllResults() > 0;
        if (!$exists) {
            $this->db->table('app_config')->insert([
                'key'   => 'pdf_result_template_id',
                'value' => '1',
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('report_pdf_templates')) {
            $this->forge->dropTable('report_pdf_templates', true);
        }
        if ($this->db->tableExists('app_config')) {
            $this->db->table('app_config')->where('key', 'pdf_result_template_id')->delete();
        }
    }
}
