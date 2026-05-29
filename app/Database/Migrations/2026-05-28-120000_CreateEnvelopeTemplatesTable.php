<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEnvelopeTemplatesTable extends Migration
{
    private function defaultLayoutJson(): string
    {
        return json_encode([
            'version'    => 1,
            'size_key'   => 'dl',
            'width_mm'   => 220,
            'height_mm'  => 110,
            'columns'    => 4,
            'rows'       => 3,
            'items'      => [],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function up()
    {
        if ($this->db->tableExists('envelope_templates')) {
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
        $this->forge->createTable('envelope_templates');

        $now = date('Y-m-d H:i:s');
        $this->db->table('envelope_templates')->insert([
            'name'        => 'Sobre predeterminado',
            'layout_json' => $this->defaultLayoutJson(),
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        if ($this->db->tableExists('app_config')) {
            $exists = $this->db->table('app_config')->where('key', 'active_envelope_template_id')->countAllResults() > 0;
            if (! $exists) {
                $this->db->table('app_config')->insert([
                    'key'   => 'active_envelope_template_id',
                    'value' => '1',
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('envelope_templates')) {
            $this->forge->dropTable('envelope_templates', true);
        }
        if ($this->db->tableExists('app_config')) {
            $this->db->table('app_config')->where('key', 'active_envelope_template_id')->delete();
        }
    }
}
